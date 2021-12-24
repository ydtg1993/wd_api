<?php

namespace App\Models;

use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovieLabel extends Model
{
    protected $table = 'movie_label';

    public function listForCid($name= '',$offset=0,$limit=20)
    {
        $wh = 'L.cid = 0 and L.status=1';
        if($name){
            $wh = $wh." and L.name like '".$name."%' ";
        }
        $res = DB::select("select L.id,L.name,L.sort,GROUP_CONCAT(A.cid) as cids from ".$this->table." as L join movie_label_category_associate as A on L.id=A.lid where ".$wh." group by A.lid order by L.sort asc,L.id desc limit ".$offset.",".$limit.";");
        return $res;
    }
}
