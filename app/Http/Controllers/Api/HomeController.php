<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:24
 */

namespace App\Http\Controllers\Api;

use App\Models\ActorPopularityChart;
use App\Models\MovieLog;
use App\Services\Logic\Home\HomeLogic;
use App\Services\Logic\Search\SearchLogic;
use Illuminate\Http\Request;
class HomeController extends BaseController
{


    public function index(Request $request)
    {
        $data = $request->input();
        $data['uid'] = $request->userData['uid']??0;
        $homeObj  = new HomeLogic();
        $reData = $homeObj->getHomeData($data,$data['home_type']??1);
        $errorInfo = $homeObj->getErrorInfo();
        
        return (($errorInfo->code??500) == 200)?
            $this->sendJson($reData):
            $this->sendError(($errorInfo->msg??'未知错误'),($errorInfo->code??500));
    }

    /**
     * 获取影片排行版
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rank(Request $request)
    {
        $data = $request->input();
        return $this->sendJson((MovieLog::getRankingVersion($data)));
    }

    /**
     * 获取演员排行版
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function actorRank(Request $request)
    {
        $data = $request->input();
        return $this->sendJson((ActorPopularityChart::getRank($data)));
    }

    /**
     * 搜索简易
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $data = $request->input();
        $data['uid'] = $request->userData['uid']??0;
        $searchLogicObj = new SearchLogic();
        $reData = $searchLogicObj->getSearch($data);
        $errorInfo = $searchLogicObj->getErrorInfo();
        return (($errorInfo->code??500) == 200)?
        $this->sendJson($reData):
        $this->sendError(($errorInfo->msg??'未知错误'),($errorInfo->code??500));
    }

    /**
     * 搜索历史【登录可用】
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLog(Request $request)
    {
        $data = $request->input();
        $data['uid'] = $request->userData['uid']??0;
        $searchLogicObj = new SearchLogic();
        $reData = $searchLogicObj->getSearchLog($data);
        $errorInfo = $searchLogicObj->getErrorInfo();
        return (($errorInfo->code??500) == 200)?
            $this->sendJson($reData):
            $this->sendError(($errorInfo->msg??'未知错误'),($errorInfo->code??500));
    }

}