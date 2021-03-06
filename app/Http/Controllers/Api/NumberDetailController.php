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
use App\Models\MovieNumber;
use App\Models\MovieNumberAss;
use App\Models\UserLikeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NumberDetailController extends BaseController
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

            $number = MovieNumber::where('id',$request->input('id'))->first();
            $data = MovieNumber::formatList($number);
            $data['is_like'] = 0;

            $uid = $request->userData['uid']??0;
            if($uid>0 &&
                UserLikeNumber::where(['uid'=>$uid,'nid'=>$request->input('id'),'status'=>1])->exists()){
                $data['is_like'] = 1;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
        return $this->sendJson($data);
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

            $cache = "number_detail_products:{$id}:{$page}:{$filter}:{$sort}";
            $records = Redis::get($cache);
            if($records){
                $data = json_decode($records,true);
                return $this->sendJson($data);
            }
            $movies = MovieNumberAss::where(['movie_number_associate.nid' => $id, 'movie.status' => 1])
                ->join('movie', 'movie.id', '=', 'movie_number_associate.mid')
                ->select('movie.*',
                    'movie_number_associate.mid');

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
