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
use Illuminate\Support\Facades\Redis;

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

        $verify_flag = true;
        $uri = $request->getRequestUri();
        if($uri == '/api/movie/reply'){
            $cache = 'Comment:verify:switch';
            $wangyiVerify = Redis::get($cache);
            if ($wangyiVerify !== '1') {
                $verify_flag = false;
            }
        }
        if($verify_flag && !Common::wangyiVerify()){
            return response()->json($error);
        }
        return $next($request);
    }
}
