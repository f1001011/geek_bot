<?php

namespace app\queue;

use app\common\CacheKey;
use app\common\JobKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\service\BotJieLongRedEnvelopeService;
use app\service\BotRedEnvelopeService;
use app\service\BotRedMineService;
use think\facade\Cache;
use think\queue\Job;

class OpenLotteryJoinJob
{
    protected $job;

    public function fire(Job $job, $data)
    {
        //执行 领奖信息发送到 telegram
        $this->job = $job;

        $name = $data['command_name'];//执行的命令。

        if ($this->send($name, $data)) {
            $job->delete();
        } else {
            // 如果任务执行失败，可以重新发布任务，设置延迟时间等
            $job->release(3); // 延迟3秒后重新执行
        }
        traceLogs($name . ' 执行发送开奖信息---END ');
    }

    private function send($name, $data)
    {
        if (!isset($name)) {
            return true;
        }
        traceLogs($name . ' 开始执行发送开奖信息---START');

        //判断需要执行的命令，，可进行其他操作比如写 资金日志等，，目前资金日志是 同步写入。
//        $strJson = Cache::get(sprintf(CacheKey::QUERY_QUEUE_REDID,$data['dataOne']['id']));
//        $string = empty($strJson) ? $data['dataOne']['message_id'] : $strJson;
//
//        $listJson = Cache::get(sprintf(CacheKey::QUERY_QUEUE_KEYBOARD_REDID,$data['dataOne']['id']));
//        $list = empty($listJson) ? json_decode($listJson,true) : $data['list'];

        if (Cache::get(sprintf(CacheKey::QUERY_QUEUE_SEND_REDID, $data['dataOne']['id']))){
            return true;
        }
        Cache::set(sprintf(CacheKey::QUERY_QUEUE_SEND_REDID, $data['dataOne']['id']),time(),CacheKey::QUERY_QUEUE_SEND_REDID_TTL);

        //获取红包 已抢用户信息
        $dataOne = LotteryJoinModel::getInstance()->getDataOne(['id' => $data['dataOne']['id']]);

        switch ($name) {
            case JobKey::FL_RED: //执行福利红包发送信息
                //break;
            case JobKey::DX_RED: //执行定向红包发送信息

                $list = BotRedEnvelopeService::getInstance()->sendRrdBotRoot($dataOne['join_num'], $dataOne['to_join_num'], $dataOne['id'], $dataOne['crowd']);
                $string = BotRedEnvelopeService::getInstance()->queryPhotoEdit($dataOne);
                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $list);
                break;
            case JobKey::JL_RED: //执行接龙红包发送信息
                //$dataOne = LotteryJoinModel::getInstance()->getDataOne(['id'=>$data['dataOne']['id']]);
                $string = BotJieLongRedEnvelopeService::getInstance()->jlqueryPhotoEdit(
                    $dataOne['money']
                    , bcdiv($dataOne['water_money'], $dataOne['money'], 4),
                    $dataOne['to_join_num'] . '/' . $dataOne['join_num'],
                    $dataOne, !($dataOne['status'] == 1)
                );
                $list = BotJieLongRedEnvelopeService::getInstance()->sendRrdBotRoot($dataOne['join_num'], $dataOne['to_join_num'], $dataOne['id'], $dataOne['crowd']);
                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $list);
                break;
            case JobKey::ZD_RED: //执行炸弹红包发送信息
                $list = BotRedMineService::getInstance()->sendRrdBotRoot($dataOne['join_num'], $dataOne['to_join_num'], $dataOne['id'],$dataOne['crowd'],$dataOne['red_password']);
                //更新消息体
                $string = BotRedMineService::getInstance()->zdCopywriting($dataOne['money'], $dataOne['username'],$dataOne);
                if (isset($dataOne['status']) && $dataOne['status'] != 1){
                    $string = BotRedMineService::getInstance()->zdCopywritingEdit($dataOne);
                }

                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $list);
                break;
        }
        return true;
    }

    // 如果任务执行失败，返回false，该任务会重新入队
    // 如果返回null，该任务会被删除
//    public function failed($data)
//    {
//
//    }
}