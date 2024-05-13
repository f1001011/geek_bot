<?php

namespace app\command;

use app\command\service\RedUserMoneyLogService;
use think\console\Input;
use think\console\Output;

class RedUserMoneyLog extends BaseCommand
{
    /**
     * 用户领取红包 日志 ，押金返回 等信息写入日志
     * 定向红包和福利红包，没领取完的。返回给用户
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('redusermoneylog')
            ->setDescription('the redusermoneylog command');
    }

    protected function execute(Input $input, Output $output)
    {
        $status = RedUserMoneyLogService::getInstance()->getCacheStatus('redusermoneylog');
        if ($status){
            $output->writeln('redusermoneylog start');
            RedUserMoneyLogService::getInstance()->start();
        }
        //函数执行完。删除指定的key
//        register_shutdown_function(function() {
//            RedUserMoneyLogService::getInstance()->delStatus('redusermoneylog');
//        });
        $output->writeln('redusermoneylog');
    }
}