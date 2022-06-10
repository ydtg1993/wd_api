<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    protected $table = 'filter_keyword';

    /**
     * 判断是否存在
     */
    public static function check($text = '')
    {
        $casheKey = 'filter_keyword';
        //优先读取缓存
        $res = RedisCache::getSetAll($casheKey);

        if(count($res)<1)
        {
            //从数据库读取
            $mRes = DB::select('select id,content from filter_keyword order by id asc limit 200');

            //写入缓存
            foreach ($mRes as $val) {
                RedisCache::addSets($casheKey, $val->content);
                $res[]=$val->content;
            }
        }

        foreach($res as $v)
        {
            if(strpos($text,$v) !== false)
            {
                return true;
            }
        }

        return false;
    }
}
