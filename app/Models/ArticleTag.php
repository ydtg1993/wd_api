<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ArticleTag extends Model
{
    protected $table = 'article_tag';
    protected $cacheKey = 'article_tag';

    //通过话题id，读取标签列表
    public static function getListsByAid($aid){

      $q='select A.tag_id,T.name from article_tag A left join tags T on A.tag_id=T.id where A.article_id='.$aid.' limit 50;';  

      $res = DB::select($q);

      $row = [];
      if(count($res)>0){
        foreach($res as $v){
            $tmp=array();
            $tmp['id'] = $v->tag_id;
            $tmp['name'] = $v->name;

            $row[] = $tmp;
        }
      }

      return $row;
    }

    //通过一个tid读取列表,可分页
    public static function getlistsByTid($tid,$offset,$limit)
    {
      $q = 'select A.id,A.title,A.description,A.click,A.thumb,A.link,A.ishot,A.sort from articles A left join article_tag T on A.id=T.article_id where T.tag_id='.$tid.' and A.status = 1 order by A.sort asc limit '.$offset.','.$limit.';';

      $res = DB::select($q);

      return $res;
    }

    public static function countByTid($tid)
    {
      $total = 0;
      $q = 'select count(0) as nums from articles A left join article_tag T on A.id=T.article_id where T.tag_id='.$tid.' and A.status=1;';
      $res = DB::select($q);
      if(isset($res[0])){
        $total = $res[0]->nums;
      }

      return $total;
    }

    //通过多个tid来读取，指定的条数
    public static function getListByTids($tid,$aid,$limit)
    {
      $q = 'select A.id,A.title,A.description,A.click,A.thumb,A.link,A.ishot,A.sort from articles A left join article_tag T on A.id=T.article_id where T.tag_id in('.$tid.') and T.article_id<>'.$aid.' and A.status = 1 limit '.$limit.';';

      $res = DB::select($q);

      return $res;
    }
}