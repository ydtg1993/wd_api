<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieFilmCompanies extends Model
{
    protected $table = 'movie_film_companies';

    /**
     * 格式化片商列表数据
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
