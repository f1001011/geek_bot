<?php

namespace app\service;

use app\common\CacheKey;
use app\facade\BotFacade;
use app\model\UserModel;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;
use think\Exception;
use think\facade\Cache;

class BotRedSendService extends BaseService
{
    use TelegramTrait;
    use RedBotTrait;
    public function send($crowd,$messageId=0){
        $list = $this->sendRrdBot($crowd);
        if ($messageId > 0){
            BotFacade::editMessageText($crowd, $messageId,'主菜单', $list);
        }else{
            BotFacade::sendWebhook($crowd,'主菜单',$list);
        }
        return true;
    }

    public function verifyUser($tgUser){
        //组装数据
//        $tgUser=[];
//        isset($get['id']) && $tgUser['id'] = $get['id'];
//        isset($get['first_name']) && $tgUser['first_name'] = $get['first_name'];
//        isset($get['last_name']) && $tgUser['last_name'] = $get['last_name'];
//        isset($get['username']) && $tgUser['username'] = $get['username'];
//        isset($get['auth_date']) && $tgUser['auth_date'] = $get['auth_date'];
//        isset($get['hash']) && $tgUser['hash'] = $get['hash'];
//        isset($get['photo_url']) && $tgUser['photo_url'] = $get['photo_url'];
        //不验证是否是 telegram 信息了
//        try {
//            $auth_data = $this->checkTelegramAuthorization($tgUser);
//            isset($get['crowd']) && $auth_data['crowd'] = $get['crowd'];
//            $this->saveTelegramUserData($auth_data);
//        } catch (Exception $e) {
//            traceLog($e->getMessage());
//            return false;
//        }
        //查询redis 是否有信息
//        if (empty($_POST['user'])){
//            return false;
//        }
//        $tgUser = json_decode($_POST['user'],true);
//        traceLog($tgUser,'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $tgUser = Cache::get(sprintf(CacheKey::REDIS_TELEGRAM_CROWD_TG_USER,$tgUser['id']));
//        if ($tgUser){
//            return true;
//        }
        $this->saveTelegramUserData($tgUser);
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

//    public function redSend($crowd,$tgUser,$messageId){
//        $list = $this->myRedSend();
//        BotFacade::editMessageText($crowd, $messageId,'欢迎使用天天娱乐红包机器人', $list);
//        //BotFacade::sendPhotoEdit($crowd,  config("telegram.bot-binding-red-photo"),'欢迎使用天天娱乐红包机器人', $list,$messageId);
//        //保存用户redis信息
//        $tgUser['crowd'] = $crowd;
//        Cache::set(sprintf(CacheKey::REDIS_TELEGRAM_CROWD_TG_USER,$tgUser['id']),json_encode($tgUser),60*60*24*30);
//        return true;
//    }
}