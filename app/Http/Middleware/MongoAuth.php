<?php


namespace App\Http\Middleware;

use \Closure;
use \Illuminate\Http\Request;

/**
 * 验证 API 请求的合法性， 保护 mango 数据源的安全
 * Class MongoAuth
 * @package App\Http\Middleware
 */
class MongoAuth
{
    const AUTH_KEY = '__mongoDb_hdb__';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $error  = [
            'code' => 2000,
            'msg' => 'token error',
            'data' => (object)[],
        ];
        $token = $request->header('token');
        $body = $request->post();
        ksort($body);
        $strSign = '';
        foreach ($body as $k => $v){
            $strSign .= $k . "=" . $v . "&";
        }
        $strSign .= "key=" . self::AUTH_KEY;
        $mdt = md5($strSign);
        if ($mdt != $token){
            return response()->json($error,200);
        }
        return $next($request);
    }
}
