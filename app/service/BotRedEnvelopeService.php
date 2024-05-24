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
use app\queue\OpenLotteryJoinJob;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

class BotRedEnvelopeService extends BaseService
{

    use TelegramTrait;
    use RedBotTrait;

    //福利红包，定向红包
    //创建红包发送消息 创建定向红包和福利红包
    public function createSend(float $money, string $people, int $joinNum, string $crowd, $startAt, string $userId, string $tgId, int $expireAt = 600)
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
            'lottery_type' => LotteryJoinModel::RED_TYPE_FL,
        ];

        if (!empty($people)) {
            $insert['in_join_user'] = $people;
            $insert['lottery_type'] = LotteryJoinModel::RED_TYPE_DX;
        }
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
            $list = $this->sendRrdBotRoot($redInfo['join_num'], 0, $redId,$redInfo['crowd']);
            //发送消息到 telegram 开始抽奖
            $res = BotFacade::sendPhoto($redInfo['crowd'], $photoUrl, $this->copywriting($redInfo), $list);
            if (!$res) {
                traceLog($res, 'dx-fl-red-sendStartBotRoot-curl-error');
                throw new \think\exception\HttpException(404, 'curl 失败');
            }
            $request = json_decode($res, true);
            //修改红包数据
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redInfo['id']], ['message_id' => $request['result']['message_id'], 'status' => LotteryJoinModel::STATUS_START]);
        } catch (\Exception $e) {
            traceLog($e->getMessage(), 'setSend-error');
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

        //2 确认是否有预设人
        $inJoinUser = $this->getInJoinUserList($dataOne['in_join_user']);
        //如果不存在的时候，说明当前没有固定用户 抢红包，把当前用户丢进红包中
        if (empty($inJoinUser)) {
            $inJoinUser[] = $userInfo['tg_id']; // 如果是 tgid 就换tgid。。
        }
        // 如果是 tgid 就换tgid。。
        if (!in_array($userInfo['tg_id'], $inJoinUser)) {
            BotFacade::SendCallbackQuery($callbackQueryId, '仅指定用户可抢');
            return fail([], '用户不具备抢红包资格');
        }

        //3 判断用户是否已经领取了 后期可改redis
        $this->userIsReceive($tgId, $redId, $activityOn, $callbackQueryId);

        //4 计算用户获得金额
        $amount = $this->grabNextRedPack($toMoney, $toJoinNum);
        traceLog("红包ID {$redId} 用户 {$tgId} 领取金额{$amount}");
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
            'user_end_money' => $userInfo['balance'] + $amount,
            'lottery_type' => $dataOne['lottery_type'],
        ];
        Db::startTrans();
        try {
            LotteryJoinModel::getInstance()->setUpdate(['id' => $redId, 'activity_on' => $activityOn], $lotteryUpdate);
            //插入领取信息
            $joinUserId = LotteryJoinUserModel::getInstance()->setInsert($insert);
            //2 执行修改用户钱包
            UserModel::where('id', $userInfo['id'])->inc('balance', $amount)->update();

            //3 执行写入红包日志
            //MoneyLogModel::getInstance()->setInsert([]);
            MoneyLogModel::getInstance()->setInsert([
                'username' => $userInfo['username'],
                'tg_id' => $userInfo['tg_id'],
                'user_id' => $userInfo['id'],
                'start_money' => $userInfo['balance'],
                'end_money' => $userInfo['balance'] + $amount,
                'change_money' => $amount,
                'water_money' => 0,
                'to_source_id' => $joinUserId,
                'source_id' => $redId,
                'remarks' => '用户领取红包金额:' . $amount,
                'type' => 2,
                'change_type' => $dataOne['lottery_type'],
                'piping' => $dataOne['crowd'],
            ]);
            // 更多的数据库操作...
            Db::commit();
            $this->deleteLock($redId);

            //返回中奖金额
            //发送消息到 telegram 中奖消息  跟新中奖消息
            $list = $this->sendRrdBotRoot($dataOne['join_num'], $lotteryUpdate['to_join_num'], $redId,$dataOne['crowd']);
            $this->redisCacheRedReceive($amount, $redId, $userInfo, $lotteryUpdate);
            //更新消息体
            //BotFacade::editMessageCaption($dataOne['crowd'], $dataOne['message_id'], $this->queryPhotoEdit($dataOne, $amount, $userInfo), $list);
            $str = $this->queryPhotoEdit($dataOne, $amount, $userInfo);
            $data = ['command_name'=>JobKey::FL_RED,'dataOne'=>$dataOne,'str'=>$str,'list'=>$list];
            Cache::set(sprintf(CacheKey::QUERY_QUEUE_REDID,$dataOne['id']),$str);
            Queue::later(5,OpenLotteryJoinJob::class,$data,JobKey::JOB_NAME_OPEN);
            //Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            traceLog($e->getMessage(), "福利-定向用户抢红包 {$redId} 结算错误");
            return 0;
        }
        //$this->deleteLock($redId);
        return $amount;
    }


    //管理员创建发送红包
