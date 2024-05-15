<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\CacheKey;
use app\common\CodeName;
use think\Exception;
use think\facade\Cache;

class SignMiddleware
{
    /**
     * sin
     *
     * @param \think\Request $request
     * @param \Closure       $next
     */
    public function handle($request, \Closure $next)
    {
        $sign = $request->header('sign');
        if (empty($sign)){
            fail([],language('sign error'),CodeName::SIGN_ERROR);
        }
        $data = decryptToken($sign);
        //获取redis sign
        //储存到 redis
        $jsonUser = Cache::get(sprintf(CacheKey::REDIS_TG_USER_INFO,$data['tg_id']));
        if (empty($jsonUser)){
            fail([],language('sign error'),CodeName::SIGN_ERROR);
        }
        //用户信息
        $request->user_info  = json_decode($jsonUser,true);

        $tgJsonUser = Cache::get(sprintf(CacheKey::REDIS_TG_USER,$data['tg_id']));
        if (empty($tgJsonUser)){
            fail([],language('sign error'),CodeName::SIGN_ERROR);
        }

        $request->tg_user_info  = json_decode($tgJsonUser,true);
        return $next($request);
    }
    public function end(\think\Response $response)
    {
        // 回调行为
    }
}
