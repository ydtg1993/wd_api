<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 16:56
 */

namespace App\Services\Logic\Movie;


use App\Models\Movie;

class MovieLogic
{

    /**
     * 格式化影片列表
     * @param array $data
     */
    public static function formatList($data = [])
    {
        return Movie::formatList($data);
    }
}