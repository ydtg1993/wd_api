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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class ComplaintController extends BaseController
{

    protected $errCode   = 10002;

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveComplaint(Request $request)
    {
        try {

            $validator = Validator()->make($request->all(), [
                'topic'  => 'required|string',
                'avid'  => 'required|string',
                'title'  => 'required|string',
                'content' => 'required',
                'connect' => 'required|string',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }

            $data['device'] = $request->header('User-Agent');
            $saveData = array_merge($validator->validated(),$data);
            $ret = Complaint::saveComplaint($saveData);
            if($ret->id <= 0){
                throw new \Exception('save error');
            }
            return $this->sendJson($data);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

}
