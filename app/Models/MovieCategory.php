<?php

namespace App\Models;

use App\Tools\RedisCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class MovieCategory extends Model
{
    protected $table = 'movie_category';

    /**
     * 写入一条数据
     * @param   string  $name   名称
     */
    public static function create($name = '',$status = 1)
    {
        //写入数据表
        $da = ['name'=>$name , 'status'=>$status];
        $lid = DB::table('movie_category')->insertGetId($da);

        if($lid > 0)
        {
            //写入缓存
            $text = base64_encode(trim($name));
            RedisCache::addSet('moive_category',$lid,$text);
        }

        return $lid;
    }

    public static function getName($cid = 0)
    {
        $res = '';
        $row = self::select('name')->where('id',$cid)->first();
        if(isset($row) && isset($row->name))
        {
            $res = $row->name;
        }
        
        return $res;
    }
}
