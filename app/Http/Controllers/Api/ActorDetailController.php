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
use App\Models\UserLikeActor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ActorDetailController extends BaseController
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
                'id' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }

            $actor = MovieActor::where('id', $request->input('id'))->with('names')->first();
            $names = [];
            foreach ($actor->names as $name) {
                $names[$name->id] = $name->name;
            }

            $data = MovieActor::formatList($actor);
            $data['names'] = $names;
            $data['is_like'] = 0;
            $sum = MovieActorAss::where('aid',$data['id']??0 )->where('status', 1)->count();
            if($sum != ($data['movie_sum']??0))
            {
                MovieActor::where('id',$data['id']??0 )->update(['movie_sum'=>$sum]);
                $data['movie_sum'] = $sum;
            }

            $uid = $request->userData['uid']??0;
            if($uid>0 &&
                UserLikeActor::where(['uid'=>$uid,'aid'=>$request->input('id'),'status'=>1])->exists()){
                $data['is_like'] = 1;
            }

            return $this->sendJson($data);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
    }

    public function products(Request $request)
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

            $id = $request->input('id');
            $page = $request->input('page');
            $pageSize = $request->input('pageSize');
            $skip = $pageSize * ($page - 1);
            $filter = $request->input('filter');
            $sort = $request->input('sort');

            $cache = "actor_detail_products:{$id}:{$page}:{$filter}:{$sort}";
            $records = Redis::get($cache);
            if($records){
                $data = json_decode($records,true);
                return $this->sendJson($data);
            }
            $movies = MovieActorAss::where(['movie_actor_associate.aid' => $id, 'movie.status' => 1])
                ->join('movie', 'movie.id', '=', 'movie_actor_associate.mid')
                ->select('movie.*',
                    'movie_actor_associate.mid');

            //filter
            switch ($filter) {
                case 1:
                    $movies = $movies->where('movie.is_subtitle', 2);
                    break;
                case 2:
                    $movies = $movies->where('movie.is_download', 2);
                    break;
                case 3:
                    $movies = $movies->where('movie.new_comment_time', '>=', date('Y-m-d 00:00:00'));
                    break;
            }
            //sort
            switch ($sort){
                case 1:
                    $movies = $movies->orderBy('movie.release_time', 'DESC');
                    break;
                case 2:
                    $movies = $movies->orderBy('movie.flux_linkage_time', 'DESC');
                    break;
                default:
                    $movies = $movies->orderBy('movie.release_time', 'DESC');
                    break;
            }

            $sum = $movies->count();

            $movies = $movies->skip($skip)
                ->take($pageSize)
                ->get();
            $data = [
                'page'=>$page,
                'pageSize'=>$pageSize,
                'sum'=>$sum,
                'list'=>[]
            ];
            foreach ($movies as $movie) {
                $data['list'][] = Movie::formatList($movie);
            }
            Redis::setex($cache,3600,json_encode($data));
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
        return $this->sendJson($data);
    }

}
