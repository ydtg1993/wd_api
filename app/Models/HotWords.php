<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
        $res = Redis::get($this->cacheKey);
        if($res){
            return (array)json_decode($res);
        }
        $mRes = DB::select('select id,content from '.$this->table.' order by times DESC limit 20');

        $res = [];
        foreach ($mRes as $val) {
            $res[]=$val->content;
        }
        Redis::set($this->cacheKey,json_encode($res));

        return $res;
    }
}
