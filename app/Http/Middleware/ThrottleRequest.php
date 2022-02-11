<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use \Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleRequest extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next , $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $key = $prefix.$this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

        /*if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildException($key, $maxAttempts);
            //throw $this->buildException($key, $maxAttempts);
            // 原来的是抛出异常,修改成直接返回
        }*/
        //去掉 `* 60` 限制秒级,加上去限制分钟,要限制其他单位，可以自己算的
//        $this->limiter->hit($key, $decayMinutes);
        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    protected function buildException($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);

        //要返回的数据
        /*$message = json_encode([
            'code' => 429,
            'data' => null,
            'msg' => '您的请求太频繁，已被限制请求',
            'retryAfter' => $retryAfter,
        ], 320);
        */

        $response = new Response($message, 200);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

    }


    protected function addHeaders(\Symfony\Component\HttpFoundation\Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        // 添加 `response` 头 为 `json`
        $response->headers->add(
            ['Content-Type' => 'application/json;charset=utf-8']
        );
        return parent::addHeaders($response, $maxAttempts, $remainingAttempts, $retryAfter);
    }

}