//    public function createSendStartBotRoot(float $money, string $people, int $joinNum, string $crowd, $startAt, int $expireAt = 0)
//    {
//        $insert = [
//            'tg_id' => 0,
//            'crowd' => $crowd,
//            'status' => 1,
//            'activity_on' => getRedEnvelopeOn(),
//            'money' => $money,
//            'join_num' => $joinNum,
//            'to_join_num' => 0,
//            'expire_at' => $expireAt,
//            'start_at' => $startAt,
//            'in_join_user' => '',
//            'lottery_type' => 0,
//        ];
//
//        if (!empty($people)) {
//            $insert['in_join_user'] = $people;
//            $insert['lottery_type'] = 1;
//        }
//        return $this->sendStartBotRoot($insert);
//    }
//
//    //开始发送红包
//    public function sendStartBotRoot(array $insert = [])
//    {
//        $photoUrl = public_path() . config('telegram.bot-binding-red-photo-one');
//        if (!file_exists($photoUrl)) {
//            return false;
//        }
//
//        // 启动事务
//        Db::startTrans();
//        try {
//            //发送红包 //写入数据成功
//            if (!$insertId = LotteryJoinModel::getInstance()->setInsert($insert)) {
//                return false;
//            }
//            //获取标签列表
//            $list = $this->sendRrdBotRoot($insert['join_num'], 0, $insertId,$insert['crowd']);
//            //发送消息到 telegram 开始抽奖
//            $res = BotFacade::sendPhoto($insert['crowd'], $photoUrl, $this->copywriting($insert['money'], $insert['in_join_user'],$insert['username']), $list);
//            traceLog($res, 'red-sendStartBotRoot-curl-ok');
//            if (!$res) {
//                traceLog($res, 'red-sendStartBotRoot-curl-error');
//                throw new \think\exception\HttpException(404, 'curl 失败');
//            }
//            $request = json_decode($res, true);
//            //修改红包数据
//            LotteryJoinModel::getInstance()->setUpdate(['id' => $insertId], ['message_id' => $request['result']['message_id']]);
//            // 提交事务
//            Db::commit();
//        } catch (\Exception $e) {
//            // 回滚事务
//            Db::rollback();
//            return false;
//        }
//        return true;
//    }

    //计算中奖用户
    private function getInJoinUserList($data = '')
    {
        if (empty($data)) {
            return [];
        }
        if (!strpos($data, ',')) {
            $data .= ',';
        }

        $inJoinUserList = explode(",", $data);

        if (empty($inJoinUserList)) {
            return [];
        }
        $inJoinUserList = array_filter($inJoinUserList);
        if (!empty($inJoinUserList)) {
            return $inJoinUserList;
        }
        return [];
    }


    //没领取完的红包结束状态，更新 telegram 消息
    public function setEndQuery($data = []){
        //判断游戏类型
        $list = $this->sendRrdBotRoot($data['join_num'], $data['to_join_num'], $data['id'],$data['crowd'],'',true);
        BotFacade::editMessageCaption($data['crowd'], $data['message_id'], language('rendend',$data['username'],$data['activity_on']), $list);
        return true;
    }
}

