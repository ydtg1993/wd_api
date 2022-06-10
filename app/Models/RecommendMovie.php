<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendMovie extends Model
{
    protected $table = 'recommend_movie';

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'mid', 'id');
    }
}
