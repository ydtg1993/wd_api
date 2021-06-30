<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        if($cid == 0) {
            $commentObj->reply_uid = 0;
            $commentObj->type = 1;
        } else {
            $commentObjCid = MovieComment::find($cid);
            if(!$commentObjCid) {
                return false;
            }

            $commentObj->reply_uid = $commentObjCid->uid;
            $commentObj->type = 2;
        }
        $commentObj->save();

        //todo  清除影片的评论缓存 还需要补充
        return $commentObj->id;
    }
}
