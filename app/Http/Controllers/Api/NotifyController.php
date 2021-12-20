<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Http\Requests\ComplaintRequest;
use App\Models\Complaint;
use App\Services\Logic\Comm\ConfLogic;
use App\Services\Logic\User\NotifyLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class NotifyController extends BaseController
{
    protected $logic;

    public function __construct()
    {
        $this->logic =  App::make(NotifyLogic::class);
    }


    protected $errCode   = 10003;

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotifyList(Request $request)
    {
        try {

            $validator = Validator()->make($request->all(), [
                'type'  => 'int',
                'isRead'  => 'int|between:0,1',
                'page'  => 'int',
                'pageSize'  => 'int',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            //$where = array_merge($validator->validated(),['uid'=>$request->userData['uid']]);
            $where = array_merge($validator->validated(),['uid'=>$request->userData['uid']]);
            $res = $this->logic->getNotifyList($where);
            return $this->sendJson($res);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setRead(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'id'  => 'int',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $where = [
                ['id','=',$validator->validated()['id']],
                ['uid','=',$request->userData['uid']],
            ];
            $this->logic->setRead($where);
            return $this->sendJson([]);
        }catch (\Exception $e){
            Log::error('delete notify error:'.$e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request){
        try {
            $validator = Validator()->make($request->all(), [
                'ids'  => 'required|array',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $this->logic->deleteOneOrBatch($validator->validated()['ids'],$request->userData['uid']);
            return $this->sendJson([]);
        }catch (\Exception $e){
            Log::error('delete notify error:'.$e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

}
