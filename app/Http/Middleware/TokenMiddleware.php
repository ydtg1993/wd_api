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
use App\Models\UserBlack;

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

        //判断拉黑的用户不能登陆
        $blackDay = UserBlack::getBlackDay($tokenData['UserBase']['uid'],3);        
        if($blackDay>=1)
        {
            $reData['msg'] = 'token error';
            return response()->json($error,200);
        }

        $request->tokenData = $tokenData;
        $request->userData = [
            'uid'=>$tokenData['UserBase']['uid'],
            'nickname'=>$tokenData['UserBase']['nickname'],
            'avatar'=>$tokenData['UserBase']['avatar'],
            'ip'=>$request->getClientIp()
        ];//是否token校验IP

        return $next($request);
    }
}
