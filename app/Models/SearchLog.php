<?php
//搜索日志
namespace App\Models;

use App\Tools\RedisCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchLog extends Model
{
    protected $table = 'user_search_log';

    /**
     * 读取指定时间范围内的数据统计
     */
    public function groupCountLists($starttime,$endtime)
    {
        $where = " where created_at between '$starttime' and '$endtime' ";
        $mRes = DB::select('select content,count(0) as nums from '.$this->table.$where.' group by content order by nums desc limit 20;');
        return $mRes;
    }
}
