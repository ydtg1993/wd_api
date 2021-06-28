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
        'ydouban:frequency:limit'=>60,
    ],

    'test'=>[
        'test'=>rand(60,60*5),
    ]


];