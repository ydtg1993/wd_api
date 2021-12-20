<?php

namespace App\Console\Commands;

use App\Models\HotWords;

use Illuminate\Console\Command;
use App\Services\Logic\RedisCache;
use Elasticsearch\ClientBuilder;

class TestCli extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:TestCli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用来命令行调试函数或者方法使用';

    protected $ES = null;

    protected $indexs = [
        'movie' => 'movie',
        'actor' => 'movie_actor',
        'series' => 'movie_series',
        'film' => 'movie_film_companies',
        'director' => 'movie_director',
        'number' => 'movie',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->ES = ClientBuilder::create()->build();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /* 获取热搜词 */
        /*$mdb = new HotWords();
        $res = $mdb->lists();

        print_r($res);*/

        /* 通过搜索引擎查 */
        $ty = 'number';
        $keyword = 'tushy';
        $isSub = 0;
        $isDown = 0;
        $isShort =0;


        $q = '';
        if($isSub){
            $q .= '{"match" : {"is_subtitle":'.$isSub.'}},';
        }
        if($isDown){
            $q .= '{"match" : {"is_download":'.$isDown.'}},';
        }
        if($isShort){
            $q .= '{"match" : {"is_short_comment":'.$isShort.'}},';
        }

        $json = '{
            "query" : {
                "bool" : {
                    "must" : [
                        {"match" : { "name" : "'.$keyword.'" }},'.$q.'
                        {"match" : { "status" : 1 }},
                        {"range" : { "id" : { "gt" : 0 } }}
                    ]
                }
            },
            "sort" :{
                "id":{"order":"asc"}
            }
        }';

        $params = [
            'index' => $this->indexs[$ty],      //索引名称
            'size'   => 5,                      //读取多少条
            'body'  => $json                    //查询条件
        ];

        print_r($params);

        $response = $this->ES->search($params);
        print_r($response);

        $res = $this->formatRes($response);
    }

    //格式化es返回的结果
    function formatRes($res)
    {
        $arr = [
            'sum' => 0,
            'lastid' => 0,
            'list' =>[]
        ];

        $hits = $res['hits'];
        if($hits)
        {
            //得到总记录数
            $arr['sum'] = $hits['total']['value'];
            
            foreach($hits['hits'] as $v){
                $arr['list'][]=$v['_source'];
                $arr['lastid'] = $v['_source']['id'];
            }
        }
        return $arr;
    }
}
