<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Http\Requests\ComplaintRequest;
use App\Models\MovieScoreNotes;
use App\Models\UserClient;
use App\Models\UserBlack;
use App\Models\MovieActor;
use App\Models\MovieActorAss;
use App\Models\MovieComment;
use App\Models\MovieDirector;
use App\Models\MovieFilmCompanies;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use App\Models\MovieNumber;
use App\Models\MovieSeries;
use App\Models\Mv;
use App\Models\UserLikeActor;
use App\Models\UserLikeDirector;
use App\Models\UserLikeFilmCompanies;
use App\Models\UserLikeSeries;
use App\Models\UserSeenMovie;
use App\Models\UserWantSeeMovie;
use App\Services\Logic\User\UserInfoLogic;
use App\Services\Logic\Common;
use App\Services\Logic\Movie\CommentActionLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Filter;

class MovieDetailController extends BaseController
{

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $uid = $request->userData['uid']??0;
            $movie = Mv::where('movie.id', $request->input('id'))
                ->leftJoin('movie_director_associate', 'movie.id', '=', 'movie_director_associate.mid')
                ->leftJoin('movie_film_companies_associate', 'movie.id', '=', 'movie_film_companies_associate.mid')
                ->leftJoin('movie_series_associate', 'movie.id', '=', 'movie_series_associate.mid')
                ->leftjoin('movie_number_associate', 'movie.id', '=', 'movie_number_associate.mid')
                ->with('labels')
                ->with('actors')
                ->select('movie.*',
                    'movie_director_associate.did as director_id',
                    'movie_film_companies_associate.film_companies_id as film_companies_id',
                    'movie_series_associate.series_id as series_id',
                    'movie_number_associate.nid as number_id')
                ->first();
            if ($movie->status !== 1) {
                return $this->sendError('已经下架');
            }

            $director = MovieDirector::where('id', $movie->director_id)->select('name', 'id')->get()->all();
            $this->is_like(UserLikeDirector::query(),$director,$uid,'did');
            $company = MovieFilmCompanies::where('id', $movie->film_companies_id)->where('status',1)->select('name', 'id')->get()->all();
            $this->is_like(UserLikeFilmCompanies::query(),$company,$uid,'film_companies_id');
            $series = MovieSeries::where('id', $movie->series_id)->where('status',1)->select('name', 'id')->get()->all();
            $this->is_like(UserLikeSeries::query(),$series,$uid,'series_id');
            $numbers = MovieNumber::where('id', $movie->number_id)->where('status',1)->select('name', 'id')->get();

            /*标签*/
            $labels = [];
            foreach ($movie->labels as $l) {
                $labels[] = $l->cid;
            }
            $labels = MovieLabel::whereIn('id', $labels)->select('name', 'id')->get();
            /*演员*/
            $actors = [];
            foreach ($movie->actors as $a) {
                $actors[] = $a->aid;
            }
            $actors = MovieActor::whereIn('id', $actors)->select('name', 'id','sex')->orderBy('sex','asc')->get()->all();
            foreach ($actors as &$actor){
                $actor['is_like'] = 0;
                if($uid>0 &&
                    UserLikeActor::where(['uid'=>$uid,'aid'=>$actor['id'],'status'=>1])->exists()){
                    $actor['is_like'] = 1;
                }
                $actor['sex'] = $actor['sex']?$actor['sex']:'♀';
                $actor['name'] = $actor['name'].$actor['sex'];
            }

            $map = [];
            foreach (json_decode($movie->map,true) as $img) {
                if (!$img) {
                    continue;
                }
                $imgTemp = $img['img']??'';
                $imgTempBig = $img['big_img']??'';
                if($imgTemp=='')
                {
                    continue;
                }
                $imgTemp =  Common::getImgDomain().$imgTemp;
                $imgTempBig = ($imgTempBig=='')?$imgTemp:(Common::getImgDomain().$imgTempBig);
                $map[] = ['img'=>$imgTemp,'big_img'=> $imgTempBig];
            }

            $scoreNote = MovieScoreNotes::where(['mid'=>$movie->id,'uid'=>$uid])->first();
            $score = $movie->score;
            if($scoreNote){
                $score = $scoreNote->score;
            }

