<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Models\MovieLabel;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;

class LabelController extends BaseController
{
    private $cacheKey = 'label_list';
    private $cacheClassKey = 'label_classes';


    /**
     * 读取标签列表
     */
    public function getList(Request $request)
    {
        $data = array();

        //优先读取缓存
        $res = RedisCache::getZSetAll($this->cacheKey,10000);
        if($res){
            foreach($res as $v){
                $data[]=json_decode($v,true);
            }
        }else{
            $ML = new MovieLabel();
            $parents = $ML->listForCid('',0,10000);
            $childrens = MovieLabel::select('id','name','cid')->where('cid','>',0)->where('status',1)->orderBy('sort','asc')->orderBy('id','desc')->get();

            //遍历子标签，生成列表
            $min=array();
            foreach($childrens as $v)
            {
                $tmp = ['id'=>$v->id,'name'=>$v->name,'parent_id'=>$v->cid];
                $min[$v->cid][]=$tmp;
            }

            //遍历父标签，最终数据
            foreach($parents as $k=>$v)
            {   $tmp=array();
                $tmp['name'] = $v->name;
                $tmp['id'] = $v->id;
                $tmp['cids'] = $v->cids;
                $tmp['children'] = isset($min[$v->id])?$min[$v->id]:[];
                if(empty($tmp['children'])){
                    continue;
                }
                $data[] = $tmp;

                //写入缓存
                RedisCache::addZSets($this->cacheKey,$k,json_encode($tmp));
            }
        }

        //根据查询条件，筛选数据
        $cid = $request->input('cid');
        if($cid){
            foreach($data as $k=>$v)
            {
                $arrCids = explode(',',$v['cids']);
                if(in_array($cid,$arrCids)==false)
                {
                    //如果不包含此分类就移除
                    unset($data[$k]);
                }
            }
        }
        array_shift($data);

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>Common::objectToArray($data)]);
    }

    /**
     * 读取标签分类
     */
    public function getCategory(Request $request)
    {
        $data = array();

        //优先读取缓存
        $res = RedisCache::getZSetAll($this->cacheClassKey,100);
        if($res){
            foreach($res as $v){
                $data[]=json_decode($v);
            }
        }else{
            $lists = DB::table('movie_category')->select('id','name')->where(['status'=>1,'show'=>1])->orderBy('sort','asc')->orderBy('id','asc')->get();
            //遍历生成数据
            foreach($lists as $k=>$v)
            {
                $tmp = ['id'=>$v->id,'name'=>$v->name];
                $data[]=$tmp;

                //写入缓存
                RedisCache::addZSets($this->cacheClassKey,$k,json_encode($tmp));
            }
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>Common::objectToArray($data)]);
    }
}
