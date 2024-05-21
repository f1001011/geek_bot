<?php

namespace app\service;

use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\UserModel;
use app\traits\RedBotTrait;
use app\traits\TelegramTrait;

class BotRedSendService extends BaseService
{
    use TelegramTrait;
    use RedBotTrait;

    public function send($crowd, $messageId = 0)
    {
        $list = $this->sendRrdBot($crowd);
        if ($messageId > 0) {
            BotFacade::editMessageText($crowd, $messageId, language('title-hbo'), $list);
        } else {
            BotFacade::sendPhoto($crowd, config("telegram.bot-binding-red-photo"), language('title-hbo'), $list);
            //BotFacade::sendWebhook($crowd, language('title-hbo'), $list);
        }
        return true;
    }

    public function verifyUser($post, $tgUser)
    {
//        if (!isset($post['encryption'])) {
//            return false;
//        }
//        $encryption = $post['encryption'];
//        unset($post['encryption']);
//
//        foreach ($post as $key => $value) {
//            $data_check_arr[] = $key . '=' . $value;
//        }
//
//        sort($data_check_arr);
//        $data_check_string = implode("&", $data_check_arr);
//        $md5 = md5($data_check_string);
//        if ($md5 != $encryption) {
//            return false;
//        }
        $this->saveTelegramUserData($tgUser);
        return true;
    }

    //获取tg用户账号
    public function getUserInfo($tgId)
    {
        $user = $this->getTgUser($tgId);
        if (empty($user)) {
            return [];
        }
        //验证用户信息是否存在 (平台是否有信息，可以直接注册和直接返回用户不存在)
        list($userInfo) = $this->verifyUserData($user['id'], $user);
        return $userInfo;
    }

    //获取用户余额
    public function getUserBalance($tgUser, $callbackQueryId)
    {
        $balance = UserModel::getInstance()->getDataOneValue(['tg_id' => $tgUser['id']], 'balance');
        //弹出消息
        BotFacade::SendCallbackQuery($callbackQueryId, 'username：' . $tgUser['username'] . "\n" . 'ID：' . $tgUser['id'] . "\n" . 'balance：' . $balance . "\n");
        return true;
    }

    //用户获取报表
    public function getUserReportLog($tgUser, $callbackQueryId)
    {
        $tgId = $tgUser['id'];
        // 今日发红包金额，未领完红包金额，今日领取红包金额，今日中雷金额，今日我发红包别人中雷金额
        $money = $notMoney = $dayMoney = $cMoney = $toMoney = 0;
        $money = LotteryJoinModel::getInstance()->getDaySendRed(['tg_id' => $tgId], 'money');
        $redList = [];
        if ($money > 0) {
            $notMoney = $money - LotteryJoinModel::getInstance()->getDaySendRed(['tg_id' => $tgId], 'to_money');
            $redList = LotteryJoinModel::getInstance()->getRedListId(['tg_id' => $tgId,'lottery_type'=>LotteryJoinModel::RED_TYPE_DL]);
        }

        //查询今日领取了多少红包
        $userMoney = LotteryJoinUserModel::getInstance()->getUserMoneyAndRepay(['tg_id' => $tgId]);

        $dayMoney = $userMoney['tmoney'] ?? 0;
        $cMoney = $userMoney['rmoney'] ?? 0;

        //用户发的红包，中雷了多少金额
        if (!empty($redList)) {
            $toMoney = LotteryJoinUserModel::getInstance()->getToUserRepayMoney($redList);
        }
        //弹出消息
        BotFacade::SendCallbackQuery($callbackQueryId,
            'username：' . $tgUser['username'] . "\n" .
            'ID：' . $tgUser['id'] . "\n" .
            '发包支出：' . $money . "\n".
            '发包未领：' . $notMoney . "\n".
            '发包中雷：' . $toMoney . "\n"."\n".
            '----------------'. "\n".
            '抢包收入：' . $dayMoney . "\n".
            '抢包中雷：' . $cMoney . "\n"
        );

        return true;

    }
}