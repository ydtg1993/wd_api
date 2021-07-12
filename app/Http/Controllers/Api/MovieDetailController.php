<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Http\Requests\ComplaintRequest;
use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieActorAss;
use App\Models\MovieComment;
use App\Models\MovieDirector;
use App\Models\MovieFilmCompanies;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use App\Models\MovieSeries;
use App\Models\UserSeenMovie;
use App\Models\UserWantSeeMovie;
use App\Services\Logic\Common;
use App\Services\Logic\Movie\CommentActionLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $movie = Movie::where('movie.id', $request->input('id'))
                ->leftJoin('movie_director_associate', 'movie.id', '=', 'movie_director_associate.mid')
                ->leftJoin('movie_film_companies_associate', 'movie.id', '=', 'movie_film_companies_associate.mid')
                ->leftJoin('movie_series_associate', 'movie.id', '=', 'movie_series_associate.mid')
                ->with('labels')
                ->with('actors')
                ->select('movie.*',
                    'movie_director_associate.did as director_id',
                    'movie_film_companies_associate.film_companies_id as ',
                    'movie_series_associate.series_id as series_id')
                ->first();
            if ($movie->status !== 1) {
                return $this->sendError('已经下架');
            }

            $director = MovieDirector::where('id', $movie->director_id)->pluck('name', 'id')->all();
            $company = MovieFilmCompanies::where('id', $movie->film_companies_id)->pluck('name', 'id')->all();
            $series = MovieSeries::where('id', $movie->series_id)->pluck('name', 'id')->all();

            /*标签*/
            $labels = [];
            foreach ($movie->labels as $l) {
                $labels[] = $l->cid;
            }
            $labels = MovieLabel::whereIn('id', $labels)->pluck('name', 'id')->all();
            /*演员*/
            $actors = [];
            foreach ($movie->actors as $a){
                $actors[] = $a->aid;
            }
            $actors = MovieActor::whereIn('id',$actors)->pluck('name','id')->all();

            $data = [
                "id" => $movie->id,
                "number" => $movie->number,
                "name" => $movie->name,
                "time" => $movie->time,
                "release_time" => $movie->release_time,
                "small_cover" => $movie->small_cover == ''?'':(Common::getImgDomain().$movie->small_cover),
                "big_cove" => $movie->big_cove == ''?'':(Common::getImgDomain().$movie->big_cove),
                "trailer" => $movie->trailer == ''?'':(Common::getImgDomain().$movie->trailer),
                "score" => $movie->score,
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
                'seen' => 0,
                'want_see' => 0
            ];

            $uid = $request->userData['uid']??0;
            if($uid>0){
                if(UserSeenMovie::where(['uid'=>$uid,'mid'=>$request->input('id'),'status'=>1])->exists()) {
                    $data['seen'] = 1;
                }
                if(UserWantSeeMovie::where(['uid'=>$uid,'mid'=>$request->input('id'),'status'=>1])->exists()){
                    $data['want_see'] = 1;
                }
            }
            $map = [];
            foreach ((array)json_decode($movie->map, true) as $m){
                $map[] = $m == '' ? '':Common::getImgDomain().$m;
            }
            $data['map'] = $map;
            return $this->sendJson($data);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
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
                'pageSize'=> 'required|int',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }

            $page = $request->input('page');
            $pageSize = $request->input('pageSize');
            $skip = $pageSize * ($page - 1);

            $model = MovieComment::where([
                'movie_comment.mid'=>$request->input('id'),
                'movie_comment.status'=>1])
                ->orderBy('movie_comment.type')
                ->orderBy('id','DESC')
                ->leftJoin('user_client','user_client.id','=','movie_comment.uid')
                ->select('movie_comment.*',
                    'user_client.nickname as user_client_nickname',
                    'user_client.avatar as user_client_avatar');

            $sum = $model->count();
            $comments = $model->skip($skip)
                ->take($pageSize)
                ->get();

            $data = [];
            foreach ($comments as $comment){
                $struct = [
                    'id'=>$comment->id,
                    'comment'=>$comment->comment,
                    'nickname'=>$comment->nickname,
                    'like'=>$comment->like,
                    'dislike'=>$comment->dislike,
                    'avatar'=>$comment->avatar,
                    'type'=>$comment->type,
                    'reply_uid'=>$comment->reply_uid,
                    'comment_time'=>$comment->comment_time,
                    'reply_comments'=>[]
                ];
                if($comment->source_type == 1){
                    $struct['nickname'] = $comment->user_client_nickname;
                    $struct['avatar'] = $comment->user_client_avatar;
                }
                if($comment->type == 1){
                    $data[$comment->id] = $struct;
                    continue;
                }
                if(isset($data[$comment->cid])){
                    $data[$comment->cid]['reply_comments'][] = $struct;
                }
            }

            return $this->sendJson([
                'page'=>$page,
                'pageSize'=>$pageSize,
                'sum'=>$sum,
                'list'=>$data
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

            $uid = $request->userData['uid']??0;
            if($uid < 0){
                throw new \Exception('无效用户token');
            }

            $res = MovieComment::add(
                $uid,
                $request->input('id'),
                $request->input('comment'),
                $request->input('comment_id'));

            if ($res == false) {
                return $this->sendError('回复失败');
            }
            Movie::where('id', $request->input('id'))->update([
                'new_comment_time' => date('Y-m-d H:i:s'),
                'comment_num' => DB::raw('comment_num+1')]);

            return $this->sendJson([]);
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

        $movie = Movie::with('actors')->select('id')->first();
        $actors = [];
        foreach ($movie->actors as $a){
            $actors[] = $a->aid;
        }
        $movie_ids = MovieActorAss::whereIn('aid',$actors)->pluck('mid')->all();
        shuffle($movie_ids);
        $movie_ids = array_slice($movie_ids,0,min(count($movie_ids),15));

        $movies = Movie::whereIn('id',$movie_ids)->get();
        $data = [];
        foreach ($movies as $movie){
            $data[] = Movie::formatList($movie);
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

        $movie = Movie::with('labels')->select('id')->first();
        $labels = [];
        foreach ($movie->labels as $l) {
            $labels[] = $l->cid;
        }
        $movie_ids = MovieLabelAss::whereIn('cid',$labels)->pluck('mid')->all();
        shuffle($movie_ids);
        $movie_ids = array_slice($movie_ids,0,min(count($movie_ids),2));

        $movies = Movie::whereIn('id',$movie_ids)->get();
        $data = [];
        foreach ($movies->toArray() as $movie){
            $data[] = Movie::formatList($movie);
        }
        return $this->sendJson($data);
    }

    /**
     * 赞踩
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request){
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
            if(empty($comment) || !$comment->exists){
                throw  new \Exception('非法参数');
            }
            $action = [
                'target_id'=>$comment['id'],
                'id'=>$comment['id'],
                'owner_id'=>$comment['uid'],
                'action'=>$validator->validated()['action'],
            ];
            CommentActionLogic::userAction(array_merge($request->userData,$action));
            return $this->sendJson([]);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

}
