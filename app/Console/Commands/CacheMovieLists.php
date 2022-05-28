<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheMovieLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CacheMovieLists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天12点后自动缓存电影列表到redis有序集合，计划任务';

    protected $key          = 'moive_number';   //影片番号key
    protected $keyCategory  = 'moive_category'; //影片分类缓存key
    protected $keyActor     = 'movie_actor';    //演员缓存key
    protected $keyDirector  = 'movie_director'; //导演缓存key
    protected $keyFilm      = 'movie_film';     //片商缓存key
    protected $keyLabel     = 'movie_label';    //标签缓存key
    protected $keySeries    = 'movie_series';   //系列缓存key
    protected $pageSize = 20;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //获取电影表总数
        $res = DB::select('select count(0) as nums from movie');
        $total = $res[0]->nums;

        $mid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $mid = $this->cacheDo($mid);
        }
        echo '最后影片的id:'.$mid.PHP_EOL;

        //获取分类表总数
        $res = DB::select('select count(0) as nums from movie_category');
        $total = $res[0]->nums;

        $cid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $cid = $this->casheDoCategory($cid);
        }
        echo '最后分类的id:'.$cid.PHP_EOL;

        //获取演员表总数
        $res = DB::select('select count(0) as nums from movie_actor');
        $total = $res[0]->nums;

        $aid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $aid = $this->cacheDoActor($aid);
        }
        echo '最后演员的id:'.$aid.PHP_EOL;

        //获取导演表总数
        $res = DB::select('select count(0) as nums from movie_director');
        $total = $res[0]->nums;

        $did = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $did = $this->cacheDoDirector($did);
        }
        echo '最后导演的id:'.$did.PHP_EOL;

        //获取片商表总数
        $res = DB::select('select count(0) as nums from movie_film_companies');
        $total = $res[0]->nums;

        $fid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $fid = $this->cacheDoFilm($fid);
        }
        echo '最后片商的id:'.$fid.PHP_EOL;

        //获取标签表总数
        $res = DB::select('select count(0) as nums from movie_label');
        $total = $res[0]->nums;

        $lid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $lid = $this->cacheDoLabel($lid);
        }
        echo '最后标签的id:'.$lid.PHP_EOL;

        //获取系列表总数
        $res = DB::select('select count(0) as nums from movie_series');
        $total = $res[0]->nums;

        $sid = 0;
        $max = ceil($total / $this->pageSize);
        for($i = 0;$i <= $max;$i ++)
        {
            $sid = $this->cacheDoSeries($sid);
        }
        echo '最后系列的id:'.$sid.PHP_EOL;


        echo '执行完毕'.PHP_EOL;
    }

    /**
     * 电影写入缓存
     * @param  int  $mid  电影的id
     */
    private function cacheDo($mid = 0)
    {
        $arrMovie = DB::select('select id,`name`,number from movie where id>? order by id asc limit ?',[$mid,$this->pageSize]);
        foreach($arrMovie as $v){
            //处理特殊数据，发现名称里面居然有换行，制表等等，用base64来隐藏数据
            $text = base64_encode(trim($v->number));
            $res = Redis::zadd($this->key,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入电影番号缓存,'.$v->number.'结果：'.$res.PHP_EOL;
            }
            $mid = $v->id;
        }
        return $mid;
    }

    /**
     * 分类写入缓存
     * @param  int  $cid  分类的id
     */
    private function casheDoCategory($cid = 0)
    {
        $arr = DB::select('select id,`name` from movie_category where id>? order by id asc limit ?',[$cid,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keyCategory,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入分类缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }

            $cid = $v->id;
        }
        return $cid;
    }

    /**
     * 演员写入缓存
     */
    private function cacheDoActor($aid = 0)
    {
        $arr = DB::select('select id,`name` from movie_actor where id>? order by id asc limit ?',[$aid,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keyActor,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入演员缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }

            $aid = $v->id;
        }
        return $aid;
    }

    /**
     * 导演写入缓存
     */
    private function cacheDoDirector($did = 0)
    {
        $arr = DB::select('select id,`name` from movie_director where id>? order by id asc limit ?',[$did,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keyDirector,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入演员缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }
            $did = $v->id;
        }
        return $did;
    }

    /**
     * 片商写入缓存
     */
    private function cacheDoFilm($fid = 0)
    {
        $arr = DB::select('select id,`name` from movie_film_companies where id>? order by id asc limit ?',[$fid,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keyFilm,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入片商缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }
            $fid = $v->id;
        }
        return $fid;
    }

    /**
     * 标签写入缓存
     */
    private function cacheDoLabel($lid = 0)
    {
        $arr = DB::select('select id,`name` from movie_label where id>? order by id asc limit ?',[$lid,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keyLabel,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入标签缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }
            $lid = $v->id;
        }
        return $lid;
    }

    /**
     * 系列写入缓存
     */
    private function cacheDoSeries($sid = 0)
    {
        $arr = DB::select('select id,`name` from movie_series where id>? order by id asc limit ?',[$sid,$this->pageSize]);
        foreach ($arr as $v) {
            $text = base64_encode(trim($v->name));
            $res = Redis::zadd($this->keySeries,intval($v->id),$text);
            if($res > 0)
            {
                echo $v->id.'，写入系列缓存，'.$v->name.'结果：'.$res.PHP_EOL;
            }
            $sid = $v->id;
        }
        return $sid;
    }

}
