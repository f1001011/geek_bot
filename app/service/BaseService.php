<?php

namespace app\service;

use app\common\CacheKey;
use app\facade\BotFacade;
use app\model\LotteryJoinModel;
use think\facade\Cache;
use think\facade\Request;

class BaseService
{
    protected static $_instance;

    public static function getInstance()
    {
        $localClass = new static();
        if ($localClass::$_instance instanceof $localClass) {
            return $localClass::$_instance;
        } else {
            $localClass::$_instance = new static();
            return self::$_instance;
        }
    }



    //防止i重复点击
    public function repeatPost($callbackQueryId = '')
    {
        $ip = Request::ip();
        //防止重复请求
        $cache = Cache::get(sprintf(CacheKey::REDIS_TELEGRAM_RED_POST_IP, $ip));
        if (!$cache) {
            Cache::set(sprintf(CacheKey::REDIS_TELEGRAM_RED_POST_IP, $ip), date('Y-m-d H:i:s'), CacheKey::REDIS_TELEGRAM_RED_POST_IP_TTL);
        } else {
            //请勿重复操作
            if (!empty($callbackQueryId)){
                BotFacade::SendCallbackQuery($callbackQueryId, '请勿重复操作');
            }
            trace("{$ip}:{$cache}:请勿重复操作", 'repeatPost');
            die;
        }
    }

    public function setSendPost($redId){
        //防止 重复发送红包
        $cache = Cache::get(sprintf(CacheKey::REDIS_TELEGRAM_RED_SEND_POST, $redId));
        if (!empty($cache)){
            trace("{$redId}:{$cache}:重复发送红包", 'setSendPost');
            die;
        }
        Cache::set(sprintf(CacheKey::REDIS_TELEGRAM_RED_POST_IP, $redId), date('Y-m-d H:i:s'), CacheKey::REDIS_TELEGRAM_RED_SEND_POST_TTL);
        return true;
    }
    //计算 当前；领取红包金额
    public function grabNextRedPack($toMoney, $toPeople)
    {
        //$toMoney 剩余金额，剩余人数
        if ($toMoney <= 0 || $toPeople <= 0) {
            return 0;
        }
        //设置最小金额
        $minAmount = 0.01;
        //如果只有一个人直接返回所有的金额
        if ($toPeople == 1) {
            return $toMoney;
        }
        //超过一个人，第一个人最多获取到红包的 60%;
        $maxAmountRate = 0.6;
        $maxAmount = $toMoney * $maxAmountRate;
        //获取应该获得的金额
        $amount = mt_rand($minAmount * 100, $maxAmount * 100) / 100;

        //出错误了
        if ($toMoney - $amount < 0) {
            return 0;
        }
        return $amount;
    }

    //加密
    public function encrypt($data, $key)
    {
        //$encryptedData = encrypt($data, $key);
        $method = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $encrypted, $key, true);
        return base64_encode($iv . $hmac . $encrypted);
    }


    //解密
    public function decrypt($data, $key)
    {
        //$decryptedData = decrypt($encryptedData, $key);
        $method = 'AES-256-CBC';
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, $ivLength, 32);
        $encrypted = substr($data, $ivLength + 32);
        $calculatedHmac = hash_hmac('sha256', $encrypted, $key, true);
        if (hash_equals($hmac, $calculatedHmac)) {
            return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
        } else {
            return false; // HMAC不匹配，数据可能被篡改
        }
    }



}