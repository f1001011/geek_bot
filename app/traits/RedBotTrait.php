<?php

namespace app\traits;

use app\common\CacheKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\UserModel;
use think\facade\Cache;

trait RedBotTrait
{

    //验证红包信息和红包发送时间
    protected function verifySetSend($redId, $photo = 'one', $ist = false)
    {
        //查询红包发送消息
        $redInfo = LotteryJoinModel::getInstance()->getDataOne(['id' => $redId]);
        if (empty($redInfo)) {
            return fail([], '红包信息不存在');
        }

        switch ($redInfo['lottery_type']) {
            case LotteryJoinModel::RED_TYPE_FL:
                $photo = 'one';
                break;
            case LotteryJoinModel::RED_TYPE_DX:
                $photo = 'two';
                break;
            case LotteryJoinModel::RED_TYPE_JL:
                $photo = 'three';
                if ($ist) {
                    $photo = 'ist';
                }
                break;
            case LotteryJoinModel::RED_TYPE_DL:
                $photo = 'four';
                break;
        }
        $photoUrl = public_path() . config("telegram.bot-binding-red-photo-{$photo}");
        if (!file_exists($photoUrl)) {
            return fail([], '红包图片不存在');
        }

        if (time() < strtotime($redInfo['start_at'])) {
            return fail([], '还没到发送时间');
        }

        return [$redInfo, $photoUrl];
    }

    protected function verifyUserData($tgId, $tgUser)
    {
        //4 返回用户应该获得金额
        //获取用户的数据表ID
        $userInfo = UserModel::getInstance()->getDataOne(['tg_id' => $tgId]);
        if (empty($userInfo)) {
            $this->addUser($tgUser);
            return [$userInfo];
        }
        return [$userInfo];
    }

    public function addUser($tgUser)
    {
        $userInfo = [
            'tg_id'       => $tgUser['id'],
            //'guid' => rand(9999, 99999) . rand(9999, 99999),
            'player_name' => $tgUser['first_name'],
            'nickname'    => $tgUser['last_name'],
            'username'    => $tgUser['username'],
            'balance'     => 0,
        ];
        $userId = UserModel::getInstance()->setInsert($userInfo);
        $userInfo['id'] = $userId;
        return $userInfo;
    }

    //验证红包资格
    protected function verifyRedQualification($redId, $callbackQueryId)
    {
        //1 查询红包订单是否结束
        $dataOne = LotteryJoinModel::getInstance()->getDataOne(['id' => $redId]);
        if (empty($dataOne)) {
            //红包信息错误
            return fail([], '红包信息不存在');
        }

        if ($dataOne['join_num'] <= $dataOne['to_join_num']) {
            BotFacade::SendCallbackQuery($callbackQueryId, '红包已抢光');
            return fail([], '红包已抢光');
        }

        //倒计时过后也是结束
        if (time() < strtotime($dataOne['start_at'])) {
            BotFacade::SendCallbackQuery($callbackQueryId, '红包还没开始');
            return fail([], '红包还没开始');
        }

        //expire_at =0 是无过期时间
        if (time() > (strtotime($dataOne['start_at']) + $dataOne['expire_at']) && $dataOne['expire_at'] != 0) {
            BotFacade::SendCallbackQuery($callbackQueryId, '红包已经结束了');
            return fail([], '红包已经结束了');
        }

        if ($dataOne['status'] != LotteryJoinModel::STATUS_START) { //0 准备中  1 开始中 2 已经结束
            BotFacade::SendCallbackQuery($callbackQueryId, '红包已经结束了');
            return fail([], '红包已经结束了');
        }

        $activityOn = $dataOne['activity_on'];
        $toMoney = $dataOne['money'] - $dataOne['to_money'];//剩余可领取红包金额
        $toJoinNum = $dataOne['join_num'] - $dataOne['to_join_num'];//剩余可领取红包人数

        if ($toMoney <= 0 || $toJoinNum <= 0) {
            BotFacade::SendCallbackQuery($callbackQueryId, '红包已经抢完了');
            return fail([], '红包已经抢完了');
        }

        return [$dataOne, $activityOn, $toMoney, $toJoinNum];
    }

    //判断用户抢红包之后，还有没有钱继续发红包的资格
    public function verifyUserBalance($balance, $need, $callbackQueryId)
    {
        if ($balance < $need) {
            BotFacade::SendCallbackQuery($callbackQueryId, '用户押金不足');
            return fail([], '用户押金不足');
        }
    }

