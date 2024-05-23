<?php
namespace app\common;
class JobKey{

    const JOB_NAME_COMMAND = 'CommandJob';//计划任务key
    const JOB_NAME_OPEN = 'OpenLotteryJoinJob';//

    const RED_AUTO_CLOSE = 'redautoclose';//计划任务key
    const RED_AUTO_SEND = 'redautosend';//计划任务key
    const RED_UNCLAIMED = 'redunclaimed';//计划任务key
    const RED_USER_MONEY_LOG = 'redusermoneylog';//计划任务key
    const SEL_HAPLESS_TASK = 'selhaplesstask';//计划任务key


    const DX_RED = 'DX_RED';
    const FL_RED = 'FL_RED';
    const JL_RED = 'JL_RED';
    const ZD_RED = 'ZD_RED';
    const TTL = 5*60;//计划任务key
}


