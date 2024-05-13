<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\CodeName;

class SignMiddleware
{
    /**
     * sin
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if ($request->param('sign') != 'sign') {
             fail([],language('sign error'),CodeName::SIGN_ERROR);
        }

        return $next($request);
    }
    public function end(\think\Response $response)
    {
        // 回调行为
    }
}