            $data = [
                "id" => $movie->id,
                "number" => $movie->number,
                "name" => $movie->name,
                "time" => $movie->time,
                "release_time" => $movie->release_time,
                "small_cover" => $movie->small_cover == '' ? '' : (Common::getImgDomain() . $movie->small_cover),
                "big_cove" => $movie->big_cove == '' ? '' : (Common::getImgDomain() . $movie->big_cove),
                "trailer" => $movie->trailer == '' ? '' : (Common::getImgDomain() . $movie->trailer),
                "map" => $map,
                "score" => $score,
                "score_people" => $movie->score_people,
                "comment_num" => $movie->comment_num,
                "flux_linkage_num" => $movie->flux_linkage_num,
                "flux_linkage" => (array)json_decode($movie->flux_linkage, true),
                "flux_linkage_time" => $movie->flux_linkage_time,
                "created_at" => $movie->created_at,
                'labels' => $labels,
                'actors' => $actors,
                'director' => $director,
                'company' => $company,
                'series' => $series,
                'numbers' => $numbers,
                'seen' => 0,
                'want_see' => 0
            ];

            if ($uid > 0) {
                if (UserSeenMovie::where(['uid' => $uid, 'mid' => $request->input('id'), 'status' => 1])->exists()) {
                    $data['seen'] = 1;
                }
                if (UserWantSeeMovie::where(['uid' => $uid, 'mid' => $request->input('id'), 'status' => 1])->exists()) {
                    $data['want_see'] = 1;
                }
            }
            /*$map = [];
            foreach ((array)json_decode($movie->map, true) as $m) {
                $map[] = $m == '' ? '' : Common::getImgDomain() . $m;
            }
            $data['map'] = $map;*/
            return $this->sendJson($data);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

    private function is_like($model,&$data,$uid,$column)
    {
        foreach ($data as &$d){
            $d['is_like'] = 0;
            if($model->where(['uid'=>$uid,$column=>$d['id'],'status'=>1])->exists()) {
                $d['is_like'] = 1;
            }
        }
    }

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function comment(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'id' => 'required|numeric',
                'page' => 'required|int',
                'pageSize' => 'required|int',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }

            $page = intval($request->input('page'));
            $pageSize = intval($request->input('pageSize'));
            $offset = $pageSize * ($page - 1);

            //得到参数
            $mid = intval($request->input('id'));

            //读取评论列表
            $MC = new MovieComment();
            $sum = $MC->getListCount($mid, 1, []);

            $comments = $MC->getLists($mid, 1, [], $offset, $pageSize, 'id desc', 'id,mid,uid,comment,score,type,source_type,nickname,avatar,reply_uid,`like`,dislike,comment_time');

            //遍历数据，获取需要子查询的数据
            $uids = [];   //需要查询的用户id
            $cids = [];   //需要查询的回复评论id
            foreach($comments as $v){
                $uids[] = $v->uid;
                $cids[] = $v->id;
            }

            //读取回复的评论数据(系统只支持第一层，不支持无限递归)
            $replyComments = Common::objectToArray($MC->getLists(0, 2, $cids, 0, 200, 'id asc', 'id,cid,uid,comment,score,type,source_type,nickname,avatar,reply_uid,`like`,dislike,comment_time'));
            $replyArr = [];    //回复的列表
            foreach($replyComments as $v){
                $uids[] = $v['uid'];
                $replyArr[$v['cid']][] = $v;
            }

            //读取用户
            $MU = new UserClient();
            $resUser = Common::objectToArray($MU->getListByids(array_unique($uids), 'id,nickname,avatar'));
            $arrUser = [];    //用户数据
            foreach($resUser as $v){
                $arrUser[$v['id']] = $v;
            }

            //拼接最终数据
            $data = [];
            foreach($comments as $v){

                $nickname = isset($arrUser[$v->uid])?$arrUser[$v->uid]['nickname']:'';
                $v->user_client_nickname = $nickname;   //昵称
                $avatar = isset($arrUser[$v->uid])?$arrUser[$v->uid]['avatar']:'';
                $v->user_client_avatar = $avatar;    //头像

                //处理数据
                $struct = MovieComment::struct($v);
                $struct['is_like'] = CommentActionLogic::getUniqueAction('like',$v->id,$request->userData['uid']);
                $struct['is_dislike'] = CommentActionLogic::getUniqueAction('dislike',$v->id,$request->userData['uid']);

                //读取回复
                if(isset($replyArr[$v->id])){
                    foreach ($replyArr[$v->id] as $val) {
                        $reply = (object)$val;
                        $nickname = isset($arrUser[$reply->uid])?$arrUser[$reply->uid]['nickname']:'';
                        $reply->user_client_nickname = $nickname;   //昵称
                        $avatar = isset($arrUser[$reply->uid])?$arrUser[$reply->uid]['avatar']:'';
                        $reply->user_client_avatar = $avatar;    //头像

                        $action = [];
                        $action['is_like'] = CommentActionLogic::getUniqueAction('like',$reply->id,$request->userData['uid']);
                        $action['is_dislike'] = CommentActionLogic::getUniqueAction('dislike',$reply->id,$request->userData['uid']);
                        $struct['reply_comments'][] = array_merge(MovieComment::struct($reply),$action);
                    }
                }

                $data[] = $struct;
            }

