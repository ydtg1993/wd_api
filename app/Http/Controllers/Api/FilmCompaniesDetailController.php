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
use App\Models\MovieFilmCompanies;
use App\Models\MovieFilmCompaniesAss;
use App\Models\MovieNumberAss;
use App\Models\MovieSeries;
use App\Models\MovieSeriesAss;
use App\Models\UserLikeFilmCompanies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FilmCompaniesDetailController extends BaseController
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

            $number = MovieFilmCompanies::where('id',$request->input('id'))->first();
            $data = MovieFilmCompanies::formatList($number);
            $data['is_like'] = 0;

            $uid = $request->userData['uid']??0;
            if($uid>0 &&
                UserLikeFilmCompanies::where(['uid'=>$uid,'film_companies_id'=>$request->input('id')])->exists()){
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

            $page = $request->input('page');
            $pageSize = $request->input('pageSize');
            $skip = $pageSize * ($page - 1);

            $movie_ids = MovieFilmCompaniesAss::where('film_companies_id',$request->input('id'))->pluck('mid')->all();

            $movies = MovieFilmCompaniesAss::where(['movie_film_companies_associate.film_companies_id' => $request->input('id'), 'movie.status' => 1])
                ->whereIn('movie.id',$movie_ids)
                ->join('movie', 'movie.id', '=', 'movie_film_companies_associate.mid')
                ->select('movie.*',
                    'movie_film_companies_associate.mid');

            //filter
            switch ($request->input('filter')) {
                case 'subtitle':
                    $movies = $movies->where('movie.is_subtitle', 2);
                    break;
                case 'download':
                    $movies = $movies->where('movie.is_download', 2);
                    break;
                case 'comment':
                    $movies = $movies->where('movie.new_comment_time', '>=', date('Y-m-d 00:00:00'));
                    break;
            }
            //sort
            switch ($request->input('sort')){
                case 'release':
                    $movies = $movies->orderBy('movie.release_time', 'DESC');
                    break;
                case 'linkage':
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
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
        return $this->sendJson($data);
    }


}
