<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 16:35
 */

namespace App\Services\Logic\Home;


use App\Models\Movie;
use App\Models\MovieCategoryAssociate;
use App\Services\Logic\RedisCache;

class CategoryLogic extends HomeBaseLogic
{

    /**
     * 获取数据
     * @param array $arg
     * @return array
     */
    public function getData($arg = array())
    {
        /*根据类别获取数据*/
        return self::getMovieList($arg);
    }

    /**
     * 获取影片数据列表
     * @param $data
     * @param bool $isCache
     * @return bool
     */
    public  static function getMovieList($data,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $cid = $data['cid']??0; // 1.

        $reData = RedisCache::getCacheData('movie','movie:category:list:',function () use ($data,$cid,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            if($cid > 0)
            {
                $movieCategoryAssociateDb = MovieCategoryAssociate::where('movie_category_associate.status',1)->where('cid',$cid);
                $movieDb = Movie::where('movie.status',1)->where('movie.is_up',1);
                $movieCategoryAssociateDb = $movieCategoryAssociateDb->leftJoinSub($movieDb,'movie',function ($join)
                {
                    $join->on('movie.id', '=', 'movie_category_associate.mid');
                });

                (($data['is_subtitle']??1) == 1)?null:($movieCategoryAssociateDb = $movieCategoryAssociateDb->where('movie.is_subtitle',2));
                (($data['is_download']??1) == 1)?null:($movieCategoryAssociateDb = $movieCategoryAssociateDb->where('movie.is_download',2));
                (($data['is_short_comment']??1) == 1)?null:($movieCategoryAssociateDb = $movieCategoryAssociateDb->where('movie.is_short_comment',2));

                $orderBy = (($data['release_time']??2) == 1)?'asc':'desc'; // 1 是 asc 2 是desc
                $movieCategoryAssociateDb = $movieCategoryAssociateDb->orderBy('movie.release_time',$orderBy);

                $orderBy = (($data['flux_linkage_time']??2) == 1)?'asc':'desc'; // 1 是 asc 2 是desc
                $movieCategoryAssociateDb = $movieCategoryAssociateDb->orderBy('movie.flux_linkage_time',$orderBy);
                $reData['sum']= $movieCategoryAssociateDb->count();
                $movieCategoryAssociateList = $movieCategoryAssociateDb->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($movieCategoryAssociateList as $val)
                {
                    $tempVal = Movie::formatList($val);
                    $tempVal['id'] = $val['mid']??0;
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }
            else
            {
                $movieDb = Movie::where('status',1)->where('is_up',1);
                (($data['is_subtitle']??1) == 1)?null:($movieDb = $movieDb->where('is_subtitle',2));
                (($data['is_download']??1) == 1)?null:($movieDb = $movieDb->where('is_download',2));
                (($data['is_short_comment']??1) == 1)?null:($movieDb = $movieDb->where('is_short_comment',2));

                $orderBy = (($data['is_short_comment']??2) == 1)?'asc':'desc'; // 1 是 asc 2 是desc
                $movieDb = $movieDb->orderBy('movie.release_time',$orderBy);

                $orderBy = (($data['flux_linkage_time']??2) == 1)?'asc':'desc'; // 1 是 asc 2 是desc
                $movieDb = $movieDb->orderBy('movie.flux_linkage_time',$orderBy);
                $reData['sum']= $movieDb->count();
                $movieDbList = $movieDb->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($movieDbList as $val)
                {
                    $tempVal = Movie::formatList($val);
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }

            return $reData;
        },['cid'=>$cid,'page'=>$page,'pageSize'=>$pageSize,'args'=>md5(json_encode($data))],$isCache);

        return $reData;
    }


}