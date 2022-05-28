<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\SearchLog;
use App\Models\SearchHotWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IsDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:is_download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '早上4点，计划任务';

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
        $page = 1;
        $pageSize = 500;//一次处理500条
        while (true) {
            $movies = Movie::where(['status'=>1,'is_up'=>1])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->select('flux_linkage_num','is_download','id')->get()->toArray();
            if (empty($movies)) {
                break;
            }
            $page++;
            
            $is_download_movie_ids = [];
            $not_download_movie_ids = [];
            foreach ($movies as $movie){
                if($movie['flux_linkage_num'] > 0 && $movie['is_download'] == 1){
                    $is_download_movie_ids[] = $movie['id'];
                }
                if($movie['flux_linkage_num'] == 0 && $movie['is_download'] == 2){
                    $not_download_movie_ids[] = $movie['id'];
                }
            }
            if(!empty($is_download_movie_ids)){
                Movie::whereIn('id',$is_download_movie_ids)->update(['is_download'=>2]);
            }
            if(!empty($not_download_movie_ids)){
                Movie::whereIn('id',$not_download_movie_ids)->update(['is_download'=>1]);
            }
            echo $page.PHP_EOL;
        }
    }
}
