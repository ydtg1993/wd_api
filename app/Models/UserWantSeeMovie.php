<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 17:12
 */

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;

class UserWantSeeMovie  extends Model
{
    protected $table = 'user_want_see_movie';

    /**
     * 想看电影 
     */
    public function edit($uid=0, $mid=0, $status=0)
    {
        $query = DB::table($this->table);
        $info = $query->where('uid',$uid)->where('mid',$mid)->first();
        if(($info->id??0)>0)
        {
            //如果存在，更新一下状态
            $query->where('uid',$uid)->where('mid',$mid)->update(['status' =>$status]);
            $id = ($info->id??0);
        }else{
            $id = $query->insertGetId([
                'uid' => $uid,
                'mid' => $mid,
                'status' => $status
            ]);
        }

        //加权分，被点击一次想看，加1分；删除想看，减1分
        if($status == 1)
        {
            echo "add";
            Movie::weightAdd($mid,1);
        }else{
            Movie::weightLose($mid,1);
        }

        RedisCache::clearCacheManageAllKey('userWantSee',$uid);//清楚指定用户浏览的缓存

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
}
