<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 9:39
 */

namespace App\Http\Controllers\Api;

use App\Services\Logic\Common;
use App\Services\Logic\User\Notes\NotesLogic;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

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
        $type = $data['action_type']??0;
        $data['uid'] = $request->userData['uid']??0;
        if($type <= 0)
        {
            return $this->sendError('无效的动作类型！');
        }

        $userAction = new NotesLogic();
        $reData = $userAction->addNotes($data,$type);
        if(($userAction->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($userAction->getErrorInfo()->msg??'未知错误!',$userAction->getErrorInfo()->code??500);
        }

        return $this->sendJson($reData);
    }

    /**
     * 获取用户动作列表
     * @param Request $request
     */
    public function getList(Request $request)
    {
        $data = $request->all();
        $type = $data['action_type']??0;
        if($type <= 0)
        {
            return $this->sendError('无效的动作类型！');
        }

        $data['uid'] = $request->userData['uid']??0;
        $userAction = new NotesLogic();
        $reData = $userAction->getNotesList($data,$type);
        if(($userAction->getErrorInfo()->code??500) != 200)
        {
            return $this->sendError($userAction->getErrorInfo()->msg??'未知错误!',$userAction->getErrorInfo()->code??500);
        }

        return $this->sendJson($reData);

    }
}