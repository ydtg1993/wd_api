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
        return base64_encode(openssl_encrypt($data, 'aes-128-cbc', base64_decode(self::TOKEN_KEY),
            OPENSSL_RAW_DATA,substr(self::TOKEN_IV,0,16)));
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
        $data = openssl_decrypt(base64_decode($token), 'aes-128-cbc', base64_decode(self::TOKEN_KEY),
            OPENSSL_RAW_DATA,substr(self::TOKEN_IV,0,16));
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
        return base64_encode(openssl_encrypt($data, 'aes-128-ecb', base64_decode(self::PWD_KEY), OPENSSL_RAW_DATA));
    }

    /**
     * 比较密码是否相等
     * @param $inPwd
     * @param $pwd
     * @return bool
     */
    public static function comparePwd($inPwd,$pwd)
    {
        $data = base64_decode(openssl_decrypt(base64_decode($inPwd), 'aes-128-ecb',
            base64_decode(self::PWD_KEY), OPENSSL_RAW_DATA));
        $reData = json_decode(($data),true);
        return ($reData['pwd']??'') == md5($pwd)?true:false;
    }

    /**
     * 检查是否是手机
     * @param $str
     * @return bool
     */
    public static function  isMobile($str)
    {
        return (preg_match("/^1[34578]\d{9}$/", $str))?true:false;
    }

    /**
     * 检查字符串中是否含有汉字
     * @param $str
     * @return bool
     */
    public static function is_Chinese_Character($str)
    {
        return (preg_match("/[\x7f-\xff]/", $str))?true:false;
    }

    /**
     * 获取图片域名配置
     */
    public static function getImgDomain()
    {
        return config('app.image_domain','');
    }

    /*
     *  生成随机字符串
     *
     *   $length    字符串长度
     */
    public static function random_str($length)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for($i = 0; $i < $length; $i++)
        {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 手机验证码验证
     * @param $code
     * @param $phone
     */
    public static function verifCodePhone($phone,$code)
    {
        //TODO
        return true;

        //验证成功则删除验证码 不成功不删除
        $key = 'userinfo:phone:verif:code:';
        $redisKey = RedisCache::getKey($key,array('phone'=>$phone));
        $tempCode = Redis::get($redisKey);
        if($tempCode != $code)
        {
            return false;
        }

        Redis::del($redisKey);
        return true;
    }

    /**
     * 生成一个手机验证码
     * @param $phone
     * @param $code
     */
    public static function saveCodePhone($phone)
    {
        $key = 'userinfo:phone:verif:code:';
        $redisKey = RedisCache::getKey($key,array('phone'=>$phone));
        $redata = rand(100000,999999);
        $time_cache = RedisCache::getTime($key);
        Redis::set($redisKey,$redata,'EX',$time_cache<=1?(60*30):$time_cache);
        return $redata;
    }

    /**
     * 邮箱验证码验证
     * @param $code
     * @param $phone
     */
    public static function verifCodeEmail($phone,$code)
    {
        //TODO
        return true;

        //验证成功则删除验证码 不成功不删除
        $key = 'userinfo:email:verif:code:';
        $redisKey = RedisCache::getKey($key,array('email'=>$phone));
        $tempCode = Redis::get($redisKey);
        if($tempCode != $code)
        {
            return false;
        }

        Redis::del($redisKey);
        return true;
    }

    /**
     * 生成一个邮箱验证码
     * @param $phone
     * @param $code
     */
    public static function saveCodeEmail($email)
    {
        $key = 'userinfo:email:verif:code:';
        $redisKey = RedisCache::getKey($key,array('email'=>md5($email)));
        $redata = rand(100000,999999);
        $time_cache = RedisCache::getTime('comm',$key);
        Redis::set($redisKey,$redata,'EX',$time_cache<=1?(60*30):$time_cache);
        return $redata;
    }

    /**
     *基于news/captcha包
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function checkCaptcha(){

        $rules = ['captcha' => 'required|captcha_api:'. request('key') . ',math'];
        $validator = validator()->make(request()->all(), $rules);
        if ($validator->fails()) {
                return false;
        }
        return true;
    }

    /**
     * 参数过滤
     * @param array $temps 模板数据
     * @param array $param 过滤的参数
     * @return array|bool
     */
    public static function paramFilter(array $temps,array $param)
    {
        //类型检查
        $data = [];
        foreach ($temps as $k=>$v)
        {
            if(isset($param[$k]))
            {
                if(settype($param[$k],gettype($v)))
                {
                    $data[$k] = $param[$k];
                }
                else
                {
                    return false;
                }
            }
            else
            {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * 必填参数检测
     * @param array $temps
     * @param array $param
     * @return bool
     */
    public static function haveToParam(array $temps,array $param)
    {
        foreach ($temps as $k=>$v)
        {
            if(!isset($param[$k]))
            {
                return false;
            }
        }
        return true;
    }




}
