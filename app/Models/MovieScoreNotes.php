<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovieScoreNotes extends Model
{
    protected $table = 'movie_score_notes';

    /**
     * 添加评分
     * @param $data
     */
    public static function add($mid,$uid,$score)
    {
        //删除之前的评分
        MovieScoreNotes::where('mid',$mid)->where('uid',$uid)->update(['status'=>2]);

        //重写一条评分
        $dataObj = new MovieScoreNotes();
        $dataObj->mid = $mid;
        $dataObj->uid = $uid;
        $dataObj->score = $score;
        $dataObj->status = 1;
        $dataObj->save();

        //计算平均分
        $mdb = new MovieScoreNotes();
        $mdb->avg($mid);

        return $dataObj->id;
    }

    /**
     * 新增评分
     */
    public function addNew($mid,$uid,$score)
    {
        //写一条评分
        $dataObj = new MovieScoreNotes();
        $dataObj->mid = $mid;
        $dataObj->uid = $uid;
        $dataObj->score = $score;
        $dataObj->status = 1;
        $dataObj->save();

        //计算平均分
        $mdb = new MovieScoreNotes();
        $mdb->avg($mid);

        return $dataObj->id;
    }

    /**
     * 读取数据
     */
    public function info($uid=0, $mid=0)
    {
        $query = DB::table($this->table);
        $info = $query->where('mid',$mid)->where('uid',$uid)->where('status',1)->first();
        return $info;
    }

    /**
     * 修改评分
     */
    public function edit($mid,$uid,$score)
    {
        MovieScoreNotes::where('mid',$mid)->where('uid',$uid)->where('status',1)->update(['score'=>$score]);
        $this->avg($mid);
    }

    /**
     * 删除积分
     */
    public function rm($mid,$uid)
    {
         MovieScoreNotes::where('mid',$mid)->where('uid',$uid)->where('status',1)->update(['status'=>2]);
        $this->avg($mid);
        Movie::where('id',$mid)->decrement('score_people');
    }

    /**
     * 计算平均分
     */
    public function avg($mid = 0)
    {
        //读取影片频评分信息
        $movieInfo = Movie::where('id',$mid)->first();
        if(($movieInfo['id']??0)>0)
        {
            $collection_score = $movieInfo->collection_score;
            $collection_score_people = $movieInfo->collection_score_people;
            $score = $movieInfo->score;
            $score_people = $movieInfo->score_people;

            $real_people = MovieScoreNotes::where('mid',$mid)->where('source_type',1)->where('status',1)->count();
            $real_score = MovieScoreNotes::where('mid',$mid)->where('source_type',1)->where('status',1)->sum('score');

            $total_score = ($collection_score * $collection_score_people) + ($score * $score_people) + $real_score;
            $total_people = $collection_score_people + $score_people + $real_people;
            //计算平均分
            if(($collection_score_people + $score_people + $real_people) > 0)
            {
                $score = $total_score / $total_people;
            }
            else
            {
                $score = 5;
            }
            Movie::where('id',$mid)->update(['score'=>$score,'score_people'=>$total_people]);
        }

        RedisCache::clearCacheManageAllKey('movie');
    }
}
