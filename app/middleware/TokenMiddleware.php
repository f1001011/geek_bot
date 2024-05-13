<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\CodeName;

class TokenMiddleware
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
        //token错误
        if ($request->param('action-token') != 'think') {
            fail([],'token-error',CodeName::TOKEN_ERROR);
        }
        //token过期
        if ($request->param('action-token') > '过期时间') {
            fail([],'token-expire',CodeName::TOKEN_EXPIRE);
        }
        return $next($request);
    }
    public function end(\think\Response $response)
    {
        // 回调行为
    }
}
