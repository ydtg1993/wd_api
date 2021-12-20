<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Services\Logic\Comm\ConfLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use App\Models\Ads;
use App\Services\Logic\Common;

class AdsController extends BaseController
{

    /**
     * 读取域名列表 
     */
    public function getList(Request $request)
    {
        $ty = $request->input('type');

        $db = new Ads();
        $data = $db->lists($ty);

        $httpUrl = Common::getImgDomain();
        $httpUrl = str_replace('resources','', $httpUrl);

        //处理图片url
        foreach($data as $key=>$val)
        {
            if(stristr($val->photo,"http")===false){
                //处理图片

                $val->photo = $httpUrl.$val->photo;
            }
            
            $data[$key] = $val;
        }



        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }

}
