<?php

namespace app\command;

use app\command\service\SelHaplessTaskService;
use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\UserModel;
use app\service\BotJieLongRedEnvelopeService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SelHaplessTask extends BaseCommand
{

    //接龙红包
    //选取倒霉蛋任务 每分钟执行一次
    //发送领取红包信息
    protected function configure()
    {
        // 指令配置
        $this->setName('selhaplesstask')
            ->setDescription('the selhaplesstask command');
    }

    protected function execute(Input $input, Output $output)
    {
        $status = SelHaplessTaskService::getInstance()->getCacheStatus('selhaplesstask');
        if ($status){
            $output->writeln('selhaplesstask start');
            SelHaplessTaskService::getInstance()->start();
        }
        //函数执行完。删除指定的key
//        register_shutdown_function(function() {
//            SelHaplessTaskService::getInstance()->delStatus('selhaplesstask');
//        });
        // 指令输出
        $output->writeln('selhaplesstask end');
    }


}