<?php

namespace app\controller;


use app\command\service\RedAutoCloseService;
use app\common\CacheKey;
use app\common\JobKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use app\model\UserModel;
use app\service\BotJieLongRedEnvelopeService;
use app\service\BotRedEnvelopeService;
use app\service\BotRedSendService;
use app\validate\CommonValidate;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;


class Index extends ApiBase
{

    public function index()
    {
//        $new_balance = bcsub(1000, 10, 4);
//        dump($new_balance);die;
//        if ($type == 1) {
//            $new_balance = bcadd(1000, 10, 4);
//        } else {
//            $new_balance = bcsub($new_balance, $amount, 4);
//        }
//
//        $arr = new \stdClass();
//        $arr->aaa = 123;
//
//        BotFacade::sendGame();

//        $a= Cache::get(sprintf(CacheKey::QUERY_QUEUE_KEYBOARD_REDID,298));
//
//
//        $dataOne = LotteryJoinModel::getInstance()->getDataOne(['id'=>231]);
//        $amount = 100;
//        $userInfo = UserModel::getInstance()->getDataOne(['id'=>7]);
//        BotRedEnvelopeService::getInstance()->queryPhotoEdit($dataOne, $amount, $userInfo);
//dump(1);die;
//        $input['message'] = 22222222222222222;
//        if (isset($input['message']['new_chat_member']) && !empty($newMessage = $input['message']['new_chat_member'])){
//            dump($newMessage);
//            die;
//        }
//        dump(111);die;
//
//        //锁住本次操作
//        $RedisLockKey =  'string_lock';
//        //$lock = Cache::get($RedisLockKey);
//       echo  date('H:i:s');
//       echo "\n";
//        do{
//            $lock = Cache::get($RedisLockKey);
//            if (!empty($lock)){
//                sleep(1);
//            }
//        }while($lock);
//        echo "\n";
//        echo  date('H:i:s');
//        Cache::set($RedisLockKey,time(),3);
//        dump(33333);
//        die;
//        try {
//        $this->edit();
//        }catch (Exception $e){
//
//            dump(2222222222222);
//        }
//        die;
//        $data=[
//          'bet'=>'W3sibW9uZXkiOjEwMCwidmFsdWUiOjExfV0',
//          'game_type'=>3,
//          'table_id'=>2,
//          'v'=>'807890841'
//        ];
//
//        die;
//        //$status = RedAutoCloseService::getInstance()->getCacheStatus(JobKey::RED_AUTO_CLOSE);
//
//
//        $json = '{"id":5814792502,"first_name":"doc","last_name":"i","username":"ohMyGodMagicIsDestory","language_code":"zh-hans","allows_write_to_pm":true}';
//        $auth_data = [
//            'chat_instance' => 7341815458717890001,
//            'chat_type' => 'sender',
//            'auth_date' => 1715912405,
//            'hash' => '96482bb91d2cadf025b3761af88a83af9ed652d59f7408f7c88b9b7e444666e0',
//            'user' =>$json
//        ];
//
//        foreach ($auth_data as $key => $value) {
//            $data_check_arr[] = $key . '=' . $value;
//        }
//
//        sort($data_check_arr);
//      $data_check_string = implode("&", $data_check_arr);
//        dump(($data_check_string));
//        dump(md5($data_check_string));
//        die;
//
////        $key = 'sadewqewesdq12331234231111111111111111111111111111111111111111';
////       $b=  decryptToken(21563351653211);
////        dump($b);
////        die;
////        $get = $this->request->get();
////        $yes = BotRedSendService::getInstance()->verifyUser($get);
////        if (!$yes){
////            echo '没注册平台';
////            return ;
////        }
////
////        //获取是否注册了平台 和用户信息
////        $userInfo = BotRedSendService::getInstance()->getUserInfo();
////        dump($userInfo->toArray());
////        die;
////
//
//
//        die;
////        $str = BotJieLongRedEnvelopeService::getInstance()->jlCopywriting(100, '0.2', 5, '你好');
//        dump($str);
//        die;
//        $param = $this->request->only(['lang']);
//
//        try {
//            validate(CommonValidate::class)->scene('edit')->check($param);
//        } catch (ValidateException $e) {
//            fail([], $e->getError());
//        }
//        testNameModel::getInstance()->getOne(1);
//        dump(1);
//        die;
    }

    public function edit()
    {

        dump(2);
        die;
    }

    public function add()
    {
        dump(3);
        die;
    }
}
