<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MovieActor;
use App\Models\UserLikeActor;
use App\Services\Logic\RedisCache;

class LikeActor extends NotesBase
{
    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $aid = $data['aid']??0;
        if($aid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的演员数据!');
            return false;
        }
        $status = $data['status']??1;

        $id = 0;
        $userLikeActor = UserLikeActor::where('uid',$uid)->where('aid',$aid)->first();
        if(($userLikeActor['id']??0)>0)
        {
            UserLikeActor::where('uid',$uid)->where('aid',$aid)->update(['status' =>$status,'like_time'=>date('Y-m-d H:i:s',time())]);
            $id = ($userLikeActor['id']??0);
        }
        else
        {
            $userLikeActorInfo = new  UserLikeActor();
            $userLikeActorInfo->uid = $uid;
            $userLikeActorInfo->aid = $aid;
            $userLikeActorInfo->status = $status;
            $userLikeActorInfo->like_time = date('Y-m-d H:i:s',time());
            $userLikeActorInfo->save();
            $id = $userLikeActorInfo->id;
        }

        $likeNum = UserLikeActor::where('aid',$aid)->where('status',1)->count();
        MovieActor::where('id',$aid)->update(['like_sum' =>$likeNum]);

        //todo 清楚演员缓存后面补充

        RedisCache::clearCacheManageAllKey('userLikeActor',$uid);//清楚指定用户浏览的缓存
        return $id;
    }

    /**
     * 获取用户收藏演员列表
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

        $reData = RedisCache::getCacheData('userLikeActor','like:actor:list:',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $likeList = UserLikeActor::where('uid',$uid)
                ->where('status',1)
                ->orderBy('like_time','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get()
                ->pluck('aid')
                ->toArray();
            $reData['sum'] = UserLikeActor::where('uid',$uid)
                ->where('status',1)->count();
            $likeListTemp = [];
            foreach ($likeList as $val)
            {
                $likeListTemp[] = $val;
            }

            $likeList = $likeListTemp;

            if(is_array($likeList) || count($likeList) > 0)
            {
                $dataList = MovieActor::whereIn('id',$likeList)->get();
                if(!$dataList)
                {
                    return $reData;
                }
                $tempData = [];
                foreach ($dataList as $val)
                {
                    $tempData[$val['id']??0] = MovieActor::formatList($val);//格式化视频数据
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