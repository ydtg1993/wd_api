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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;


class ConfController extends BaseController
{
    protected $confLogic ;
    protected $errCode   = 10001;

    public function __construct()
    {
        $this->confLogic =  App::make(ConfLogic::class);
    }
    /**
     * @param Request $request
     */
    public function getAllConf(Request $request)
    {
        try {
            $data = $this->confLogic->getAllConf($request);
            return $this->sendJson($data);
        }catch (\Exception $e){
            Log::error('conf error:'.$e->getMessage());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }
    /**
     * @param Request $request
     */
    public function getOneConf(Request $request, $type)
    {

        try {
            $validator = Validator()->make(['type'=>$type], [
                'type' => 'required|int|min:1|max:7',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $data = $this->confLogic->getConfByType($type);
            return $this->sendJson($data);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

}
