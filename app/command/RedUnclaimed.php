<?php

namespace app\command;

use app\command\service\RedUnclaimedService;

use think\console\Input;
use think\console\Output;

class RedUnclaimed extends BaseCommand
{

    //为领取完的红包金额返还  10分钟执行一次
    protected function configure()
    {
        // 指令配置
        $this->setName('redunclaimed')
            ->setDescription('the redunclaimed command');
    }

    protected function execute(Input $input, Output $output)
    {
        $status = RedUnclaimedService::getInstance()->getCacheStatus('redunclaimed');
        if ($status){
            $output->writeln('redunclaimed start');
            RedUnclaimedService::getInstance()->start();
        }
        //函数执行完。删除指定的key
//        register_shutdown_function(function() {
//            RedUnclaimedService::getInstance()->delStatus('redunclaimed');
//        });
        $output->writeln('redunclaimed end');
        return;
    }
}