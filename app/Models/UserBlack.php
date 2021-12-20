<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class UserBlack extends Model
{
    protected $table = 'user_black';

    /**
     * 得到封禁的天数
     * param   int  @uid    用户名
     * param   int  @status 2=禁言； 3=拉黑
    */
    public static function getBlackDay($uid,$status = 2)
    {
        $days = 0;
        $info = DB::table('user_black')->where('uid',$uid)->where('status',$status)->first();
        if($info){
            $start = strtotime($info->unlock_time);
            $end = time();

            $days = ceil(($start - $end)/86400);
        }
        return $days;
    }

}
