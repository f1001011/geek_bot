<?php

namespace app\service;

use app\facade\BotFacade;
use app\traits\TelegramTrait;
use think\Exception;

class BotRedSendService extends BaseService
{
    use TelegramTrait;
    public function send($crowd){
        $list = $this->sendRrdBot($crowd);
        BotFacade::sendWebhook($crowd,'发送消息',$list);
        return true;
    }

    public function verifyUser($get){
        try {
            $auth_data = $this->checkTelegramAuthorization($get);
            $this->saveTelegramUserData($auth_data);
        } catch (Exception $e) {

            die ($e->getMessage());
        }
    }
}