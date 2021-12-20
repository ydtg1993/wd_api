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
use App\Models\UserWantSeeMovie;
use App\Services\Logic\Movie\MovieLogic;
use App\Services\Logic\RedisCache;

class WantSeeLogic extends NotesBase
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
        $status = $data['status']??1;

        $id = 0;
        $userWantSeeInfo = UserWantSeeMovie::where('uid',$uid)->where('status',1)->where('mid',$mid)->first();
        if(($userWantSeeInfo['id']??0)>0)
        {
            UserWantSeeMovie::where('uid',$uid)->where('mid',$mid)->update(['status' =>$status]);
            $id = ($userWantSeeInfo['id']??0);
        }
        else
       {
           $userWantSee = new  UserWantSeeMovie();
           $userWantSee->uid = $uid;
           $userWantSee->mid = $mid;
           $userWantSee->save();
           $id = $userWantSee->id;
        }


       RedisCache::clearCacheManageAllKey('userWantSee',$uid);//清楚指定用户浏览的缓存
       return $id;
    }

    /**
     * 获取用户想看记录
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

        $type = $data['type']??0;
        $sort = $data['sort']??0;// 0 默认排序 1加入时间排序 2。发布日期排序
        $sortType = $data['sortType']??'desc';// 排方式序 asc

        if($sort != 0)
        {
            $sortType = in_array($sortType,['desc','asc'])?$sortType:'desc';
        }

        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;

        $reData = RedisCache::getCacheData('userWantSee','want:see:list:',function () use ($data,$page,$pageSize,$uid,$type,$sort,$sortType)
        {
            $reData = ['list'=>[],'sum'=>0];
            $userWantSeeDb = UserWantSeeMovie::where('user_want_see_movie.uid',$uid)
                ->where('user_want_see_movie.status',1)
                ->leftJoin('movie', 'movie.id', '=', 'user_want_see_movie.mid');
            /*->leftJoinSub($movieDbObj,'movie',function ($join)
            {
                $join->on('user_want_see_movie.mid', '=', 'movie.id');
            });*/

            if($type > 0)
            {
                $movieCategoryAssociateDb = MovieCategoryAssociate::where('movie_category_associate.status',1);
                $userWantSeeDb = $userWantSeeDb->leftJoinSub($movieCategoryAssociateDb,'movie_category_associate',function ($join)
                {
                    $join->on('user_want_see_movie.mid', '=', 'movie_category_associate.mid');
                });

                $userWantSeeDb = $userWantSeeDb ->where('movie_category_associate.cid',$type);
            }

            $reData['sum'] = $userWantSeeDb->count();
            if($sort == 2)
            {
                $userWantSeeDb = $userWantSeeDb->orderBy('movie.release_time',$sortType);
            }
            else if($sort == 1)
            {
                $userWantSeeDb = $userWantSeeDb->orderBy('user_want_see_movie.mark_time',$sortType);
            }

            $userDataInfo = $userWantSeeDb->offset(($page - 1) * $pageSize)
                ->limit($pageSize)->get()->pluck('mid')->toArray();

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
                    $reData['list'][] = ($tempMovie[$val]??[]);
                }
            }

            return $reData;
        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize,'type'=>$type,'sort'=>$sort,'sortType'=>$sortType],$isCache,$uid);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }
}