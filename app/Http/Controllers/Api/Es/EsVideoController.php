<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:18
 */

namespace App\Http\Controllers\Api\Es;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ComplaintRequest;
use App\Models\Es\VideoElasticquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class EsVideoController extends BaseController
{
    protected $logic;

    public function __construct()
    {
        $this->logic =  App::make(VideoElasticquentModel::class);
    }


    protected $errCode   = 10004;

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $validator = Validator()->make($request->all(), [
                'page'  => 'int',
                'pageSize'  => 'int',
                'keyword'  => 'required|string',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->getMessageBag()->all()[0]);
            }
            $param = $validator->validated();
            $this->logic->setFilerField($request->all());
            $res = $this->logic->getQuery($param['keyword'],$param['page']??1,$param['pageSize']??10);
            return $this->sendJson($res);
        }catch (\Exception $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            return $this->sendError($e->getMessage(),$this->errCode);
        }
    }



}
