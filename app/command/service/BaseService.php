<?php

namespace app\command\service;

use app\common\CacheKey;
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

    //判断是否有任务在执行该方法
    public function getCacheStatus($name)
    {
        //判断是否在执行该逻辑
        $cname = sprintf(CacheKey::REDIS_RED_COMMAND_IS_STATUS,$name);
        $status = Cache::get($cname);
        if ($status){
            return false;
        }
        Cache::set($cname,time(),10);
        return true;
    }

    //删除缓存状态
    public function delStatus($name){
        $cname = sprintf(CacheKey::REDIS_RED_COMMAND_IS_STATUS,$name);
        Cache::delete($cname);
        return true;
    }
}