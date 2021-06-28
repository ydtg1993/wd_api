<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserClient extends Model
{
    protected $table = 'user_client';

    const PHONE_VER_STATUS_YES = 1;
    const PHONE_VER_STATUS_NO = 2;

    const EMAIL_VER_STATUS_YES = 1;
    const EMAIL_VER_STATUS_NO = 2;

    const DEFAULT_USER_NAME = '小黄豆';

}
