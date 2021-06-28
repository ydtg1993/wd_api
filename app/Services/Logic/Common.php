<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:21
 */

namespace App\Services\Logic;


use Illuminate\Support\Facades\Redis;

class Common
{

    const TOKEN_KEY = '*yellowDouBan_TOKEN_2021*';
    const TOKEN_IV = '*ydb_iv_token_2021*';
    const PWD_KEY = '*yellowDouBan_pwd_2021*';

    /**
     * 频率限制
     * @param array $keyData
     * @param int $time
     * @param int $num
     * @return bool
     */
    public static function frequencyLimit($keyData = array(), $time = 0, $num = 1)
    {
        $node = 'common';
        $key = 'ydouban:frequency:limit';
        $redisKey = RedisCache::getKey($key, $keyData);
        $redata = Redis::get($redisKey);
        if (intval($redata) >= $num) {
            return false;
        } elseif (intval($redata) <= 0) {
            Redis::incrBy($redisKey, 1);
            $time = ($time <= 0) ? RedisCache::getTime($node, $key) : $time;
            Redis::expire($redisKey, $time > 0 ? $time : 1);//设置缓存时间
        } else {
            Redis::incrBy($redisKey, 1);
        }
        return true;
    }

    /**
     * 生成token
     * @param array $data
     * @return string
     */
    public static function generateToken($data = [])
    {
        $data['time'] = time();
        $data = base64_encode(json_encode($data)) ;
        return openssl_encrypt($data, 'aes-128-ecb', base64_decode(self::TOKEN_KEY), OPENSSL_ZERO_PADDING,self::TOKEN_IV);
    }

    /**
     * 解析token数据
     * @param string $token
     * @return mixed
     */
    public static function  parsingToken($token = '')
    {
        if($token == '' && $token == null)
        {
            return [];
        }
        //解密过程待定
        $data = openssl_decrypt($token, 'aes-128-ecb', base64_decode(self::TOKEN_KEY), OPENSSL_ZERO_PADDING,self::TOKEN_IV);
        $reData = json_decode(base64_decode($data),true);
        return $reData;
    }

    /**
     * 加密密码
     * @param $pwd
     * @return string
     */
    public static function encodePwd($pwd)
    {
        $data = [
            'time'=>time(),
            'pwd'=>md5($pwd)
        ];
        $data = base64_encode(json_encode($data)) ;
        return openssl_encrypt($data, 'aes-128-ecb', base64_decode(self::PWD_KEY), OPENSSL_RAW_DATA);
    }

    /**
     * 比较密码是否相等
     * @param $inPwd
     * @param $pwd
     * @return bool
     */
    public static function comparePwd($inPwd,$pwd)
    {
        $data = openssl_decrypt($pwd, 'aes-128-ecb', base64_decode(self::PWD_KEY), OPENSSL_RAW_DATA);
        $reData = json_decode(base64_decode($data),true);
        return ($reData['pwd']??'') == md5($inPwd)?true:false;
    }


}