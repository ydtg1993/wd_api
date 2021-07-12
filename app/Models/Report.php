<?php

namespace App\Models;

use App\Events\UserEvent\UserReportEvent;
use App\Services\Logic\Movie\CommentActionLogic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    protected $table = 'reports';
    protected $fillable = [
        'uuid',
        'u_number',
        'uid',
        'avid',
        'reason',
        'content',
        'remark',
        'created_at',
        'updated_at',
    ];
    protected $guarded = ['id'];


    public static function saveReport( $data ){
        $data['uuid'] = Str::random(32);
        return static::create($data);
    }
    //统计
    public static function boot(){
        parent::boot();
        static::created(function ($model){
            CommentActionLogic::userReport([
                'owner_id'=>$model->uid
            ]);
        });
    }

}
