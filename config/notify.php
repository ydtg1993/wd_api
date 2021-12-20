<?php
return [
    /**消息类型入库配置***/
    //目标类型
    'target_type'=>[
        'comment'=>'comment',//评论+回复
    ],
//    //发送人类型
//    'sender_type'=>[
//        'user'=>'user',//用户
//        'admin'=>'admin'//管理员
//    ],
//    //消息类型
//    'type'=>[
//        'announce'=>'announce',//系统公告
//        'message'=>'message',//私信
//    ],
    //动作类型
    'type'=>[
        'like'=>1,//赞
        'dislike'=>2,//踩
        'my_comment'=>3,//我的评论
        'my_replay'=>4,//我的回复
        'focus'=>5,//关注
        'announce'=>99,//公告
    ],
    /**消息类型页面展示***/
    'display_front_template'=>[
        1=>'我的评论：',
        2=>'我的评论：',
        3=>'影片番号：',
        4=>'我的评论：',
        5=>'我的评论：',
        9=>'系统消息：',
        99=>'',
    ],
    //扩展订阅配置
    'subscription_config'=>[
    ],

];
