<?php

namespace app\controller;


use app\facade\BotFacade;
use app\service\BotCommonService;
use app\service\BotCrowdListService;
use app\service\BotRedEnvelopeService;
use app\service\BotRedSendService;
use app\validate\CommonValidate;
use think\exception\ValidateException;

class ApiTelegramBotRedEnvelope extends ApiBase
{
    //机器人绑定域名
    public function setWebhook()
    {
        BotFacade::setWebhookPost();
        success();
    }

    public function getChats()
    {
        $data = BotFacade::getChats();
        if (!$data) {
            fail();
        }
        success(json_decode($data, true));
    }

    //获取绑定的地址
    public function getWebhookInfo()
    {
        $data = BotFacade::getWebhookPostInfo();
        if (!$data) {
            fail();
        }
        success(json_decode($data, true));
    }

    public function setDelete()
    {
        //删除机器人绑定域名信息
        $data = BotFacade::setWebhookDeletePost();
        success();
    }

    //telegram消息回调
    public function webhook()
    {
        //用户领取红包
        //需要发送 红包id查询红包数据
        $request = file_get_contents('php://input');

        $request = json_decode($request, true);

        //判断是否是新消息。机器人加入房间消息
        $this->botCrowd($request);
        //判断是否是系统命令 比如 start
        $this->systemCommand($request);

        if (empty($request) || empty($request['callback_query']['message'])) {
            // 消息体错误
            traceLog($request, 'red-webhook-error');
            fail();
        }

        //响应成功
        //获取群ID
        $data = $request['callback_query'];
        $messageId = $data['message']['message_id'];//消息ID
        $crowd = $data['message']['chat']['id'];//群ID
        $command = $data['data'];//输入命令
        $tgId = $data['from']['id'];//用户的tgId
        $tgUser = $data['from'];//用户信息
        traceLog(['message_id' => $messageId, 'crowd' => $crowd, 'command' => $command,], 'red-webhook-data');

        //判断是否是 发送红包命令
//        if ($command == 'my_red_send' || $command == '/my_red_send'){
//            //返回用户我要发红包按钮
//            BotCommonService::getInstance()->myRedSend($crowd,$tgUser,$messageId);
//            success();
//        }

        if ($command == 'start' || $command == '/start'){
            //返回主菜单
            traceLog($request, '222222222222222222222222');
            BotRedSendService::getInstance()->send($crowd,$messageId);
            //BotCommonService::getInstance()->send($crowd,$tgUser,$messageId);
            success();
        }

        //1 判断是否是红包领取命令   命令是否正确
        if (strpos($command, config('telegram.bot-binding-red-string-one')) !== false) {
            BotCommonService::getInstance()->verifyRedType($command, $tgId, $request['callback_query']['id'], $request['callback_query']['from']);
            success();
        }

        //如果是接龙红包
        success();
    }

    //后台创建发送红包订单
//    public function createSendBot()
//    {
//        $param = $this->request->param();
//        try {
//            validate(CommonValidate::class)->scene('send-bot-red')->check($param);
//        } catch (ValidateException $e) {
//            traceLog($e->getError(), 'createSendBot-error');
//            fail([], $e->getError());
//        }
//        $userId = 5;//获取userId
//        $tgId = 7198514363;//获取tgID
//
//        //判断是那种红包
//        switch ($param['lottery_type']) {
//            case 0:
//                $data = BotRedEnvelopeService::getInstance()->createSend($param['money'], '', $param['num'], $param['crowd'], date('Y-m-d H:i:s'), $userId, $tgId, $param['expire_at'] ?? 0);
//                break;
//            case 1:
//                if (empty($param['people'])){
//                    fail([],'定向红包必须要有中奖人员');
//                }
//                $param['num'] = 1;
//                $data = BotRedEnvelopeService::getInstance()->createSend($param['money'], $param['people'], $param['num'], $param['crowd'], date('Y-m-d H:i:s'), $userId, $tgId, $param['expire_at'] ?? 0);
//                break;
//            case 2:
//                $data = BotJieLongRedEnvelopeService::getInstance()->createSend($param['crowd'], $param['money'], $param['num'], $userId, $tgId, date('Y-m-d H:i:s'), $param['expire_at'] ?? 0);
//
//                break;
//            case 3:
//                break;
//        }
//
//        if (!$data) {
//            fail();
//        }
//        success();
//    }

