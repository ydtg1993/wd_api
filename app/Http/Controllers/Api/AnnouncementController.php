<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api;

use App\Services\Logic\AnnouncementLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends BaseController
{
    protected $logic;
    protected $errCode   = 10000;
    public function __construct()
    {
        $this->logic = App::make(AnnouncementLogic::class);
    }

    /**
     * @param Request $request
     */
    public function getAnnouncement(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'type' => 'required|in:1,2,3',
                'page' => 'required|int',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $data = $this->logic->getData($validator->validated());
            return $this->sendJson($data);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }

}
