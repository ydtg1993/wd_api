<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserSeenMovie extends Model
{
    protected $table = 'user_seen_movie';

    /**
     * 用户看过操作 
     */
    public function edit($uid=0, $mid=0, $status=0, $score=0)
    {
        $query = DB::table($this->table);
        $info = $query->where('uid',$uid)->where('mid',$mid)->first();
        if(($info->id??0)>0)
        {
            //如果存在，更新一下状态和评分
            $query->where('uid',$uid)->where('mid',$mid)->update(['status' =>$status,'score'=>$score]);
            $id = ($info->id??0);
        }else{
            $id = $query->insertGetId([
                'uid' => $uid,
                'mid' => $mid,
                'status' => $status,
                'score' => $score
            ]);
        }

        //加权分，被点击一次想看，加1分；删除想看，减1分
        if($status == 1)
        {
            Movie::weightAdd($mid,1);
        }else{
            Movie::weightLose($mid,1);
        }

        RedisCache::clearCacheManageAllKey('userSeen',$uid);//清楚指定用户浏览的缓存

        return $id;
    }

    /**
     * 判断数据是否已经存在 
     */
    public function check($uid=0, $mid=0,$status=1)
    {
        $id = 0;
        $query = DB::table($this->table);
        $info = $query->where('uid',$uid)->where('mid',$mid)->where('status',$status)->first();
        if(($info->id??0)>0)
        {
            $id = $info->id;
        }
        return $id;
    }

    /**
     * 获取用户看过的影片数量 
     */
    public function total($uid)
    {
        return DB::table($this->table)->where('uid',$uid)->where('status',1)->count();
    }
}