    //点击按钮发送红包开始
//    public function setSendBot()
//    {
//
//        //验证是否是拥有者发的
//        if (true) {
//            //不是本人不可以发送
//        }
//
//        $param = $this->request->param();
//        try {
//            validate(CommonValidate::class)->scene('set-send-bot-red')->check($param);
//        } catch (ValidateException $e) {
//            fail([], $e->getError());
//        }
//
//        //用户点击的时候。获取信息，先插入信息。在执行发送消息
//
//        switch ($param['lottery_type']) {
//            case 0:
//            case 1:
//                BotRedEnvelopeService::getInstance()->setSend($param['red_id']);
//                break;
//            case 2:
//                BotJieLongRedEnvelopeService::getInstance()->setSend($param['red_id']);
//                break;
//            //return BotRedEnvelopeService::getInstance()->setSend($param['red_id']);
//        }
//        success();
//    }

    //开始发送红包 后台发起抽奖 接口发红包。直接发出去
    public function sendStartBot()
    {
        //money 本次抽奖金额
        //people 内置中奖人  123,123,123,123,格式
        //num 本次抽奖人数
        //crowd 发送群组ID
        $param = $this->request->param();
        try {
            validate(CommonValidate::class)->scene('send-bot-red')->check($param);
        } catch (ValidateException $e) {
            fail([], $e->getError());
        }
        $data = BotRedEnvelopeService::getInstance()->createSendStartBotRoot($param['money'], $param['people'] ?? '', $param['num'], $param['crowd'], $param['start_at'], $param['expire_at'] ?? 0);
        if (!$data) {
            fail();
        }
        success();
    }

    //机器人加入新房间
    protected function botCrowd($request)
    {
        //判断是否是新消息。机器人加入房间消息
        if (!empty($request['message']['new_chat_member']) && $request['message']['new_chat_member']['is_bot']) {
            //机器人加入房间信息
            $message = $request['message'];

            $data = [
                'title' => $message['chat']['title'],
                'crowd_id' => $message['chat']['id'],
                'first_name' => $message['new_chat_member']['first_name'],
                'botname' => $message['new_chat_member']['username'],
                'user_id' => $message['from']['id'],
                'username' => $message['from']['username'],
                'del' => 0,
            ];

            BotCrowdListService::getInstance()->botCrowdBind($data);
            return true;
        }
        //判断是否是新消息。机器人被踢出房间消息
        if (!empty($request['my_chat_member']) && $request['my_chat_member']['old_chat_member']['user']['is_bot']) {
            $message = $request['my_chat_member'];

            $data = [
                'crowd_id' => $message['chat']['id'],
                'botname' => $message['new_chat_member']['user']['username'],
            ];
            //修改这个条件
            BotCrowdListService::getInstance()->botCrowdEdit($data);
            return true;
        }
        return true;
    }

    public function systemCommand($request){

        if (empty($request) || empty($request['message']['text'])) {
            // 消息体错误
            return;
        }
        //如果是系统命令
        $message = $request['message'];
        if (empty($message['chat']['id'])){
            return ;
        }
        BotRedSendService::getInstance()->send($message['chat']['id']);

    }

    public function test()
    {
        $command = 'robRed_163';//输入命令
        $tgId = 7198514363;//用户的tgId
//        $tgUser = array(
//            'id' => '7198514363',
//            'is_bot' => '',
//            'first_name' => 'zhaofeng',
//            'last_name' => 'zhaofeng',
//            'username' => 'zhaofengzhaofeng',
//            'language_code' => 'zh-hans',
//        );
//        $c = 3247267662489238522;
//        BotCommonService::getInstance()->verifyRedType($command, $tgId, $c, $tgUser);




    }
}