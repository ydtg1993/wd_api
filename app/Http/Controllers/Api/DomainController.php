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
use App\Models\SysDomain;

class DomainController extends BaseController
{

    /**
     * 读取域名列表 
     */
    public function getDomain(Request $request)
    {
        $db = new SysDomain();
        $data = $db->lists();

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }

}
