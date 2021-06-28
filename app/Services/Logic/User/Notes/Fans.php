<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MovieActor;
use App\Models\UserClient;
use App\Models\UserLikeActor;
use App\Models\UserLikeUser;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;

class Fans extends NotesBase
{
    /**
     * 用户关注一个用户
     * @param $data
     * @return bool|int|mixed|null
     */
    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $goal_id = $data['goal_id']??0;
        if($goal_id <= 0)
        {
            $this->errorInfo->setCode(500,'无效的目标用户数据!');
            return false;
        }

        $goalUserInfo = UserClient::find($goal_id);
        if(($goalUserInfo['id']??0)<=0)
        {
            $this->errorInfo->setCode(500,'无效的目标用户数据!');
            return false;
        }

        $status = $data['status']??1;

        $id = 0;
        $userLikeUser = UserLikeUser::where('uid',$uid)->where('goal_uid',$goal_id)->first();
        if(($userLikeUser['id']??0)>0)
        {
            UserLikeUser::where('uid',$uid)->where('goal_uid',$goal_id)->update(['status' =>$status,'like_time'=>date('Y-m-d H:i:s',time())]);
            $id = ($userLikeUser['id']??0);
        }
        else
        {
            $userLikeUserInfo = new  UserLikeUser();
            $userLikeUserInfo->uid = $uid;
            $userLikeUserInfo->goal_uid = $goal_id;
            $userLikeUserInfo->status = $status;
            $userLikeUserInfo->like_time = date('Y-m-d H:i:s',time());
            $userLikeUserInfo->save();
            $id = $userLikeUserInfo->id;
        }

        $num_attention = UserLikeUser::where('uid',$uid)->where('status',1)->count();
        UserClient::where('id',$uid)->update(['attention' =>$num_attention]);

        $num_fans = UserLikeUser::where('goal_id',$goal_id)->where('status',1)->count();
        UserClient::where('id',$goal_id)->update(['fans' =>$num_fans]);

        RedisCache::clearCacheManageAllKey('userLikeUser',$uid);//清楚指定用户关注/粉丝的缓存
        RedisCache::clearCacheManageAllKey('userLikeUser',$goal_id);//清楚指定用户关注/粉丝的缓存
        return $id;
    }

    /**
     * 获取用户关注/粉丝列表
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

        $type = $data['type']??1; // 1是关注列表 2是粉丝列表

        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;

        $reData = [];
        if($type == 1)
        {
            $reData = RedisCache::getCacheData('userLikeUser','like:attention:list:',function () use ($data,$page,$pageSize,$uid)
            {
                $reData = [];
                $likeList = UserLikeUser::where('uid',$uid)
                    ->where('status',1)
                    ->orderBy('like_time','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get()
                    ->pluck('goal_id')
                    ->toArray();

                if(is_array($likeList) || count($likeList) > 0)
                {
                    $dataList = UserClient::whereIn('id',$likeList)->get();
                    if(!$dataList)
                    {
                        return $reData;
                    }
                    $tempData = [];
                    foreach ($dataList as $val)
                    {
                        $tempData[$val['id']??0] = [
                            'nickname'=>$val['nickname']??'',
                            'id'=>$val['id']??'',
                            'avatar'=> (($val['avatar']??'') == '')?'':( Common::getImgDomain().($val['avatar']??'')),
                            'piece_list_num'=>$val['piece_list_num']??'',
                            'seen_num'=>$val['seen_num']??'',
                        ];//格式化用户数据
                    }

                    foreach ($likeList as $val)
                    {
                        $reData[] = ($tempData[$val]??[]);
                    }
                }
                return $reData;
            },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);
        }
        else
        {
            $reData = RedisCache::getCacheData('userLikeUser','like:fans:list:',function () use ($data,$page,$pageSize,$uid)
            {
                $reData = [];
                $likeList = UserLikeUser::where('goal_id',$uid)
                    ->where('status',1)
                    ->orderBy('like_time','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get()
                    ->pluck('uid')
                    ->toArray();

                if(is_array($likeList) || count($likeList) > 0)
                {
                    $dataList = UserClient::whereIn('id',$likeList)->get();
                    if(!$dataList)
                    {
                        return $reData;
                    }
                    $tempData = [];
                    foreach ($dataList as $val)
                    {
                        $tempData[$val['id']??0] = [
                            'nickname'=>$val['nickname']??'',
                            'id'=>$val['id']??'',
                            'avatar'=> (($val['avatar']??'') == '')?'':( Common::getImgDomain().($val['avatar']??'')),
                            'piece_list_num'=>$val['piece_list_num']??'',
                            'seen_num'=>$val['seen_num']??'',
                        ];//格式化用户数据
                    }

                    foreach ($likeList as $val)
                    {
                        $reData[] = ($tempData[$val]??[]);
                    }
                }
                return $reData;
            },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);
        }

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }
}