<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:35
 */

namespace App\Services\Logic\Home;


class PopularLogic extends HomeBaseLogic
{

    public function getData($arg = array())
    {
        /*
         * 网站首页，默认首屏。
1.1.1 热门-影片列表样式及字段
整个列表区域做可点击小抓手效果，点击跳转至影片详情页。
热门影片，只要符合影片排序规则的影片，不区分影片类别，即可以包
括全部类别（有码/无码/欧美/FC2）影片都可以在【首页-热门】页面展
示。
由此影片封面图尺寸高度会不一样，同一行的列表整体高度也因此会不
一样，采用瀑布流排版布局。
1.1.2 热门-列表展示规则
排序规则：首先按照影片「评分」分值排序，其次按照「评论」数量排
序，最后按照最新评论时间排序。
展示数量：系统根据排序规则筛选出前30条数据作为热门影片，网站首
页默认首屏展示。*/
        return [];
    }
}