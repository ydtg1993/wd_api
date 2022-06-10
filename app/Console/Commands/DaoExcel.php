<?php

namespace App\Console\Commands;

use App\Tools\RedisCache;
use App\Models\MovieCategory;
use App\Models\MovieSeries;
use App\Models\MovieDirector;
use App\Models\MovieFilmCompanies;
use App\Models\MovieLabel;
use App\Models\MovieActor;
use App\Models\Movie;
use App\Models\MovieNumbers;
use App\Tools\UserTool;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Console\Command;

class DaoExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:DaoExcel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试导入影片数据';

    protected $keyMovie     = 'moive_number';
    protected $keyCategory  = 'moive_category'; //影片分类缓存key
    protected $keyActor     = 'movie_actor';    //演员缓存key
    protected $keyDirector  = 'movie_director'; //导演缓存key
    protected $keyFilm      = 'movie_film';     //片商缓存key
    protected $keyLabel     = 'movie_label';    //标签缓存key
    protected $keySeries    = 'movie_series';   //系列缓存key
    protected $keyNum       = 'moive_number_group'; //影片番号组缓存key

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //读取数据
        $lineSize = 0;
        //得到总页数
        $allPage = ceil($lineSize / 50);

        $lists = $this->readExcel('./public/movie_demo.csv',1,$lineSize);

        $movie = new Movie();
        foreach($lists as $v)
        {
            $mName      = trim($v[0]);    //名称
            $mNumber    = strtoupper(trim($v[1]));    //番号
            $mCategory  = trim($v[2]);    //分类
            $mSeries    = trim($v[3]);    //系列
            $mSell      = trim($v[4]);    //卖家
            $mTime      = intval($v[5]);  //影片时长(秒)
            $mRelease   = trim($v[6]);    //发行时间
            $mDirector  = trim($v[7]);    //导演
            $mFilm      = trim($v[8]);    //片商
            $mDown      = trim($v[9]);    //可否下载
            $mSub       = trim($v[10]);   //是否包含字幕
            $mLabel     = trim($v[11]);   //标签
            $mActor     = trim($v[12]);   //演员

            //少于13列，直接跳过
            if(count($v) < 13)
            {
                echo '跳过数据不全：'.$mName.PHP_EOL;
                continue;
            }

            //判断名称和番号必填
            if(empty($mName) || empty($mNumber) || empty($mCategory))
            {
                echo '跳过缺失影片名称,番号,分类的数据:'.$mName.':'.$mNumber.PHP_EOL;
                continue;
            }

            //判断是否重复
            $mkey = base64_encode($mNumber);
            $mid = RedisCache::getSetScore($this->keyMovie,$mkey);
            if($mid)
            {
                echo '跳过重复数据:'.$mName.':'.$mNumber.PHP_EOL;
                continue;
            }
            

            //进行番号组处理
            $sGroup = UserTool::getNumberGroup($mNumber);

            if($sGroup)
            {
                //判断分类是否存在
                $nkey   = base64_encode($sGroup);
                $nId    = RedisCache::getSetScore($this->keyNum,$nkey);
                if($nId == false)
                {
                    $dbNum = MovieNumbers::select('id')->where('name',$sGroup)->first();
                    if($dbNum && isset($dbNum->id))
                    {
                        $nId = $dbNum->id; 
                    }else{
                        $nId = MovieNumbers::create($sGroup);
                        echo '新增番号组:'.$sGroup.PHP_EOL;
                    }
                }
            }

            //判断分类是否存在
            $ckey   = base64_encode($mCategory);
            $cid    = RedisCache::getSetScore($this->keyCategory,$ckey);
            if($cid == false)
            {
                $cid = MovieCategory::create($mCategory,1);
                echo '新增分类:'.$mCategory.PHP_EOL;
            }

            //判断系列是否存在
            $sid = 0;
            if(!empty($mSeries))
            {
                $skey   = base64_encode($mSeries);
                $sid    = RedisCache::getSetScore($this->keySeries,$skey);
                if($sid == false)
                {
                    $sid = MovieSeries::create($mSeries,1);
                    //插入关联表
                    DB::table('movie_series_category_associate')->insert(['series_id'=>$sid,'cid'=>1]);
                    echo '新增系列:'.$mSeries.PHP_EOL;
                }
            }

            //判断导演是否存在
            $did = 0;
            if(!empty($mDirector))
            {
                $dkey   = base64_encode($mDirector);
                $did    = RedisCache::getSetScore($this->keyDirector,$dkey);
                if($did == false)
                {
                    $did = MovieDirector::create($mDirector,1);
                    echo '新增导演:'.$mDirector.PHP_EOL;
                }
            }

            //判断片商是否存在
            $fid = 0;
            if(!empty($mFilm))
            {
                $fkey   = base64_encode($mFilm);
                $fid    = RedisCache::getSetScore($this->keyFilm,$fkey);
                if($fid == false)
                {
                    $fid = MovieFilmCompanies::create($mFilm,1);
                    //插入关联表
                    DB::table('movie_film_companies_category_associate')->insert(['film_companies_id'=>$fid,'cid'=>1]);
                    echo '新增片商:'.$mFilm.PHP_EOL;
                }
            }

            //判断标签是否存在，数组循环
            $arrLids = array();
            $arrLabels = explode(';',$mLabel);
            foreach($arrLabels as $v)
            {
                if(!empty(trim($v)))
                {
                    $lkey   = base64_encode(trim($v));
                    $lid    = RedisCache::getSetScore($this->keyLabel,$lkey);
                    if($lid == false)
                    {
                        $lid = MovieLabel::create($v,1,0);
                        echo '新增标签：'.$v.PHP_EOL;
                    }
                    //标签id数组
                    $arrLids[] = $lid;
                }
            }

            //判断演员是否存在，数组循环
            $arrAids = array();
            $arrActors = explode(';',$mActor);
            foreach($arrActors as $v)
            {
                if(!empty(trim($v)))
                {
                    $akey   = base64_encode(trim($v));
                    $aid    = RedisCache::getSetScore($this->keyActor,$akey);
                    if($aid == false)
                    {
                        $aid = MovieActor::create($v,1,'♀','','[]');
                        //插入关联表
                        DB::table('movie_actor_category_associate')->insert(['aid'=>$aid,'cid'=>1]);
                        echo '新增演员:'.$v.PHP_EOL;
                    }
                    //标签id数组
                    $arrAids[] = $aid;
                }
            }
            
            //写入影片数到数据库
            $timeRelease = strtotime($mRelease);

            $data = array();
            $data['name']           = $mName;
            $data['number']         = $mNumber;
            $data['release_time']   = date('Y-m-d H:i:s',$timeRelease);
            $data['score']          = 0;
            $data['time']           = $mTime;
            $data['sell']           = $mSell;
            $data['is_download']    = ($mDown == '是')? 2 : 1;
            $data['is_subtitle']    = ($mSub == '是')? 2 : 1;
            $data['is_hot']         = 1;

            $data['arrLabels']      = $arrLids;
            $data['arrActors']      = $arrAids;
            $data['director']       = $did;
            $data['series']         = $sid;
            $data['company']        = $fid;

            //填充默认数据
            $data['flux_linkage_num'] = 0;
            $data['flux_linkage'] = '[]';
            $data['big_cove'] = '';
            $data['small_cover'] = '';
            $data['trailer'] = '';
            $data['map'] = '[]';

            $res = $movie->create($data,$cid);
            if($res >0 ){
                echo '导入影片数据:'.$mName.PHP_EOL;
            }
        }

        echo '本次导入执行完成'.PHP_EOL;
    }

    /**
     * 分页读取文件行数，每次50行
     * @param   string  $filePath   文件目录
     * @param   int     $line       从多少行开始读取，默认第0行
     */
    private function readExcel($filePath,$line=0,&$lineSize)
    {
        $res = array();
        //将文档按行读入内存
        $f = file( $filePath);
        $lineSize = count($f);

        //得到文件总行数
        $max = ($line+50>count($f))?count($f):$line+50;

        //遍历读取50行
        for($i=$line;$i<$max;$i++)
        {
            //读取一行,防止填入全半角符号
            $t = str_replace('；',';',$f[$i]);
            //按照英文逗号，切割
            $a = explode(',',$t);
            $res[]=$a;
        }
        unset($f);
        return $res;
    }
}
