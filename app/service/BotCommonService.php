<?php

namespace app\service;

use app\common\CacheKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use think\facade\Cache;

class BotCommonService extends BaseService
{


    public function verifyRedType($command,$tgId, $callbackQueryId, $tgUser)
    {
        list($redId) = $this->disassembleCommand($command);

        if ($redId <=0){
            return fail();
        }

        //获取房间信息，分配到指定 service
        $data = LotteryJoinModel::getInstance()->getCacheCreateInfo($redId);
        if (!$data){
            return fail();
        }

        //解析红包类型
        if ($data['lottery_type'] == LotteryJoinModel::RED_TYPE_FL || $data['lottery_type'] == LotteryJoinModel::RED_TYPE_DX){
            BotRedEnvelopeService::getInstance()->getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser);
        }

        if ($data['lottery_type'] == LotteryJoinModel::RED_TYPE_JL){
            BotJieLongRedEnvelopeService::getInstance()->getRedBotAnalysis($redId, $tgId, $callbackQueryId, $tgUser);
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
    public function getKeyboardLayout()
    {
        //获取总的键盘布局  包含发 定向红包。福利红包。接龙红包。地雷红包

    }

    //用户点击我要发红按钮，保存用户发红包信息。并且跳转到用户自己页面，返回用户可发红包点击按钮
    public function myRedSend($crowd,$tgUser,$messageId){
        //用户返回我要发红包按钮
        return BotRedSendService::getInstance()->redSend($crowd,$tgUser,$messageId);
    }

}