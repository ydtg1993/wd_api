<?php

namespace App\Console\Commands;

use App\Models\AdsList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class AutoExpireAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:AutoExpireAds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天24点之后自动过期之前的广告,计划任务';

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
        $lists = AdsList::select('id','name','location')->where('end_time','<',date('Y-m-d H:i:s'))->where('status',1)->get();

        foreach($lists as $v)
        {
            //更新数据库
            AdsList::where('id',$v->id)->update(['status'=>3]);

            //清除缓存
            Redis::del('ads_list:'.$v['location']);
            echo '过期广告:'.$v->name.PHP_EOL;
        }

        echo '过期广告完成'.PHP_EOL;
    }
}
