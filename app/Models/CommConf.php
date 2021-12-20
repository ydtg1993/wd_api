<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class CommConf extends Model
{
    protected $table = 'comm_conf';


        public static function getConfByType( $type ): array
        {
            return static::where('type','=',$type)->first()?static::where('type','=',$type)->select('type','values')->first()->toArray():[];
        }

    public static function getAllConf( ): array
    {
            $list = static::whereIn('type',[1,2,3,4,5,6,7])->select('type','values')->orderBy('type','asc')->get();
            if($list->isEmpty()){
                return [];
            }
            return $list->toArray();
    }

}
