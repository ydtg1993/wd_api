<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;

class ActorLog extends Model
{
    protected $table = 'actor_log';

    /**
     * 添加影片浏览记录
     */
    public static function addActorBrowse($aid)
    {
        if($aid <= 0)
        {
            return 0;
        }
        $movieObj = new ActorLog();
        $movieObj->aid = $aid;
        $movieObj->save();
        return $movieObj->id;
    }

}
