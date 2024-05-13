<?php

namespace app\command;

use think\console\Command;

define('REQUEST_ID',rand(100000,999999).bin2hex(random_bytes(10)).date('YmdHis'));
class BaseCommand extends Command
{

}