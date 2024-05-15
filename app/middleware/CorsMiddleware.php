<?php
namespace app\middleware;

class CorsMiddleware
{
    public function handle($request, \Closure $next)
    {
        // 设置响应头
        header('Access-Control-Allow-Origin: *'); // 允许所有来源的访问，或者你可以指定一个具体的域名
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // 允许的 HTTP 方法
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With,Sign,Token'); // 允许的 HTTP 头
        header('Access-Control-Allow-Credentials: true'); // 允许携带凭证（如 cookies）

        // 对于 OPTIONS 请求，直接返回响应头信息，不继续向下执行
        if ($request->method() == 'OPTIONS') {
            return response('');
        }

        // 继续执行后续请求
        return $next($request);
    }
}