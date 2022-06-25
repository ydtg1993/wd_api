<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:24
 */

namespace App\Http\Controllers\Api;

use App\Models\ActorPopularityChart;
use App\Models\Movie;
use App\Models\RecommendMovie;
use App\Services\Logic\Common;
use App\Services\Logic\Home\HomeLogic;
use App\Services\Logic\Search\SearchLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HomeController extends BaseController
{
    public function index(Request $request)
    {
        $data = $request->input();
        $data['uid'] = $request->userData['uid']??0;

        $reData=[];
        if(isset($data['home_type']) && $data['home_type']==2)
        {
            $homeObj  = new HomeLogic();
            $reData = $homeObj->getHomeData($data,$data['home_type']??1);
        }else if(isset($data['home_type'])){

            if($data['home_type']==4)
            {
                $data['flux_linkage_time'] = 2;
                $data['day_limit'] = 1;
            }

            $movieDb = new Movie();
            $reData = $movieDb->getMovieListByCache($data,true);
        }

        return $this->sendJson($reData);
    }

    /**
     * 首页热门视频轮播推荐
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function carousel(Request $request)
    {
        $category = $request->input('category');
        $today = date('Y-m-d 00:00:00');

        $cache = "carousel:{$category}";
        $record = Redis::get($cache);
        $recommends = RecommendMovie::where(['recommend_movie.status'=>0,'recommend_movie.category'=>$category,'recommend_movie.ctime'=>$today])
            ->join('movie','movie.id','=','recommend_movie.mid')
            ->orderBy('recommend_movie.hot','DESC')
            ->limit(10)
            ->select('movie.id','recommend_movie.hot', 'movie.name','movie.score','movie.score_people','recommend_movie.photo')
            ->get()->toArray();
        foreach ($recommends as &$recommend){
            $recommend['photo'] = Common::getImgDomain().$recommend['photo'];
        }
        return $this->sendJson($recommends);
    }

    /**
     * 获取影片排行版
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rank(Request $request)
    {
        $data = $request->input();

        $page = $data['page'] ?? 1;
        $pageSize = $data['pageSize'] ?? 10;
        $type = $data['type'] ?? 0;// 0.全部、1.有码、2.无码、3.欧美
        $time = $data['time'] ?? 0;// 0.全部、1.日版、2.周榜、3.月榜
        $reData = ['list' => [], 'sum' => 0, 'cache' => 0];

        $cache = "Rank:movie:rank:{$type}:{$time}";
        $record = Redis::get($cache);
        if ($record) {
            $record = (array)json_decode($record,true);
            $reData['list'] = array_slice($record['list'],($page-1)*$pageSize,$pageSize);
            $reData['sum'] = $record['sum'];
            $reData['cache'] = 1;
        }
        return $this->sendJson($reData);
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

    public function searchLogClear(Request $request)
    {
        $data = $request->input();
        $data['uid'] = $request->userData['uid']??0;
        $searchLogicObj = new SearchLogic();
        $reData = $searchLogicObj->clearSearchLog($data);
        $errorInfo = $searchLogicObj->getErrorInfo();
        return (($errorInfo->code??500) == 200)?
            $this->sendJson($reData):
            $this->sendError(($errorInfo->msg??'未知错误'),($errorInfo->code??500));
    }



}
