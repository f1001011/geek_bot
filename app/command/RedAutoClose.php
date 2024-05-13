<?php
declare (strict_types=1);

namespace app\command;

use app\command\service\RedAutoCloseService;

use think\console\Input;
use think\console\Output;

class RedAutoClose extends BaseCommand
{
    /**
     * 结束 已经过去的红包抽奖状态  防止有红包状态没更新到
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('redautoclose')
            ->setDescription('the redautoclose command');
    }

    protected function execute(Input $input, Output $output)
    {
        $status = RedAutoCloseService::getInstance()->getCacheStatus('redautoclose');
        if ($status){
            $output->writeln('redautoclose start');
            RedAutoCloseService::getInstance()->start();
        }
        //函数执行完。删除指定的key
//        register_shutdown_function(function() {
//            RedAutoCloseService::getInstance()->delStatus('redautoclose');
//        });
        // 指令输出
        $output->writeln('redautoclose');
    }

}
