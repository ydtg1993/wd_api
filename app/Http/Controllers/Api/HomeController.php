<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:24
 */

namespace App\Http\Controllers\Api;

use App\Services\Logic\Home\HomeLogic;
use Illuminate\Http\Request;
class HomeController extends BaseController
{


    public function index(Request $request)
    {
        $data = $request->input();
        $homeObj  = new HomeLogic();
        $reData = $homeObj->getHomeData($data,$data['type']??1);
        $errorInfo = $homeObj->getErrorInfo();
        if(($errorInfo->code??500) == 200)
        {
            return $this->sendJson($reData);
        }
        
        return (($errorInfo->code??500) == 200)?
            $this->sendJson($reData):
            $this->sendError(($errorInfo->msg??'未知错误'),($errorInfo->code??500));
    }
}