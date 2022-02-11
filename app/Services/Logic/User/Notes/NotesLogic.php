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
    const FAND_NOTES = 4;//关注粉丝
    const LIKE_ACTOR_NOTES = 5;//收藏的演员
    const LIKE_DIRECTOR_NOTES = 6;//收藏的导演
    const LIKE_FILM_COMPANIES_NOTES = 7;//收藏的片商
    const LIKE_NUMBER_NOTES = 8;//收藏的番号
    const LIKE_SERIES_NOTES = 9;//收藏的系列
    const PIECE_LIST_NOTES = 10;//片单相关
    const LIKE_LABEL_NOTES = 11;//标签

    protected $namePath = 'App\\Services\\Logic\\User\\Notes\\';
    protected $baseClassName = 'BaseLogic';
    //类型执行
    protected $typeClass = [
        self::RECENTLY_VIEW_NOTES=>'RecentlyViewedLogic',
        self::WANT_SEE_NOTES=>'WantSeeLogic',
        self::SEEN_NOTES=>'SeenLogic',
        self::FAND_NOTES=>'Fans',
        self::LIKE_ACTOR_NOTES=>'LikeActor',
        self::LIKE_DIRECTOR_NOTES=>'LikeDirector',
        self::LIKE_FILM_COMPANIES_NOTES=>'LikeFilmCompanies',
        self::LIKE_NUMBER_NOTES=>'LikeNumber',
        self::LIKE_SERIES_NOTES=>'LikeSeries',
        self::PIECE_LIST_NOTES=>'PieceListLogic',
        self::LIKE_LABEL_NOTES=>'LikeLabel'
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