    //写入领取缓存和红包结束信息
    protected function redisCacheRedReceive($amount, $redId, $userInfo, $lotteryUpdateData, $userMoney = 0, $delete = false)
    {
        $json = json_encode(['user_id' => $userInfo['id'], 'money' => $amount, 'user_name' => $userInfo['username'], 'user_repay' => $userMoney]);
        if (!$delete) {
            //写入已经领取的用户
            Cache::SADD(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId), $json);
            Cache::expire(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId), CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER_TTL);
            //写入抽奖结束信息
            if (isset($lotteryUpdateData['status']) && $lotteryUpdateData['status'] != 1) {
                Cache::set(sprintf(CacheKey::REDIS_TELEGRAM_RED_END, $redId), $redId, CacheKey::REDIS_TELEGRAM_RED_END_TTL);
            }
        } else {
            //删除插入进去的值
            if (Cache::sIsMember(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId), $json)) {
                Cache::sRem(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId), $json);
            }
            //删除结束信息
            Cache::delete(sprintf(CacheKey::REDIS_TELEGRAM_RED_END, $redId));
        }
        return true;
    }

    //判断 用户是否已经领取过了红包
    protected function userIsReceive($tgId, $redId, $activityOn, $callbackQueryId)
    {
        //判断用户是否领取过了
        $lotteryUser = LotteryJoinUserModel::getInstance()->getDataOne(['tg_id' => $tgId, 'lottery_id' => $redId, 'activity_on' => $activityOn]);
        if (!empty($lotteryUser)) {
            BotFacade::SendCallbackQuery($callbackQueryId, '已经领取过了');
            return fail([], '已经领取过了');
        }
        return true;
    }


    //红包发送，保存红包发送时间。如果有用户领取了，则变为最后用户领取的时间。 确保接龙红包有人再用，好跑计划任务
    public function botRedStartSendOrUserEndData($get = false)
    {
        //$get 是获取这个时间，false 是储存redis值
        $key = CacheKey::REDIS_RED_ID_START_SENG_DATE_JL;

        if ($get === true) {
            $time = Cache::GET($key);
            //获取已经查询的次数
            $keyNumber = CacheKey::REDIS_RED_ID_START_SENG_DATE_JL_NUMBER;
            if (!empty($time)) {
                //删除已经写入的次数
                Cache::DELETE($keyNumber);
                return true;
            }

            //获取任务查询的次数
            $num = Cache::GET($keyNumber);

            //如果任务查询次数信息没有，写入1次。
            if (empty($num)) {
                $num = 1;
                //写入 redis
                Cache::SET($keyNumber, $num, CacheKey::REDIS_RED_ID_START_SENG_DATE_JL_TTL);
                return false;
            }
            //有查询次数的时候。获取查询了多少次，没达到次数，就不查询数据库
            if ($num < 2) {
                $num++;
                Cache::SET($keyNumber, $num, CacheKey::REDIS_RED_ID_START_SENG_DATE_JL_TTL);
                return false;
            }
            //达到次数，返回信息，查询数据库
            return true;
        }
        Cache::SET($key, time(), CacheKey::REDIS_RED_ID_START_SENG_DATE_JL_TTL);
        return true;
    }


    //写入领取的用户  这个队列数据不删除，以这个为标砖2，保证不会重复跑任务
    public function setRedisUserList($redId, $tgId, $num, $true = false)
    {
        //判断队列是否存在该用户，不存在就写入。存在就直接返回
        $key = sprintf(CacheKey::REDIS_LIST_PARTICIPATE_USER, $redId);

        //用户已经加入集合
        if (Cache::SISMEMBER($key, $tgId)) {
            return false;
        };

        //如果任务写入失败，。删除刚才插入进去的数据
        if ($true) {
            Cache::SREM($key, $tgId);
            return false;
        }

        //集合已经达到量
        if (Cache::SCARD($key, $tgId) > $num) {
            return false;
        };

        Cache::SADD($key, $tgId);//加入到集合
        Cache::expire($key, 36000);
        return true;
    }

    //跑任务的红包信息  挂任务一直跑
    public function setRedisLotteryJoinList($redId)
    {
        $key = CacheKey::REDIS_LIST_LOTTERY_JOIN_SEND;
        Cache::SADD($key, $redId);
        return true;
    }

//    //写入用户领奖数据
//    public function setRedisUserListLog($redId, $lotteryLog)
//    {
//        $key = sprintf(CacheKey::REDIS_LIST_PARTICIPATE_USER_LOG, $redId);
//        Cache::LPUSH($key, $lotteryLog);
//        Cache::expire($key, 36000);
//        return true;
//    }
//
//    //写入用户资金记录
//    public function setRedisUserListMoneyLog($redId, $moneyLog)
//    {
//        $key = sprintf(CacheKey::REDIS_LIST_INSERT_MONEY_LOG, $redId);
//        Cache::LPUSH($key, $moneyLog);//先进先出
//        Cache::expire($key, 36000);
//        return true;
//    }
//
//    //写入红包数据修改
//    public function setRedisLotteryListUpdate($redId, $lotteryUpdate)
//    {
//        $key = sprintf(CacheKey::REDIS_LIST_UPDATE_LOTTERY_JOIN, $redId);
//        Cache::LPUSH($key, $lotteryUpdate);//先进先出
//        Cache::expire($key, 36000);
//        return true;
//    }
//
//    public function setRedisLotteryListJoinUserInsert($redId, $insert)
//    {
//        $key = sprintf(CacheKey::REDIS_LIST_INSERT_LOTTERY_JOIN_USER, $redId);
//        Cache::LPUSH($key, $insert);//先进先出
//        Cache::expire($key, 36000);
//        return true;
//    }
}