<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ads extends Model
{
    protected $table = 'ads_list';
    protected $cacheKey = 'ads_list';

    /**
     * 读取关键词列表
     */
    public function lists($location='')
    {
        //优先读取缓存
        $res = RedisCache::getZSetAll($this->cacheKey.':'.$location,100);
        if($res){
            $row = [];
            foreach($res as $v){
                $row[]=json_decode($v);
            }
            return $row;
        }


        //读取数据库
        $mRes = DB::select('select id,name,location,photo,url,is_close,sort from '.$this->table.' where location=? and start_time <? and end_time>? and status=1 order by sort desc limit 100',[$location,date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]);

        //写入缓存
        foreach ($mRes as $key=>$val) {
            RedisCache::addZSets($this->cacheKey.':'.$location,$key, json_encode($val));
        }

        //读取缓存
        $res = RedisCache::getZSetAll($this->cacheKey.':'.$location,100);

        $row = [];
        if($res){
            foreach($res as $v){
                $row[]=json_decode($v);
            }
        }
        return $row;
    }
}