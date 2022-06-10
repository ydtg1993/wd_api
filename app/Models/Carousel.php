<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carousel extends Model
{
    protected $table = 'carousel';


    public function movie()
    {
        return $this->belongsTo(Movie::class, 'mid', 'id');
    }

//    public function comments()
//    {
//        return $this->belongsTo(UserLikeComment::class, 'id', 'cid');
//    }

}
