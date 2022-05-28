<?php
namespace App\Models;

use App\Tools\RedisCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AdsList extends Model
{
    protected $table = 'ads_list';

    /**
     * 写入数据
     */
    public function add($da=array())
    {
        if($da){
            DB::table($this->table)->insertGetId($da);
        }
    }

    /**
     * 修改数据
     */
    public function edit($da=array(),$id=0)
    {
        if($da){
            DB::table($this->table)->where('id',$id)->update($da);
        }
    }

    /**
     * 删除
     */
    public function del($id)
    {
        //清除数据
        DB::delete('delete from '.$this->table.' where id=? limit 1;',[$id]);
    }
}
