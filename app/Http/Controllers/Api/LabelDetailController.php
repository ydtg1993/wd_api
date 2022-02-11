<?php
namespace App\Http\Controllers\Api;

use App\Http\Requests\ComplaintRequest;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use App\Models\UserLikeLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LabelDetailController extends BaseController
{

    /**
     *
     * @param ComplaintRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $id = intval($request->input('id'));

            $label = MovieLabel::where('id',$id)->first();
            $data = MovieLabel::formatList($label);
            $data['is_like'] = 0;

            $uid = $request->userData['uid']??0;
            if($uid>0 &&
                UserLikeLabel::where(['uid'=>$uid,'lid'=>$request->input('id'),'status'=>1])->exists()){
                $data['is_like'] = 1;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '_' . $e->getFile() . '_' . $e->getLine());
            return $this->sendError($e->getMessage());
        }
        return $this->sendJson($data);
    }
}
