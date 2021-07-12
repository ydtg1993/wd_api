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
use App\Models\Report;
use App\Services\Logic\Comm\ConfLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ReportController extends BaseController
{

    protected $errCode   = 10003;

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveReport(Request $request)
    {
        try {

            $validator = Validator()->make($request->all(), [
                'reason'  => 'required|string',
                'title'  => 'required|string',
                'avid'  => 'required|string',
                'content' => 'required',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $data['uid'] = $request->userData['uid'];
            $data['u_number'] = $request->userData['nickname'];
            $saveData = array_merge($validator->validated(),$data);
            $ret = Report::saveReport($saveData);
            if($ret->id <= 0){
                throw new \Exception('save report error');
            }
            return $this->sendJson([]);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

}
