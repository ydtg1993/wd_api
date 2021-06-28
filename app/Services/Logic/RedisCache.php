<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 14:07
 */

namespace App\Services\Logic;

use Illuminate\Support\Facades\Redis;

class RedisCache
{
    private static $CacheConf = null;

    const DEFAULT_TIME = 30;

    /**
     * 获取redis 配置时间
     * @return \Illuminate\Config\Repository|mixed|null
     */
    public static function getCacheConfig()
    {
        return ((self::$CacheConf == null) ? (self::$CacheConf = config('redis_cache')) : (self::$CacheConf));
    }

    /*
    * 获取缓存时间
    */
    public static function getTime($node,$key)
    {
        $cacheConf = self::getCacheConfig();
        return $cacheConf['is_cache'] ? (($cacheConf[$node]??[])[$key] ?? 60 * 30) : 1;//默认30分钟
    }

    /**
     * 获取缓存的KEY
     * @param $key
     * @param array $data
     * @return string
     */
    public static function getKey($key, array $data = [])
    {
        $strKey = '';
        foreach ($data as $k => $v) {
            $strKey .= ($k . ':' . $v);
        }
        $key = $key . ':' . $strKey;
        return $key;
    }

    /**
     * 添加一个KEY到管理器
     * @param string $node 节点
     * @param string $AddKey 管理的REDIS key
     */
    public static function addCacheManageKey($node = '', $AddKey = '',$uid = 0)
    {
        $node = ($uid>0)?($node.$uid):$node;
        if (!Redis::sismember($node, $AddKey))
        {
            Redis::sadd($node, $AddKey);
        }
    }

    /**
     * 清除某个节点管理器中所有的key
     * @param string $node
     * @param string $key
     */
    public static function clearCacheManageAllKey($node = '',$uid = 0)
    {
        $node = ($uid>0)?($node.$uid):$node;
        $keys = Redis::smembers($node);
        foreach ($keys as $v)
        {
            Redis::del($v);
            Redis::srem($node,$v);
        }
        if($uid > 0)
        {
            Redis::del($node);//并且删掉节点
        }
    }

    /**
     * 获取数据缓存  - 普通缓存模式
     * @param $key
     * @param string $fun
     * @param array $arg
     * @param array $keyData
     * @param bool $isCache
     * @return bool
     */
    public static function getCacheData($node,$key,$fun = '',$keyData = array(),$isCache = true,$uid = 0)
    {
        $cacheConf = self::getCacheConfig();
        $redisKey = self::getKey($key,$keyData);
        $redata = $isCache ? Redis::get($redisKey) :NULL;
        if($redata === NULL  || $cacheConf['is_cache'] == false)
        {
            $redata = $fun();
            if($redata === '' || $redata === null)
            {
                return $redata;
            }

            if(is_array($redata) && count($redata) <= 0 )
            {
                return $redata;
            }
            $redata = is_array($redata) ||is_object($redata) ?json_encode($redata):
                $redata;
            //加入节点管理器
            self::addCacheManageKey($node,$key,$uid);
            Redis::set($redisKey,$redata,'EX',self::getTime($node,$key));
            return json_decode($redata,true);
        }
        //return $redata ;
        return json_decode($redata,true) ;
    }


}
