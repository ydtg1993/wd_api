<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieDirector extends Model
{
    protected $table = 'movie_director';

    /**
     * 格式化导演列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $reData = [];
        $reData['id'] = $data['id']??0;
        $reData['name'] = $data['name']??'';
        $reData['movie_sum'] = $data['movie_sum']??0;
        $reData['like_sum'] = $data['like_sum']??0;
        return $reData;
    }
}
