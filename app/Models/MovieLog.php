<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovieLog extends Model
{
    protected $table = 'movie_log';

    const UPDATED_AT = null;

    /**
     * 添加影片浏览记录
     */
    public static function addMovieBrowse($mid)
    {
        if($mid <= 0)
        {
            return 0;
        }

        //查询影片类别
        $categoryAssociate = MovieCategoryAssociate::where('mid',$mid)->where('status',1)->first();
        $movieObj = new MovieLog();
        $movieObj->mid = $mid;
        $movieObj->cid = $categoryAssociate['cid']??0;
        $movieObj->save();
        return $movieObj->id;
    }

    public static function getRankingVersion($data)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $type = $data['type']??0;// 0.全部、1.有码、2.无码、3.欧美
        $time = $data['time']??0;// 0.全部、1.日版、2.周榜、3.月榜

        $reData = RedisCache::getCacheData('Rank','movie:rank:count:list:',function () use ($data,$page,$pageSize,$time,$type)
        {
            $reData = ['list'=>[],'sum'=>0];

            $log = new MovieLog();
            $type>0?($log = $log->where('cid',$type)):null;
            if($time > 0)
            {
                $time == 1?($log = $log->where('created_at','>=',date('Y-m-d 00:00:00',time()))):null;
                $time == 2?($log = $log->where('created_at','>=',(date('Y-m-d 00:00:00' ,strtotime( '-'.(date('w',time())-1) .' days', time()))))):null;
                $time == 3?($log = $log->where('created_at','>=',date('Y-m-01 00:00:00',time()))):null;
            }

            $log = $log->selectRaw('count(mid) as num,mid')->groupBy('mid');
            $reData['sum'] = DB::table( DB::raw("({$log->toSql()}) as log") )
                ->mergeBindings($log->getQuery())
                ->count();
            $browseList = $log->orderBy('num', 'desc')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->pluck('mid')->toArray();

            $browseListTemp = [];
            foreach ($browseList as $val)
            {
                $browseListTemp[] = $val;
            }

            $browseList = $browseListTemp;
            if(is_array($browseList) || count($browseList) > 0)
            {
                $MovieList = Movie::whereIn('id',$browseList)->get();
                $tempMovie = [];

                foreach ($MovieList as $val)
                {
                    $tempMovie[$val['id']??0] = Movie::formatList($val);//格式化视频数据
                }
                $rank = ($page-1)*10;
                foreach ($browseList as $val)
                {
                    $rank++;
                    $temp = $tempMovie[$val]??[];
                    $temp['rank'] =$rank;
                    $reData['list'][] = $temp;
                }
            }
            $reData['sum']>=100?($reData['sum'] = 100):null;
            return $reData;
        },['time'=>$time,'page'=>$page,'pageSize'=>$pageSize,'type'=>$type],true);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }
}
