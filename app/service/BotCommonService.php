<?php

namespace app\service;

use app\common\CacheKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use think\facade\Cache;

class BotCommonService extends BaseService
{


    public function verifyRedType($command, $tgId, $callbackQueryId, $tgUser)
    {
        $request = BaseService::getInstance()->tgRequestSend($command,$tgId);
        if ($request){
            return false;
        }

        list($redId) = $this->disassembleCommand($command);
        if ($redId <= 0) {
            return false;
        }

        //获取房间信息，分配到指定 service
        $data = LotteryJoinModel::getInstance()->getCacheCreateInfo($redId);
        if (!$data) {
            return false;
        }
        $RedisLockKey =  sprintf(CacheKey::REDIS_TG_LOCK_SETTLEMENT,$redId);
        if (Cache::get($RedisLockKey)){
            return false;
        };
        Cache::set($RedisLockKey,time(),10);

        //解析红包类型
        if ($data['lottery_type'] == LotteryJoinModel::RED_TYPE_FL || $data['lottery_type'] == LotteryJoinModel::RED_TYPE_DX) {
            return BotRedEnvelopeService::getInstance()->getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser);
        }

        if ($data['lottery_type'] == LotteryJoinModel::RED_TYPE_JL) {
            return BotJieLongRedEnvelopeService::getInstance()->getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser);
        }

        if ($data['lottery_type'] == LotteryJoinModel::RED_TYPE_DL) {
            return BotRedMineService::getInstance()->getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser);
        }

    }


    //拆卸 telegram 命令和用户信息验证
    protected function disassembleCommand($command)
    {
        //是红包领取命令
        //2 解析命令中的 订单ID
        $commandArray = explode('_', $command);

        //3 计算用户获取的红包金额
        if (empty($commandArray[1])) {
            traceLog("订单解析错误:{$command}", 'bot-binding-red-string-one');
            return [];
        }
        $redId = $commandArray[1];
        unset($commandArray);
        //红包已经结束
        $status = Cache::get(sprintf(CacheKey::REDIS_TELEGRAM_RED_END, $redId));
        if ($status) {
//            BotService::SendCallbackQuery($callbackQueryId,'红包已抢光');
            return fail([], '红包已经结束');
        }

        return [$redId];
    }

    public function setQuery($data = [])
    {
        foreach ($data as $key => $value) {
            //解析红包类型
            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_FL || $value['lottery_type'] == LotteryJoinModel::RED_TYPE_DX) {
                BotRedEnvelopeService::getInstance()->setEndQuery($value);
            }

            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_JL) {
                BotJieLongRedEnvelopeService::getInstance()->setEndQuery($value);
            }

            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_DL) {
                BotRedMineService::getInstance()->setEndQuery($value);
            }
        }
        return true;
    }

}