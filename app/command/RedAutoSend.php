<?php

namespace app\command;


use app\command\service\RedAutoSendService;
use think\console\Input;
use think\console\Output;

class RedAutoSend extends BaseCommand
{
    /**
     * 红包自动发送。扫描红包发送，如果有没发送的，及时发送出去
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('redautosend')
            ->setDescription('the redautosend command');
    }

    protected function execute(Input $input, Output $output)
    {
        $status = RedAutoSendService::getInstance()->getCacheStatus('redautosend');
        if ($status){
            $output->writeln('redautosend start');
            RedAutoSendService::getInstance()->start();
        }
        //函数执行完。删除指定的key
//        register_shutdown_function(function() {
//            RedAutoSendService::getInstance()->delStatus('redautosend');
//        });
        $output->writeln('redautosend end');
    }
}