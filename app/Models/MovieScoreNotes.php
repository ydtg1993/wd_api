<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/21
 * Time: 9:45
 */

namespace App\Models;

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
        $dataObj = new MovieScoreNotes();
        $dataObj->mid = $mid;
        $dataObj->uid = $uid;
        $dataObj->score = $score;
        $dataObj->status = 1;
        $dataObj->save();

        //todo 重新计算影片评分 还需要补充

        //todo  清除影片的评论缓存 还需要补充
        return $dataObj->id;
    }
}