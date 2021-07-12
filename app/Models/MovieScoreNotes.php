<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/21
 * Time: 9:45
 */

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
class MovieScoreNotes extends Model
{
    protected $table = 'movie_score_notes';

    /**
     * 添加评分
     * @param $data
     */
    public static function add($mid,$uid,$score)
    {
        MovieScoreNotes::where('mid',$mid)->where('uid',$uid)->update(['status'=>2]);

        $dataObj = new MovieScoreNotes();
        $dataObj->mid = $mid;
        $dataObj->uid = $uid;
        $dataObj->score = $score;
        $dataObj->status = 1;
        $dataObj->save();

        //读取影片频评分信息
        $movieInfo = Movie::where('id',$mid)->first();
        if(($movieInfo['id']??0)>0)
        {
            $collection_score = $movieInfo['collection_score']??0;
            $collection_score_people = $movieInfo['collection_score_people']??0;

            $people = MovieScoreNotes::where('mid',$mid)->where('status',1)->count();
            $score = MovieScoreNotes::where('mid',$mid)->where('status',1)->sum('score');

            if(($collection_score_people + $people) > 0)
            {
                $score = ($collection_score + $score)/($collection_score_people + $people);
            }
            else
            {
                $score = 5;
            }
            Movie::where('id',$mid)->update(['score'=>$score]);
        }

        RedisCache::clearCacheManageAllKey('movie');
        return $dataObj->id;
    }
}