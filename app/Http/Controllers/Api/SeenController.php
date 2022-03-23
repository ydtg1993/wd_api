<?php

namespace App\Http\Controllers\api;

use App\Models\Filter;
use App\Models\UserBlack;
use App\Models\UserClient;
use App\Models\Movie;
use App\Models\MovieComment;
use App\Models\MovieScoreNotes;
use App\Models\UserSeenMovie;
use App\Models\UserWantSeeMovie;
use App\Services\Logic\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;

class SeenController extends Controller
{
    /**
     * 添加看过操作
     */
    public function add(Request $request)
    {
        $uid = $request->userData['uid']??0;
        if($uid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效用户']);
        }

        $cache = 'Comment:verify:switch';
        $wangyiVerify = Redis::get($cache);
        if ($wangyiVerify == 1) {
            if (!Common::wangyiVerify()) {
                return Response::json(['code' => 500, 'msg' => '验证码错误']);
            }
        }

        $mid = $request->input('mid')??0;
        if($mid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效影片']);
        }

        $score =  $request->input('score')??0;
        if($score > 10)
        {
            return Response::json(['code'=>500,'msg'=>'无效评分']);
        }

        $comment = $request->input('comment')??'';
        if(strlen($comment) >= 500)
        {
            return Response::json(['code'=>500,'msg'=>'评论数据长度超过500']);
        }

        if($comment){
            $userInfoObj = new UserInfoLogic();
            $userInfo = $userInfoObj->getUserInfo($uid,false);
            if ($userInfo && isset($userInfo['status']) && $userInfo['status']>1){
                $ext = '禁言';
                $days = UserBlack::getBlackDay($uid,2);
                $msg = "您的账户已被".$ext.$days."天，请在解禁后评论";

                if($days>999)
                {
                    $msg = "您的账户已被永久".$ext."，请在解禁后评论";
                }
                return Response::json(['code'=>500,'msg'=>$msg]);
            }
        }

        $status = 1;

        //数据库操作
        $mdb = new UserSeenMovie();

        if($mdb->check($uid, $mid)>0)
        {
            return Response::json(['code'=>500,'msg'=>'数据已经存在，重复提交']);
        }

        $id = $mdb->edit($uid, $mid, $status,$score);
        $data = ['data'=>$id];

         //添加评分,0分不计算在评分内
        if($score>0)
        {
            $mScore = new MovieScoreNotes();
            $mScore->addNew($mid,$uid,$score);
        }

        //添加评论
        MovieComment::add($uid,$mid,$comment,$score);

        //更新用户看过数量
        $num_Seen = $mdb->total($uid);
        UserClient::where('id',$uid)->update(['seen_num' =>$num_Seen]);

        //删除想看
        $mdb = new UserWantSeeMovie();
        $id = $mdb->edit($uid, $mid, 2);

        //过滤词判断
        $warning = '';
        if(Filter::check($comment)==true)
        {
            $warning = '你发送的内容有敏感信息，官方审核后方可显示';
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','warning'=>$warning,'data'=>$data]);
    }

    /**
     * 读取看过的数据
     */
    public function info(Request $request)
    {
        $uid = $request->userData['uid']??0;
        if($uid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效用户']);
        }

        $mid = $request->input('mid')??0;
        if($mid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效影片']);
        }

        $data = array();
        //读取评分
        $mScore = new MovieScoreNotes();
        $info = $mScore->info($uid, $mid);
        if($info)
        {
            $data['score']=$info->score;
        }

        //读取评论
        $mComment = new MovieComment();
        $minfo = $mComment->info($uid,$mid);
        if($minfo)
        {
            $data['comment']=$minfo->comment;
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','data'=>$data]);
    }


    /**
     * 修改操作
     */
    public function edit(Request $request)
    {
        $uid = $request->userData['uid']??0;
        if($uid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效用户']);
        }

        $mid = $request->input('mid')??0;
        if($mid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效影片']);
        }

        $score =  $request->input('score')??0;
        if($score > 10)
        {
            return Response::json(['code'=>500,'msg'=>'无效评分']);
        }

        $comment = $request->input('comment')??'';
        if(strlen($comment) >= 500)
        {
            return Response::json(['code'=>500,'msg'=>'评论数据长度超过500']);
        }

        $userInfoObj = new UserInfoLogic();
        $userInfo = $userInfoObj->getUserInfo($uid,false);
        if ($userInfo['status']>1){
            $ext = '禁言';
            $days = UserBlack::getBlackDay($uid,2);
            $msg = "您的账户已被".$ext.$days."天，请在解禁后评论";

            if($days>999)
            {
                $msg = "您的账户已被永久".$ext."，请在解禁后评论";
            }
            return Response::json(['code'=>500,'msg'=>$msg]);
        }

        //数据库操作
        $mdb = new UserSeenMovie();
        $id = $mdb->edit($uid, $mid, 1,$score);
        $data = ['data'=>$id];

        //修改评分,0分不计算在评分内
        $mScore = new MovieScoreNotes();
        if($score >0)
        {
            $mScore->addNew($mid,$uid,$score);
        }else{
            $mScore->rm($mid,$uid);
        }

        //修改评论
        if(MovieComment::where('mid',$mid)->where('uid',$uid)
            ->where('status',1)
            ->where('cid',0)->exists()){
            MovieComment::edit($uid,$mid,$comment,$score);
        }else {
            MovieComment::add($uid, $mid, $comment, $score);
        }

        //更新用户看过数量
        $num_Seen = $mdb->total($uid);
        UserClient::where('id',$uid)->update(['seen_num' =>$num_Seen]);

        //删除想看
        $mdb = new UserWantSeeMovie();
        $id = $mdb->edit($uid, $mid, 2);

        //过滤词判断
        $warning = '';
        if(Filter::check($comment)==true)
        {
            $warning = '你发送的内容有敏感信息，官方审核后方可显示';
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','warning'=>$warning,'data'=>$data]);
    }

    /**
     * 删除操作
     */
    public function del(Request $request)
    {
        $uid = $request->userData['uid']??0;
        if($uid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效用户']);
        }

        $mid = $request->input('mid')??0;
        if($mid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效影片']);
        }

        $status = 2;


        //数据库操作
        $mdb = new UserSeenMovie();
        if($mdb->check($uid, $mid,2)>0)
        {
            return Response::json(['code'=>500,'msg'=>'已经删除成功']);
        }
        $mdb->edit($uid, $mid, $status,0);

        //删除积分
        $mScore = new MovieScoreNotes();
        $mScore->rm($mid,$uid);

        //删除评论
        MovieComment::rm($uid,$mid);

        //更新用户看过数量
        $num_Seen = $mdb->total($uid);
        UserClient::where('id',$uid)->update(['seen_num' =>$num_Seen]);

        return Response::json(['code'=>200,'msg'=>'操作成功']);
    }


    /**
     * 批量评论
     */
    public function batch(Request $request)
    {
        $number = $request->input('number');
        if(!$number){
            return Response::json(['code'=>500,'msg'=>'请填写番号']);
        }
        //根据番号查找
        $mid = 0;
        $mv = Movie::select('id')->where('number', $number)->first();
        if(isset($mv->id) && $mv->id)
        {
            $mid = $mv->id;
        }else{
            return Response::json(['code'=>500,'msg'=>'此番号不存在']);
        }

        //得到批量的内容
        $data =  $request->input('data');
        $list = json_decode($data,true);

        if(count($list)<1)
        {
            return Response::json(['code'=>500,'msg'=>'请填写至少一栏用户名和内容']);
        }

        //数据库操作
        $mdb = new UserSeenMovie();
        //遍历
        $chk = true;
        $msg = '输入内容错误';
        $warning = '';
        foreach($list as $k=>$v){
            //判断用户名
            $user = $this->chkUser($v['username'],$msg);
            if(count($user)<1)
            {
                $chk = false;
                break;
            }
            $v['uid'] = $user['uid'];
            $v['nickname'] = $user['nickname'];
            $v['avatar'] = $user['avatar'];
            $v['score'] = rand(7,10);

            //子回复
            if(isset($v['child']) && count($v['child'])>0){
                foreach($v['child'] as $k1=>$v1){
                    $user = $this->chkUser($v1['username'],$msg);
                    if(count($user)<1)
                    {
                        $chk = false;
                        break;
                    }
                    $v1['uid'] = $user['uid'];
                    $v1['nickname'] = $user['nickname'];
                    $v1['avatar'] = $user['avatar'];
                    $v1['score'] = rand(7,10);
                    if(Filter::check($v1['comment'])==true)
                    {
                        $warning = '你发送的内容有敏感信息，官方审核后方可显示';
                    }

                    $v['child'][$k1] = $v1;
                }
            }
            if(Filter::check($v['comment'])==true)
            {
                $warning = '你发送的内容有敏感信息，官方审核后方可显示';
            }
            if($mdb->check($v['uid'], $mid)>0)
            {
                return Response::json(['code'=>500,'msg'=>'用户['.$v['username'].']评论该影片数据已经存在，重复提交']);
            }
            $list[$k] = $v;
        }

        if($chk==false){
            return Response::json(['code'=>500,'msg'=>$msg]);
        }

        //数据库操作
        $mScore = new MovieScoreNotes();
        $tt = time();
        foreach($list as $v)
        {
            if($mdb->check($v['uid'], $mid)>0)
            {
                return Response::json(['code'=>500,'msg'=>'用户['.$v['username'].']评论该影片数据已经存在，重复提交']);
            }

            //生成随机事件
            $tt += rand(10,3600);
            $ctime = date('Y-m-d H:i:s',$tt);
            //更新看过记录
            $mdb->edit($v['uid'], $mid, 1,$v['score']);
            //添加积分
            $mScore->addNew($mid,$v['uid'],$v['score']);
            //添加评论
            $cid = MovieComment::add($v['uid'],$mid,$v['comment'],$v['score'],0);
            //更新评论为采集
            MovieComment::where('id',$cid)->update(['source_type'=>3,'nickname'=>$v['nickname'],'avatar'=>$v['avatar'],'comment_time'=>$ctime]);
            //更新用户看过数量
            $num_Seen = $mdb->total($v['uid']);
            UserClient::where('id',$v['uid'])->update(['seen_num' =>$num_Seen]);

            //子回复
            if(isset($v['child']) && count($v['child'])>0)
            {
                $tc = $tt;
                foreach($v['child'] as $v1)
                {
                    $ccid = MovieComment::add($v1['uid'],$mid,$v1['comment'],$v1['score'],$cid);
                    //更新评论为采集
                    $tc += rand(10,3600);
                    $ctime = date('Y-m-d H:i:s',$tc);
                    MovieComment::where('id',$ccid)->update(['source_type'=>3,'nickname'=>$v1['nickname'],'avatar'=>$v1['avatar'],'comment_time'=>$ctime]);
                }
            }
        }
        return Response::json(['code'=>200,'msg'=>'操作成功','warning'=>$warning,'data'=>1]);
    }


    /**
     * 读取判断用户状态
     * */
    private function chkUser($uName,&$msg)
    {
        //判断用户名
        if(!$uName){
            $msg = '请填写对应的用户名';
            return [];
        }
        $uc = UserClient::select('id','status','nickname','avatar')->where('nickname',$uName)->orWhere('phone',$uName)->orWhere('email',$uName)->first();
        if($uc && isset($uc->status))
        {
            $uid = $uc->id;
            $nickname = $uc->nickname;
            $avatar = $uc->avatar;

            if($uc->status>1){
                $ext = '禁言';
                $days = UserBlack::getBlackDay($uid,2);
                $msg = '您的账户['.$uName.']已被'.$ext.$days.'天，请在解禁后评论';

                if($days>999)
                {
                    $msg = '您的账户['.$uName.']已被永久'.$ext.'，请在解禁后评论';
                }
                return [];
            }
            return ['uid'=>$uid,'nickname'=>$nickname,'avatar'=>$avatar];
        }

        $msg = '用户['.$uName.']不存在';
        return [];
    }

}
