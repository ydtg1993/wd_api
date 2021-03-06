<?php

namespace App\Models;

use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;

class MoviePieceList extends Model
{
    protected $table = 'movie_piece_list';

    /**
     * 格式化片商列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $cover = $data['cover']??'';
        $reData = [];
        $reData['id'] = $data['id']??0;
        $reData['uid'] = $data['uid']??'';
        $reData['name'] = $data['name']??'';

        $reData['cover'] = $cover == ''?'':Common::getImgDomain().$cover;
        $reData['movie_sum'] = $data['movie_sum']??0;
        $reData['like_sum'] = $data['like_sum']??0;
        $reData['pv_browse_sum'] = $data['pv_browse_sum']??0;

        $reData['intro'] = $data['intro']??0;
        $reData['is_hot'] = $data['is_hot']??1;
        $reData['authority'] = $data['authority']??1;
        $reData['type'] = $data['type']??1;
        $reData['save_cover'] = $cover;
        $reData['audit'] = $data['audit'];   //审核状态

        $reData['created_at'] = ($data['created_at']??'') == '' ? '':date('Y-m-d H:i;s',strtotime(($data['created_at']??'')));//格式化一下
        return $reData;
    }

    public function numbers()
    {
        return $this->hasMany(PieceListMovie::class,'plid','id');
    }
}
