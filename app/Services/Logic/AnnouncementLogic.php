<?php
namespace App\Services\Logic;
use App\Models\Announcement;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;

class  AnnouncementLogic extends \App\Services\Logic\HandleLogic
{

    protected $redisPrefix = "announcement:";
    protected $redisTtl = 86400;
    protected $cachePage = 3;

    public function  getData( $condition )
    {
        $res = [
            'list'=>[],
            'count'=>0,
        ];
        $page = $condition['page'];
        $pageSize = 20;
        $skip = $pageSize*($page-1);
        $baseQuery = Announcement::when($condition['type'],function ($query,$data) {
            return $query->where('type', '=', $data);
        });
        $res['count'] = $baseQuery->count();
        if( $res['count']  <=0 ){
            return $res;
        }
        $cacheKey = $this->redisPrefix . 'type:' . $condition['type'].":page:".$condition['page'];
        $redis = Redis::connection();
        if($this->bollFromRedis( $page )) {
            $data = $redis->get($cacheKey);
            if(!empty($data)){
                $res['list'] = json_decode($data,true);
                return $res;
            }
        }

        $res['list'] = $baseQuery->skip($skip)->take($pageSize)->orderBy('id','desc')->get();
        if($baseQuery->get()->isEmpty()){
            return $res;
        }
        $res['list'] = $baseQuery->get()->toArray();
        $redis->setex(
            $cacheKey,$this->redisTtl,json_encode($res['list'],JSON_UNESCAPED_UNICODE)
        );
        return $res;
    }


    public function bollFromRedis( $page ): bool
    {
        if($page <= $this->cachePage){
            return true;
        }
        return false;
    }


}
