<?php


namespace App\Console\Commands;


use App\Models\Movie;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddTheLabel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:AddTheLabel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新【黄豆瓣】所有”有码“影片的标签数据，与【JavDB】同步';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected $mongo_table = 'javdb';
    protected $labelMap = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 查询 mysql 现有标签
        $this->labelMap = MovieLabel::all()->pluck('id','name')->toArray();

        $total = $this->count();
        $pageSize = 200;
        $maxPage = ceil($total / $pageSize);
        $page =  1;

        $bar = $this->output->createProgressBar($maxPage);
        $bar->setBarCharacter('<comment>=</comment>');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('|');
        $bar->setBarWidth(50);

        // 分页查询 mongo db 有码的数据
        while ($page < $maxPage){
//            $this->info("共 {$maxPage} 页面 ，当前 {$page} 页 .\n");
            $begen = ($page - 1) * $pageSize;
            $data = DB::connection('mongodb')
                ->collection($this->mongo_table)
                ->where('group', '=', '有码')
                ->skip((int)$begen)
                ->take((int)$pageSize)
                ->orderBy('_id', 'desc')
                ->get();
            $this->processDetail($data);
            $bar->advance();
            $page ++;
        }
        $bar->finish();
    }

    public function processDetail($data)
    {
        foreach ($data as $k => $v) {

            // 查询对应的标签数据
            $label_disable  = [];
            if ($v['uid'] == ''){
                continue;
            }

            $movie = Movie::where('number', $v['uid'])->get()->first();
            if (!$movie){
                // 该电影不存在
//                $this->info("跳过电影, 番号 {$v['uid']} 不存在");
                continue;
            }

            if (!is_array($v['video_sort']) || count($v['video_sort']) < 1){
                continue;
            }

            // 查询对应的标签数据
            $result = DB::table('movie_label_associate')
                ->leftJoin('movie_label', 'movie_label.id', '=', 'movie_label_associate.cid')
                ->wherein('movie_label.name', $v['video_sort'])
                ->where('movie_label_associate.mid', '=', $movie->id)
                ->select('movie_label.name')
                ->get()
                ->toArray();

            $has = [];
            foreach ($result as $item) {
                $has[] = $item->name;
            }

            // 比对标签数量
            foreach ($v['video_sort'] as $key => $value) {
                if (!in_array($value, $has)){
                    $label_disable[] = $value;
                }
            }


            // 更新本地标签数据
            foreach ($label_disable as $val){

                if (!isset($this->labelMap[$val])) {
                    // 该 label 不存在
//                    $this->info("跳过标签, 标签 {$val} 不存在");
                    continue;
                }
                $cid = $this->labelMap[$val];

                $label_associate = new MovieLabelAss();
                $label_associate->cid = $cid;
                $label_associate->mid = $movie->id;
                $label_associate->status = 1;
                $label_associate->save();
//                $this->info("新增成功, 标签 {$cid} , 电影 {$movie->id}");
            }

        }
    }

    public function count()
    {
        return DB::connection('mongodb')->collection($this->mongo_table)->where('group', '=', '有码')->count();
    }
}
