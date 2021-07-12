<?php


namespace App\Http\Controllers\Api;



use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController  extends  BaseController
{


    public function search(Request $request){
        $param = $request->all();

        $page = $param['page'] ?? 1;
        $pageSize = $param['page_size'] ?? Movie::pagesize;

        //关键词
        //限制空
        $validator = Validator()->make($param, [
            'keyword' => 'required|max:64|min:1',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->getMessageBag()->all()[0];
            return $this->sendError($msg,400);
        }

        //根据关键词获取视频
        $page =  Movie::searchAPage($param['keyword'], $page,$pageSize);

        return $this->sendJson($page);

    }


}
