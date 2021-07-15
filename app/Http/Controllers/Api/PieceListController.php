<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 9:39
 */

namespace App\Http\Controllers\Api;

use App\Models\MoviePieceList;
use App\Models\UserLikeUser;
use App\Models\UserPieceList;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use App\Services\Logic\User\Notes\NotesLogic;
use App\Services\Logic\User\Notes\PieceListLogic;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class PieceListController extends BaseController
{

    /**
     * 获取片单详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Request $request)
    {
        $data = $request->all();
        $pid = $data['pid']??0;
        $data['uid'] = $request->userData['uid']??0;
        if($pid <= 0)
        {
            return $this->sendError('无效的片单！');
        }

        $pieceListObj = new PieceListLogic();
        $reData = $pieceListObj->getInfo($pid);
        if(($pieceListObj->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($pieceListObj->getErrorInfo()->msg??'未知错误!',$pieceListObj->getErrorInfo()->code??500);
        }

        $reData['is_like'] = 0;
        if($data['uid']>0 &&
            UserPieceList::where(['uid'=>$data['uid'],'plid'=>$pid,'status'=>1])->exists()){
            $reData['is_like'] = 1;
        }

        MoviePieceList::where('id',$pid)->increment('pv_browse_sum');//记录浏览次数
        return $this->sendJson($reData);
    }

    /**
     * 获取片单影片列表
     * @param Request $request
     */
    public function getMovieList(Request $request)
    {
        $data = $request->all();
        $pid = $data['pid']??0;
        if($pid <= 0)
        {
            return $this->sendError('无效的片单信息！');
        }
        $pieceListObj = new PieceListLogic();
        $reData = $pieceListObj->getMovieList($data);

        if(($pieceListObj->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($pieceListObj->getErrorInfo()->msg??'未知错误!',$pieceListObj->getErrorInfo()->code??500);
        }

        return $this->sendJson($reData);

    }

    /**
     * 获取用户主页信息
     * @param Request $request
     */
    public function getHomeUser(Request $request)
    {
        $template = ['user_id'=>0];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $uid = $request->userData['uid']??0;
        $user_id = $data['user_id']??0;
        $userInfoObj = new UserInfoLogic();
        $userInfo = $userInfoObj->getUserInfo($user_id);
        $user_id = ($userInfo['id']??0);
        if($user_id<= 0)
        {
            return $this->sendError('无效的用户！');
        }

        $reData = [];
        $reTempData = RedisCache::getCacheData('userLikeUser','userinfo:first:attention',function () use ($user_id,$uid)
        {
            $likeInfo = UserLikeUser::where('uid',$uid) ->where('status',1) ->where('goal_uid',$user_id)->first();
            return ($likeInfo['id']??0 <= 0)?null:1;
        },['uid'=>$uid,'goal_uid'=>$user_id],true,$uid);

        $reData = UserInfoLogic::userDisData($userInfo);
        $reData['user_id'] = $user_id;
        $reData['user_attention'] = $reTempData== 1 ?1:2;

        return $this->sendJson($reData);

    }


    /**
     * 获取用户主页信息 用户动作信息-近期浏览
     * @param Request $request
     */
    public function getHomeUserAction(Request $request)
    {
        $data = $request->all();
        $type = $data['action_type']??0;
        $user_id = $data['user_id']??0;
        if($type <= 0)
        {
            return $this->sendError('无效的动作类型！');
        }

        $data['uid'] = $user_id;
        $userAction = new NotesLogic();
        $reData = $userAction->getNotesList($data,$type);
        if(($userAction->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($userAction->getErrorInfo()->msg??'未知错误!',$userAction->getErrorInfo()->code??500);
        }

        return $this->sendJson($reData);
    }


    /**
     * 获取片单列表
     * @param Request $request
     */
    public function getPieceList(Request $request)
    {
        $template = ['type'=>1];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }

        $template['page'] = 1;
        $template['pageSize'] = 10;
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $pieceListObj = new PieceListLogic();
        $reData = $pieceListObj->getList($data);

        if(($pieceListObj->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($pieceListObj->getErrorInfo()->msg??'未知错误!',$pieceListObj->getErrorInfo()->code??500);
        }

        return $this->sendJson($reData);

    }


    /**
     * 添加或者给片单删除一个影片
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMovie(Request $request)
    {
        $template = ['mid'=>0,'pid'=>0,'status'=>1];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }

        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $data['uid'] = $request->userData['uid']??0;

        $pieceListObj = new PieceListLogic();
        $reData = $pieceListObj->addMovie($data);

        if(($pieceListObj->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($pieceListObj->getErrorInfo()->msg??'未知错误!',$pieceListObj->getErrorInfo()->code??500);
        }

        return $this->sendJson(['id'=>$reData]);
    }
}
