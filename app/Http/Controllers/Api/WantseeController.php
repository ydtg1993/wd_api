<?php

namespace App\Http\Controllers\api;

use App\Models\UserClient;
use App\Models\UserSeenMovie;
use App\Models\MovieComment;
use App\Models\MovieScoreNotes;
use App\Models\UserWantSeeMovie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class WantseeController extends Controller
{
    //添加想看操作
    public function add(Request $request)
    {
        $uid = $request->userData['uid'] ?? 0;
        if ($uid <= 0) {
            return Response::json(['code' => 500, 'msg' => '无效用户']);
        }

        $mid = $request->input('mid') ?? 0;
        if ($mid <= 0) {
            return Response::json(['code' => 500, 'msg' => '无效影片']);
        }
        $status = 1;

        //数据库操作
        $mdb = new UserWantSeeMovie();
        if ($mdb->check($uid, $mid, 1) > 0) {
            return Response::json(['code' => 500, 'msg' => '数据已经存在，重复提交']);
        }

        $id = $mdb->edit($uid, $mid, $status);
        $data = ['data' => $id];

        //删除看过
        $mdb = new UserSeenMovie();
        $mdb->edit($uid, $mid, 2, 0);

        //删除积分
        $mScore = new MovieScoreNotes();
        $mScore->rm($mid, $uid);

        //删除评论
        MovieComment::rm($uid, $mid);

        //更新用户看过数量
        $num_Seen = $mdb->total($uid);
        UserClient::where('id', $uid)->update(['seen_num' => $num_Seen]);

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }

    //删除想看操作
    public function del(Request $request)
    {
        $uid = $request->userData['uid'] ?? 0;
        if ($uid <= 0) {
            return Response::json(['code' => 500, 'msg' => '无效用户']);
        }

        $mid = $request->input('mid') ?? 0;
        if ($mid <= 0) {
            return Response::json(['code' => 500, 'msg' => '无效影片']);
        }
        $status = 2;

        //数据库操作
        $mdb = new UserWantSeeMovie();
        if ($mdb->check($uid, $mid, 2) > 0) {
            return Response::json(['code' => 500, 'msg' => '已经删除成功']);
        }
        $id = $mdb->edit($uid, $mid, $status);

        return Response::json(['code' => 200, 'msg' => '操作成功']);

    }
}
