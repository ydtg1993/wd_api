<?php
namespace App\Models;

//use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Article extends Model
{
    protected $table = 'articles';
    protected $cacheKey = 'articles';

    /**
     * 读取关键词列表
     */
    public static function getLists($ishot=0,$page=1,$pageSize=10)
    {
        $row = [];

        $wh=' where status=1 ';
        if($ishot>0){
            $wh = ' where ishot=1 and status=1 ';
        }

        $limit = ($page - 1 )*$pageSize;

        $sql = 'select id,title,description,thumb,link,ishot,sort,updated_at from articles '.$wh.' order by sort desc limit '.$limit.','.$pageSize.';';

        //读取数据库
        $row = DB::select($sql);
        
        return $row;
    }

    public static function getTotal($ishot=0)
    {
        $total=0;

        //读取数据库
        if($ishot>0){
            $total = self::where('status',1)->where('ishot',$ishot)->count();
        }else{
            $total = self::where('status',1)->count();
        }

        
        return intval($total);
    }

    //与标签多对多关联
    public static function tags($aids = [])
    {
        $res = [];

        if(count($aids)<1)
        {
            return $res;
        }

        $aid = join(',',$aids);
        $sql = 'select A.article_id,A.tag_id,T.name from article_tag A left join tags T on A.tag_id = T.id where A.article_id in('.$aid.') limit 100;';
        //读取数据库
        $row = DB::select($sql);

        return $row;
    }

}