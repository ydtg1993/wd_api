<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 19:08
 */

namespace App\Services\Logic\Comm;


use App\Models\CommConf;
use App\Services\Logic\RedisCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdInvestmentLogic extends BaseConfLogic
{
    protected $type = 1;

    public function saveConf($data)
    {
        $dataInfo = CommConf::where('type',$this->type)->first();
        $id = $dataInfo['id']??0;
        $saveData = [];
        $save_type = $data['display_type']??1;//1邮箱 2网址
        $saveData['type'] = $save_type;
        $saveData['save_val'] = ($save_type == 1) ?($data['save_email']??''):($data['save_url']??'');//1邮箱 2网址


        $dataObj = ($id <= 0)?(new CommConf()):CommConf::find($id);

        $dataObj->type = $this->type;
        $dataObj->values = json_encode($saveData);
        $dataObj->status = 1;
        $dataObj->save();
        return true;
    }

    public function getConf($isCache = true)
    {
        $dataInfo = RedisCache::getCacheData('common','ydouban:conf:type',function ()
        {
            $dataInfo = CommConf::where('type',$this->type)->first();
            $dataInfo = ($dataInfo ? ($dataInfo->toArray()):[]);
            return $this->resolveConf($dataInfo);
        },['type'=>$this->type],$isCache);
        return $dataInfo;
    }

    public function resolveConf($dataInfo)
    {
        $id = $dataInfo['id']??0;

        if($id <= 0)
        {
            $saveData = [];
            $saveData['save_type'] = 1;
            $saveData['save_email'] = '';
            $saveData['save_url'] = '';
            return $saveData;
        }
        $temp = json_decode($dataInfo['values'],true);

        $saveData = [];
        $saveData['save_email'] = '';
        $saveData['save_url'] = '';

        $tempType = ($temp['type']??1);
        ($tempType == 1)?($saveData['save_email']=$temp['save_val']??''):($saveData['save_url']=$temp['save_val']??'');
        $saveData['display_type'] = $tempType;
        $saveData['type'] = $this->type;
        return $saveData;

        return $data;
    }
}