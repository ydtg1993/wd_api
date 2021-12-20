<?php

namespace App\Http\Controllers\Api;

use App\Models\HotWords;
use App\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;
use App\Services\Logic\Common;
use App\Models\UserSearchLog;
use App\Models\MovieNumber;
use App\Models\Movie;

class SearchController extends BaseController
{
    protected $indexs = [
        'movie' => 'movie',
        'actor' => 'movie_actor',
        'series' => 'movie_series',
        'film' => 'movie_film_companies',
        'director' => 'movie_director',
        'number' => 'movie',
        'piece' => 'piece'
    ];

    //读取热门关键词
    public function hotword(Request $request)
    {
        //读取列表
        $mdb = new HotWords();
        $lists = $mdb->lists();
        $data = array();
        $data['sum'] = count($lists);
        $data['list'] = $lists;

        return Response::json(['code'=>200,'msg'=>'code','data'=>$data]);
    }

    //搜索功能
    public function search(Request $request)
    {
        $keyword    = $request->input('search');
        $ty         = $request->input('ty');  //movie=影片 actor=演员 series=系列 film=片商 director=导演 number=番号 piece=片单
        $isSub      = $request->input('is_subtitle');   //字幕
        $isDown     = $request->input('is_download');   //可下载
        $isShort    = $request->input('is_short_comment'); //短评
        $pageSize   = $request->input('pageSize');      //每次读取多少条
        $page       = $request->input('page');          //当前页面
        $lastId     = $request->input('lastid');        //读取列表最后的id，用于新分页

        //兼容之前的接口
        $ty = (!$ty)?'movie':$ty;
        $lastId = (!$lastId)?0:$lastId;
        if($ty!='movie' && $ty!='number')
        {
            $isSub = 0;
            $isDown = 0;
            $isShort = 0;
        }

        //是否番号分组搜索
        $isNumber = false;

        //名称查询条件查询
        $q = '{"match" : { "name" : "'.$keyword.'" }}';
        //名称和其他条件查询

        //增加番号权重
        if($ty=='number'){
            $q = '{"match" : {"number":"'.$keyword.'"}},{"term" : {"is_up":1}}';

            $filter=[' ','.','-'];
            foreach($filter as $v)
            {
                if(strpos($keyword,$v) !== false)
                {
                    $isNumber = true;
                    break;
                }
            }

            //加权分，番号被搜索一次加1分，必须是确切的番号
            $mv = Movie::select('id')->where('number',$keyword)->first();
            if(isset($mv->id) && $mv->id>0)
            {
                Movie::weightAdd($mv->id,1);
            }
        }
        if($ty=='movie'){
            $q.=',{"term" : {"is_up":1}}';
        }

        if($ty=='piece'){
            $q = '{"match" : {"keyword":"'.$keyword.'"}}
            ,{"term" : {"authority":1}}
            ,{"term" : {"audit":1}}';
        }

        if($isSub){
            $q .= '
            ,{"term" : {"is_subtitle":'.$isSub.'}}';
        }
        if($isDown){
            $q .= '
            ,{"term" : {"is_download":'.$isDown.'}}';
        }
        if($isShort){
            $q .= '
            ,{"term" : {"is_short_comment":'.$isShort.'}}';
        }

        //修改成根据相关性排序
        $json = '{
            "query" : {
                "bool": {
                    "must": [
                        { "term": { "status": 1 }},
                        '.$q.'
                    ]
                }
            },
            "sort" :[
                { "_score": { "order": "desc" }},
                { "id": { "order": "asc" }}
            ]
        }';

        $params = [
            'index' => $this->indexs[$ty],      //索引名称
            'size'   => $pageSize,              //读取多少条
            'body'  => $json                    //查询条件
        ];

        if($page){
            $params = [
                'index' => $this->indexs[$ty],      //索引名称
                'from' => ($page-1) * $pageSize,    //游标（性能不佳，不能读取大数据）
                'size'   => $pageSize,              //读取多少条
                'body'  => $json                    //查询条件
            ];
        }

        //请求es，得到结果
        $ES = ClientBuilder::create()->setHosts(config('elasticsearch.hosts'))->build();

        //先数据窗口扩容
        $maxParam = [
            'index' => $this->indexs[$ty],
            'body' => [
                'settings' => [
                    'max_result_window' => 1000000
                ]
            ]
        ];
        $ES->indices()->putSettings($maxParam);

        $response = $ES->search($params);

        $da = $this->formatRes($response);

        //番号重组数据
        if($ty=='number' && $isNumber==false)
        {
            //查询数据库，去获id
            $numberId = MovieNumber::getIdWithName($keyword);
            $numberSum =0;
            if($numberId){
                $numberSum = MovieNumber::getCountById($numberId);
            }

            $da['is_group'] = 1;
            $da['name'] = $keyword;
            $da['number_Id'] = $numberId;
            $da['sum'] = $numberSum;      
        }

        //给用户添加搜索记录
        $uid = 0;
        $token = $request->header('token');
        $tokenData = Common::parsingToken($token);//解析token
        if($tokenData)
        {
            $uid = $tokenData['UserBase']['uid'];
        }

        $userSearchObj = new UserSearchLog();
        $userSearchObj->uid = $uid;
        $userSearchObj->content = $keyword;
        $userSearchObj->ty = $ty;
        $userSearchObj->ip = $request->getClientIp();
        $userSearchObj->status = 1;
        $userSearchObj->save();

        return Response::json(['code'=>200,'msg'=>'code','data'=>$da]);
    }

    //格式化es返回的结果
    private function formatRes($res)
    {
        $arr = [
            'is_group' => 0,
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

                //补全图片地址
                if(isset($v['_source']))
                {
                    if(isset($v['_source']['small_cover']) && !empty($v['_source']['small_cover']))
                    {
                        $v['_source']['small_cover'] = Common::getImgDomain().$v['_source']['small_cover'];
                    }
                    if(isset($v['_source']['big_cove']) && !empty($v['_source']['big_cove']))
                    {
                        $v['_source']['big_cove'] = Common::getImgDomain().$v['_source']['big_cove'];
                    }
                    if(isset($v['_source']['photo']) && !empty($v['_source']['photo']))
                    {
                        $v['_source']['photo'] = Common::getImgDomain().$v['_source']['photo'];
                    }
                    if(isset($v['_source']['trailer']) && !empty($v['_source']['trailer']))
                    {
                        $v['_source']['trailer'] = Common::getImgDomain().$v['_source']['trailer'];
                    }
                    if(isset($v['_source']['cover']) && !empty($v['_source']['cover']))
                    {
                        $v['_source']['cover'] = Common::getImgDomain().$v['_source']['cover'];
                    }
                    unset($v['_source']['flux_linkage']);
                    unset($v['_source']['map']);
                }

                $arr['list'][]=$v['_source'];
                $arr['lastid'] = $v['_source']['id'];
            }
        }
        return $arr;
    }
}