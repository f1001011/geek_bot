<?php

namespace app\queue;

use app\common\CacheKey;
use app\common\JobKey;
use app\facade\BotFacade;
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
        $str = Cache::get(sprintf(CacheKey::QUERY_QUEUE_REDID,$data['dataOne']['id']));
        $string = empty($str) ? $data['dataOne']['message_id'] : $str;
        switch ($name) {
            case JobKey::FL_RED: //执行福利红包发送信息
                //break;
            case JobKey::DX_RED: //执行定向红包发送信息

                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $data['list']);
                break;
            case JobKey::JL_RED: //执行接龙红包发送信息

                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $data['list']);
                break;
            case JobKey::ZD_RED: //执行炸弹红包发送信息
                BotFacade::editMessageCaption($data['dataOne']['crowd'], $data['dataOne']['message_id'], $string, $data['list']);
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