<?php

namespace app\controller;

use app\service\BotJieLongRedEnvelopeService;
use app\service\BotRedEnvelopeService;
use app\service\BotRedMineService;
use app\service\BotRedSendService;
use app\validate\CommonValidate;
use think\exception\ValidateException;

class ApiTelegramBotRedSend extends ApiBase
{

    //客户登录到发红包平台
    public function send()
    {
        //发送消息到群  主动发送消息到 群，用户可以点击发送红包
        $crowd = $this->request->param('crowd', -4199654142);
        BotRedSendService::getInstance()->send($crowd);
        success();
    }


    //验证用户信息是否正确
    public function verifyUser()
    {
        $post = $_POST;
        traceLog($_POST,'----post----verify---user----');
        if (empty($post['user'])) {
            fail([], '不是telegram来源');
        }
        $tgUser = json_decode($post['user'], true);
        $isTelegram = BotRedSendService::getInstance()->verifyUser($post,$tgUser);
        //获取是否注册了平台 和用户信息
        $userInfo = BotRedSendService::getInstance()->getUserInfo($tgUser['id']);
        success($userInfo);
    }


    //用户创建发红包信息
    public function userCreateSendBot()
    {
        $param = $this->request->param();
        try {
            validate(CommonValidate::class)->scene('send-bot-red')->check($param);
        } catch (ValidateException $e) {
            traceLog($e->getError(), 'createSendBot-error');
            fail([], $e->getError());
        }

        //获取是否注册了平台 和用户信息
        $userId = $this->request->user_info['id'];
        $tgId = $this->request->user_info['tg_id'];
        $param['crowd'] = config('telegram.crowd');

        //判断是那种红包
        switch ($param['lottery_type']) {
            case 0:
                $data = BotRedEnvelopeService::getInstance()->createSend($param['money'], '', $param['num'], $param['crowd'], date('Y-m-d H:i:s'), $userId, $tgId, $param['expire_at'] ?? 0);
                break;
            case 1:
                if (empty($param['people'])) {
                    fail([], '定向红包必须要有中奖人员');
                }
                $param['num'] = 1;

                $userInfo = BotRedEnvelopeService::getInstance()->getTgUser($param['people']);
                if (empty($userInfo)) {
                    fail([], '用户未参加平台活动');
                }
                $data = BotRedEnvelopeService::getInstance()->createSend($param['money'], $param['people'], $param['num'], $param['crowd'], date('Y-m-d H:i:s'), $userId, $tgId, $param['expire_at'] ?? 0);
                break;
            case 2:
                $data = BotJieLongRedEnvelopeService::getInstance()->createSend($param['crowd'], $param['money'], $param['num'], $userId, $tgId, date('Y-m-d H:i:s'), $param['expire_at'] ?? 0);
                break;
            case 3:
                if (!isset($param['password']) || $param['password'] < 0 || $param['password'] >9){
                    fail([], '输入炸雷红包密码');
                }
                $data = BotRedMineService::getInstance()->createSend($param['money'], $param['password'], $param['num'],$param['crowd'], date('Y-m-d H:i:s'), $userId, $tgId,$param['expire_at'] ?? 0);
                break;
        }

        if (!$data) {
            fail();
        }
        success();
    }

}