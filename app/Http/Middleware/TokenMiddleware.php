<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:37
 */

namespace App\Http\Middleware;

use App\Services\Logic\Common;
use Closure;
use Illuminate\Support\Facades\Auth;

class TokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
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
        $tokenData = Common::parsingToken($token);//解析token
        if(empty($tokenData)){
            $reData['msg'] = 'token error';
            return response()->json($error,200);
        }
        $request->tokenData = $tokenData;
        $request->userData = [
            'uid'=>$tokenData['UserBase']['uid'],
            'u_number'=>$tokenData['UserBase']['number'],
            'ip'=>$request->getClientIp()
        ];//是否token校验IP

        return $next($request);
    }
}
