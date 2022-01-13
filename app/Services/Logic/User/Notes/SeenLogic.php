<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/21
 * Time: 15:37
 */

namespace App\Services\Logic\User\Notes;


use App\Models\Movie;
use App\Models\MovieCategoryAssociate;
use App\Models\MovieComment;
use App\Models\MovieScoreNotes;
use App\Models\UserClient;
use App\Models\UserSeenMovie;
use App\Services\Logic\RedisCache;
use App\User;
use Illuminate\Support\Facades\Response;

class SeenLogic  extends NotesBase
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
        $status = $data['status']??1;//1添加想看 2.取消

        $score = $data['score']??0;
        if($status == 1)
        {
            if($score <= 0 || $score > 10)
            {
                $this->errorInfo->setCode(500,'无效的评分数据!');
                return false;
            }

            $comment = $data['comment']??'';
            if(strlen($comment) <= 0 || strlen($comment) >= 500)
            {
                $this->errorInfo->setCode(500,'评论数据长度不合法！');
                return false;
            }

            //添加评分
            MovieScoreNotes::add($mid,$uid,$score);

            //添加评论
            MovieComment::add($uid,$mid,$comment,$score);
        }else{
            MovieScoreNotes::where(['mid'=>$mid,'uid'=>$uid])->update(['status'=>2]);
            //删除积分
            $mScore = new MovieScoreNotes();
            $mScore->rm($mid,$uid);
            //删除评论
            MovieComment::rm($uid,$mid);
        }

        $userSeenInfo = UserSeenMovie::where('uid',$uid)->where('status',1)->where('mid',$mid)->first();
        if(($userSeenInfo['id']??0)>0)
        {
            UserSeenMovie::where('uid',$uid)->where('mid',$mid)->update(['status' =>$status]);
            $id = ($userSeenInfo['id']??0);
        }
        else
        {
            $userSeenInfo = new  UserSeenMovie();
            $userSeenInfo->uid = $uid;
            $userSeenInfo->mid = $mid;
            $userSeenInfo->score = $score;
            $userSeenInfo->status = $status;
            $userSeenInfo->save();
            $id = $userSeenInfo->id;
        }

        //更新用户看过数量
        $num_Seen = UserSeenMovie::where('uid',$uid)->where('status',1)->count();
        UserClient::where('id',$uid)->update(['seen_num' =>$num_Seen]);

        RedisCache::clearCacheManageAllKey('userSeen',$uid);//清楚指定用户浏览的缓存
        return $id;
    }

    /**
     * 获取用户看过记录
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
        $score = $data['score']??0;//评分范围 0全部  【1 1-2】， 【2 2-3】 ,  【3 3-4】, 【4 4-5 】, 【5 5】
        $sort = $data['sort']??0;// 0 默认排序 1加入时间排序 2。发布日期排序
        $sortType = $data['sortType']??'desc';// 排方式序 asc

        if($sort != 0)
        {
            $sortType = in_array($sortType,['desc','asc'])?$sortType:'desc';
        }

        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;

        $reData = RedisCache::getCacheData('userSeen','seen:list:',function () use ($data,$page,$pageSize,$uid,$type,$sort,$sortType,$score)
        {
            $reData = ['list'=>[],'sum'=>0];
            $userSeenDb = UserSeenMovie::where('user_seen_movie.uid',$uid)
                ->where('user_seen_movie.status',1)
                ->leftJoin('movie', 'movie.id', '=', 'user_seen_movie.mid');
            /*->leftJoinSub($movieDbObj,'movie',function ($join)
            {
                $join->on('user_seen_movie.mid', '=', 'movie.id');
            });*/

            if($type > 0)
            {
                $movieCategoryAssociateDb = MovieCategoryAssociate::where('movie_category_associate.status',1);
                $userSeenDb = $userSeenDb->leftJoinSub($movieCategoryAssociateDb,'movie_category_associate',function ($join)
                {
                    $join->on('user_seen_movie.mid', '=', 'movie_category_associate.mid');
                });

                $userSeenDb = $userSeenDb ->where('movie_category_associate.cid',$type);
            }

            if($score >0 && $score <= 5 )
            {
                $scoreArr = [1=>['min'=>0,'max'=>4],
                    2=>['min'=>4,'max'=>6],
                    3=>['min'=>6,'max'=>8],
                    4=>['min'=>8,'max'=>10]
                ];

                $userSeenDb = $userSeenDb ->where('movie.score','>=',$scoreArr[$score]['min']??0);
                $userSeenDb = $userSeenDb ->where('movie.score','<=',$scoreArr[$score]['max']??0);
            }

            $reData['sum'] = $userSeenDb->count();
            if($sort == 2)
            {
                $userSeenDb = $userSeenDb->orderBy('movie.release_time',$sortType);
            }
            else if($sort == 1)
            {
                $userSeenDb = $userSeenDb->orderBy('user_seen_movie.mark_time',$sortType);
            }

            $userDataInfo = $userSeenDb->offset(($page - 1) * $pageSize)
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
                    $tempMovie[$val['id']??0] = Movie::formatList($val);//格式化视频数据
                }

                foreach ($userDataInfo as $val)
                {
                    $reData['list'][] = ($tempMovie[$val]??[]);
                }
            }

            return $reData;
        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize,'type'=>$type,'sort'=>$sort,'sortType'=>$sortType,'score'=>$score],$isCache,$uid);

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }
}
