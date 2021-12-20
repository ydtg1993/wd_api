<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SysDomain extends Model
{
    protected $table = 'sys_domain';
    protected $cacheKey = 'sys_domain';

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
        $mRes = DB::select('select id,domain from '.$this->table.' order by id asc limit 100');

        //写入缓存
        foreach ($mRes as $val) {
            RedisCache::addSets($this->cacheKey, $val->domain);
            $res[]=$val->domain;
        }

        return $res;
    }
}