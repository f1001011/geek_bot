<?php

namespace app\service;

use app\common\JobKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\MoneyLogModel;
use app\model\UserModel;
use app\queue\CommandJob;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;
use think\facade\Db;
use think\facade\Queue;

class BotJieLongRedEnvelopeService extends BaseService
{

    use TelegramTrait;
    use RedBotTrait;

    //接龙红包创建，不是 倒霉蛋发送
    public function createSend(string $crowd, float $money, int $joinNum, string $userId, string $tgId, $startAt, int $expireAt = 0)
    {
        // $tgId,$crowd,$userId $money $joinNum  $expireAt  $startAt
        $expireAt = $expireAt > 0 ? $expireAt : 600;  //默认10分钟停止
        empty($startAt) && $startAt = date('Y-m-d H:i:s');
        $waterMoney = $money * config('telegram.bot-binding-red-service-charge');//发红包收取的利息
        //$money = $money + $waterMoney;
        $moneyT = $money + $waterMoney;//总需要金额
        $insert = [
            'tg_id' => $tgId,
            'crowd' => $crowd,
            'user_id' => $userId,
            'status' => LotteryJoinModel::STATUS_HAVE,
            'activity_on' => getRedEnvelopeOn(20, '-1'),
            'money' => $money,
            'water_money' => $waterMoney,
            'join_num' => $joinNum,
            'to_join_num' => 0,
            'expire_at' => $expireAt,
            'start_at' => $startAt,
            'in_join_user' => '',
            'lottery_type' => LotteryJoinModel::RED_TYPE_JL,
            'jl_number' => 1
        ];

        //判断用户钱包钱是否足够
        if ($userId <= 0) {
            return fail([], '发送用户ID不存在');
        }
        //查询用户的余额是否足够
        $userInfo = UserModel::getInstance()->getDataOne(['id' => $userId]);
        if (empty($userInfo)) {
            return fail([], '用户不存在');
        }
        $toMoney = $userInfo['balance'] - $moneyT;
        if ($toMoney < 0) {
            return fail([], '用户余额不足');
        }

        $insert['user_start_money'] = $userInfo['balance'];
        $insert['user_end_money'] = $toMoney;
        $insert['username'] = $userInfo['username'];

        //资金日志
        $moneyLogInsert = [
            'username' => $userInfo['username'],
            'tg_id' => $userInfo['tg_id'],
            'user_id' => $userInfo['id'],
            'start_money' => $userInfo['balance'],
            'end_money' => $toMoney,
            'change_money' => $moneyT,
            'water_money' => $waterMoney,
            'to_source_id' => 0,
            'remarks' => '用户创建红包',
            'type' => 1,
            'change_type' => $insert['lottery_type'],
            'piping' => $crowd,
        ];
        $LotteryJoinId = 0;
        Db::startTrans();
        try {
            $LotteryJoinId = $moneyLogInsert['source_id'] = LotteryJoinModel::getInstance()->setInsert($insert);
            UserModel::getInstance()->setUpdate(['id' => $userId], ['balance' => $toMoney]);
            //写入日志
            MoneyLogModel::getInstance()->setInsert($moneyLogInsert);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            traceLog($e->getError(), 'jl-createSend-error');
            return false;
        }

        //确认信息
        //创建成功启动 queue
        $qid = Queue::later(2,CommandJob::class,['command_name'=> JobKey::RED_AUTO_SEND],JobKey::JOB_NAME_COMMAND);
        traceLog('创建红包成功启动...queueId='.$qid,'queueId');
        //调用用户点击发送方法 可直接发送
        return $LotteryJoinId;
    }

