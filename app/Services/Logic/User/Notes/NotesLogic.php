<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/15
 * Time: 11:12
 */

namespace App\Services\Logic\User\Notes;


use App\Services\Logic\HandleLogic;

class NotesLogic extends HandleLogic
{
    const RECENTLY_VIEW_NOTES = 1;//浏览记录
    const WANT_SEE_NOTES = 2;//想看
    const SEEN_NOTES = 3;//看过

    protected $namePath = 'App\\Services\\Logic\\User\\Notes';
    protected $baseClassName = 'BaseLogic';
    //类型执行
    protected $typeClass = [
        self::RECENTLY_VIEW_NOTES=>'RecentlyViewedLogic',
        self::WANT_SEE_NOTES=>'WantSeeLogic',
        self::SEEN_NOTES=>'SeenLogic',


    ];

    /**
     * 添加咏鹅用户动作记录
     * @param $data
     * @param $type
     * @return array
     */
    public function addNotes($data,$type)
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
            $reData = $dbObj->addNotes($data);
            $this->errorInfo = $dbObj->getErrorInfo();
            return $reData;
        }
    }

    /**
     * 获取用户用户动作记录列表
     * @param $data
     * @param $type
     * @return array
     */
    public function getNotesList($data,$type,$isCache = true)
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
            $reData = $dbObj->getNotesList($data,$isCache);
            $this->errorInfo = $dbObj->getErrorInfo();
            return $reData;
        }
    }

}