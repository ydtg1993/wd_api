<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:35
 */

namespace App\Services\Logic\Home;


use App\Models\Movie;
use App\Services\Logic\RedisCache;

class PopularLogic extends HomeBaseLogic
{

    public function getData($arg = array())
    {
        return PopularLogic::getPopular($arg);
    }

    /**
     * 获取热门影片数据列表
     * @param $data
     * @param bool $isCache
     * @return bool
     */
    public  static function getPopular($data,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $reData = RedisCache::getCacheData('movie','movie:popular:list:',function () use ($data,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            $MovieListDb = Movie::where('status',1)->where('status',1)->orderBy('score','desc')->orderBy('comment_num','desc')->orderBy('new_comment_time','desc');
            $reData['sum'] = $MovieListDb->count();
            $MovieList = $MovieListDb->offset(($page - 1) * $pageSize)
                ->limit($pageSize)->get();

            if($MovieList)
            {
                foreach ($MovieList as $val)
                {
                    $reData['list'][] = Movie::formatList($val);
                }

                return $reData;
            }
            return $reData;


        },['page'=>$page,'pageSize'=>$pageSize],$isCache);

        return $reData;
    }
}