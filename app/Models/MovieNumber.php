<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovieNumber extends Model
{
    protected $table = 'movie_number';

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

    /**
     * 相似度查询
     */
    public static function getIdWithName($name)
    {
        $id = 0;
        $res = DB::select("select id from movie_number where name like ? limit 1",[$name.'%']);
        if($res && isset($res[0]) && isset($res[0]->id)){
            $id = $res[0]->id;
        }
        return $id;
    }

    /**
     * 根据id来获取数量
     */
    public static function getCountById($id)
    {
        $total = 0;
        $res = DB::select("select movie_sum from movie_number where id=? limit 1",[$id]);
        if($res && isset($res[0]) && isset($res[0]->movie_sum)){
            $total = $res[0]->movie_sum;
        }
        return $total;
    }

    public function numbers()
    {
        return $this->hasMany(MovieNumberAss::class,'nid','id');
    }
}
