<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MovieFilmCompanies;
use App\Models\UserLikeActor;
use App\Models\UserLikeFilmCompanies;
use App\Services\Logic\RedisCache;

class LikeFilmCompanies extends NotesBase
{
    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $film_companies_id = $data['film_companies_id']??0;
        if($film_companies_id <= 0)
        {
            $this->errorInfo->setCode(500,'无效的片商数据!');
            return false;
        }
        $status = $data['status']??1;

        $id = 0;
        $userLikeInfo = UserLikeFilmCompanies::where('uid',$uid)->where('film_companies_id',$film_companies_id)->first();
        if(($userLikeInfo['id']??0)>0)
        {
            UserLikeFilmCompanies::where('uid',$uid)->where('film_companies_id',$film_companies_id)->update(['status' =>$status,'like_time'=>date('Y-m-d H:i:s',time())]);
            $id = ($userLikeInfo['id']??0);
        }
        else
        {
            $userLikeObjInfo = new  UserLikeFilmCompanies();
            $userLikeObjInfo->uid = $uid;
            $userLikeObjInfo->film_companies_id = $film_companies_id;
            $userLikeObjInfo->status = $status;
            $userLikeObjInfo->like_time = date('Y-m-d H:i:s',time());
            $userLikeObjInfo->save();
            $id = $userLikeObjInfo->id;
        }

        $likeNum = UserLikeFilmCompanies::where('film_companies_id',$film_companies_id)->where('status',1)->count();
        MovieFilmCompanies::where('id',$film_companies_id)->update(['like_sum' =>$likeNum]);
        //todo 清楚系列缓存后面补充


        RedisCache::clearCacheManageAllKey('userLikeFilmCompanies',$uid);//清楚指定用户浏览的缓存
        return $id;
    }

    /**
     * 获取用户收藏片商列表
     * @param $data
     * @param bool $isCache
     * @return array|bool|null
     */
    public function getNotesList($data,$isCache = true)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return [];
        }

        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;

        $reData = RedisCache::getCacheData('userLikeFilmCompanies','like:film:companies:list:',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $likeList = UserLikeFilmCompanies::where('uid',$uid)
                ->where('status',1)
                ->orderBy('like_time','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get()
                ->pluck('film_companies_id')
                ->toArray();

            $reData['sum'] = UserLikeFilmCompanies::where('uid',$uid)
                ->where('status',1)->count();

            $likeListTemp = [];
            foreach ($likeList as $val)
            {
                $likeListTemp[] = $val;
            }

            $likeList = $likeListTemp;
            if(is_array($likeList) || count($likeList) > 0)
            {
                $dataList = MovieFilmCompanies::whereIn('id',$likeList)->get();
                if(!$dataList)
                {
                    return $reData;
                }
                $tempData = [];
                foreach ($dataList as $val)
                {
                    $tempData[$val['id']??0] = MovieFilmCompanies::formatList($val);//格式化视频数据
                }

                foreach ($likeList as $val)
                {
                    $reData['list'][] = ($tempData[$val]??[]);
                }
            }
            return $reData;
        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }
}