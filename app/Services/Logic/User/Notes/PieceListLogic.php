<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/24
 * Time: 15:15
 */

namespace App\Services\Logic\User\Notes;


use App\Models\Movie;
use App\Models\MoviePieceList;
use App\Models\PieceListMovie;
use App\Models\UserClient;
use App\Models\UserPieceList;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use App\Services\Logic\User\UserInfoLogic;

class PieceListLogic extends  NotesBase
{

    public function addNotes($data)
    {
        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的用户信息!');
            return false;
        }

        $this->createDefPieceList($uid);

        $type = $data['type']??0;//1 是简易创建  2 是详细创建
        $status = $data['status']??1;//1 是新增 2 是删除 3 是修改 4是收藏

        $name = $data['name']??'';
        if($name=='')
        {
            $this->errorInfo->setCode(500,'片单名称不能为空！');
            return false;
        }

        $plid = $data['plid']??1;
        if($type == 1)
        {
            if($status != 1)
            {
                $this->errorInfo->setCode(500,'简易创建只能创建！');
                return false;
            }

            $moviePieceListDb = new MoviePieceList();
            $moviePieceListDb->name = $name;
            $moviePieceListDb->uid = $uid;
            $moviePieceListDb->status = 1;
            $moviePieceListDb->authority = 1;
            $moviePieceListDb->type = 1;
            $moviePieceListDb->save();
            $plid = $moviePieceListDb->id;
        }
        else
        {
            if($status == 1)
            {
                $moviePieceListDb = new MoviePieceList();
                $moviePieceListDb->name = $name;
                $moviePieceListDb->uid = $uid;
                $moviePieceListDb->status = 1;
                $moviePieceListDb->authority = $data['authority']??1;
                $moviePieceListDb->type = 1;
                $moviePieceListDb->cover = $data['cover']??'';
                $moviePieceListDb->intro = $data['intro']??'';
                $moviePieceListDb->save();
                $plid = $moviePieceListDb->id;
            }
            else
            {
                if($plid <= 0)
                {
                    $this->errorInfo->setCode(500,'无效的片单ID！');
                    return false;
                }
                if($status == 3)
                {
                    $moviePieceObj = MoviePieceList::find($plid);
                    if(($moviePieceObj->type??1) == 3)
                    {
                        $this->errorInfo->setCode(500,'默认片单不能更新！');
                        return false;
                    }

                    MoviePieceList::where('id',$plid)->where('uid',$uid)->update([
                        'status' =>1,
                        'name' =>$name,
                        'cover' =>$data['cover']??'',
                        'intro' =>$data['intro']??'',
                        'authority' =>$data['authority']??1,
                    ]);
                }
                else if($status == 2)
                {
                    $moviePieceObj = MoviePieceList::find($plid);
                    if(($moviePieceObj->type??1) == 3)
                    {
                        $this->errorInfo->setCode(500,'默认片单不能删除！');
                        return false;
                    }

                    MoviePieceList::where('id',$plid)->where('uid',$uid)->where('type','<>',3)->update(['status' =>2]);//只能删除自己的 [不能删除默认的]
                    UserPieceList::where('uid',$uid)->where('plid',$plid)->where('type','<>',1)->update(['status' =>2]);//删除用户部分表 [不能删除默认的]
                }
                else if($status == 4)
                {
                    $moviePieceObj = MoviePieceList::find($plid);
                    if(($moviePieceObj->authority??2) == 2)
                    {
                        $this->errorInfo->setCode(500,'私有片单不能收藏！');
                        return false;
                    }
                }
            }
        }

        $userPieceListData = UserPieceList::where('plid',$plid)->where('uid',$uid)->first();
        if(($userPieceListData['id']??0)<=0)
        {
            $userPieceListDb = new UserPieceList();
            $userPieceListDb->type = $status==4?3:2;
            $userPieceListDb->status = 1;
            $userPieceListDb->plid = $plid;
            $userPieceListDb->uid = $uid;
            $userPieceListDb->save();
        }
        else
        {
            UserPieceList::where('plid',$plid)->where('uid',$uid)->update([
                'status' =>1
            ]);
        }

