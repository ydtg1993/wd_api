<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 14:08
 */

return [
    'is_cache'=>true,

    // 节点加KEY组成 节点主要用于缓存清除
    'common'=>[
        'ydouban:frequency:limit'=>60,//黄豆瓣 评率限制
        'ydouban:conf:all'=>60,//黄豆瓣所有配置
        'ydouban:conf:type'=>60,//黄豆瓣单个配置

        'userinfo:phone:verif:code:'=>60*30,//手机验证码
        'userinfo:email:verif:code:'=>60*30,//邮箱验证码
    ],

    'userinfo'=>[
        'userinfo:first:'=>60,
        'userinfo:search:log:'=>60,//影片搜索记录
    ],

    'userActionRecVie'=>[
        'recently:viewed:list:'=>60,//用户浏览列表
    ],

    'userWantSee'=>[
        'want:see:list:'=>60,//用户想看列表
    ],

    'userSeen'=>[
        'seen:list:'=>60,//用户看过列表
    ],

    'userLikeActor'=>[
        'like:actor:list:'=>60,//用户收藏演员列表
    ],

    'userLikeDirector'=>[
        'like:director:list:'=>60,//用户收藏导演列表
    ],

    'userLikeFilmCompanies'=>[
        'like:film:companies:list:'=>60,//用户收藏片商列表
    ],

    'userLikeSeries'=>[
        'like:series:list:'=>60,//用户收藏系列列表
    ],

    'userLikeNumber'=>[
        'like:number:list:'=>60,//用户收藏番号列表
    ],

    'userLikeUser'=>[
        'like:fans:list:'=>60,//用户粉丝列表
        'like:attention:list:'=>60,//用户关注列表
        'userinfo:first:attention'=>60,//用户单个关注信息
    ],

    'userPieceList'=>[
        'piece:user:list:c'=>60,//用户片单列表-创建
        'piece:user:list:l'=>60,//用户片单列表-收藏
        'piece:user:first'=>60,//获取片单详情
        'piece:user:movie:list:'=>60,//获取片单-影片列表
        'piece:list:'=>60,//片单列表
    ],

    'movie'=>[
        'movie:popular:list:'=>60,//热门影片列表
        'movie:category:list:'=>60,//类别影片列表
        'movie:attention:mid:list:'=>60,//关注影片ID列表
        'movie:attention:list:'=>60,//关注影片列表
        'movie:search:list:'=>60,//搜索影片列表
    ],

    'actor'=>[
        'movie:actor:list:'=>60,//演员列表
    ],

    'series'=>[
        'movie:series:list:'=>60,//系列列表
    ],

    'filmCompanies'=>[
        'movie:film:companies:list:'=>60,//片商列表
        ],

    'Rank'=>
    [
        'movie:rank:count:list:'=>60,//影片排行列表
        'actor:rank:count:list:'=>60,//演员排行列表
    ],





    'test'=>[
        'test'=>rand(60,60*5),
    ]


];