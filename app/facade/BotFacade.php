<?php

namespace app\facade;
use app\facade\bin\TelegramBotBin;
use think\Facade;
class BotFacade extends Facade
{
    protected static function getFacadeClass()
    {
        return TelegramBotBin::class;
    }
}