        //刷新用户片单数据
        RedisCache::clearCacheManageAllKey('userPieceList',$uid);//清楚指定用户浏览的缓存
        
        return true;
    }

    /**
     * 获取用户收藏片单列表
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

        $this->createDefPieceList($uid);

        $type = $data['type']??0;//0全部 1.创建  2.收藏
        $reData = [];
        if($type == 0)
        {
            $likeList = $this->getLList($data,$uid,$isCache);
            $reData['likeList'] = $likeList['list']??[];
            $reData['likeListSum'] = $likeList['sum']??0;
            $createList = $this->getCList($data,$uid,$isCache);
            $reData['createList'] = $createList['list']??[];
            $reData['createListsum'] = $createList['sum']??0;
            return $reData;
        }
        else if($type == 1)
        {
            $createList = $this->getCList($data,$uid,$isCache);
            $reData['createList'] = $createList['list']??[];
            $reData['createListsum'] = $createList['sum']??0;
        }
        else if($type == 2)
        {
            $likeList = $this->getLList($data,$uid,$isCache);
            $reData['likeList'] = $likeList['list']??[];
            $reData['likeListSum'] = $likeList['sum']??0;
        }

        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }

    /**
     * 获取用户创建的片单
     * @param $data
     * @param $uid
     * @param bool $isCache
     * @return bool
     */
    public function getCList($data,$uid,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $reData = RedisCache::getCacheData('userPieceList','piece:user:list:c',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $dataList = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type','<=',2)
                ->orderBy('type','asc')
                ->orderBy('created_at','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            $reData['sum'] = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type','<=',2)->count();

            $dataIDs = $dataList
                ->pluck('plid')
                ->toArray();

            if(is_array($dataIDs) || count($dataIDs) > 0)
            {
                $dataPieceList = MoviePieceList::whereIn('id',$dataIDs)->get();
                if(!$dataPieceList)
                {
                    return $reData;
                }
                $tempData = [];
                foreach ($dataPieceList as $val)
                {
                    $tempData[$val['id']??0] = MoviePieceList::formatList($val);//格式化片单数据
                }

                foreach ($dataList as $val)
                {
                    $tempVal = ($tempData[$val['plid']]??[]);
                    $tempVal['a_id'] = $val['id']??0;//关联ID
                    $reData['list'][] = $tempVal;
                }
            }
            return $reData;
        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);
        return (is_array($reData) || count($reData) >0 )? $reData:[];
    }

    /**
     * 获取用户收藏的片单
     * @param $data
     * @param $uid
     * @param bool $isCache
     * @return bool
     */
    public function getLList($data,$uid,$isCache = true)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $reData = RedisCache::getCacheData('userPieceList','piece:user:list:l',function () use ($data,$page,$pageSize,$uid)
        {
            $reData = ['list'=>[],'sum'=>0];
            $dataList = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type',3)
                ->orderBy('created_at','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            $reData['sum'] = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type',3)->count();

            $dataIDs = $dataList
                ->pluck('plid')
                ->toArray();

            if(is_array($dataIDs) || count($dataIDs) > 0)
            {
                $dataPieceList = MoviePieceList::whereIn('id',$dataIDs)->get();
                if(!$dataPieceList)
                {
                    return $reData;
                }

                $uids =  $dataPieceList->pluck('uid')
                ->toArray();

                $tempUidData = [];
                if(count($dataIDs) > 0)
                {
                    $dataUserClient = UserClient::whereIn('id',$uids)->get();
                    foreach ($dataUserClient as $val)
                    {
                        $avatar = $val['avatar']??'';
                        $tempUidData[$val['id']??0] = [
                            'avatar'=>($avatar==''?(''):(Common::getImgDomain().$avatar)),
                            'nickname' => $val['nickname']??'',
                        ];
                    }
                }

                $tempData = [];
                foreach ($dataPieceList as $val)
                {
                    $tempVal = MoviePieceList::formatList($val);//格式化片单数据
                    $tempVal['avatar'] = ($tempUidData[$val['uid']??0]??array())['avatar']??'';
                    $tempVal['nickname']= ($tempUidData[$val['uid']??0]??array())['nickname']??'';
                    $tempData[$val['id']??0] = $tempVal;
                }

                foreach ($dataList as $val)
                {
                    $tempVal = ($tempData[$val['plid']]??[]);
                    $tempVal['a_id'] = $val['id']??0;//关联ID
                    $reData['list'][] = $tempVal;
                }
            }
            return $reData;

        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);
        return $reData;
    }


    /**
     * 创建默认片单
     * @param $uid
     */
    public function createDefPieceList($uid)
    {
        $MoviePieceListData = MoviePieceList::where('type',3)->where('uid',$uid)->first();
        if(($MoviePieceListData['id']??0) <=0 )
        {
            $moviePieceListDb = new MoviePieceList();
            $moviePieceListDb->name = '系统默认片单';
            $moviePieceListDb->uid = $uid;
            $moviePieceListDb->status = 1;
            $moviePieceListDb->authority = $data['authority']??1;
            $moviePieceListDb->type = 3;
            $moviePieceListDb->cover = $data['cover']??'';
            $moviePieceListDb->intro = '系统默认片单';
            $moviePieceListDb->save();
            $plid = $moviePieceListDb->id;

            $userPieceListData = UserPieceList::where('type',3)->where('uid',$uid)->first();
            if(($userPieceListData['id']??0)<=0)
            {
                $userPieceListDb = new UserPieceList();
                $userPieceListDb->type = 1;
                $userPieceListDb->status = 1;
                $userPieceListDb->plid = $plid;
                $userPieceListDb->uid = $uid;
                $userPieceListDb->save();
            }
        }

    }


    /**
     * 获取片单信息
     * @param $pid
     */
    public function getInfo($pid,$isCache = true)
    {
        $reData = RedisCache::getCacheData('userPieceList','piece:user:first',function () use ($pid)
        {
            $pieceList = MoviePieceList::where('id',$pid) ->where('status',1)
                ->first();
            if (($pieceList['id']??0) <= 0)
            {
                return [];
            }
            $reData = MoviePieceList::formatList($pieceList);
            if(($pieceList['type']??0) == 2)
            {
                $reData['uid'] = 0;
            }
            return $reData;

        },['pid'=>$pid],$isCache);

        if(($reData['id'])<=0)
        {
            $this->errorInfo->setCode(500,'片单不存在或者已经被删除！!');
            return [];
        }

        if(($pieceList['type']??0) == 2)
        {
            $reData['userInfo'] = [];
        }
        else
        {
            $userInfoObj = new UserInfoLogic();
            $userInfo = $userInfoObj->getUserInfo($reData['uid']??0);
            $reData['userInfo'] = UserInfoLogic::userDisData($userInfo);
        }

        return $reData;
    }

    /**
     * 获取片单影片列表
     * @param $pid
     * @param int $page
     * @param int $pageSize
     * @param bool $isCache
     */
    public function getMovieList($data,$isCache = true)
    {
        $pid = $data['pid']??0;
        if($pid <= 0)
        {
            $this->errorInfo->setCode(500,'无效的片单信息！');
            return [];
        }
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $reData = RedisCache::getCacheData('userPieceList','piece:user:movie:list:',function () use ($pid,$data,$page,$pageSize)
        {
            $reData = ['list'=>[],'sum'=>0];
            $dataList = PieceListMovie::where('plid',$pid)
                ->where('status',1)
                ->orderBy('created_at','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            $reData['sum'] = PieceListMovie::where('plid',$pid)
                ->where('status',1)->count();
            $dataIDs = $dataList
                ->pluck('mid')
                ->toArray();

            if(is_array($dataIDs) || count($dataIDs) > 0)
            {
                $dataMovieList = Movie::whereIn('id',$dataIDs)->get();
                if(!$dataMovieList)
                {
                    return $reData;
                }

                $tempData = [];
                foreach ($dataMovieList as $val)
                {
                    $tempData[$val['id']??0] = Movie::formatList($val);//格式化影片数据
                }

                foreach ($dataList as $val)
                {
                    $tempVal = ($tempData[$val['mid']]??[]);
                    $tempVal['a_id'] = $val['id']??0;//关联ID
                    $reData['list'][] = $tempVal;
                }
            }
            return $reData;

        },['plid'=>$pid,'page'=>$page,'pageSize'=>$pageSize],$isCache);

        return $reData;
    }


    /**
     * 获取片单列表
     * @param $data
     * @param bool $isCache
     * @return array
     */
    public function getList($data,$isCache = true)
    {
        $type = $data['type']??1;//1.全部 2.热门
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;
        $reData = RedisCache::getCacheData('userPieceList','piece:list:',function () use ($data,$page,$pageSize,$type)
        {
            $reData = ['list'=>[],'sum'=>0];
            $pieceListDb = null;
            if($type == 2)
            {
                $pieceListDb = MoviePieceList::where('type','<>',3)
                    ->where('authority',1)
                    ->where('status',1);
                $reData['sum'] = $pieceListDb->count();
                $pieceListDb = $pieceListDb->orderBy('pv_browse_sum','desc')
                    ->orderBy('like_sum','desc')
                    ->orderBy('created_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize);

            }
            else
            {
                $pieceListDb = MoviePieceList::where('type','<>',3)
                    ->where('authority',1)
                    ->where('status',1);
                $reData['sum'] = $pieceListDb->count();
                $pieceListDb = $pieceListDb->orderBy('created_at','desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize);
            }

            $pieceList = $pieceListDb->get();
            $uids =  $pieceList->pluck('uid')
                ->toArray();
            $tempUidData = [];
            if(is_array($uids) || count($uids) > 0)
            {
                $dataUserClient = UserClient::whereIn('id',$uids)->get();
                foreach ($dataUserClient as $val)
                {
                    $avatar = $val['avatar']??'';
                    $tempUidData[$val['id']??0] = [
                        'avatar'=>($avatar==''?(''):(Common::getImgDomain().$avatar)),
                        'nickname' => $val['nickname']??'',
                    ];
                }
            }

            foreach ($pieceList as $val)
            {
                $tempVal = MoviePieceList::formatList($val);//格式化片单数据
                $tempVal['avatar'] = ($tempUidData[$val['uid']??0]??array())['avatar']??'';
                $tempVal['nickname']= ($tempUidData[$val['uid']??0]??array())['nickname']??'';
                $reData['list'][] = $tempVal;
            }

            return $reData;

        },['type'=>$type,'page'=>$page,'pageSize'=>$pageSize],$isCache);

        return $reData;
    }

    /**
     * 片单添加影片
     * @param $data
     */
    public function addMovie($data)
    {
        $mid = $data['mid']??0;
        $pid = $data['pid']??0;//片单ID
        $uid = $data['uid']??0;//片单ID
        $status = $data['status']??1;//1 添加 2 是删除

        if($mid <= 0 )
        {
            $this->errorInfo->setCode(500,'无效的影片！');
            return [];
        }

        if($uid <= 0 )
        {
            $this->errorInfo->setCode(500,'无效的用户！');
            return [];
        }

        if($pid <= 0 )
        {
            $this->errorInfo->setCode(500,'无效的片单！');
            return [];
        }

        $moviePieceListInfo = MoviePieceList::where('id',$pid)->where('uid',$uid)->first();
        if(($moviePieceListInfo['id']??0)<=0)
        {
            $this->errorInfo->setCode(500,'只有创建该片单的用户才能添加影片！');
            return [];
        }

        $pieceListInfo = PieceListMovie::where('plid',$pid)->where('mid',$mid)->first();
        if(($pieceListInfo['id']??0)<=0)
        {
            $pieceListObj= new PieceListMovie();
            $pieceListObj->plid = $pid;
            $pieceListObj->mid = $mid;
            $pieceListObj->status = $status;
            $pieceListObj->save();
        }

        PieceListMovie::where('plid',$pid)->where('mid',$mid)->update([
            'status' =>$status,
        ]);
        RedisCache::clearCacheManageAllKey('userPieceList');//清楚浏览的缓存
        RedisCache::clearCacheManageAllKey('userPieceList',$uid);//清楚浏览的缓存

        return $pid;
    }
}