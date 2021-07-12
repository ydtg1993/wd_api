<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/22
 * Time: 15:01
 */

namespace App\Services\Logic\User;


use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieComment;
use App\Models\Notify;
use App\Models\UserLikeActor;
use App\Services\Logic\RedisCache;
use Illuminate\Support\Facades\Redis;

class NotifyLogic
{

    const COMMENT_PREFIX_KEY='movie_comment:';
    const MOVIE_PREFIX_KEY='movie:';
    const TYPE_COMMENT=3;
    const TYPE_REPLY=4;

    public $needSearchComment;
    public $needSearchMovie;
    /**
     * 批量删除
     * @param array $ids
     * @return bool
     */
    public function deleteOneOrBatch(array $ids,$uid){
        $count = Notify::whereIn('id',$ids)->where('uid',$uid)->count();
        if( $count <=0 ){
            return true;
        }
        $ret= Notify::whereIn('id',$ids)->where('uid',$uid)->delete();
        if($ret<=0){
            return false;
        }
        return true;
    }


    /**
     * 设置已读
     * @param $id
     * @return bool
     */
    public function setRead( $where ){
        $notify = Notify::where($where)->first();
        if(empty($notify) || !$notify->exists){
            return false;
        }
        $notify->is_read = 1;
        $ret = $notify->save();
        if($ret == false){
            return  false;
        }
        return true;
    }


    /**
     * 获取用户收藏演员列表
     * @param $data
     * @param bool $isCache
     * @return array|bool|null
     */
    public function getNotifyList($data,$isCache = false)
    {
        $res = [
            'list'=>[],
            'count'=>0,
        ];

        $uid = $data['uid']??0;
        if($uid <= 0)
        {
            return [];
        }

        $page = $data['page']??1;
        $pageSize = $data['pageSize']??10;

        $baseQuery = Notify::when($data['uid'],function ($query,$data) {
            return $query->where('uid', '=', $data);
        })->when(isset($data['type']),function ($query,$data) {
            return $query->where('type', '=', $data);
        })->when(isset($data['isRead']),function ($query,$data) {
            return $query->where('is_read', '=', $data);
        });
        $res['count'] = $baseQuery->count();
        if( $res['count']  <=0 ){
            return $res;
        }
        //list
        $ret = $baseQuery->orderBy('id','desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->select('id','content','target_id','target_source_id',
                'is_read','type','sender_id','sender_name','sender_avatar')
            ->get();
        if(empty($ret) || $ret->isEmpty()){
            return $res;
        }
        $notify = $ret->toArray();
        $this->generateEasyArray($notify);
        $allComment = $this->getCommentContent();
        $allMovie = $this->getMovieContent();

        $allAvatar = $this->getUserAvatar($ret->pluck('sender_id'));
        foreach ($notify as $k=>$v) {
            switch ($v['type']) {
                case self::TYPE_COMMENT://评价
                    $v['target_id'] ? $notify[$k]['content'][] = $allComment[$v['target_id']] : $notify[$k]['content'][] = '';
                    $v['target_source_id'] ? $notify[$k]['content'][] = config('notify.display_front_template')[$v['type']]
                        . $allMovie[$v['target_source_id']] : $allMovie[$k]['content'][] = '';
                    break;
                case self::TYPE_REPLY://我的回复
                    $v['target_id'] ? $notify[$k]['content'][] = $allComment[$v['target_id']] : $notify[$k]['content'][] = '';
                    $v['target_source_id'] ? $notify[$k]['content'][] = config('notify.display_front_template')[$v['type']]
                        . $allComment[$v['target_source_id']] : $allMovie[$k]['content'][] = '';
                    break;
                default:
                    $v['target_id'] ? $notify[$k]['content'][] = config('notify.display_front_template')[$v['type']] .
                        $allComment[$v['target_id']] : $notify[$k]['content'][] = '';
                    break;
            }
       }
       $res['list'] = $notify;
       return $res;
    }

