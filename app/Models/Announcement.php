<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Announcement extends Model
{
    protected $table = 'system_announcements';
    protected $guarded = ['id'];

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'content',
        'url',
        'remark',
        'display_type',
        'created_at',
        'updated_at',
    ];





}
