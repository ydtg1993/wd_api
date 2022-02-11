<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:37
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnableCrossRequestMiddleware
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
        /**
         * 用于跨域调用
         */
        if($request->isMethod('OPTIONS')){
            $response = response('',200);

        }else{
            $response = $next($request);
        }

        //$response->header('Access-Control-Allow-Origin',"*");
        //$response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE');
       // $response->header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, Cookies, Token,token,content-type');
        //$response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Cache-Control', 'no-store');

        return $response;
    }
}