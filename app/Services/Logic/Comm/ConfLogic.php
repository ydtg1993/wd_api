<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:57
 */

namespace App\Services\Logic\Comm;


use App\Models\CommConf;
use App\Services\Logic\BaseError;
use App\Services\Logic\HandleLogic;
use App\Services\Logic\RedisCache;
use Illuminate\Support\Facades\Redis;

class ConfLogic extends HandleLogic
{


    protected $redisPrefix = "Conf:";
    protected $redisTtl = 86400;
    /*
         * 1.广告招商 2. 下载本站 3.关于我们 4.友情链接 5.隐私条款 6.磁链使用教程*/


    protected  $keyMap = [
        1=>'ad_investment',
        2=>'download_setting',
        3=>'about_us',
        4=>'friend_link',
        5=>'private_item',
        6=>'magnet_link',
        7=>'comment_notes',
    ];

    /**
     *
     * @param $type
     * @return array|array[]|mixed
     */
    public function getConfByType( $type ){
        $cacheKey = $this->redisPrefix.$this->keyMap[$type];

        $redis = Redis::connection();
        $data = $redis->get($cacheKey);
        if(!empty($data)){
            return json_decode($data,true);
        }
        $initData = [
            $this->keyMap[$type]=>$this->dataFormat($this->keyMap[$type])
        ];
        $data = CommConf::getConfByType($type);
        if(empty($data['values'])){
            return  $initData;
        }
        $values = json_decode($data['values'],true);
        foreach ($values as $k=>$v){
            if(is_array($k)){
                $initData[$this->keyMap[$type]] = $values;
                break;
            }
            $initData[$this->keyMap[$type]][$k] = $v;
        }
        $redis->set(
            $cacheKey,json_encode($initData),$this->redisTtl
        );
        return $initData;
    }


    public function dataFormat( $type='' ){
        $format = [
            'ad_investment'=>[
                'url'=>'',
                'email'=>'',
            ],
            'download_setting'=>[
                'url'=>'',
            ],
            'about_us'=>[
                'url'=>'',
                'content'=>'',
            ],
            'friend_link'=>[
                ['name'=>'','url'=>''],
                ['name'=>'','url'=>''],
                ['name'=>'','url'=>''],
            ],
            'private_item'=>[
                'url'=>'',
                'content'=>'',
            ],
            'magnet_link'=>[
                'url'=>'',
                'content'=>'',
            ],
            'comment_notes'=>[
                'isopen'=>0,
                'countdown'=>0,
                'content'=>'',
            ],
        ];
        return $type?$format[$type]:$format;
    }

    /**
     * @return array|mixed
     */
    public function getAllConf(){
        $cacheKey = $this->redisPrefix.'all';
        $redis = Redis::connection();
        $data = $redis->get($cacheKey);
        if(!empty($data)){
            return json_decode($data,true);
        }
        $initData = $this->dataFormat();
        $data = CommConf::getAllConf();
        if( count($data) <= 0 ){
            return [];
        }
        foreach ($data as $kc=>$vc){
            $value = json_decode($vc['values'],true);
            foreach ($initData[$this->keyMap[$vc['type']]] as $k=>$v){
//                pr($initData[$this->keyMap[$vc['type']]]);
//                pr($k);
                if(is_array($k)){
                    $initData[$this->keyMap[$vc['type']]] = $value;
                }
                if(isset($value[$k])) {
                    $initData[$this->keyMap[$vc['type']]][$k] = $value[$k];
                }
            }
        }
        $redis->set(
            $cacheKey,json_encode($initData),$this->redisTtl
        );
        return $initData;

    }






}