            return $this->sendJson([
                'page' => $page,
                'pageSize' => $pageSize,
                'sum' => $sum,
                'list' => $data
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

    public function reply(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'id' => 'required|numeric',
                'comment' => 'required|string|min:6|max:255',
                'comment_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }

            $uid = $request->userData['uid'] ?? 0;
            if ($uid < 0) {
                throw new \Exception('无效用户token');
            }

            $userInfoObj = new UserInfoLogic();
            $userInfo = $userInfoObj->getUserInfo($uid,false);
            if ($userInfo['status']>1){
                $ext = '禁言';
                $days = UserBlack::getBlackDay($uid,2);
                $msg = "您的账户已被".$ext.$days."天，请在解禁后评论";

                if($days>999)
                {
                    $msg = "您的账户已被永久".$ext;
                }
                return $this->sendError($msg);
            }

            //过滤词判断
            $warning = '';
            if(Filter::check($request->input('comment'))==true)
            {
                $warning = '你发送的内容有敏感信息，官方审核后方可显示';
            }

            //回复过滤，正在审核中的数据，不能被回复
            if($request->input('comment_id'))
            {
                $cInfo = MovieComment::infoById($request->input('comment_id'));
                if(isset($cInfo->audit) && $cInfo->audit!=1){
                    $msg = "您回复的评论正在审核中，不能进行回复";
                    return $this->sendError($msg);
                }
            }


            $res = MovieComment::add(
                $uid,
                $request->input('id'),
                $request->input('comment'),
                0,
                $request->input('comment_id'));

            if ($res == false) {
                return $this->sendError('回复失败');
            }
            Mv::where('id', $request->input('id'))->update([
                'new_comment_time' => date('Y-m-d H:i:s'),
                'comment_num' => DB::raw('comment_num+1')]);

            return $this->sendJson([],200,'执行完成',$warning);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

    public function show(Request $request)
    {
        $validator = Validator()->make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
        }

        //查出所有演员
        $actors = MovieActorAss::where('mid',$request->input('id')??0)->where('status',1)->get()->pluck('aid')->toArray();
        if(!($actors && count($actors) >0 ))
        {
            return $this->sendJson([]);//没有数据
        }

        $num = $request->input('num')??15;
        $num = ($num <= 2)?2:$num;
        $num = ($num >= 30)?30:$num;

        $movie_ids = MovieActorAss::whereIn('aid', $actors)->where('mid','<>',$request->input('id')??0)->where('status',1)->pluck('mid')->all();//查询其他影片 并排除自己
        shuffle($movie_ids);
        $movie_ids = array_slice($movie_ids, 0, min(count($movie_ids), $num));//最多取15个
        $movies = Mv::whereIn('id', $movie_ids)->get();
        $data = [];
        foreach ($movies as $movie) {
            $data[] = Mv::formatList($movie);
        }
        return $this->sendJson($data);
    }

    public function guess(Request $request)
    {
        $validator = Validator()->make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
        }

        $movie = Mv::with('labels')->select('id')->first();
        $labels = [];
        foreach ($movie->labels as $l) {
            $labels[] = $l->cid;
        }
        $movie_ids = MovieLabelAss::whereIn('cid', $labels)->pluck('mid')->all();
        shuffle($movie_ids);
        $movie_ids = array_slice($movie_ids, 0, min(count($movie_ids), 10));

        $movies = Mv::whereIn('id', $movie_ids)->get();
        $data = [];
        foreach ($movies->toArray() as $movie) {
            $data[] = Mv::formatList($movie);
        }
        return $this->sendJson($data);
    }

    /**
     * 赞踩
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'id' => 'required|int',
                'action' => 'required|string|in:like,dislike',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $id = $validator->validated()['id'];
            $comment = MovieComment::find($id);
            if (empty($comment) || !$comment->exists) {
                throw  new \Exception('非法参数');
            }
            $action = [
                'target_id' => $comment['id'],
                'id' => $comment['id'],
                'owner_id' => $comment['uid'],
                'action' => $validator->validated()['action'],
            ];
            CommentActionLogic::userAction(array_merge($request->userData, $action));
            return $this->sendJson([]);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

}
