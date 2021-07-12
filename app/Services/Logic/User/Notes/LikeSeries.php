<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MovieSeries;
use App\Models\UserLikeActor;
use App\Models\UserLikeSeries;
use App\Services\Logic\RedisCache;

class LikeSeries extends NotesBase
{
    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $series_id = $data['series_id']??0;
        if($series_id <= 0)
        {
            $this->errorInfo->setCode(500,'无效的系列数据!');
            return false;
        }
        $status = $data['status']??1;

        $id = 0;
        $userLikeInfo = UserLikeSeries::where('uid',$uid)->where('series_id',$series_id)->first();
        if(($userLikeInfo['id']??0)>0)
        {
            UserLikeSeries::where('uid',$uid)->where('series_id',$series_id)->update(['status' =>$status,'like_time'=>date('Y-m-d H:i:s',time())]);
            $id = ($userLikeInfo['id']??0);
        }
        else
        {
            $userLikeObjInfo = new  UserLikeSeries();
            $userLikeObjInfo->uid = $uid;
            $userLikeObjInfo->series_id = $series_id;
            $userLikeObjInfo->status = $status;
            $userLikeObjInfo->like_time = date('Y-m-d H:i:s',time());
            $userLikeObjInfo->save();
            $id = $userLikeObjInfo->id;
        }

        $likeNum = UserLikeSeries::where('series_id',$series_id)->where('status',1)->count();
        MovieSeries::where('id',$series_id)->update(['like_sum' =>$likeNum]);
        //todo 清楚番号缓存后面补充

        RedisCache::clearCacheManageAllKey('userLikeSeries',$uid);//清楚指定用户浏览的缓存
        return $id;
    }

    /**
     * 获取用户收藏系列列表
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

        $reData = RedisCache::getCacheData('userLikeSeries','like:series:list:',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $likeList = UserLikeSeries::where('uid',$uid)
                ->where('status',1)
                ->orderBy('like_time','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get()
                ->pluck('series_id')
                ->toArray();

            $reData['sum'] = UserLikeSeries::where('uid',$uid)
                ->where('status',1)->count();

            $likeListTemp = [];
            foreach ($likeList as $val)
            {
                $likeListTemp[] = $val;
            }

            $likeList = $likeListTemp;

            if(is_array($likeList) || count($likeList) > 0)
            {
                $dataList = MovieSeries::whereIn('id',$likeList)->get();
                if(!$dataList)
                {
                    return $reData;
                }
                $tempData = [];
                foreach ($dataList as $val)
                {
                    $tempData[$val['id']??0] = MovieSeries::formatList($val);//格式化视频数据
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