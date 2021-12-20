<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HotWords extends Model
{
    protected $table = 'hot_keyword';
    protected $cacheKey = 'hot_keyword';

    /**
     * 读取关键词列表
     */
    public function lists()
    {
        //优先读取缓存
        $res = RedisCache::getSetAll($this->cacheKey);
        if($res){
            return $res;
        }

        //读取数据库
        $mRes = DB::select('select id,content from '.$this->table.' order by id asc limit 20');

        //写入缓存
        foreach ($mRes as $val) {
            RedisCache::addSets($this->cacheKey, $val->content);
            $res[]=$val->content;
        }

        return $res;
    }
}