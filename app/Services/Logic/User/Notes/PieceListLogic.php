<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/24
 * Time: 15:15
 */

namespace App\Services\Logic\User\Notes;


use App\Models\MoviePieceList;
use App\Models\UserClient;
use App\Models\UserPieceList;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;

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

        $type = $data['type']??1;//1 是简易创建  2 是详细创建
        $status = $data['status']??1;//1 是新增 2 是删除 3 是修改

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
                    MoviePieceList::where('id',$plid)->where('uid',$uid)->update(['status' =>2]);//只能删除自己的
                    UserPieceList::where('uid',$uid)->where('plid',$plid)->update(['status' =>2]);//删除用户部分表
                }

            }

        }

        $userPieceListData = UserPieceList::where('plid',$plid)->where('uid',$uid)->first();
        if(($userPieceListData['id']??0)<=0)
        {
            $userPieceListDb = new UserPieceList();
            $userPieceListDb->type = 2;
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
        
        return true;
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

        $type = $data['type']??0;//0全部 1.创建  2.收藏
        $reData = [];
        if($type == 0)
        {
            $reData['likeList'] = $this->getLList($data,$uid,$isCache);
            $reData['createList'] = $this->getCList($data,$uid,$isCache);
            return $reData;
        }
        else if($type == 1)
        {
            $reData['createList'] = $this->getCList($data,$uid,$isCache);
        }
        else if($type == 2)
        {
            $reData['likeList'] = $this->getLList($data,$uid,$isCache);
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
            $reData = [];
            $dataList = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type','<=',2)
                ->orderBy('created_at','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

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
                    $reData[] = $tempVal;
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
            $reData = [];
            $dataList = UserPieceList::where('uid',$uid)
                ->where('status',1)
                ->where('type',3)
                ->orderBy('created_at','desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

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
                    $reData[] = $tempVal;
                }
            }
            return $reData;

        },['uid'=>$uid,'page'=>$page,'pageSize'=>$pageSize],$isCache,$uid);
        return $reData;
    }
}