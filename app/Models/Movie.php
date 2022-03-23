<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\MovieLabel;
use App\Models\MovieLabelCategoryAssociate;
use Illuminate\Support\Facades\Redis;

class Movie extends Model
{
    protected $table = 'movie';

    const pagesize = 10;//默认页数

    /**
     *
     * 读取数据通过分类-使用缓存
     */
    public function getMovieListByCache($data, $isCache)
    {
        $page = $data['page']??1;
        $pageSize = $data['pageSize']??12;
        $cid = $data['cid']??0;

        $reData = RedisCache::getCacheDataOnly('movie','movie:category:list:',['home_type'=>$data['home_type'],'cid'=>$cid,'page'=>$page,'pageSize'=>$pageSize,'args'=>md5(json_encode($data))],$isCache);
        if(!$reData){
            //标签走另外的函数
            if($data['home_type']==5){
                $reData = self::getMovieListBylabel($data);
            }else{
                $reData = self::getMovieList($data);
            }
            RedisCache::setCacheDataOnly('movie','movie:category:list:',$reData,['home_type'=>$data['home_type'],'cid'=>$cid,'page'=>$page,'pageSize'=>$pageSize,'args'=>md5(json_encode($data))],$isCache);
        }
        return $reData;
    }

    /**
     * 读取影片列表通过分类
     */
    public static function getMovieList($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pageSize'] == 12 ? 12:10;
        $cid = $data['cid'] ?? 0;

        //判断搜索条件
        $where = ' status =1 and is_up=1 ';
        if ($data['home_type'] == 1) {  //热门
            $where = 'is_hot = 1 and ' . $where;
        }
        if ($cid > 0) {   //分类
            $where = 'cid = ' . $cid . ' and ' . $where;
        }
        if (isset($data['is_subtitle']) && $data['is_subtitle']) {
            $where = 'is_subtitle = ' . $data['is_subtitle'] . ' and ' . $where;
        }
        if (isset($data['is_download']) && $data['is_download']) {
            $where = 'is_download = ' . $data['is_download'] . ' and ' . $where;
        }
        if (isset($data['is_short_comment']) && $data['is_short_comment']) {
            $where = 'is_short_comment = ' . $data['is_short_comment'] . ' and ' . $where;
        }
        if (isset($data['day_limit'])) {
            $where = "flux_linkage_time between '" . date('Y-m-d 00:00:00', time() - 3600 * 24) . "' and '" . date('Y-m-d 23:59:59', time()) . "' and " . $where;
        }

        //排序
        $orderby = 'id desc';
        if (isset($data['release_time']) && $data['release_time']) {
            $orderby = ' release_time desc ';
            if ($data['release_time'] == 1) {
                $orderby = ' release_time asc ';
            }
        }
        if (isset($data['flux_linkage_time']) && $data['flux_linkage_time']) {
            $orderby = ' flux_linkage_time desc ';
            if ($data['flux_linkage_time'] == 1) {
                $orderby = ' flux_linkage_time asc ';
            }
        }
        if ($data['home_type'] == 1) {
            $orderby = ' score desc,weight desc';
        }

        $offset = ($page - 1) * $pageSize;  //游标
        $limit = $pageSize;   //每页读取多少条

        $reData = ['list' => [], 'sum' => 0];

        //如果包含分类条件
        $res=[];
        $cache = "home:".md5($where.$orderby).":".$page."_".$pageSize;
        $cache_nums = "home:".md5($where.$orderby).":nums";

        $record = Redis::get($cache);
        $nums = Redis::get($cache_nums);
        if (!$record) {
            $rows = DB::select('select id,name,number,release_time,created_at
                ,is_download,is_subtitle,is_short_comment,is_hot
                ,new_comment_time,flux_linkage_time,comment_num,score
                ,small_cover,big_cove
                from movie
                where ' . $where . '
                order by ' . $orderby . ' limit ' . $offset . ',' . $limit . ';');
            //加工数据
            $rows = Common::objectToArray($rows);
            foreach ($rows as $v) {
                $res[] = Movie::formatList($v);
            }
            Redis::setex($cache, 7200, json_encode($res));
        } else {
            $res = json_decode($record, true);
        }
        if (!$nums) {
            $count = DB::select('select count(0) as nums
                from movie
                where ' . $where . ';');
            $nums = $count[0]->nums;
            Redis::setex($cache_nums, 7200, $nums);
        }

        $reData['list'] = Common::objectToArray($res);
        $reData['sum'] = (int)$nums;

        return $reData;
    }


    /**
     * 读取影片列表通过标签
     */
    public static function getMovieListBylabel($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pageSize'] ?? 10;
        $lid = $data['cid'] ?? 0;
        $gid = $data['gid'] ?? 0;

        //判断搜索条件
        $where = ' M.status =1 and M.is_up=1 ';

        if ($lid > 0) {
            //读取一次标签，如果是父标签
            $children = MovieLabel::select('id')->where('cid', $lid)->get();
            if ($children) {
                $tmpIds = [$lid];
                foreach ($children as $v) {
                    $tmpIds[] = $v->id;
                }
                $lid = join(',', $tmpIds);
            }

            $where = 'L.cid in (' . $lid . ') and ' . $where;
        }

        if ($gid > 0) {
            //根据分类读取
            $where = 'M.cid =' . $gid . ' and ' . $where;
        }
        if (isset($data['is_subtitle']) && $data['is_subtitle']) {
            $where = 'M.is_subtitle = ' . $data['is_subtitle'] . ' and ' . $where;
        }
        if (isset($data['is_download']) && $data['is_download']) {
            $where = 'M.is_download = ' . $data['is_download'] . ' and ' . $where;
        }
        if (isset($data['is_short_comment']) && $data['is_short_comment']) {
            $where = 'M.is_short_comment = ' . $data['is_short_comment'] . ' and ' . $where;
        }

        //排序
        $orderby = 'M.flux_linkage_time desc,M.id desc';

        $offset = ($page - 1) * $pageSize;  //游标
        $limit = $pageSize;   //每页读取多少条

        $reData = ['list' => [], 'sum' => 0];

        //最终sql
        $selectSql = 'select distinct(M.id),M.name,M.number,M.release_time,M.created_at
                ,M.is_download,M.is_subtitle,M.is_short_comment,M.is_hot
                ,M.new_comment_time,M.flux_linkage_time,M.comment_num,M.score
                ,M.small_cover,M.big_cove
                from movie as M
                where ' . $where . '
                order by ' . $orderby . ' limit ' . $offset . ',' . $limit . ';';
        $countSql = 'select count(distinct(M.id)) as nums
                from movie as M
                where ' . $where . ';';

        if ($lid > 0) {
            //只有读取标签时，才决定连表
            $selectSql = 'select distinct(M.id),M.name,M.number,M.release_time,M.created_at
                ,M.is_download,M.is_subtitle,M.is_short_comment,M.is_hot
                ,M.new_comment_time,M.flux_linkage_time,M.comment_num,M.score
                ,M.small_cover,M.big_cove
                from movie as M
                join movie_label_associate as L
                on M.id = L.mid
                where ' . $where . '
                order by ' . $orderby . ' limit ' . $offset . ',' . $limit . ';';
            $countSql = 'select count(distinct(M.id)) as nums
                from movie as M
                join movie_label_associate as L
                on M.id = L.mid
                where ' . $where . ';';
        }

        //如果包含分类条件
        $cache = "home:" . md5($where . $orderby) . ":".$page."_".$pageSize;
        $cache_nums = "home:" . md5($where) . ":nums";
        $res = [];
        $record = Redis::get($cache);
        if(!$record) {
            $rows = DB::select($selectSql);
            $rows = Common::objectToArray($rows);
            foreach ($rows as $v) {
                $res[] = Movie::formatList($v);
            }
            Redis::setex($cache, 7200, json_encode($res));
        }else{
            $res = json_decode($record, true);
        }

        $nums = Redis::get($cache_nums);
        if(!$nums) {
            $count = DB::select($countSql);
            $nums = $count[0]->nums;
            Redis::setex($cache_nums, 7200, $nums);
        }

        $reData['list'] = Common::objectToArray($res);
        $reData['sum'] = (int)$nums;

        return $reData;
    }

    /**
     * 格式化影片列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $is_new_comment_day = ((strtotime($data['new_comment_time'] ?? '') - strtotime(date('Y-m-d 00:00:00'))) >= 0) ? 1 : 2;//最新评论时间减去 今日开始时间 如果大于0 则今日新评论
        $is_new_comment_day = ($is_new_comment_day == 2) ? (
        (((strtotime($data['new_comment_time'] ?? '') - (strtotime(date('Y-m-d 00:00:00')) - (60 * 60 * 24))) >= 0) ? 3 : 2)
        ) : 1;

        $is_flux_linkage_day = ((strtotime($data['flux_linkage_time'] ?? '') - strtotime(date('Y-m-d 00:00:00'))) >= 0) ? 1 : 2;
        $is_flux_linkage_day = ($is_flux_linkage_day == 2) ? (
        (((strtotime($data['flux_linkage_time'] ?? '') - (strtotime(date('Y-m-d 00:00:00')) - (60 * 60 * 24))) >= 0) ? 3 : 2)
        ) : 1;

        $small_cover = $data['small_cover'] ?? '';
        $big_cove = $data['big_cove'] ?? '';
        $reData = [];
        $reData['id'] = $data['id'] ?? 0;

        $reData['name'] = $data['name'] ?? '';
        $reData['number'] = $data['number'] ?? '';
        $reData['release_time'] = $data['release_time'] ?? '';
        $reData['created_at'] = $data['created_at'] ?? '';

        $reData['is_download'] = $data['is_download'] ?? 1;//状态 1.不可下载  2.可下载
        $reData['is_subtitle'] = $data['is_subtitle'] ?? 1;//状态 1.不含字幕  2.含字幕
        $reData['is_hot'] = $data['is_hot'] ?? 1;//状态 1.普通  2.热门
        $reData['is_new_comment'] = $is_new_comment_day;//状态 1.今日新评  2.无状态 3.昨日新评

        $reData['is_flux_linkage'] = $is_flux_linkage_day;//状态 1.今日新种  2.无状态 3.昨日新种
        $reData['comment_num'] = $data['comment_num'] ?? 0;
        $reData['score'] = $data['score'] ?? 0;
        $reData['small_cover'] = $small_cover == '' ? '' : (Common::getImgDomain() . $small_cover);

        $reData['big_cove'] = $big_cove == '' ? '' : (Common::getImgDomain() . $big_cove);
        $reData['is_short_comment'] = $data['is_short_comment'] ?? 0;;

        return $reData;
    }

    public function labels()
    {
        return $this->hasMany(MovieLabelAss::class, 'mid', 'id');
    }

//    public function actors()
//    {
//        return $this->hasMany(MovieActorAss::class,'mid','id');
//    }

    /**
     * @Description 关联演员影片表
     * @DateTime    2018-10-31
     * @return      [type]      [description]
     * @copyright   [copyright]
     */
    public function actors()
    {
        return $this->belongsToMany('App\Models\MovieActor', 'movie_actor_associate', 'mid', 'aid');
    }


    /**
     * @Description 关联演员影片表
     * @DateTime    2018-10-31
     * @return      [type]      [description]
     * @copyright   [copyright]
     */
    public function directors()
    {
        return $this->belongsToMany('App\Models\MovieDirector', 'movie_director_associate', 'mid', 'did');
    }

    /**
     * 影片加权分新增
     * @param mid     影片id
     * @param score   加权更新分数
     */
    public static function weightAdd($mid, $score = 0)
    {
        //DB::enableQueryLog();
        Movie::where('id', $mid)->increment('weight', $score);
        //print_r(DB::getQueryLog());
        return;
    }

    /**
     * 影片加权分减少
     * @param mid     影片id
     * @param score   加权更新分数
     */
    public static function weightLose($mid, $score = 0)
    {
        //DB::enableQueryLog();
        Movie::where('id', $mid)->decrement('weight', $score);
        //print_r(DB::getQueryLog());
        return;
    }

}
