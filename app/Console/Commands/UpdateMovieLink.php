<?php


namespace App\Console\Commands;


use App\Http\Common\Common;
use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMovieLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:UpdateMovieLink';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新【黄豆瓣】电影的磁链，与[JavDB][JavBus]同步';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected $mongo_table = [
        'javdb',
        'javbus'
    ];

    protected $last_time = '2022-05-01 00:00:00';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pageSize = 200;


        foreach ($this->mongo_table as $db_name) {
            $total   = $this->count($db_name);
            $maxPage = ceil($total / $pageSize);
            $page    = 1;

            $this->info("开始处理 {$db_name} 的数据:");

            $bar = $this->output->createProgressBar($maxPage);
            $bar->setBarCharacter('<comment>=</comment>');
            $bar->setEmptyBarCharacter(' ');
            $bar->setProgressCharacter('|');
            $bar->setBarWidth(50);

            while ($page < $maxPage) {
                $begen = ($page - 1) * $pageSize;

                $data = DB::connection('mongodb')
                    ->collection($db_name)
                    ->where('utime', '>', $this->last_time)
                    ->skip((int)$begen)
                    ->take((int)$pageSize)
                    ->orderBy('_id', 'desc')
                    ->get();

                $this->processDetail($data);
                $bar->advance();
                $page++;
            }
            $bar->finish();
        }
    }


    private function processDetail($data)
    {
        foreach ($data as $k => $v) {
            if (!isset($v['magnet']) || !is_array($v['magnet']) || count($v['magnet']) < 1) {
                //$this->info("磁链不存在.....");
                continue;
            }
            $movie = Movie::where('number', $v['uid'])->get()->first();

            if (!$movie) {
                //$this->info($v['uid'] . " 番号不存在.....");
                continue;
            }

            $flux_linkage_tmp        = json_decode($movie->flux_linkage, true);
            $tmp = $movie->flux_linkage     = $this->merge($flux_linkage_tmp, $v['magnet']);
            $movie->flux_linkage_num = count($movie->flux_linkage);
            $movie->save();
        }
    }

    /**
     * @param $data1 movie 表数据
     *  {
     * "is-small": 1,
     * "is-warning": 2,
     * "meta": "(3.81GB,4個文件)",
     * "name": "BangBus.21.05.05.Emma.Sirus.XXX.1080p.MP4-WRB[rarbg]",
     * "time": "2021-11-27 11:13:24",
     * "tooltip": 2,
     * "url": "magnet:?xt=urn:btih:91a8bb06f4ad0b9b27528a4be6d7721d01109657\u0026dn=[HDouban.com]BangBus.21.05.05.Emma.Sirus.XXX.1080p.MP4-WRB[rarbg]"
     * }
     * @param $data2 mongo 数据
     * {
     * "name" : "RKPrime.22.06.06.Charly.Summer.Sock.It.To.Me.XXX.1080p.MP4-WRB[rarbg]",
     * "time" : null,
     * "url" : "magnet:?xt=urn:btih:493769a5d85b010a271b5755c09baf1a0c3f7619&dn=[javdb.com]RKPrime.22.06.06.Charly.Summer.Sock.It.To.Me.XXX.1080p.MP4-WRB[rarbg]",
     * "meta" : "939MB, 4個文件",
     * "is-small" : "高清",
     * "is-warning" : null
     * }
     * @return array
     */
    private function merge($data1, $data2)
    {
        $data = $data1;
        foreach ($data2 as $item) {
            $same = false;
            if (!$item['url']) {
                //磁链不存在
                continue;
            }
            foreach ($data1 as $v) {
                if ($v['url'] == $item['url']) {
                    $same = true;
                }
            }

            if (!$same) {
                $data[] = $this->fluxLinkageFormat($item);
            }
        }
        return $data;
    }

    /**
     * 格式化磁链数据
     * @param $data
     * @return array
     */
    private function fluxLinkageFormat($data)
    {
        $is_small = $data['is-small'] ?? null;
        $is_small = $is_small == '' || $is_small == null ? 2 : 1;

        $is_warning = $data['is-warning'] ?? null;
        $is_warning = $is_warning == '' || $is_warning == null ? 2 : 1;

        $tooltip = $data['tooltip'] ?? null;
        $tooltip = $tooltip == '' || $tooltip == null ? 2 : 1;

        $magnet     = $data['url'] ?? null;
        $magnet     = $magnet == '' || $magnet == null ? '' : $magnet;
        $magnetTemp = strtr($magnet, [
            'javdb.com' => 'HDouban.com',
        ]);

        $reData = [
            'name'       => $data['name'] ?? '',
            'url'        => $magnetTemp,
            'meta'       => $data['meta'] ?? '',
            'is-small'   => $is_small,
            'is-warning' => $is_warning,
            'tooltip'    => $tooltip,
            'time'       => $this->isDateTime($data['time'] ?? ''),
        ];
        return $reData;
    }

    /**
     * 判断字符串是否是时间格式
     * @param $dateTime
     * @return false|int|null
     */
    private function isDateTime($dateTime)
    {
        $ret = strtotime($dateTime);
        return ($ret !== false && $ret != -1 && $ret > 0) ? date('Y-m-d H:i:s', $ret) : NULL;
    }


    public function count($db_name)
    {
        return DB::connection('mongodb')->collection($db_name)->where('utime', '>', $this->last_time)->count();
    }

}
