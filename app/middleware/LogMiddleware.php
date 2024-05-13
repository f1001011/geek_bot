<?php

namespace app\middleware;

use think\facade\Log;

class LogMiddleware
{
    public function handle($request, \Closure $next)
    {
        //请求调用开始日志
        traceLog($request->param(),'http_web_start');
        return $next($request);
    }

    public function end($request, \think\Response $response)
    {
        traceLog([$request->param(),$response],'http_web_end');
        // 回调行为
    }
}