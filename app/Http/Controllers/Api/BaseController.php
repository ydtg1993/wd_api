<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:11
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Logic\BaseError;
use App\Services\Logic\Common;

class BaseController extends Controller
{
    private static $codeInfo = array(
        200=>'success!',
        201=>'参数类型错误!',
        202=>'缺少必须的参数!',
    );

    /**
     * 参数过滤
     * @param array $temps 模板数据
     * @param array $param 过滤的参数
     * @return array|bool
     */
    public function paramFilter(array $temps,array $param)
    {
        return Common::paramFilter($temps,$param);
    }

    /**
     * 必填参数检测
     * @param array $temps
     * @param array $param
     * @return bool
     */
    public function haveToParam(array $temps,array $param)
    {
        return Common::haveToParam($temps,$param);
    }

    /**
     * @param $data
     * @param int $code
     * @param string $msg
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendJson($data,$code = 200,$msg = '',$warning='')
    {
        $reData  = [
            'code' => $code,
            'msg' => $msg == ''?(isset(BaseError::$msgCode[$code])?BaseError::$msgCode[$code]:'未知错误'):$msg,
            'warning'=>$warning,
            'data' =>  is_string($data)?$data:Common::objectToArray($data)
        ];
        //数据返回
        return response()->json($reData ?? [],200);
    }

    /**
     * @param string $msg
     * @param int $code
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendError($msg = '',$code = 500,$data = [])
    {
        $reData  = [
            'code' => $code,//控制状态
            'msg' => $msg == ''?(isset(BaseError::$msgCode[$code])?BaseError::$msgCode[$code]:'未知错误'):$msg,
            'data' => Common::objectToArray($data)
        ];
        //数据返回
        return response()->json($reData ?? [],200);
    }
}
