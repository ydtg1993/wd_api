<?php
namespace App\Http\Controllers\Api;

use App\Services\Logic\Comm\ConfLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use App\Models\Article;
use App\Models\BaseConf;
use App\Models\ArticleTag;
use App\Models\Tags;
use App\Services\Logic\Common;
use Illuminate\Support\Facades\Redis;

class ArticleController extends BaseController
{

    /**
     * 读取列表 
     */
    public function getList(Request $request)
    {
        

        $ishot = intval($request->input('ishot'));
        $page = intval($request->input('page'));
        $pageSize = intval($request->input('pageSize'));
        if($pageSize<1){
            $page = 1;
            $pageSize=3;
        }

        $tid = intval($request->input('tid'));

        $list = [];

        //优先读取缓存
        $cache = "article:" . md5($ishot.$tid.'_') . ":".$page."_".$pageSize;
        $cache_nums = "article:" . md5($ishot.$tid.'_') . ":nums";

        $record = Redis::get($cache);
        if(!$record) {
            //获得该标签下的话题id
            if($tid>0){
                $list = ArticleTag::getlistsByTid($tid,($page-1)*$pageSize,$pageSize);
            }else{
                $list = Article::getLists($ishot,$page,$pageSize);   //列表
            }
            Redis::setex($cache, 7200, json_encode($list));

            $list = Common::objectToArray($list);
        }else{
            $list = json_decode($record, true);
        }

        $sum = Redis::get($cache_nums);
        if(!$sum) {
            //获得该标签下的话题id
            if($tid>0){
                $sum = ArticleTag::countByTid($tid);
            }else{
                $sum = Article::getTotal($ishot);
            }
            Redis::setex($cache_nums, 7200, $sum);
        }

        $httpUrl = Common::getImgDomain();
        $httpUrl = str_replace('resources','', $httpUrl);

        //处理图片url
        $aids = [];
        foreach($list as $key=>$val)
        {
            if(stristr($val['thumb'],"http")===false){
                //处理图片

                $val['thumb'] = $httpUrl.$val['thumb'];
            }
            
            $list[$key] = $val;
            $aids[] = $val['id'];
        }

        //读取标签
        $tagUsedAid = [];
        $tags = Article::tags($aids);
        foreach($tags as $v){
            $tmp = array();
            $tmp['id'] = $v->tag_id;
            $tmp['name'] = $v->name;
            $tagUsedAid[$v->article_id][]=$tmp;
        }

        //重新加工列表数据
        foreach($list as $key=>$val){
            $val['label'] = isset($tagUsedAid[$val['id']])?$tagUsedAid[$val['id']]:[];
            $list[$key] = $val;
        }

        $data = ['list'=>$list,'sum'=>$sum];

        if($ishot>0)
        {
            $space = 0 ;
            
            $cfg = BaseConf::select('val')->where('key','article_space')->first();
            if(isset($cfg->val))
            {
                $space = $cfg->val;
            }
            $data = ['list'=>$list,'sum'=>$sum,'space'=>$space];
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }

    /**
     * 相关的话题
     * 1，相同标签的
     * 2，最新更新的
     * 3，评论数最多的
     * */
    public function related(Request $request)
    {
        $aid = $request->input('id')??0;

        $list = [];

        //读取相同标签的1条数据
        $tags =ArticleTag::select('id')->where('article_id',$aid)->get();
        $tids = [];   //存储该话题所有的标签id
        foreach($tags as $v){
            //读取该话题使用的所有标签
            $tids[]=$v->id;
        }
        $taid = 0;   //相同标签的其他话题id
        if(count($tids)>0){
            $tags = ArticleTag::getListByTids(join($tids,','),$aid,1);
            if(isset($tags[0])){
                $list[] = $tags[0];
                $taid = $tags[0]->id;
            }
        }

        //读取最新的标签,如果相同标签不存在，这里多读一条
        $newNum = 2;
        if(count($list)==1){
            $newNum = 1;
        }
        $new = Article::select('id','title','description','click','thumb','link','ishot','sort')->where('id','<>',$aid)->where('id','<>',$taid)->orderBy('created_at','desc')->paginate($newNum)->items();
        $list = array_merge($list,$new);
        $tnid = 0;   //存储新话题id
        if(isset($list[0])){
            $taid = $list[0]->id;
        }
        if(isset($list[1])){
            $tnid = $list[1]->id;
        }

        //读取评论数最多的标签
        $hot = Article::select('id','title','description','click','thumb','link','ishot','sort')->where('id','<>',$aid)->where('id','<>',$taid)->where('id','<>',$tnid)->orderBy('comment_nums','desc')->paginate(1)->items();
        $list = array_merge($list,$hot);

        $httpUrl = Common::getImgDomain();
        $httpUrl = str_replace('resources','', $httpUrl);

        //处理图片url
        $aids = [];
        foreach($list as $key=>$val)
        {
            if(stristr($val->thumb,"http")===false){
                //处理图片

                $val->thumb = $httpUrl.$val->thumb;
            }
            
            $list[$key] = $val;
            $aids[] = $val->id;
        }

        //读取标签
        $tagUsedAid = [];
        $tags = Article::tags($aids);
        foreach($tags as $v){
            $tmp = array();
            $tmp['id'] = $v->tag_id;
            $tmp['name'] = $v->name;
            $tagUsedAid[$v->article_id][]=$tmp;
        }

        //重新加工列表数据
        foreach($list as $key=>$val){
            $val['label'] = isset($tagUsedAid[$val['id']])?$tagUsedAid[$val['id']]:[];
            $list[$key] = $val;
        }

        $data = ['list'=>$list,'sum'=>count($list)];
        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }

    /**
     * 读取详情页 
     */
    public function getInfo(Request $request)
    {
        $id = intval($request->input('id'));
        $info = Article::where('id',$id)->first();

        if(!isset($info->id)){
            return Response::json(['code'=>100,'msg'=>'话题不存在','data'=>[]]);
        }

        $httpUrl = Common::getImgDomain();
        $httpUrl = str_replace('resources','', $httpUrl);

        if(isset($info->thumb) && stristr($info->thumb,"http")===false){
            //处理图片

            $info->thumb = $httpUrl.$info->thumb;
        }

        //读取标签
        $info->label = ArticleTag::getListsByAid($id);

        //更新点击量计数器
        $rKey = md5($request->userData['ip'].':'.$request->userData['uid']);
        $chk = Redis::get($rKey);
        if(!$chk){
            Article::where('id',$id)->increment('click',1);
            Redis::setex($rKey, 3600*12, '1');
        } 

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$info]);
    }

    /**
     * 话题标签列表
     */
    public function tagsList(Request $request)
    {
        $pageSize = $request->input('pageSize')?intval($request->input('pageSize')):10;
        //读取标签列表
        $tags = Tags::select('id','name','sort')->orderBy('sort','asc')->paginate($pageSize);

        return Response::json(['code'=>200,'msg'=>'操作成功','list'=>$tags->items(),'sum'=>$tags->total()]);
    }

}
