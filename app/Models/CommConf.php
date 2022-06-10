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
    const NOTES = 7;  // 短评须知
    const UNDERAGE = 8;  // 未成年提示
    const SHARE = 9;  // app分享
    const SWITCH = 10;  // app分享

    const COMMENT_SWITCH_KEY = 'Comment:verify:switch';

    protected $table = 'comm_conf';


        public static function getConfByType( $type ): array
        {
            return static::where('type','=',$type)->first()?static::where('type','=',$type)->select('type','values')->first()->toArray():[];
        }

    public static function getAllConf( ): array
    {
            $list = static::select('type','values')->orderBy('type','asc')->get();
            if($list->isEmpty()){
                return [];
            }
            return $list->toArray();
    }

}
