<?php

namespace App\Models;

use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;

class MovieActor extends Model
{
    protected $table = 'movie_actor';

    /**
     * 格式化演员列表数据
     * @param array $data
     */
    public static function formatList($data = [], $addInfo = false)
    {
        $photo = $data['photo'] ?? '';
        $reData = [];
        $reData['id'] = $data['id'] ?? 0;
        $reData['name'] = $data['name'] ?? '';
        $reData['photo'] = $photo == '' ? '' : (Common::getImgDomain() . $photo);
        $reData['sex'] = $data['sex'] ?? 1;
        $reData['social_accounts'] = json_decode($data['social_accounts'] ?? '', true);
        $reData['movie_sum'] = $data['movie_sum'] ?? 0;
        $reData['like_sum'] = $data['like_sum'] ?? 0;

        $addInfo && $reData['info'] = [
            'new_movie_count' => $data['new_movie_count'],
            'new_movie_pv' => $data['new_movie_pv'],
            'new_movie_want' => $data['new_movie_want'],
            'new_movie_seen' => $data['new_movie_seen'],
            'new_movie_score' => $data['new_movie_score'],
            'new_movie_score_people' => $data['new_movie_score_people'],
        ];
        return $reData;
    }

    /**
     * 获取演员列表
     * @param $data
     * @param bool $is_cache
     * @return array
     */
    public static function getList($data, $isCache = true)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pageSize'] ?? 10;
        $cid = $data['cid'] ?? 0; // 1.
        $cache_ext = ['cid' => $cid, 'page' => $page, 'pageSize' => $pageSize];
        if($data['gender'] != ''){
            $cache_ext['gender'] = ($data['gender'] == 1) ? 1 : 0;
        }
        $reData = RedisCache::getCacheData('actor', 'movie:actor:list:', function () use ($data, $cid, $page, $pageSize) {
            $reData = ['list' => [], 'sum' => 0];
            if ($cid > 0) {
                $actorCategoryAssociateDb = MovieActorCategoryAssociate::where('movie_actor_category_associate.status', 1)->where('movie_actor_category_associate.cid', $cid);
                $movieDb = MovieActor::where('movie_actor.status', 1);
                $actorCategoryAssociateDb = $actorCategoryAssociateDb->leftJoinSub($movieDb, 'movie_actor', function ($join) {
                    $join->on('movie_actor.id', '=', 'movie_actor_category_associate.aid');
                });
                if($data['gender'] != ''){
                    $gender = ($data['gender'] == 1) ? 1 : 0;
                    if($gender == 1){
                        $actorCategoryAssociateDb->where('movie_actor.sex','♂');
                    }else{
                        $actorCategoryAssociateDb->where('movie_actor.sex','♀')->orWhere('movie_actor.sex','');
                    }
                }
                $reData['sum'] = $actorCategoryAssociateDb->count();
                $actorCategoryAssociateList = $actorCategoryAssociateDb->orderBy('movie_actor.movie_sum', 'desc')
                    ->orderBy('movie_actor.like_sum', 'desc')
                    ->orderBy('movie_actor.updated_at', 'desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();

                foreach ($actorCategoryAssociateList as $val) {
                    $tempVal = self::formatList($val);
                    $tempVal['id'] = $val['aid'] ?? 0;
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            } else {
                $actorCategoryAssociateDb = MovieActor::where('status', 1);
                $actorList = $actorCategoryAssociateDb->orderBy('movie_sum', 'desc')->orderBy('like_sum', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();
                $reData['sum'] = $actorCategoryAssociateDb->count();
                foreach ($actorList as $val) {
                    $tempVal = self::formatList($val);
                    $reData['list'][] = $tempVal;
                }

                return $reData;
            }

            return $reData;

        }, $cache_ext, $isCache);

        return $reData;
    }

    public function names()
    {
        return $this->hasMany(MovieActorName::class, 'aid', 'id');
    }

    public function categories()
    {
        return$this->hasMany(MovieActorCategoryAssociate::class,'aid','id');
    }
}