    /**
     * @param $notify
     */
    public function generateEasyArray($notify){
        foreach ($notify as $k=>$v){
            switch ($v['type']){
                case 3://评价
                    $v['target_id']?$this->needSearchComment[] = [
                        'id'=>$v['target_id'],
                        'cacheKey'=>'movie_comment:'.$v['target_id'],
                    ]:true;
                    $v['target_source_id']?$this->needSearchMovie[]   = [
                        'id'=>$v['target_source_id'],
                        'cacheKey'=>'movie:'.$v['target_source_id'],
                    ]:true;
                    break;
                default:
                    $v['target_id']?$this->needSearchComment[] = [
                        'id'=>$v['target_id'],
                        'cacheKey'=>'movie_comment:'.$v['target_id'],
                    ]:true;
                    $v['target_source_id']?$this->needSearchComment[] = [
                        'id'=>$v['target_source_id'],
                        'cacheKey'=>'movie_comment:'.$v['target_source_id'],
                    ]:true;
                    break;
            }
        }
    }

    /**
     *
     * @return array|false
     */
    protected function getCommentContent(  ){
        $comment = $this->needSearchComment;
        $redis = Redis::connection();
        if(empty($comment)){
            return  true;
        }
        $allValueTmp = $allValue = array_column($comment,'id');
        //初始化
        $result = array_fill_keys($allValue,0);
        $allCacheKey = array_column($comment,'cacheKey');
        if(count($allCacheKey)<=0){
            return false;
        }
        $mkValue = $redis->mget($allCacheKey);
        $needDbKey = [];
         array_map(function ($cacheValue,$key) use(&$needDbKey,$allValue,&$result){
             $cacheValue?:$needDbKey[]= $key;
             $result[$key] = $cacheValue;
            return true;
        },$mkValue,$allValueTmp);
        //全部命中缓存
        if(count($needDbKey) <= 0){
            return $result;
        }
        $movieComment = MovieComment::whereIn('id',array_values($needDbKey))->select('comment','id')->get();
        if(!$movieComment || $movieComment->isEmpty()){
            return $result;
        }
        $movieComment = $movieComment->toArray();
        $mkSetRedis = [];
        array_map(function ($v) use(&$mkSetRedis,&$result){
            $v['comment']?$mkSetRedis[self::COMMENT_PREFIX_KEY.$v['id']]= $v['comment']:'';
            $result[$v['id']] = $v['comment'];
            return true;
        },$movieComment);
        $redis->mset($mkSetRedis);
        return $result;
    }

    protected function getMovieContent( ){
        $redis = Redis::connection();
        $comment = $this->needSearchMovie;
        if(empty($comment)){
            return  true;
        }
        $allValueTmp = $allValue = array_column($comment,'id');
        //初始化
        $result = array_fill_keys($allValue,0);
        $allCacheKey = array_column($comment,'cacheKey');
        if(count($allCacheKey)<=0){
            return false;
        }
        $mkValue = $redis->mget($allCacheKey);
        $needDbKey = [];
        array_map(function ($cacheValue,$key) use(&$needDbKey,$allValue,&$result){
            $cacheValue?:$needDbKey[]= $key;
            $result[$key] = $cacheValue;
            return true;
        },$mkValue,$allValueTmp);
        //全部命中缓存
        if(count($needDbKey) <= 0){
            return $result;
        }
        $movieComment = Movie::whereIn('id',array_values($needDbKey))->select('number','name','id')->get();
        if(!$movieComment || $movieComment->isEmpty()){
            return $result;
        }
        $movieComment = $movieComment->toArray();
        $mkSetRedis = [];
        array_map(function ($v) use(&$mkSetRedis,&$result){
            $v['number']?$mkSetRedis[self::MOVIE_PREFIX_KEY.$v['id']]= $v['number'].' '.$v['name']:'';
            $result[$v['id']] = $v['number'].' '.$v['name'];
            return true;
        },$movieComment);
        $redis->mset($mkSetRedis);
        return $result;
    }
    public function getUserAvatar( $uids ){

    }

}
