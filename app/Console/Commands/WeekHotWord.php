<?php

namespace App\Console\Commands;

use App\Models\SearchLog;
use App\Models\SearchHotWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WeekHotWord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:WeekHotWord';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每周一早上8点统计一次热搜词，计划任务';

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
        $sTime = date('Y-m-d', strtotime('-' . (6+date('w')) . ' days'));
        $eTime = date("Y-m-d H:i:s",strtotime('-1 sunday',time()));
        echo $sTime.'~'.$eTime;

        DB::table('user_search_log')->where('created_at','<',$sTime)->delete();
        $mdb = new SearchLog();
        $arr = $mdb->groupCountLists($sTime,$eTime);

        SearchHotWord::where('created_at','<',date('Y-m-d'))->delete();
        foreach($arr as $v){
            SearchHotWord::insert(['content'=>$v->content,'times'=>$v->nums]);
        }
        Redis::del('hot_keyword');
        echo '生成热词完成'.PHP_EOL;
    }
}
