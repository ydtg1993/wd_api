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

class UserClient extends Model
{
    protected $table = 'user_client';

    const PHONE_VER_STATUS_YES = 1;
    const PHONE_VER_STATUS_NO = 2;

    const EMAIL_VER_STATUS_YES = 1;
    const EMAIL_VER_STATUS_NO = 2;

    const DEFAULT_USER_NAME = '小黄豆';

    /**
     * 读取用户列表
     * @param    string ids     回复的评论id列表
     * @param    string fields  需要读取的字段
     */
    public function getListByids($ids = [],$fields='id'){
        $str = join(',', $ids);
        $limit = count($ids);

        $res = DB::select('select '.$fields.' from '.$this->table.' where id in (?)  limit '.$limit.';',[$str]);

        return $res;
    }


    public function events()
    {
        return $this->belongsTo(UserClientEvent::class, 'id', 'uid');
    }

}
