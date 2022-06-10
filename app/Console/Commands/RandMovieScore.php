<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class RandMovieScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:RandMovieScore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天12点后针对评分为0或10的，生成虚拟数据，计划任务';

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
        echo '开始生成没有评分的随机数:'.PHP_EOL;
        $this->movieScoreCount(0);
        echo '结束任务'.PHP_EOL;
    }

    private function movieScoreCount($startId = 0)
    {
        $res = DB::select('SELECT id FROM movie where id > '.$startId.' and score in(0,10) order by id asc limit 100');

        //获取不到这个值后，跳出循环
        if(count($res)<1){
            return 0;
        }
        
        //循环写入数据
        foreach($res as $val)
        {
            $startId = $val->id;
            $score = rand(5,9);   //生成随机评分
            $people = rand(50,200); //生成随机人数
            DB::table('movie')->where('id',$startId)->update(['score'=>$score,'score_people'=>$people]);
            echo '开始生成:'.$startId.','.$score.PHP_EOL;
        }

        //递归自身
        $this->movieScoreCount($startId);
    }
}
