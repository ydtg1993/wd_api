<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;

class MovieFilmCompanies extends Model
{
    protected $table = 'movie_film_companies';

    /**
     * 格式化片商列表数据
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
     * 获取获取片商列表
     * @param $data
     * @param bool $is_cache
     * @return array
     */
    public static function getList($data,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $cid = $data['cid']??0; // 1.
        $reData = RedisCache::getCacheData('filmCompanies','movie:film:companies:list:',function () use ($data,$cid,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            if($cid > 0)
            {
                $filmCompaniesCategoryAssociateDb = MovieFilmCompaniesCategoryAssociate::where('movie_film_companies_category_associate.status',1)->where('movie_film_companies_category_associate.cid',$cid);
                $movieDb = MovieFilmCompanies::where('movie_film_companies.status',1);
                $filmCompaniesCategoryAssociateDb = $filmCompaniesCategoryAssociateDb->leftJoinSub($movieDb,'movie_film_companies',function ($join)
                {
                    $join->on('movie_film_companies.id', '=', 'movie_film_companies_category_associate.film_companies_id');
                });

                $reData['sum'] = $filmCompaniesCategoryAssociateDb->count();
                    $filmCompaniesCategoryAssociateList = $filmCompaniesCategoryAssociateDb->orderBy('movie_film_companies.movie_sum','desc')
                    ->orderBy('movie_film_companies.like_sum','desc')
                    ->orderBy('movie_film_companies.updated_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($filmCompaniesCategoryAssociateList as $val)
                {
                    $tempVal = self::formatList($val);
                    $tempVal['id'] = $val['film_companies_id']??0;
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }
            else
            {
                $reData['sum'] = MovieFilmCompanies::where('status',1)->count();
                $filmCompaniesList = MovieFilmCompanies::where('status',1)->orderBy('movie_sum','desc')
                    ->orderBy('like_sum','desc')
                    ->orderBy('updated_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($filmCompaniesList as $val)
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
        return $this->hasMany(MovieFilmCompaniesCategoryAssociate::class,'film_companies_id','id');
    }

    public function numbers()
    {
        return $this->hasMany(MovieFilmCompaniesAss::class,'film_companies_id','id');
    }
}
