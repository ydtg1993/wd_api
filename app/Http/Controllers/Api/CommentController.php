<?php

namespace App\Http\Controllers\api;

use App\Models\Filter;
use App\Models\UserBlack;
use App\Models\UserClient;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\UserWantSeeMovie;
use App\Services\Logic\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redis;

class CommentController extends Controller
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

        if(!Common::wangyiVerify()){
            return Response::json(['code'=>500,'msg'=>'验证码错误']);
        }

        $aid = $request->input('aid')??0;
        if($aid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效话题']);
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

        $rid = $request->input('reply_id')??0;
        $type = 1;
        $ruid = 0;
        $audit = 1;

        //读取被回复的评论
        if($rid>0){
            $type=2;

            $replay = ArticleComment::select('uid')->where('id',$rid)->first();
            if(isset($replay->uid)){
                $ruid = $replay->uid;
            }
        }

        //过滤词判断
        $warning = '';
        if(Filter::check($comment)==true)
        {
            $audit = 0;
            $warning = '你发送的内容有敏感信息，官方审核后方可显示';
        }

        //数据库操作
        $da = [
            'uid'=>$uid,
            'aid'=>$aid,
            'cid'=>$rid,
            'comment'=>$comment,
            'type'=>$type,
            'reply_uid'=>$ruid,
            'status'=>1,
            'audit'=>$audit
        ];

        if(ArticleComment::again($uid, $aid,$comment)>0)
        {
            return Response::json(['code'=>500,'msg'=>'数据已经存在，重复提交']);
        }

        //添加评论
        $id=ArticleComment::add($da);
        $da['id'] = $id;

        //更新评论计数器
        Article::where('id',$aid)->increment('comment_nums',1);

        return Response::json(['code'=>200,'msg'=>'操作成功','warning'=>$warning,'data'=>$da]);
    }


    /**
     * 赞和踩
     */
    public function dolike(Request $request)
    {
        $uid = $request->userData['uid']??0;
        if($uid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效用户']);
        }

        $acid = $request->input('acid')??0;
        if($acid <= 0)
        {
            return Response::json(['code'=>500,'msg'=>'无效评论']);
        }

        $rKey = 'dolink:'.$uid.':'.$acid;
        if(Redis::get($rKey)){
            return Response::json(['code'=>500,'msg'=>'重复操作']);
        }

        $ty = $request->input('ty')??'up';

        //更新数据库
        if($ty=='up'){
            //赞
            ArticleComment::where('id',$acid)->increment('like',1);
        }else if($ty=='down'){
            //踩
            ArticleComment::where('id',$acid)->increment('dislike',1);
        }

        //缓存
        Redis::setex($rKey,3600*24,md5($rKey));

        return Response::json(['code'=>200,'msg'=>'操作成功']);
    }

    /**
     * 评论列表
     */
    public function lists(Request $request)
    {
        $aid = intval($request->input('aid'));
        $page = intval($request->input('page'));
        $pageSize = intval($request->input('pageSize'));

        //读取评论列表第一层
        $comments = ArticleComment::where('aid',$aid)->where('cid',0)->where('status',1)->where('audit',1)->get();
        $sum = ArticleComment::where('aid',$aid)->where('cid',0)->where('status',1)->where('audit',1)->count();

        //遍历数据，获取需要子查询的数据
        $uids = [];   //需要查询的用户id
        $cids = [];   //需要查询的回复评论id
        foreach($comments as $v){
            $uids[] = $v->uid;
            $cids[] = $v->id;
        }

        //读取回复的评论数据(系统只支持第一层，不支持无限递归)
        if(count($cids)>0)
        {
            $childen = ArticleComment::where('aid',$aid)->whereIn('cid',$cids)->where('status',1)->where('audit',1)->where('type',2)->get();
            $replyComments = Common::objectToArray($childen);

            $replyArr = [];    //回复的列表
            foreach($replyComments as $v){
                $uids[] = $v['uid'];
                $replyArr[$v['cid']][] = $v;
            }
        }

        //读取用户数据
        $resUser = Common::objectToArray(UserClient::whereIn('id',array_unique($uids))->select('id','nickname','avatar')->get());
        $arrUser = [];    //用户数据
        foreach($resUser as $v){
            $arrUser[$v['id']] = $v;
        }

        //拼接最终数据
        $data = [];
        foreach($comments as $v){

            $nickname = isset($arrUser[$v->uid])?$arrUser[$v->uid]['nickname']:'';
            $v->user_client_nickname = $nickname;   //昵称
            $avatar = isset($arrUser[$v->uid])?$arrUser[$v->uid]['avatar']:'';
            $v->user_client_avatar = $avatar;    //头像

            //处理数据
            $struct = ArticleComment::struct($v);

            //读取回复
            if(isset($replyArr[$v->id])){
                foreach ($replyArr[$v->id] as $val) {
                    $reply = (object)$val;
                    $nickname = isset($arrUser[$reply->uid])?$arrUser[$reply->uid]['nickname']:'';
                    $reply->user_client_nickname = $nickname;   //昵称
                    $avatar = isset($arrUser[$reply->uid])?$arrUser[$reply->uid]['avatar']:'';
                    $reply->user_client_avatar = $avatar;    //头像

                    $action = [];
                    $struct['reply_comments'][] = array_merge(ArticleComment::struct($reply),$action);
                }
            }

            $data[] = $struct;
        }

        return Response::json(['code'=>200,'msg'=>'操作成功','sum'=>$sum,'list'=>$data]);
    }
}
