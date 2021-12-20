<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/15
 * Time: 11:18
 */

namespace App\Services\Logic\User\Notes;


use App\Models\Movie;
use App\Models\MovieCategoryAssociate;
use App\Models\UserBrowseMovie;
use App\Services\Logic\Movie\MovieLogic;
use App\Services\Logic\RedisCache;

class RecentlyViewedLogic extends NotesBase
{

    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $mid = $data['mid']??0;
        if($mid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的影片数据!');
            return false;
        }

        /*如果列表不允许出现重复*/
        $userBrowseInfo = UserBrowseMovie::where('uid',$uid)->where('status',1)->where('mid',$mid)->first();
        if(($userBrowseInfo['id']??0)>0)
        {
            UserBrowseMovie::where('uid',$uid)->where('mid',$mid)->update(['status' =>3]);
        }

       $userBrowse = new  UserBrowseMovie();
       $userBrowse->uid = $uid;
       $userBrowse->mid = $mid;
       $userBrowse->save();
       RedisCache::clearCacheManageAllKey('userActionRecVie',$uid);//清楚指定用户浏览的缓存
       return $userBrowse->id;
    }

    /**
     * 获取用户浏览记录
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
        $type = $data['type']??0;// 0 全部

        $reData = RedisCache::getCacheData('userActionRecVie','recently:viewed:list:',function () use ($data,$page,$pageSize,$uid,$type)
        {
            $reData = ['list'=>[],'sum'=>0];
            if($type <= 0)
            {
                $browseList = UserBrowseMovie::where('uid',$uid)->where('status',1)->orderBy('browse_time','desc')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->pluck('mid')->toArray();
                $reData['sum'] = UserBrowseMovie::where('uid',$uid)->where('status',1)->count();
                $browseListTemp = [];
                foreach ($browseList as $val)
                {
                    $browseListTemp[] = $val;
                }

                $browseList = $browseListTemp;
                if(is_array($browseList) || count($browseList) > 0)
                {
                    $MovieList = Movie::whereIn('id',$browseList)->get();
                    $tempMovie = [];

                    foreach ($MovieList as $val)
                    {
                        $tempMovie[$val['id']??0] = MovieLogic::formatList($val);//格式化视频数据
                    }
                    foreach ($browseList as $val)
                    {
                        $reData['list'][] = $tempMovie[$val]??[];
                    }
                }
                return $reData;
            }
            else
            {
                $userBrowseMovieDb = UserBrowseMovie::where('user_browse_movie.uid',$uid)
                    ->where('user_browse_movie.status',1);

                $movieCategoryAssociateDb = MovieCategoryAssociate::where('movie_category_associate.status',1);
                $userBrowseMovieDb = $userBrowseMovieDb->leftJoinSub($movieCategoryAssociateDb,'movie_category_associate',function ($join)
                {
                    $join->on('user_browse_movie.mid', '=', 'movie_category_associate.mid');
                });

                $userBrowseMovieDb = $userBrowseMovieDb ->where('movie_category_associate.cid',$type);

                $reData['sum'] = $userBrowseMovieDb->count();
                $userBrowseMovieDb = $userBrowseMovieDb->orderBy('user_browse_movie.browse_time','desc');
                $userDataInfo = $userBrowseMovieDb->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get()
                    ->pluck('mid')
                    ->toArray();
                $browseListTemp = [];
                foreach ($userDataInfo as $val)
                {
                    $browseListTemp[] = $val;
                }

                $userDataInfo = $browseListTemp;
                if(is_array($userDataInfo) || count($userDataInfo) > 0)
                {
                    $MovieList = Movie::whereIn('id',$userDataInfo)->get();
                    $tempMovie = [];
                    foreach ($MovieList as $val)
                    {
                        $tempMovie[$val['id']??0] = MovieLogic::formatList($val);//格式化视频数据
                    }

                    foreach ($userDataInfo as $val)
                    {
                        $reData['list'][] = $tempMovie[$val]??[];
                    }
                }
                return $reData;
            }

            return $reData;
        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize,'type'=>$type],$isCache,$uid);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }


}