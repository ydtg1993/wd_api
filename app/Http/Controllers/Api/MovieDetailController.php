<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Http\Requests\ComplaintRequest;
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
            $actors = MovieActor::whereIn('id', $actors)->select('name', 'id')->get()->all();
            foreach ($actors as &$actor){
                $actor['is_like'] = 0;
                if($uid>0 &&
                    UserLikeActor::where(['uid'=>$uid,'aid'=>$actor['id'],'status'=>1])->exists()){
                    $actor['is_like'] = 1;
                }
            }

            $map = [];
            foreach ((array)json_decode($movie->map) as $img) {
                if (!$img) {
                    continue;
                }
                $map[] = Common::getImgDomain() . $img;
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
            $map = [];
            foreach ((array)json_decode($movie->map, true) as $m) {
                $map[] = $m == '' ? '' : Common::getImgDomain() . $m;
            }
            $data['map'] = $map;
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

            $page = $request->input('page');
            $pageSize = $request->input('pageSize');
            $skip = $pageSize * ($page - 1);

            $model = MovieComment::where([
                'movie_comment.mid' => $request->input('id'),
                'movie_comment.status' => 1])
                ->where(['movie_comment.type' => 1, 'movie_comment.status' => 1])
                ->orderBy('movie_comment.type')
                ->orderBy('id', 'DESC')
                ->leftJoin('user_client', 'user_client.id', '=', 'movie_comment.uid')
                ->with('replys')
                ->select('movie_comment.*',
                    'user_client.nickname as user_client_nickname',
                    'user_client.avatar as user_client_avatar');

            $sum = $model->count();
            $comments = $model->skip($skip)
                ->take($pageSize)
                ->get();

            $data = [];
            foreach ($comments as $comment) {
                $struct = MovieComment::struct($comment);
                foreach ($comment->replys as $reply) {
                    $struct['reply_comments'][] = MovieComment::struct($reply);
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

            $res = MovieComment::add(
                $uid,
                $request->input('id'),
                $request->input('comment'),
                $request->input('comment_id'));

            if ($res == false) {
                return $this->sendError('回复失败');
            }
            Mv::where('id', $request->input('id'))->update([
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

        $movie = Mv::with('actors')->select('id')->first();
        $actors = [];
        foreach ($movie->actors as $a) {
            $actors[] = $a->aid;
        }
        $movie_ids = MovieActorAss::whereIn('aid', $actors)->pluck('mid')->all();
        shuffle($movie_ids);
        $movie_ids = array_slice($movie_ids, 0, min(count($movie_ids), 15));

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
        $movie_ids = array_slice($movie_ids, 0, min(count($movie_ids), 2));

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
