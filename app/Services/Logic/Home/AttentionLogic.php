<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:38
 */

namespace App\Services\Logic\Home;


class AttentionLogic extends HomeBaseLogic
{

    public function getData($arg = array())
    {
        //先判断是否登录- 登录继续

        //逻辑实在太复杂了作为首页数据不太推荐正在让产品重新整理新的需求

        //读取用户收藏演员

        //读取用户收藏导演

        //读取用户收藏系列

        //读取用户收藏片商

        //读取用户收藏番号

        //读取用户收藏片单

        //读取想看得影片

        //读取看过的影片

        //联合查询 影片表  演员关联表 导演关联表 系列关联表 片商关联表 番号关联表 片单关联表 想看数据筛选  看过数据筛选

        //写两套  一套按需求写  一套 根据影片关注的量进行排序【以防万一 到时不行可以直接切换 .env预留代码开关】

        return [];
    }
}