<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 14:17
 */

namespace App\Services\Logic;


class BaseError
{
    public $data = array();
    public $msg = '';
    public $code = 200;

    public static $msgCode = array(
        200 => '成功！',
        500 => '系统错误！',
    );

    public function getMsg($code)
    {
        return self::$msgCode[$code]??'未知错误';
    }

    public function setCode($code = 200,$msg = '')
    {
        $this->code = $code;
        $this->msg = ($msg == '') ? ($this->msgCode[$code]??'未知错误'):$msg;
        return $this;
    }
}