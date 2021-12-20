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

class VerifyCaptcha
{
    /**
     * 验证码拦截器
     * post参数 key和captcha
     * 获取验证码route xx.xx.com/captcha/api/math
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $error  = [
            'code' => 2001,
            'msg' => '验证码错误',
            'data' => (object)[],
        ];
        if(!Common::wangyiVerify()){
            return response()->json($error);
        }
        return $next($request);
    }
}
