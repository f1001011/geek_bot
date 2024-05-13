<?php

namespace app\middleware;

use Closure;
use think\facade\Db;

class LogSqlMiddleware
{
    public function handle($request, Closure $next)
    {
        Db::listen(function ($sql, $time, $explain) {
            // 记录SQL语句、执行时间和解释信息到日志
            $log = [
                //'title' => 'SQL记录:',
                'sql' => $sql,
                'time' => $time,
                //'explain' => $explain,
                //'request_id' => REQUEST_ID,
            ];
            traceLog($log, 'http_web_start');
        });
        $response = $next($request);

        // 执行后可进行的操作
        return $response;
    }
}