    //用户点击发送按钮
    //点击发送按钮。发送红包消息
    public function setSend($redId)
    {
        //验证红包信息和红包发送时间
        list($redInfo, $photoUrl) = $this->verifySetSend($redId);
        if ($redInfo['status'] != 0 ) {
            fail([], '已发送');
        }
        $this->setSendPost($redId);
        try {
            //获取标签列表
            $list = $this->sendRrdBotRoot($redInfo['join_num'], 0, $redId,$redInfo['crowd']);
            //发送消息到 telegram 开始抽奖
            $res = BotFacade::sendPhoto($redInfo['crowd'], $photoUrl,
                $this->jlCopywriting($redInfo['money'], bcdiv($redInfo['water_money'], $redInfo['money'], 4), $redInfo['join_num'],$redInfo['username'],$redInfo),
                $list);
            if (!$res) {
                traceLog($res, 'jielong-red-sendStartBotRoot-curl-error');
                throw new \think\exception\HttpException(404, 'curl 失败');
            }
            $request = json_decode($res, true);
            //修改红包数据
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redInfo['id']], ['message_id' => $request['result']['message_id'], 'status' => LotteryJoinModel::STATUS_START]);
        } catch (\Exception $e) {
            traceLog($e->getMessage(), 'setSend-error');
            return fail([], $e->getMessage());
        }
        //发送成功。，写入 接龙红包使用 redis 信息
        $this->botRedStartSendOrUserEndData();
        return true;
    }

    //倒霉蛋发红包。
    public function haplessUserSetSend($insert = [], $lotteryJoinUserUpdate = [], $lotteryJoinUpdate = [],$moneyLog = [])
    {
        //倒霉蛋发红包， 1 创建发红包信息 2 扣除用户押金  3 更改发送红包的信息， 2 发送通知

        Db::startTrans();
        try {
            //1 创建发红包信息
            $lotteryId = LotteryJoinModel::getInstance()->setInsert($insert);
            //修改用户金额
            //UserModel::getInstance()->dec($insert['user_id'],$insert['money']+$insert['water_money']);
            //写入资金记录
            MoneyLogModel::getInstance()->setInsert($moneyLog);
            LotteryJoinModel::getInstance()->setUpdate(['id'=>$lotteryJoinUpdate['id']],['is_status'=>$lotteryJoinUpdate['is_status'],'hapless_user_id'=>$lotteryJoinUpdate['hapless_user_id']]);
            LotteryJoinUserModel::getInstance()->setUpdate(['id'=>$lotteryJoinUserUpdate['id']],['is_hapless'=>$lotteryJoinUserUpdate['is_hapless']]);
            //同一个红包。如果倒霉蛋选出来。其他用户的日志要变更，并通过任务返回用户的金额
            LotteryJoinUserModel::getInstance()->updateStatusDeposit($lotteryJoinUserUpdate['id'], $lotteryJoinUpdate['id']);

            //写入日志

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }

        //倒霉蛋发送红包
        $this->setSend($lotteryId); //计划任务跑
        return true;

    }

    //机器人消息体解析 $command 消息命令  $messageId 消息ID  $crowd群号 $tgId领取用户的tgId
    public function getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser = [])
    {
        $this->repeatPost($callbackQueryId);//防止重复请求
        //验证用户信息是否存在 (平台是否有信息，可以直接注册和直接返回用户不存在)
        list($userInfo) = $this->verifyUserData($tgId, $tgUser);
        return $this->getRedEnvelopeUser($tgId, $redId, $callbackQueryId, $userInfo);
    }

    public function getRedEnvelopeUser($tgId, $redId, $callbackQueryId, $userInfo)
    {
        //验证红包领取信息
        list($dataOne, $activityOn, $toMoney, $toJoinNum) = $this->verifyRedQualification($redId, $callbackQueryId);

        //1 判断用户是否已经领取了 后期可改redis
        $this->userIsReceive($tgId, $redId, $activityOn, $callbackQueryId);

        //抢红包需要押金，判断用户是否金额足够
        //本次需要扣除的押金和水钱
        $depositMoney = $dataOne['money'] + $dataOne['water_money'];
        if ($dataOne['lottery_type'] == LotteryJoinModel::RED_TYPE_JL) {
            $this->verifyUserBalance($userInfo['balance'], $depositMoney, $callbackQueryId);
        }

        //2 计算用户可以领取到多少金额
        $amount = $this->grabNextRedPack($toMoney, $toJoinNum);
        traceLog("红包ID {$redId} 用户 {$tgId} 领取金额{$amount}");

        //3 计算已经领取的金额和已经领取了多少人
        $stopMoney = $amount + $dataOne['to_money'];//总领取了多少金额
        $stopJoinNum = $dataOne['to_join_num'] + 1;//总领取了多少人 +1

        //用户需要扣除押金
        $yinMoney = $depositMoney - $amount;

        //###组装修改的红包信息###如果是最后一个用户参数抽奖了。抽奖状态变更为已结束
        $lotteryUpdate = [
            'to_money' => $stopMoney,
            'to_join_num' => $stopJoinNum,
            'start_at' => date('Y-m-d H:i:s'),
            'join_user' => $dataOne['join_user'] . $userInfo['id'] . ','
        ];

        $status = 1;
        if ($toJoinNum <= 1) {
            $status = LotteryJoinModel::STATUS_END;
            $lotteryUpdate['status'] = LotteryJoinModel::STATUS_END;
        }

        //####组装写入 领取红包的信息
        $lotteryJoinUserInsert = [
            'user_id' => $userInfo['id'],
            'tg_id' => $tgId,
            'activity_on' => $activityOn,
            'lottery_id' => $redId,
            'to_money' => $stopMoney,
            'money' => $amount,
            'user_name' => $userInfo['username'],
            'user_start_money' => $userInfo['balance'] ?? 0,
            'user_end_money' => $userInfo['balance'] + $amount,
            'user_deposit_money' => $depositMoney,
            'user_still_money' => 0,
            'lottery_type' => $dataOne['lottery_type'],
        ];

        //用户抢红包需要扣除押金，抢完过后选一个用户作为 倒霉蛋，通过倒霉蛋继续发红包
        Db::startTrans();
        try {
            //1 更改红包信息
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redId, 'activity_on' => $activityOn], $lotteryUpdate);
            //2 写入红包领取信息
            $joinUserId = LotteryJoinUserModel::getInstance()->setInsert($lotteryJoinUserInsert);
            //3 写入用户领取金额日志

            MoneyLogModel::getInstance()->setInsert([
                'username' => $userInfo['username'],
                'tg_id' => $userInfo['tg_id'],
                'user_id' => $userInfo['id'],
                'start_money' => $userInfo['balance'],
                'end_money' => $userInfo['balance'] + $amount - $depositMoney,//用户金额加上 加上用户押金之后获得的金额 - 本次的押金
                'change_money' => $amount - $depositMoney,
                'water_money' => 0,
                'to_source_id' => $joinUserId,
                'source_id' => $redId,
                'remarks' => '用户领取接龙红包金额:' . $amount . ',押金:' . $depositMoney.'冻结：'.$yinMoney,
                'type' => 2,
                'change_type' => $dataOne['lottery_type'],
                'piping' => $dataOne['crowd'],
            ]);

            //2 执行修改用户钱包
            UserModel::getInstance()->dec($userInfo['id'], $yinMoney);
            //更新消息体 内联键盘
            $list = $this->sendRrdBotRoot($dataOne['join_num'], $lotteryUpdate['to_join_num'], $redId,$dataOne['crowd']);
            $this->redisCacheRedReceive($amount, $redId, $userInfo, $lotteryUpdate);

            //是否需要发送信息时 用户领取了多少U 也显示
            $false = $status == LotteryJoinModel::STATUS_END;

            BotFacade::editMessageCaption($dataOne['crowd'], $dataOne['message_id'],
                $this->jlqueryPhotoEdit($dataOne['money'],
                    bcdiv($dataOne['water_money'], $dataOne['money'], 4),
                    $stopJoinNum . '/' . $dataOne['join_num'], $stopJoinNum,$dataOne['username'], $toMoney, $redId, $false), $list);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            traceLog($e->getMessage(), "接龙用户抢红包 {$redId} 结算错误");
            // 处理异常或返回错误
            return 0;
        }

        //选完倒霉蛋之后，返回其他用户的押金  计划任务选取
        //用户领取信息写入
        $this->botRedStartSendOrUserEndData();//用户领取接龙红包信息更新redis ttl
        $pid = '';
        $false && $pid = Queue::later(2,CommandJob::class,['command_name'=>JobKey::SEL_HAPLESS_TASK],JobKey::JOB_NAME_COMMAND);
        traceLog('领取红包中...pid='.$pid,'queueId');

        return true;
    }

    public function setEndQuery($data = []){
        //判断游戏类型
        $list = $this->sendRrdBotRoot($data['join_num'], $data['to_join_num'], $data['id'],$data['crowd'],'',true);
        BotFacade::editMessageCaption($data['crowd'], $data['message_id'], '', $list);
        return true;
    }
}