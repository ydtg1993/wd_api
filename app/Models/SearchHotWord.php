<?php
//搜索日志
namespace App\Models;

use App\Tools\RedisCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchHotWord extends Model
{
    protected $table = 'hot_keyword';
    protected $cacheKey = 'hot_keyword';

    /**
     * 读取关键词列表
     */
    public function lists()
    {
        //读取数据库
        $mRes = DB::select('select id,content from '.$this->table.' order by id asc limit 20');

        return $mRes;
    }

    /**
     * 读取当前已经设置的条数
     */
    public function total()
    {
        //读取数据库
        $mRes = DB::select('select count(0) as nums from '.$this->table.'');

        return $mRes[0]->nums;
    }
}
