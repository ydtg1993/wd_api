<?php

namespace App\Models;

use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ActorPopularityChart extends Model
{
    protected $table = 'actor_popularity_chart';

    public static function getRank($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pageSize'] ?? 10;
        $type = $data['type'] ?? 0;// 0.全部、1.有码、2.无码、3.欧美
        if(isset($data['time'])){
            $time = strtotime('+1 month',$data['time']);
        }else{
            $time = time();
        }

        $reData = ['list' => [], 'sum' => 0, 'cache' => 0];
        $this_month = date('Y-m-01 00:00:00', $time);//本月时间
        $this_month_key = date('Y-m',$time);
        $cache = "Rank:actor:rank:{$type}:{$this_month_key}";
        $record = Redis::get($cache);
        if ($record) {
            $record = (array)json_decode($record, true);
            $reData['list'] = array_slice($record['list'], ($page - 1) * $pageSize, $pageSize);
            $reData['sum'] = $record['sum'];
            $reData['cache'] = 1;
            return $reData;
        }

        $actors = ActorPopularityChart::join('movie_actor', 'actor_popularity_chart.aid', '=', 'movie_actor.id')
            ->whereIn('movie_actor.sex', ['♀',''])
            ->where('actor_popularity_chart.cid', $type)
            ->where('actor_popularity_chart.mtime', $this_month)
            ->orderBy('actor_popularity_chart.rank', 'asc')
            ->offset(0)
            ->limit(100)
            ->select('movie_actor.*',
                'actor_popularity_chart.new_movie_count',
                'actor_popularity_chart.new_movie_pv',
                'actor_popularity_chart.new_movie_want',
                'actor_popularity_chart.new_movie_seen',
                'actor_popularity_chart.new_movie_score',
                'actor_popularity_chart.new_movie_score_people')
            ->get()->toArray();

        $temp = [];
        foreach ($actors as $val) {
            $actor = MovieActor::formatList((array)$val,true);
            $actor['float'] = $val['rank_float'];
            $temp[] = $actor;
        }
        $reData['sum'] = count($temp);
        $reData['list'] = $temp;
        Redis::setex($cache, 3600 * 48, json_encode($reData));

        return $reData;
    }

}
