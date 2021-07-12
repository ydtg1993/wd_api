<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MovieDirector;
use App\Models\UserLikeActor;
use App\Models\UserLikeDirector;
use App\Services\Logic\RedisCache;

class LikeDirector extends NotesBase
{
    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $did = $data['did']??0;
        if($did <= 0)
        {
            $this->errorInfo->setCode(500,'无效的导演数据!');
            return false;
        }
        $status = $data['status']??1;

        $id = 0;
        $userLikeDirector = UserLikeDirector::where('uid',$uid)->where('did',$did)->first();
        if(($userLikeDirector['id']??0)>0)
        {
            UserLikeDirector::where('uid',$uid)->where('did',$did)->update(['status' =>$status,'like_time'=>date('Y-m-d H:i:s',time())]);
            $id = ($userLikeDirector['id']??0);
        }
        else
        {
            $userLikeDirectorInfo = new  UserLikeDirector();
            $userLikeDirectorInfo->uid = $uid;
            $userLikeDirectorInfo->did = $did;
            $userLikeDirectorInfo->status = $status;
            $userLikeDirectorInfo->like_time = date('Y-m-d H:i:s',time());
            $userLikeDirectorInfo->save();
            $id = $userLikeDirectorInfo->id;
        }

        $likeNum = UserLikeDirector::where('did',$did)->where('status',1)->count();
        MovieDirector::where('id',$did)->update(['like_sum' =>$likeNum]);
        //todo 清楚导演缓存后面补充

        RedisCache::clearCacheManageAllKey('userLikeDirector',$uid);//清楚指定用户浏览的缓存
        return $id;
    }

    /**
     * 获取用户收藏导演列表
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

        $reData = RedisCache::getCacheData('userLikeDirector','like:director:list:',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $likeList = UserLikeDirector::where('uid',$uid)
                ->where('status',1)
                ->orderBy('like_time','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get()
                ->pluck('did')
                ->toArray();
            $reData['sum'] = UserLikeDirector::where('uid',$uid)
                ->where('status',1)->count();
            $likeListTemp = [];
            foreach ($likeList as $val)
            {
                $likeListTemp[] = $val;
            }

            $likeList = $likeListTemp;
            if(is_array($likeList) || count($likeList) > 0)
            {
                $dataList = MovieDirector::whereIn('id',$likeList)->get();
                if(!$dataList)
                {
                    return $reData;
                }
                $tempData = [];
                foreach ($dataList as $val)
                {
                    $tempData[$val['id']??0] = MovieDirector::formatList($val);//格式化视频数据
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