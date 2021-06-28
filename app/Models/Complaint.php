<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Complaint extends Model
{
    protected $table = 'complaints';
    protected $fillable = [
        'movie_id',
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
        $data['movie_id'] = Str::random(32);
        return static::create($data);
    }

}
