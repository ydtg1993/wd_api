<?php

namespace App\Models;

use App\Services\Logic\Movie\CommentActionLogic;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class MovieComment extends Model
{
    protected $table = 'movie_comment';


    /***
     * 添加评论
     * @param $uid
     * @param $mid
     * @param $comment
     * @param int $cid
     */
    public static function add($uid,$mid,$comment,$cid = 0)
    {
        $commentObj = new MovieComment();
        $commentObj->mid = $mid;
        $commentObj->uid = $uid;
        $commentObj->cid = $cid;
        $commentObj->comment = $comment;
        $commentObj->status = 1;
        if($cid == 0)
        {
            $commentObj->reply_uid = 0;
            $commentObj->type = 1;
        }
        else
        {
            $commentObjCid = MovieComment::find($cid);
            if(!$commentObjCid)
            {
                return false;
            }

            $commentObj->reply_uid = $commentObjCid->uid;
            $commentObj->type = 2;
        }
        $commentObj->save();

        //todo  清除影片的评论缓存 还需要补充
        return $commentObj->id;

    }

    //通知
    public static function boot(){
        parent::boot();
        static::created(function ($model){
            if($model->uid <=0){
                return true;
            }
            $action= [
                'nickname'=>$model->nickname,
                'uid'=>$model->uid,
                'target_id'=>$model->id,
                'avatar'=>App::make(UserInfoLogic::class)->getUserInfo($model->uid)['avatar'],
            ];
            if($model->type=1) {//评价
                $action['owner_id'] = $model->uid;
                $action['target_source_id'] = $model->mid;
                CommentActionLogic::userComment($action);
            }else{//回复
                $action['owner_id'] = $model->reply_uid;
                $action['target_source_id'] = $model->cid;
                CommentActionLogic::userReply($action);
            }
        });
    }
}
