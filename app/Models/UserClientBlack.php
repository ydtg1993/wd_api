<?php
//用户封禁表
namespace App\Models;

use App\Tools\RedisCache;
use App\Models\UserClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
class UserClientBlack extends Model
{
    protected $table = 'user_black';

    /**
     * @param   int     uid     用户id
     * @param   string  uname   冗余用户名
     * @param   string  phone   冗余手机号码
     * @param   string  email   冗余邮箱
     * @param   int     status  状态（1=解封，2=禁言；3=拉黑）
     * @param   int     unlockday   设置的自动解封的天数，比如1天，这里传入1
     * @param   string  remarks 封禁的原因
     * @return  int
     */
    public function lock( $uid, $uname,$phone,$email, $status, $unlockday,$remarks){

        $unlocktime = date("Y-m-d",strtotime("+1 day"));
        switch($unlockday)
        {
            case 1:
                $unlocktime = date("Y-m-d",strtotime("+1 day"));    //封一天
                break;
            case 3:
                $unlocktime = date("Y-m-d",strtotime("+3 day"));    //封三天
                break;
            case 7:
                $unlocktime = date("Y-m-d",strtotime("+1 week"));   //封一周
                break;
            case 30:
                $unlocktime = date("Y-m-d",strtotime("+1 month"));  //封一个月
                break;
            case 99999:
                $unlocktime = date("Y-m-d",strtotime("+100 year"));    //永久封闭
                break;
        }

        //写入数据表
        $da = ['uid'=>$uid, 
                'uname'=>$uname,
                'phone'=>$phone,
                'email'=>$email,
                'status'=>$status,
                'unlock_time'=> $unlocktime,
                'remarks' => $remarks
            ];

        $lid = DB::table($this->table)->insertGetId($da);

        return $lid;
    }

    /**
     * @param   int     uid     解封用户id
     */
    public function unlock($uid)
    {
        $d = ['status'=>1,
            'unlock_time'=>date("Y-m-d H:i:s")
        ];
        $model = DB::table($this->table)->where('uid',$uid)->where('status','>',1)->update($d);
        return;
    }

     /**
     * 判断数据是否已经存在 
     */
    public function check($uid=0)
    {
        $id = 0;
        $query = DB::table($this->table);
        $info = $query->where('uid',$uid)->where('status','>',1)->first();
        if(($info->id??0)>0)
        {
            $id = $info->id;
        }
        return $id;
    }

    /**
     * 批量解封 
     */
    public function unlockAll()
    {
        $d = ['status'=>1,
            'unlock_time'=>date("Y-m-d H:i:s")
        ];

        //修改用户表状态
        $muc = new UserClient();

        $lists = DB::table($this->table)->select('uid','id')->where('unlock_time','<=',date("Y-m-d H:i:s"))->where('status','>',1)->get();
        foreach ($lists as $v)
        {
            $muc->saveData($v->uid,['status'=>1]);
        }

        $res = DB::table($this->table)->where('unlock_time','<=',date("Y-m-d H:i:s"))->where('status','>',1)->update($d);

        return $res;
    }

}
