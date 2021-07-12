<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:25
 */

namespace App\Services\Logic\Home;


use App\Services\Logic\HandleLogic;

class HomeLogic extends HandleLogic
{
    protected $namePath = 'App\\Services\\Logic\\Home\\';



    //类型执行
    protected  $typeClass = array(
        1=>'PopularLogic',//热门
        2=>'AttentionLogic',//关注
        3=>'CategoryLogic',//类别-获取
    );

    /**
     * 获取主页数据
     * @param array $arg
     * @param int $type
     * @return array
     */
    public function getHomeData($arg = array(),$type = 1)
    {
        $runClassName = $this->getClassName($type);
        if(!class_exists($runClassName))
        {
            $this->errorInfo->setCode(500,'未知的操作对象');
            return [];
        }
        else
        {
            $dbObj = new  $runClassName;
            $reData = $dbObj->getData($arg);
            $this->errorInfo = $dbObj->getErrorInfo();
            return $reData;
        }
    }
}