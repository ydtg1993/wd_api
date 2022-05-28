<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\MovieCategory;
use App\Models\MovieLog;
use App\Tools\RedisCache;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class JobAssociate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:JobAssociate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天12点后统计关系表【演员，导演，片商，系列，番号组，标签】之间的计数，计划任务';

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
        //获取演员
        echo '开始统计演员表对应计数器:'.PHP_EOL;
        $this->countActor(0);
        echo '结束演员统计'.PHP_EOL;

        //获取导演
        echo '开始统计导演表对应计数器:'.PHP_EOL;
        $this->countDirector(0);
        echo '结束导演统计'.PHP_EOL;

        //获取片商
        echo '开始统计片商表对应计数器:'.PHP_EOL;
        $this->countFilm(0);
        echo '结束片商统计'.PHP_EOL;

        //获取系列
        echo '开始统计系列表对应计数器:'.PHP_EOL;
        $this->countSeries(0);
        echo '结束系列统计'.PHP_EOL;

        //获取系列
        echo '开始统计番号组对应计数器:'.PHP_EOL;
        $this->countNumber(0);

        //获取标签
        echo '开始统计标签对应计数器:'.PHP_EOL;
        $this->countLabel(0);
        echo '结束标签统计'.PHP_EOL;

        //本月累计新增PV浏览量 计入
        echo '开始统计pv:'.PHP_EOL;
        $this->countPv();
    }

    private function countPv()
    {
        $time = time();
        $this_month = date('Y-m-01 00:00:00', $time);//本月时间
        $page = 1;
        $limit = 500;

        $ES = ClientBuilder::create()->setHosts([env('ELASTIC_HOST').':'.env('ELASTIC_PORT')])->build();
        while(true){
            if($time > strtotime(date('Y-m-01 12:00:00', $time))){
                //本月首日清0
                break;
            }
            $start = ($page - 1) * $limit;
            $data = Movie::where('status',1)->take($limit)->skip($start)->pluck('id')->all();
            $page++;
            if (empty($data)) {
                return;
            }
            foreach ($data as $id){
                $ES->update([
                    'index' => 'movie',
                    'type' => '_doc',
                    'id' => $id,
                    'body' => [
                        'doc' => [
                            'pv' => 0
                        ]
                    ]
                ]);
            }
        }

        $cids = MovieCategory::pluck('id')->all();
        foreach ($cids as $cid) {
            $log = DB::select("SELECT mid,count(0) as num FROM movie_log where cid='{$cid}' AND created_at>'{$this_month}' group by mid");
            foreach ($log as $l) {
                $ES->update([
                    'index' => 'movie',
                    'type' => '_doc',
                    'id' => $l->mid,
                    'body' => [
                        'doc' => [
                            'pv' => $l->num
                        ]
                    ]
                ]);
            }
        }
    }

    private function countActor($startId = 0)
    {
        $res = DB::select('SELECT aid,count(0) as nums FROM movie_actor_associate where aid>'.$startId.' group by aid order by aid asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->aid;
            $nums = $val->nums;
            DB::table('movie_actor')->where('id',$startId)->update(['movie_sum'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countActor($startId);
    }

    private function countDirector($startId = 0)
    {
        $res = DB::select('SELECT did,count(0) as nums FROM movie_director_associate where did>'.$startId.' group by did order by did asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->did;
            $nums = $val->nums;
            DB::table('movie_director')->where('id',$startId)->update(['movie_sum'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countDirector($startId);
    }

    private function countFilm($startId = 0)
    {
        $res = DB::select('SELECT film_companies_id,count(0) as nums FROM movie_film_companies_associate where film_companies_id>'.$startId.' group by film_companies_id order by film_companies_id asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->film_companies_id;
            $nums = $val->nums;
            DB::table('movie_film_companies')->where('id',$startId)->update(['movie_sum'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countFilm($startId);
    }

    private function countSeries($startId = 0)
    {
        $res = DB::select('SELECT series_id,count(0) as nums FROM movie_series_associate where series_id>'.$startId.' group by series_id order by series_id asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->series_id;
            $nums = $val->nums;
            DB::table('movie_series')->where('id',$startId)->update(['movie_sum'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countSeries($startId);
    }


    private function countNumber($startId = 0)
    {
        $res = DB::select('SELECT nid,count(0) as nums FROM movie_number_associate where nid>'.$startId.' group by nid order by nid asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->nid;
            $nums = $val->nums;
            DB::table('movie_number')->where('id',$startId)->update(['movie_sum'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countNumber($startId);
    }

    private function countLabel($startId = 0)
    {
        $res = DB::select('SELECT cid,count(0) as nums FROM movie_label_associate where cid>'.$startId.' group by cid order by cid asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }

        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->cid;
            $nums = $val->nums;
            DB::table('movie_label')->where('id',$startId)->update(['item_num'=>$nums]);
            echo '开始计数:'.$startId.','.$nums.PHP_EOL;
        }

        //递归自身
        $this->countLabel($startId);
    }
}
