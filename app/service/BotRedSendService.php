<?php

namespace app\service;

use app\facade\BotFacade;
use app\model\UserModel;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;
use think\Exception;

class BotRedSendService extends BaseService
{
    use TelegramTrait;
    use RedBotTrait;
    public function send($crowd){
        $list = $this->sendRrdBot($crowd);
        BotFacade::sendWebhook($crowd,'发送消息',$list);
        return true;
    }

    public function verifyUser($get){
        //组装数据
        $tgUser=[];
        isset($get['id']) && $tgUser['id'] = $get['id'];
        isset($get['first_name']) && $tgUser['first_name'] = $get['first_name'];
        isset($get['last_name']) && $tgUser['last_name'] = $get['last_name'];
        isset($get['username']) && $tgUser['username'] = $get['username'];
        isset($get['auth_date']) && $tgUser['auth_date'] = $get['auth_date'];
        isset($get['hash']) && $tgUser['hash'] = $get['hash'];
        try {
            $auth_data = $this->checkTelegramAuthorization($tgUser);
            isset($get['crowd']) && $auth_data['crowd'] = $get['crowd'];
            $this->saveTelegramUserData($auth_data);
        } catch (Exception $e) {
            traceLog($e->getMessage());
            return false;
        }
        return true;
    }

    //获取tg用户账号
    public function getUserInfo($tgId)
    {
        $user = $this->getTgUser($tgId);
        if (empty($user)){
            return [];
        }
        //验证用户信息是否存在 (平台是否有信息，可以直接注册和直接返回用户不存在)
        list($userInfo) = $this->verifyUserData($user['id'], $user);
        return $userInfo;
    }



}