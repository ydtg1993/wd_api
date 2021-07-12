<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActorPopularityChart extends Model
{
    protected $table = 'actor_popularity_chart';

    public static function getRank($data)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $type = $data['type']??0;// 0.全部、1.有码、2.无码、3.欧美
        $time = $data['time']??(time());// 时间戳

        $reData = RedisCache::getCacheData('Rank','actor:rank:count:list:',function () use ($data,$page,$pageSize,$type,$time)
        {
            $reData = ['list'=>[],'sum'=>0];
            $log = new ActorPopularityChart();
            $type>0?($log = $log->where('cid',$type)):null;

            $log = $log->where('mtime','>=',date('Y-m-01 00:00:00',$time))
                ->where('mtime','<',date('Y-m-d 00:00:00', strtotime(date('Y-m-01',$time) . ' +1 month')))
                ->orderBy('hot_val', 'desc')
                ->orderBy('up_mhot', 'desc');

            $reData['sum'] = DB::table( DB::raw("({$log->toSql()}) as log") )
                ->mergeBindings($log->getQuery())
                ->count();

            $browseList = $log->offset(($page - 1) * $pageSize)
                ->limit($pageSize)->get()
                ->pluck('aid')
                ->toArray();

            $browseListTemp = [];
            foreach ($browseList as $val)
            {
                $browseListTemp[] = $val;
            }

            $browseList = $browseListTemp;
            if(is_array($browseList) || count($browseList) > 0)
            {
                $MovieList = MovieActor::whereIn('id',$browseList)->get();
                $tempMovie = [];

                foreach ($MovieList as $val)
                {
                    $tempMovie[$val['id']??0] = MovieActor::formatList($val);//格式化演员数据
                }
                $rank = ($page-1)*10;
                foreach ($browseList as $val)
                {
                    $rank++;
                    $temp = $tempMovie[$val]??[];
                    $temp['rank'] = $rank;
                    $reData['list'][] = $temp;
                }
            }

            return $reData;
        },['page'=>$page,'pageSize'=>$pageSize,'type'=>$type],true);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }

}
