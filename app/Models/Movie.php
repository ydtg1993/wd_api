<?php

namespace App\Models;

use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table = 'movie';

    const pagesize = 10;//默认页数

    /**
     * 格式化影片列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $is_new_comment_day = ((strtotime($data['new_comment_time']??'') - strtotime(date('Y-m-d 00:00:00'))) >= 0)?1:2 ;//最新评论时间减去 今日开始时间 如果大于0 则今日新评论
        $is_new_comment_day = ($is_new_comment_day == 2)?(
        (((strtotime($data['new_comment_time']??'') - (strtotime(date('Y-m-d 00:00:00')) -(60*60*24) )) >= 0)?3:2)
        ):1;

        $is_flux_linkage_day = ((strtotime($data['flux_linkage_time']??'') - strtotime(date('Y-m-d 00:00:00'))) >= 0)?1:2;
        $is_flux_linkage_day = ($is_flux_linkage_day == 2)?(
        (((strtotime($data['flux_linkage_time']??'') - (strtotime(date('Y-m-d 00:00:00')) -(60*60*24) )) >= 0)?3:2)
        ):1;

        $small_cover = $data['small_cover']??'';
        $big_cove = $data['big_cove']??'';
        $reData = [];
        $reData['id'] = $data['id']??0;

        $reData['name'] = $data['name']??'';
        $reData['number'] = $data['number']??'';
        $reData['release_time'] = $data['release_time']??'';
        $reData['created_at'] = $data['created_at']??'';

        $reData['is_download'] = $data['is_download']??1;//状态 1.不可下载  2.可下载
        $reData['is_subtitle'] = $data['is_subtitle']??1;//状态 1.不含字幕  2.含字幕
        $reData['is_hot'] = $data['is_hot']??1;//状态 1.普通  2.热门
        $reData['is_new_comment'] = $is_new_comment_day;//状态 1.今日新评  2.无状态 3.昨日新评

        $reData['is_flux_linkage'] = $is_flux_linkage_day;//状态 1.今日新种  2.无状态 3.昨日新种
        $reData['comment_num'] = $data['comment_num']??0;
        $reData['score'] = $data['score']??0;
        $reData['small_cover'] = $small_cover == ''?'':(Common::getImgDomain().$small_cover);

        $reData['big_cove'] = $big_cove == ''?'':(Common::getImgDomain().$big_cove);
        $reData['is_short_comment'] = $data['is_short_comment']??0;;

        return $reData;
    }

    public function labels()
    {
        return $this->hasMany(MovieLabelAss::class,'mid','id');
    }

//    public function actors()
//    {
//        return $this->hasMany(MovieActorAss::class,'mid','id');
//    }

    /**
     * @Description 关联演员影片表
     * @DateTime    2018-10-31
     * @copyright   [copyright]
     * @return      [type]      [description]
     */
    public function actors()
    {
        return $this->belongsToMany('App\Models\MovieActor', 'movie_actor_associate', 'mid', 'aid');
    }


    /**
     * @Description 关联演员影片表
     * @DateTime    2018-10-31
     * @copyright   [copyright]
     * @return      [type]      [description]
     */
    public function directors()
    {
        return $this->belongsToMany('App\Models\MovieDirector', 'movie_director_associate', 'mid', 'did');
    }

    /**
     * ELASTICSEARCH 模糊搜索同时支持 [车牌，标题，描述]
     * @param $keyword
     * @param $page
     * @param int $pageSize
     * @return array
     */
    public static function searchAPage($keyword, $page,$pageSize = self::pagesize)
    {
        if(empty($keyword)){
            return [];
        }


        $query = VideoElasticquent::getQueryArray($keyword,$page,$pageSize);
        if(empty($query)){
            return [];
        }

        $videos = VideoElasticquent::complexSearch($query);

        $total = $videos->totalHits();
        $more  = (int)(bool)($pageSize * $page < $total);

        return [
            'more' => $more,
            'total'=>$total,
            'video' => $videos,
        ];
    }







}
