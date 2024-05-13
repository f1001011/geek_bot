<?php

namespace app\controller;

use app\facade\BotFacade;
use app\service\BotRedSendService;

class ApiTelegramBotRedSend extends ApiBase
{

    //客户登录到发红包平台
    public function send(){
        //发送消息到群  主动发送消息到 群，用户可以点击发送红包
        $crowd = $this->request->param('crowd',-4199654142);
        BotRedSendService::getInstance()->send($crowd);
        success();
    }

    public function verifyUser(){

        $get = $this->request->get();
        BotRedSendService::getInstance()->verifyUser();
    }

}