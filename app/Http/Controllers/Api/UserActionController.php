<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 9:39
 */

namespace App\Http\Controllers\Api;

use App\Models\Movie;
use App\Models\MovieComment;
use App\Models\MovieLog;
use App\Models\MovieScoreNotes;
use App\Models\UserLikeUser;
use App\Models\UserSeenMovie;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use App\Services\Logic\User\Notes\NotesLogic;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class UserActionController extends BaseController
{

    /**
     * 添加用户动作
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $data = $request->all();
        $type = $data['action_type'] ?? 0;
        $data['uid'] = $request->userData['uid'] ?? 0;
        if ($type <= 0) {
            return $this->sendError('无效的动作类型！');
        } elseif ($type == 3 && $data['status'] != 2) {
            if (!Common::wangyiVerify()) {
                return $this->sendError('验证码错误');
            }
        }
        $userAction = new NotesLogic();
        $reData = $userAction->addNotes($data, $type);
        if (($userAction->getErrorInfo()->code ?? 500) != 200) {
            return $this->sendError($userAction->getErrorInfo()->msg ?? '未知错误!', $userAction->getErrorInfo()->code ?? 500);
        }

        return $this->sendJson(['data' => $reData]);
    }

    /**
     * 获取用户动作列表
     * @param Request $request
     */
    public function getList(Request $request)
    {
        $data = $request->all();
        $type = $data['action_type'] ?? 0;
        if ($type <= 0) {
            return $this->sendError('无效的动作类型！');
        }

        $data['uid'] = $request->userData['uid'] ?? 0;
        $userAction = new NotesLogic();
        $reData = $userAction->getNotesList($data, $type);
        if (($userAction->getErrorInfo()->code ?? 500) != 200) {
            return $this->sendError($userAction->getErrorInfo()->msg ?? '未知错误!', $userAction->getErrorInfo()->code ?? 500);
        }

        return $this->sendJson($reData);

    }

    /**
     * 获取用户主页信息
     * @param Request $request
     */
    public function getHomeUser(Request $request)
    {
        $template = ['user_id' => 0];
        $data = $request->all();
        if (!$this->haveToParam($template, $data)) {
            return $this->sendJson('', 202);
        }
        $data = $this->paramFilter($template, $data);
        if ($data === false) {
            return $this->sendJson('', 201);
        }

        $uid = $request->userData['uid'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        $userInfoObj = new UserInfoLogic();
        $userInfo = $userInfoObj->getUserInfo($user_id);
        $user_id = ($userInfo['id'] ?? 0);
        if ($user_id <= 0) {
            return $this->sendError('无效的用户！');
        }

        $reData = [];
        $reTempData = RedisCache::getCacheData('userLikeUser', 'userinfo:first:attention', function () use ($user_id, $uid) {
            $likeInfo = UserLikeUser::where('uid', $uid)->where('status', 1)->where('goal_uid', $user_id)->first();
            return (($likeInfo['id'] ?? 0) <= 0) ? null : 1;
        }, ['uid' => $uid, 'goal_uid' => $user_id], true, $uid);

        $reData = UserInfoLogic::userDisData($userInfo);
        $reData['user_id'] = $user_id;
        $reData['user_attention'] = $reTempData == 1 ? 1 : 2;

        return $this->sendJson(['userInfo' => $reData]);

    }


    /**
     * 获取用户主页信息 用户动作信息 获取用户主页信息 用户动作信息-近期浏览
     * @param Request $request
     */
    public function getHomeUserAction(Request $request)
    {
        $data = $request->all();
        $type = $data['action_type'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        if ($type <= 0) {
            return $this->sendError('无效的动作类型！');
        }

        $data['uid'] = $user_id;
        $data['thisUid'] = $request->userData['uid'] ?? 0;
        $userAction = new NotesLogic();
        $reData = $userAction->getNotesList($data, $type);
        if (($userAction->getErrorInfo()->code ?? 500) != 200) {
            return $this->sendError($userAction->getErrorInfo()->msg ?? '未知错误!', $userAction->getErrorInfo()->code ?? 500);
        }

        return $this->sendJson($reData);
    }

    /**
     * 添加影片浏览记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCountMovie(Request $request)
    {
        $data = $request->all();
        $mid = $data['mid'] ?? 0;
        return $this->sendJson(['id' => MovieLog::addMovieBrowse($mid)]);
    }

    /**
     * 添加演员浏览记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCountActor(Request $request)
    {
        $data = $request->all();
        $aid = $data['aid'] ?? 0;
        return $this->sendJson(['id' => MovieLog::addMovieBrowse($aid)]);
    }

    /**
     * 评分
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function giveScore(Request $request)
    {
        $uid = $request->userData['uid'] ?? 0;
        $mid = $request->input('mid');
        $score = $request->input('score');
        if ($uid <= 0) {
            return $this->sendError('无效的动作类型！');
        }
        if($score < 1 || $score > 10){
            return $this->sendError('无效的动作类型！');
        }
        try {
            DB::beginTransaction();
            $scoreNoteRecord = MovieScoreNotes::where(['mid'=>$mid,'uid'=>$uid,'status'=>1])->first();
            if($scoreNoteRecord){
                MovieScoreNotes::where('id',$scoreNoteRecord->id)->update([
                    'score' => $score
                ]);
            }else {
                MovieScoreNotes::insert([
                    'mid' => $mid,
                    'score' => $score,
                    'uid' => $uid
                ]);
            }
            $scoreNotes = MovieScoreNotes::where('mid', $mid)->pluck('score')->all();
            $score_people = count($scoreNotes);
            $total = array_sum($scoreNotes);
            $score = (int)ceil($total / $score_people);
            Movie::where('id', $mid)->update(['score' => $score, 'score_people' => $score_people]);
            MovieComment::where('mid',$mid)->where('uid',$uid)
                ->where('status',1)
                ->where('cid',0)
                ->update(['score'=>$score]);
            $seenMovieRecord =UserSeenMovie::where(['mid'=>$mid,'uid'=>$uid,'status'=>1])->first();
            if($seenMovieRecord){
                UserSeenMovie::where('id',$seenMovieRecord->id)->update(['score'=>$score]);
            }else{
                UserSeenMovie::insert(['mid'=>$mid,'uid'=>$uid,'score'=>$score]);
            }
        }catch (\Exception $e){
            DB::rollBack();
            return $this->sendError('数据处理异常');
        }
        DB::commit();
        return $this->sendJson(['score'=>$score]);
    }
}
