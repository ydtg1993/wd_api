<?php

namespace App\Http\Controllers\Api;

use App\Models\HotWords;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;
use App\Services\Logic\Common;
use App\Models\UserSearchLog;
use App\Models\MovieNumber;
use App\Models\Movie;
use \Yurun\Util\Chinese;

class SearchController extends BaseController
{
    private $query = [];

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

        return Response::json(['code' => 200, 'msg' => 'code', 'data' => $data]);
    }

    //搜索功能
    public function search(Request $request)
    {
        $keyword = $request->input('search');
        $ty = $request->input('ty');  //movie=影片 actor=演员 series=系列 film=片商 director=导演 number=番号 piece=片单
        $pageSize = $request->input('pageSize');      //每次读取多少条
        $page = $request->input('page');          //当前页面
        $review = false;
        //兼容之前的接口 默认movie
        $ty = (!$ty) ? 'movie' : $ty;

        $this->queryInit();
        $this->queryCondition('must', ['match' => ['name' => $keyword]]);
        $this->queryCondition('filter', ['term' => ['status' => 1]]);

        //增加番号权重
        if ($ty == 'number') {
            $this->mappingNumber($request, $keyword, $data);
            $this->queryCondition('filter', ['term' => ['is_up' => 1]], true);
            if (!empty($data)) {
                //番号系列查询方式
                $this->recordUserSearch($request, $keyword, $ty);
                return Response::json(['code' => 200, 'msg' => 'code', 'data' => $data]);
            }
        }
        if ($ty == 'movie') {
            $this->queryCondition('filter', ['term' => ['is_up' => 1]], true);
            //重写sort
            $this->query['sort'] = [
                '_score' => ['order' => 'desc'],
                'pv' => ['order' => 'desc'],
                'id' => ['order' => 'desc']
            ];
            $data = ['page' => $page, 'size' => $pageSize];
            $this->mappingMovie($request, $keyword, $review);
        }

        if ($ty == 'piece') {
            $this->queryCondition('must', ['match' => ['keyword' => $keyword]]);
            $this->queryCondition('filter', ['term' => ['authority' => 1]], true);
            $this->queryCondition('filter', ['term' => ['audit' => 1]], true);
        }

        //请求es，得到结果
        $ES = ClientBuilder::create()->setHosts(config('elasticsearch.hosts'))->build();

        //先数据窗口扩容
        $ES->indices()->putSettings([
            'index' => $this->indexs[$ty],
            'body' => [
                'settings' => [
                    'max_result_window' => 1000000
                ]
            ]
        ]);

        if($review){
            $response = $ES->search([
                'index' => $this->indexs[$ty],      //索引名称
                'from' => 0,  //游标（性能不佳，不能读取大数据）
                'size' => 100,                //读取多少条
                'body' => json_encode($this->query, JSON_UNESCAPED_UNICODE)              //查询条件
            ]);
            $da = $this->formatRes($response);
            $list = [];
            preg_match_all("/^([a-zA-Z]+)([\-|\.]+)([0-9]*)/",strtolower($keyword),$preg);
            $key = current($preg[1]);
            foreach ($da['list'] as $d){
                if(preg_match("/{$key}/",$d['number'])){
                    $list[] = $d;
                }
            }
            $da['list'] = array_slice($list,($page - 1) * $pageSize,$pageSize);
            $da['sum'] = count($list);
            $this->recordUserSearch($request, $keyword, $ty);
            return Response::json(['code' => 200, 'msg' => 'code', 'data' => $da]);
        }

        $response = $ES->search([
            'index' => $this->indexs[$ty],      //索引名称
            'from' => ($page - 1) * $pageSize,  //游标（性能不佳，不能读取大数据）
            'size' => $pageSize,                //读取多少条
            'body' => json_encode($this->query, JSON_UNESCAPED_UNICODE)              //查询条件
        ]);

        $da = $this->formatRes($response);
        //给用户添加搜索记录
        $this->recordUserSearch($request, $keyword, $ty);
        return Response::json(['code' => 200, 'msg' => 'code', 'data' => $da]);
    }

    private function recordUserSearch($request, $keyword, $ty)
    {
        //给用户添加搜索记录
        $uid = 0;
        $token = $request->header('token');
        $tokenData = Common::parsingToken($token);//解析token
        if ($tokenData) {
            $uid = $tokenData['UserBase']['uid'];
        }
        $userSearchObj = new UserSearchLog();
        $userSearchObj->uid = $uid;
        $userSearchObj->content = $keyword;
        $userSearchObj->ty = $ty;
        $userSearchObj->ip = $request->getClientIp();
        $userSearchObj->status = 1;
        $userSearchObj->save();
    }

    private function mappingNumber($request, $keyword, &$data = [])
    {
        $this->queryCondition('must', ['match' => ['number' => $keyword]]);

        if (str_replace([' ', '.', '-', '_'], '', $keyword) != $keyword) {
            //找到标识符 单一番号搜索
            //加权分，番号被搜索一次加1分，必须是确切的番号
            $mv = Movie::select('id')->where('number', $keyword)->first();
            if (isset($mv->id) && $mv->id > 0) {
                Movie::weightAdd($mv->id, 1);
            }

            if ($request->input('is_subtitle')) {
                $this->queryCondition('filter', ['term' => ['is_subtitle' => $request->input('is_subtitle')]], true);
            }
            if ($request->input('is_download')) {
                $this->queryCondition('filter', ['term' => ['is_download' => $request->input('is_download')]], true);
            }
            if ($request->input('is_short_comment')) {
                $this->queryCondition('filter', ['term' => ['is_short_comment' => $request->input('is_short_comment')]], true);
            }
        } else {
            //番号系列查找 数据库模糊查询
            $numberId = MovieNumber::getIdWithName($keyword);
            $numberSum = 0;
            if ($numberId) {
                $numberSum = MovieNumber::getCountById($numberId);
            }

            $data['is_group'] = 1;
            $data['name'] = $keyword;
            $data['number_Id'] = $numberId;
            $data['sum'] = $numberSum;
        }
    }

    private function mappingMovie($request, $keyword, &$review = false)
    {
        $this->queryCondition('must', ['match' => ['name' => $keyword]]);
        if (preg_match("/[\u{3400}-\u{9FFF}]/", $keyword)) {
            $this->queryRemoveCondition('must');
            $this->queryCondition('must_not', ['term' => ['categoty_id' => 3]]);
            $this->queryCondition('should', ['match' => ['name' => ['query' => $keyword, 'boost' => 20]]]);
            $this->query['query']['bool']['minimum_should_match'] = 1;
            //含有中文时
            Chinese::setMode('JSON');
            $keyword2 = (string)current(Chinese::toTraditional($keyword));
            if ($keyword2 != "" && $keyword2 != $keyword) {
                $this->queryCondition('should', ['match' => ['name' => ['query' => $keyword2, 'boost' => 10]]], true);
            } else {
                $keyword2 = (string)current(Chinese::toSimplified($keyword));
                if ($keyword2 != "" && $keyword2 != $keyword) {
                    $this->queryCondition('should', ['match' => ['name' => ['query' => $keyword2, 'boost' => 10]]], true);
                } else {
                    $this->queryRemoveCondition('should');
                    $this->queryCondition('must', ['match' => ['name' => ['query' => $keyword]]]);
                }
            }
        } else if (preg_match("/^([a-zA-Z]+)([\-|\.]+)([0-9]*)|^([a-zA-Z]+)\s+([0-9]+)/", $keyword)) {
            $this->queryCondition('must', ['match' => ['number.nb' => strtolower($keyword)]]);
            $review = true;
        } else if (preg_match("/^[a-zA-Z]+/", $keyword)) {
            $this->queryRemoveCondition('must');
            $this->queryCondition('should', ['match' => ['name' => ['query' => $keyword, 'boost' => 10]]]);
            $this->queryCondition('should', ['match' => ['number.nb' => ['query' => strtolower($keyword), 'boost' => 20]]], true);
            $this->query['query']['bool']['minimum_should_match'] = 1;
        }

        if ($request->input('is_subtitle')) {
            $this->queryCondition('filter', ['term' => ['is_subtitle' => $request->input('is_subtitle')]], true);
        }
        if ($request->input('is_download')) {
            $this->queryCondition('filter', ['term' => ['is_download' => $request->input('is_download')]], true);
        }
        if ($request->input('is_short_comment')) {
            $this->queryCondition('filter', ['term' => ['is_short_comment' => $request->input('is_short_comment')]], true);
        }
    }

    private function queryInit()
    {
        $this->query = [
            'query' => [
                'bool' => []
            ],
            'sort' => [
                '_score' => ['order' => 'desc'],
                'id' => ['order' => 'desc']
            ]
        ];
    }

    private function queryCondition($pattern, $condition, $fill = false)
    {
        if ($fill) {
            $this->query['query']['bool'][$pattern][] = $condition;
        } else {
            $this->query['query']['bool'][$pattern] = [$condition];
        }
    }

    private function queryRemoveCondition($pattern)
    {
        unset($this->query['query']['bool'][$pattern]);
    }

    //格式化es返回的结果
    private function formatRes($res)
    {
        $arr = [
            'is_group' => 0,
            'sum' => 0,
            'lastid' => 0,
            'list' => []
        ];

        $hits = $res['hits'];
        if ($hits) {
            //得到总记录数
            $arr['sum'] = $hits['total']['value'] >100 ? 100:$hits['total']['value'];

            foreach ($hits['hits'] as $v) {

                //补全图片地址
                if (isset($v['_source'])) {
                    if (isset($v['_source']['small_cover']) && !empty($v['_source']['small_cover'])) {
                        $v['_source']['small_cover'] = Common::getImgDomain() . $v['_source']['small_cover'];
                    }
                    if (isset($v['_source']['big_cove']) && !empty($v['_source']['big_cove'])) {
                        $v['_source']['big_cove'] = Common::getImgDomain() . $v['_source']['big_cove'];
                    }
                    if (isset($v['_source']['photo']) && !empty($v['_source']['photo'])) {
                        $v['_source']['photo'] = Common::getImgDomain() . $v['_source']['photo'];
                    }
                    if (isset($v['_source']['trailer']) && !empty($v['_source']['trailer'])) {
                        $v['_source']['trailer'] = Common::getImgDomain() . $v['_source']['trailer'];
                    }
                    if (isset($v['_source']['cover']) && !empty($v['_source']['cover'])) {
                        $v['_source']['cover'] = Common::getImgDomain() . $v['_source']['cover'];
                    }
                    unset($v['_source']['flux_linkage']);
                    unset($v['_source']['map']);
                }

                $arr['list'][] = $v['_source'];
                $arr['lastid'] = $v['_source']['id'];
            }
        }
        return $arr;
    }
}
