<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:25
 */

namespace App\Services\Logic\Home;


use App\Services\Logic\BaseLogic;

class HomeLogic extends BaseLogic
{
    protected static $namePath = 'App\\Services\\Logic\\Home\\';



    //类型执行
    protected static $typeClass = array(
        1=>'PopularLogic',//热门
        2=>'AttentionLogic',//关注
        3=>'CategoryLogic',//类别-无码
        4=>'CategoryLogic',//类别-有码
        5=>'CategoryLogic',//类别-欧美
        6=>'CategoryLogic',//类别-FC2

    );

    /**
     * 获取主页数据
     * @param array $arg
     * @param int $type
     * @return array
     */
    public function getHomeData($arg = array(),$type = 1)
    {
        $runClassName = self::getClassName($type);
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