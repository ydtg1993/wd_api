<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/11
 * Time: 16:29
 */

namespace App\Services\Logic;


class BaseLogic
{
    protected $errorInfo = null;

    protected  $namePath = 'App\\Services\\Logic\\';
    protected  $className = 'BaseLogic';


    //ÀàÐÍÖ´ÐÐ
    protected  $typeClass = array(

    );

    public function __construct()
    {
        $this->errorInfo = new BaseError();
    }

    /**
     * @return null
     */
    public function getErrorInfo()
    {
        return $this->errorInfo == null ? (new BaseError()):$this->errorInfo;
    }

    protected  function getClassName($type)
    {
        return  isset(self::$typeClass[$type]) ? self::$namePath.self::$typeClass[$type] : self::$namePath.self::$className;
    }



}