<?php

namespace app\queue;

use app\command\service\RedAutoCloseService;
use app\command\service\RedAutoSendService;
use app\command\service\RedUnclaimedService;
use app\command\service\RedUserMoneyLogService;
use app\command\service\SelHaplessTaskService;
use app\common\JobKey;
use think\queue\Job;

class CommandJob
{
    protected $job;
    public function fire(Job $job, $data)
    {
        $this->job = $job;
        // 执行邮件发送的逻辑
        // 这里简单模拟发送邮件
        $name = $data['command_name'];
        // 假设的邮件发送函数
        $this->send($name);
        // 如果邮件发送成功，则删除任务
        $job->delete();
        traceLogs($name.' 执行END');
        // 也可以记录日志等
    }

    private function send($name)
    {
        if (!isset($name)) {
            return true;
        }
        traceLogs($name.' 开始执行任务START');
        //判断需要执行的命令
        switch ($name) {//自动结束 已经结束的红包状态，redautoclose
            case JobKey::RED_AUTO_CLOSE:
                $status = RedAutoCloseService::getInstance()->getCacheStatus(JobKey::RED_AUTO_CLOSE);
                traceLogs($name." 执行任务中...status=$status");
                if ($status) {
                    $this->job->delete();
                    RedAutoCloseService::getInstance()->start();
                }
                break;
            case JobKey::SEL_HAPLESS_TASK://自动选取倒霉蛋  发送红包出去selhaplesstask
                $status = SelHaplessTaskService::getInstance()->getCacheStatus(JobKey::SEL_HAPLESS_TASK);
                traceLogs($name." 执行任务中...status=$status");
                if ($status) {
                    $this->job->delete();
                    SelHaplessTaskService::getInstance()->start();
                }
                break;
            case JobKey::RED_USER_MONEY_LOG: //用户押金返还 redusermoneylog
                $status = RedUserMoneyLogService::getInstance()->getCacheStatus(JobKey::RED_USER_MONEY_LOG);
                traceLogs($name." 执行任务中...status=$status");
                if ($status) {
                    $this->job->delete();
                    RedUserMoneyLogService::getInstance()->start();
                }
                break;
            case JobKey::RED_UNCLAIMED: // 用户未领取完的红包余额返回 redunclaimed
                $status = RedUnclaimedService::getInstance()->getCacheStatus(JobKey::RED_UNCLAIMED);
                traceLogs($name." 执行任务中...status=$status");
                if ($status) {
                    $this->job->delete();
                    RedUnclaimedService::getInstance()->start();
                }
                break;
            case JobKey::RED_AUTO_SEND: //未发送出去的红包，执行发送 redautosend
                $status = RedAutoSendService::getInstance()->getCacheStatus(JobKey::RED_AUTO_SEND);
                traceLogs($name." 执行任务中...status=$status");
                if ($status) {
                    $this->job->delete();
                    RedAutoSendService::getInstance()->start();
                }
                break;
        }
        return true;
    }

    // 如果任务执行失败，返回false，该任务会重新入队
    // 如果返回null，该任务会被删除
    public function failed($data)
    {
        // 记录失败日志等
        // ...
    }
}