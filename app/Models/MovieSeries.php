<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;

class MovieSeries extends Model
{
    protected $table = 'movie_series';

    /**
     * 格式化系列列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $reData = [];
        $reData['id'] = $data['id']??0;
        $reData['name'] = $data['name']??'';
        $reData['movie_sum'] = $data['movie_sum']??0;
        $reData['like_sum'] = $data['like_sum']??0;
        return $reData;
    }

    /**
     * 获取系列列表
     * @param $data
     * @param bool $is_cache
     * @return array
     */
    public static function getList($data,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $cid = $data['cid']??0; // 1.
        $reData = RedisCache::getCacheData('series','movie:series:list:',function () use ($data,$cid,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            if($cid > 0)
            {
                $seriesCategoryAssociateDb = MovieSeriesCategoryAssociate::where('movie_series_category_associate.status',1)->where('movie_series_category_associate.cid',$cid);
                $movieDb = MovieSeries::where('movie_series.status',1);
                $seriesCategoryAssociateDb = $seriesCategoryAssociateDb->leftJoinSub($movieDb,'movie_series',function ($join)
                {
                    $join->on('movie_series.id', '=', 'movie_series_category_associate.series_id');
                });
                $reData['sum'] = $seriesCategoryAssociateDb->count();
                $seriesCategoryAssociateList = $seriesCategoryAssociateDb->orderBy('movie_series.movie_sum','desc')
                    ->orderBy('movie_series.like_sum','desc')
                    ->orderBy('movie_series.updated_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($seriesCategoryAssociateList as $val)
                {
                    $tempVal = self::formatList($val);
                    $tempVal['id'] = $val['series_id']??0;
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }
            else
            {
                $seriesCategoryAssociateDb = MovieSeries::where('status',1);
                $reData['sum'] = $seriesCategoryAssociateDb->count();
                $actorList = $seriesCategoryAssociateDb->orderBy('movie_sum','desc')
                    ->orderBy('like_sum','desc')
                    ->orderBy('updated_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($actorList as $val)
                {
                    $tempVal = self::formatList($val);
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }

            return $reData;

        },['cid'=>$cid,'page'=>$page,'pageSize'=>$pageSize],$isCache);

        return $reData;
    }

    public function categories()
    {
        return $this->hasMany(MovieSeriesCategoryAssociate::class,'series_id','id');
    }

    public function numbers()
    {
        return $this->hasMany(MovieSeriesAss::class,'series_id','id');
    }
}
