<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\MovieCategory;
use App\Models\MovieComment;
use App\Models\MovieLog;
use App\Models\RecommendMovie;
use App\Models\UserSeenMovie;
use App\Models\UserWantSeeMovie;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MovieHotCal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movieHot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '热门推荐影片 每日热度统计';

    /**
     * 图片路径切分
     * @var int
     */
    protected $chunk = 64;

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
        $this->clearAll('carousel:*');
        $categories = MovieCategory::where('status', 1)->pluck('id')->all();
        $timeline = date('Y-m-d', strtotime("-2 day"));
        $today = date('Y-m-d 00:00:00');
        $base_dir = rtrim(public_path('resources'), '/') . '/';
        foreach ($categories as $category) {
            $logs = MovieLog::where('cid', $category)
                ->where('created_at', '>', $timeline)
                ->groupBy('mid')
                ->orderBy('pv', 'desc')
                ->orderBy('mid', 'desc')
                ->select(DB::raw('count(mid) as pv, mid'))
                ->limit(100)
                ->get()->toArray();
            if (empty($logs)) {
                $logs = MovieLog::where('cid', $category)
                    ->where('created_at', '>', date('Y-m-d', strtotime("-7 day")))
                    ->groupBy('mid')
                    ->orderBy('pv', 'desc')
                    ->orderBy('mid', 'desc')
                    ->select(DB::raw('count(mid) as pv, mid'))
                    ->limit(100)
                    ->get()->toArray();
                if (empty($logs)) {
                    continue;
                }
            }
            foreach ($logs as &$log) {
                $mid = $log['mid'];
                $log['want_see'] = UserWantSeeMovie::where(['status' => 1, 'mid' => $mid])->where('mark_time', '>', $timeline)->count();
                $log['seen'] = UserSeenMovie::where(['status' => 1, 'mid' => $mid])->where('mark_time', '>', $timeline)->count();
                $log['comment_num'] = MovieComment::where(['status' => 1, 'cid' => 0, 'source_type' => 1, 'mid' => $mid])->where('comment_time', '>', $timeline)->count();
                $log['category'] = $category;
                $log['photo'] = '';
                $log['ctime'] = $today;
                $log['hot'] = $log['pv'] + ($log['comment_num'] * 5) + ($log['want_see'] + $log['seen']) * 3;
            }
            array_multisort(array_column($logs, 'hot'), SORT_DESC, $logs);
            $this->resource(array_slice($logs, 11));
            $logs = array_slice($logs, 0, 10);
            foreach ($logs as &$log) {
                //图像资源复制
                $movie = Movie::where('id', $log['mid'])->first();
                $dir = 'recommend_movie/' . ($movie->id % $this->chunk) . '/' . $movie->id . '/';
                $newDir = $base_dir . $dir;
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0777, true);
                    chmod($newDir, 0777);
                }
                if (!is_file($base_dir . $movie->big_cove)) {
                    continue;
                }
                $ext = pathinfo($movie->big_cove, PATHINFO_EXTENSION);
                if (is_file($newDir . 'cover.' . $ext)) {
                    $log['photo'] = $dir . 'cover.' . $ext;
                    continue;
                }
                $res = copy($base_dir . $movie->big_cove, $newDir . 'cover.' . $ext);
                if ($res) {
                    $log['photo'] = $dir . 'cover.' . $ext;
                }
            }
            RecommendMovie::insert($logs);
        }
    }

    private function resource($data)
    {
        $base_dir = rtrim(public_path('resources'), '/') . '/';
        foreach ($data as $d) {
            $id = $d['mid'];
            $dir = 'recommend_movie/' . ($id % $this->chunk) . '/' . $id . '/';
            $path = $base_dir . $dir;
            if (is_dir($path)) {
                $dirs = scandir($path);
                foreach ($dirs as $dir) {
                    if ($dir != '.' && $dir != '..') {
                        $sonDir = $path . '/' . $dir;
                        if(is_file($sonDir)) {
                            @unlink($sonDir);
                        }
                    }
                }
                @rmdir($path);
            }
        }
    }

    private function clearAll($cache)
    {
        $prefix = config('database.redis.options.prefix');
        $keys = Redis::keys($cache);
        foreach ($keys as $key) {
            Redis::del(str_replace($prefix, '', $key));
        }
    }
}
