<?php

namespace app\service;


use app\common\CacheKey;
use app\common\JobKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\MoneyLogModel;
use app\model\UserModel;
use app\queue\CommandJob;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

class BotRedMineService extends BaseService
{

    use TelegramTrait;
    use RedBotTrait;

    //炸雷红包
    public function createSend(float $money, string $password, int $joinNum, string $crowd, $startAt, string $userId, string $tgId, int $expireAt = 600)
    {
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
            'activity_on' => getRedEnvelopeOn(),
            'money' => $money,
            'join_num' => $joinNum,
            'to_join_num' => 0,
            'expire_at' => $expireAt,
            'start_at' => $startAt,
            'water_money' => $waterMoney,
            'in_join_user' => '',
            'lottery_type' => LotteryJoinModel::RED_TYPE_DL,
            'red_password'=>$password,
            'is_status'=>LotteryJoinModel::IS__STATUS_END_JL,
        ];

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

        Db::startTrans();
        try {
            $moneyLogInsert['source_id'] = LotteryJoinModel::getInstance()->setInsert($insert);
            UserModel::getInstance()->setUpdate(['id' => $userId], ['balance' => $toMoney]);
            MoneyLogModel::getInstance()->setInsert($moneyLogInsert);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            traceLog($e->getError(), 'createSend-error');
            Db::rollback();
            return false;
        }
        //创建成功启动 queue
        Queue::later(2,CommandJob::class,['command_name'=> JobKey::RED_AUTO_SEND],JobKey::JOB_NAME_COMMAND);
        return true;
    }

    //点击发送按钮。发送红包消息
    public function setSend($redId)
    {
        //查询红包发送消息,验证消息正确性
        list($redInfo, $photoUrl) = $this->verifySetSend($redId);
        if ($redInfo['status'] != 0) {
            fail([], '已发送');
        }
        //用户发送红包防止重复点击
        $this->setSendPost($redId);

        try {
            //获取标签列表
            $list = $this->sendRrdBotRoot($redInfo['join_num'], 0, $redId,$redInfo['crowd'],$redInfo['red_password']);
            //发送消息到 telegram 开始抽奖
            $res = BotFacade::sendPhoto($redInfo['crowd'], $photoUrl, $this->zdCopywriting($redInfo['money'],$redInfo['username'],$redInfo), $list);
            if (!$res) {
                traceLog($res, 'dx-fl-red-mine-curl-error');
                throw new \think\exception\HttpException(404, 'curl 失败');
            }
            $request = json_decode($res, true);
            //修改红包数据
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redInfo['id']], ['message_id' => $request['result']['message_id'], 'status' => LotteryJoinModel::STATUS_START]);
        } catch (\Exception $e) {
            traceLog($e->getMessage(), 'setSend-mine-error');
            return fail([], $e->getMessage());
        }
        return success();
    }

    //机器人消息体解析 $command 消息命令  $messageId 消息ID  $crowd群号 $tgId领取用户的tgId
    public function getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser = [])
    {
        $this->repeatPost($callbackQueryId,$tgId);//防止重复请求
        //验证用户信息是否存在 (平台是否有信息，可以直接注册和直接返回用户不存在)
        list($userInfo) = $this->verifyUserData($tgId, $tgUser);
        return $this->getRedEnvelopeUser($tgId, $redId, $callbackQueryId, $userInfo);
    }


    //用户领取红包 $redId红包ID，$activityOn红包订单号
    public function getRedEnvelopeUser(int $tgId, int $redId, $callbackQueryId = 0, $userInfo = [])
    {
        //验证红包领取信息
        list($dataOne, $activityOn, $toMoney, $toJoinNum) = $this->verifyRedQualification($redId, $callbackQueryId);

        //抢炸雷红包需要押金，判断用户是否金额足够
        $depositMoney = $dataOne['money'] * config('telegram.bot-binding-red-zd-rate');//需要赔偿的钱
        if ($dataOne['lottery_type'] == LotteryJoinModel::RED_TYPE_DL) {
            $this->verifyUserBalance($userInfo['balance'], $depositMoney, $callbackQueryId);
        }

        if ($dataOne['tg_id'] == $tgId){
            BotFacade::SendCallbackQuery($callbackQueryId, '本人红包不可领取');
            return fail([], '本人红包不可领取');
        }

        //3 判断用户是否已经领取了 后期可改redis
        $this->userIsReceive($tgId, $redId, $activityOn, $callbackQueryId);

        //4 计算用户获得金额
        $amount = $this->grabNextRedPackDL($toMoney, $toJoinNum);

        //如果用户的位数是炸雷红包的最后一位，扣除用户  原红包金额的 2倍
        $userMoney = 0;//用户需要增加的金额 为负数就是减少

        $centre = $this->isLastDigitSix($amount,$dataOne['red_password']);
        if ($centre){
            //中炸雷红包，用户赔偿 金额  本次获得的金额 - 需要赔偿的押金的2倍 负数
            $userMoney = $depositMoney - $amount;
        }

        traceLog("----红包ID {$redId} 用户 {$tgId} 领取金额{$amount} 赔偿 {$userMoney} -----");

        //5 计算已经领取的金额和已经领取了多少人
        $stopMoney = $amount + $dataOne['to_money'];//总领取了多少金额
        $stopJoinNum = $dataOne['to_join_num'] + 1;//总领取了多少人


        //######如果是最后一个用户参数抽奖了。抽奖状态变更为已结束 start_at 有人抢的时候，开始时间跟新为当前时间
        $lotteryUpdate = [
            'is_status' => LotteryJoinModel::IS__STATUS_END_JL,
            'to_money' => $stopMoney,
            'to_join_num' => $stopJoinNum,
            'start_at' => date('Y-m-d H:i:s'),
            'join_user' => $dataOne['join_user'] . $userInfo['id'] . ','
        ];

        if ($toJoinNum <= 1) {
            $lotteryUpdate['status'] = LotteryJoinModel::STATUS_END_ALL;
        }

        ################
        //6 金额 写入用户钱包。写入红包日志，修改当前红包信息
        $lotteryJoinUserId = 0;
        $insert = [
            'user_id' => $userInfo['id'],
            'tg_id' => $tgId,
            'activity_on' => $activityOn,
            'lottery_id' => $redId,
            'to_money' => $stopMoney,
            'money' => $amount,
            'user_name' => $userInfo['username'],
            'user_start_money' => $userInfo['balance'] ?? 0,
            'user_end_money' => $userInfo['balance'] + $amount - $depositMoney,
            'lottery_type' => $dataOne['lottery_type'],
            'user_repay' => $centre ? $depositMoney : 0,
        ];

        Db::startTrans();
        try {
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redId, 'activity_on' => $activityOn], $lotteryUpdate);
            //插入领取信息
            $joinUserId = LotteryJoinUserModel::getInstance()->setInsert($insert);
            //2 执行修改用户钱包
            UserModel::getInstance()->incOrDec($userInfo['id'], $amount - $depositMoney);

            //3 执行写入红包日志
            //MoneyLogModel::getInstance()->setInsert([]);
            MoneyLogModel::getInstance()->setInsert([
                'username' => $userInfo['username'],
                'tg_id' => $userInfo['tg_id'],
                'user_id' => $userInfo['id'],
                'start_money' => $userInfo['balance'],
                'end_money' => $userInfo['balance'] + $amount - $depositMoney,
                'change_money' => $amount - $depositMoney,
                'water_money' => 0,
                'to_source_id' => $joinUserId,
                'source_id' => $redId,
                'remarks' => '用户领取红包金额:' . $amount.',赔偿金额：'.($centre ? $depositMoney : 0).',实际变动索赔：'.($depositMoney-$amount),
                'type' => 2,
                'change_type' => $dataOne['lottery_type'],
                'piping' => $dataOne['crowd'],
            ]);
            // 更多的数据库操作...
            //返回中奖金额
            //发送消息到 telegram 中奖消息  跟新中奖消息
            //$list = $this->sendRrdBotRoot($dataOne['join_num'], $lotteryUpdate['to_join_num'], $redId,$dataOne['crowd']);
            $list = $this->sendRrdBotRoot($dataOne['join_num'], $lotteryUpdate['to_join_num'], $redId,$dataOne['crowd'],$dataOne['red_password']);
            $this->redisCacheRedReceive($amount, $redId, $userInfo, $lotteryUpdate,$userMoney);

            //更新消息体
            $str = $this->zdCopywriting($amount, $dataOne['username'],$dataOne);
            if (isset($lotteryUpdate['status']) && $lotteryUpdate['status'] != 1){
                $str = $this->zdCopywritingEdit($dataOne);
            }
            //如果用户中雷了，给包主添加余额，给包主写日志
            if ($userMoney != 0){
                //获取包主余额
                $bzData = UserModel::getInstance()->getDataOne(['id'=>$dataOne['user_id']]);
                MoneyLogModel::getInstance()->setInsert([
                    'username' => $bzData['username'],
                    'tg_id' => $bzData['tg_id'],
                    'user_id' => $bzData['id'],
                    'start_money' => $bzData['balance'],
                    'end_money' => $bzData['balance'] + $depositMoney,
                    'change_money' => $depositMoney,
                    'water_money' => 0,
                    'to_source_id' => $joinUserId,
                    'source_id' => $redId,
                    'remarks' => '用户tgId：'.$userInfo['tg_id'].',中雷号：'.$dataOne['red_password'].',中奖：'.$amount.',赔偿：'.$depositMoney,
                    'type' => 6,
                    'change_type' => $dataOne['lottery_type'],
                    'piping' => $dataOne['crowd'],
                ]);
                UserModel::getInstance()->incOrDec($dataOne['user_id'], $depositMoney);
            }
            BotFacade::editMessageCaption($dataOne['crowd'], $dataOne['message_id'], $str, $list);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            traceLog($e->getMessage(), "福利-地雷 {$redId} 结算错误");
            // 处理异常或返回错误
            return 0;
        }
        return $amount;
    }


    //没领取完的红包结束状态，更新 telegram 消息
    public function setEndQuery($data = []){
        //判断游戏类型
        $list = $this->sendRrdBotRoot($data['join_num'], $data['to_join_num'], $data['id'],$data['crowd'],$data['red_password'],true);
        BotFacade::editMessageCaption($data['crowd'], $data['message_id'], language('rendend',$data['username'],$data['activity_on']), $list);
        return true;
    }
}

