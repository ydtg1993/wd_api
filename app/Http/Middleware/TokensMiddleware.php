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

class TokensMiddleware
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
            $request->tokenData = null;
            $request->userData = [
                'uid'=>0,//没有token 代表未登录 0 表示未登录
                'u_number'=>'',
                'ip'=>$request->getClientIp()
            ];//是否token校验IP
            return $next($request);
        }
        $request->tokenData = $tokenData;
        $request->userData = [
            'uid'=>$tokenData['UserBase']['uid']??0,
            'u_number'=>$tokenData['UserBase']['number']??'',
            'ip'=>$request->getClientIp()
        ];//是否token校验IP
        /*$request->userData = [
            'uid'=>1,
            'u_number'=>'',
            'ip'=>$request->getClientIp()
        ];//是否token校验IP*/
        return $next($request);
    }
}