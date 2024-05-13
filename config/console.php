<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'redautoclose'=>\app\command\RedAutoClose::class, //结束 已经过去的红包抽奖状态  防止有红包状态没更新到
        'selhaplesstask'=>\app\command\SelHaplessTask::class, //用户抽取倒霉蛋
        'redusermoneylog'=>\app\command\RedUserMoneyLog::class, //用户押金返回
        'redunclaimed'=>\app\command\RedUnclaimed::class,//用户为领取完红包 返回
        'redautosend'=>\app\command\RedAutoSend::class,//没有发送出去的红包，及时发出去
    ],
];
