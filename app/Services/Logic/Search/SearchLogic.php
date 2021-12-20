<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/7/8
 * Time: 16:19
 */

namespace App\Services\Logic\Search;


use App\Models\Movie;
use App\Models\UserSearchLog;
use App\Services\Logic\BaseLogic;
use App\Services\Logic\RedisCache;

class SearchLogic extends BaseLogic
{

    /**
     * @param array $data
     * @return array|bool
     */
    public function getSearch($data = [])
    {
        $uid = $data['uid']??0;
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $search = $data['search']??'';
        if($search == '')
        {
            $this->errorInfo->setCode(500,'必须存在搜索内容！');
            return [];
        }

        if(strlen($search) >= 150)
        {
            $this->errorInfo->setCode(500,'搜索的内容太长了！');
            return [];
        }

        $reData = RedisCache::getCacheData('movie','movie:search:list:',function () use ($data,$page,$pageSize,$search)
        {
            $reData = ['list'=>[],'sum'=>0];
            $movieSearch = Movie::where('status',1);

            (($data['is_subtitle']??1) == 1)?null:($movieSearch = $movieSearch->where('is_subtitle',2));
            (($data['is_download']??1) == 1)?null:($movieSearch = $movieSearch->where('is_download',2));
            (($data['is_short_comment']??1) == 1)?null:($movieSearch = $movieSearch->where('is_short_comment',2));

            $movieSearch = $movieSearch->where(function($query) use ($search)
            {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('number', 'like', '%' . $search . '%');
            });

            $reData['sum'] = $movieSearch->count();
            $movieSearch = $movieSearch
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            foreach ($movieSearch as $val)
            {
                $reData['list'][] = Movie::formatList($val);
            }

            return $reData;
        },['page'=>$page,'pageSize'=>$pageSize,'search'=>md5(json_encode($data))],true);

        //给用户添加搜索记录
        if($uid > 0)
        {
            $userSearch = UserSearchLog::where('content',$search)->where('uid',$uid)->first();
            if(($userSearch['id']??0) <= 0)
            {
                $userSearchObj = new UserSearchLog();
                $userSearchObj->uid = $uid;
                $userSearchObj->content = $search;
                $userSearchObj->status = 1;
                $userSearchObj->save();
            }
            UserSearchLog::where('content',$search)->where('uid',$uid)->update(['created_at'=>date('Y-m-d H:i:s',time()),'status'=>1]);
        }

        return $reData;
    }

    /**
     * 获取用户搜索记录
     * @param int $uid
     */
    public function getSearchLog($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            return [];
        }
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        return RedisCache::getCacheData('userinfo','userinfo:search:log:',function () use ($uid,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            $movieSearch = UserSearchLog::where('uid',$uid)
                ->where('status',1)
                ->select('content')
                ->distinct()
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            foreach ($movieSearch as $val)
            {
                $reData['list'][] = $val;
            }

            return $reData;
        },['page'=>$page,'pageSize'=>$pageSize,'uid'=>$uid],true,$uid);

    }

    /**
     * 清楚搜索记录
     * @param $data
     * @return array
     */
    public function clearSearchLog($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            return [];
        }
        UserSearchLog::where('uid',$uid)->update(['status'=>2]);
        return [];
    }

}