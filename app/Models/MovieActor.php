<?php

namespace App\Models;

use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;

class MovieActor extends Model
{
    protected $table = 'movie_actor';

    /**
     * 格式化演员列表数据
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $photo = $data['photo']??'';
        $reData = [];
        $reData['id'] = $data['id']??0;
        $reData['name'] = $data['name']??'';
        $reData['photo'] = $photo == ''?'':(Common::getImgDomain().$photo);
        $reData['sex'] = $data['sex']??1;
        $reData['social_accounts'] = json_decode($data['social_accounts']??'',true) ;
        $reData['movie_sum'] = $data['movie_sum']??0;
        $reData['like_sum'] = $data['like_sum']??0;
        return $reData;
    }

    public function names()
    {
        return $this->hasMany(MovieActorName::class,'aid','id');
    }
}
