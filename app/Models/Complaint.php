<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Complaint extends Model
{
    protected $table = 'complaints';
    protected $fillable = [
        'avid',
        'topic',
        'title',
        'content',
        'device',
        'connect',
        'remark',
        'created_at',
        'updated_at',
    ];
    protected $guarded = ['id'];


    public static function saveComplaint( $data ){
        return static::create($data);
    }

}
