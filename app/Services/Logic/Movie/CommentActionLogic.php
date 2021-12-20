<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 16:56
 */

namespace App\Services\Logic\Movie;


use App\Events\UserEvent\UserCommentEvent;
use App\Events\UserEvent\UserDislikeEvent;
use App\Events\UserEvent\UserLikeEvent;
use App\Events\UserEvent\UserReplyEvent;
use App\Events\UserEvent\UserReportEvent;
use App\Models\MovieComment;
use App\Services\Logic\BaseLogic;
use App\Services\Logic\Common;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class CommentActionLogic extends BaseLogic
{
        const PREFIX_COMMENT_ACTION='movie_comment:action:';

    /**
     * 赞 踩
     * @param $data
     * @return array|bool|null
     */
    public static function  userAction( $data ){
        $isAction = static::checkUniqueAction($data['action'],$data['id'],$data['uid']);
        if($isAction){
            return -1;
        }
        MovieComment::where('id','=',$data['id'])->increment($data['action'],1);
        if($data['uid'] <=0 ){//过滤机器评论
            return true;
        }
        //通知
        $event = [
            'sender_id'=>$data['uid'],
            'sender_name'=>$data['nickname'],
            'sender_avatar'=>(strval(substr($data['avatar']
                    ,strlen(Common::getImgDomain()))))??'',
            'target_id'=>$data['target_id'],
            'uid'=>$data['owner_id'],
        ];
        $map = [
            'like'=>new UserLikeEvent($event),
            'dislike'=>new UserDislikeEvent($event),
        ];
        return event($map[$data['action']]);
    }

    /**
     * 回复
     * @param $data
     * @return array|null
     */
    public static function userReply( $data ){
        //通知
        $event = [
            'sender_id'=>$data['uid'],
            'sender_name'=>$data['nickname'],
            'sender_avatar'=>$data['avatar']??config('filesystems.avatar_path'),
            'target_id'=>$data['target_id'],
            'target_source_id'=>$data['target_source_id'],
            'uid'=>$data['owner_id'],
        ];
        return event(new UserReplyEvent($event));
    }

    /**
     * 评论
     * @param $data
     * @return array|null
     */
    public static function userComment( $data ){
        //通知
        $event = [
            'sender_id'=>$data['uid'],
            'sender_name'=>$data['nickname'],
            'sender_avatar'=>$data['avatar']??config('filesystems.avatar_path'),
            'target_id'=>$data['target_id'],
            'target_source_id'=>$data['target_source_id'],
            'uid'=>$data['owner_id'],
        ];
        return event(new UserCommentEvent($event));
    }

    /**
     * 举报
     * @param $data
     * @return array|null
     */
    public static function userReport( $data ){
        //通知
        $event = [
            'uid'=>$data['owner_id'],
        ];
        return event(new UserReportEvent($event));
    }



    /**
     * 唯一动作的校验
     * @param string $action
     * @param $id
     * @param $uid
     * @return bool
     */
    protected static function checkUniqueAction( $action ,$id,$uid){
        $redis = Redis::connection();
        $cacheKey = self::PREFIX_COMMENT_ACTION.$action.$id;
        $isAction = $redis->getbit($cacheKey,$uid);
        if($isAction){
            return true;
        }
        $redis->setbit($cacheKey,$uid,1);
        return false;
    }


    /**
     * 获取动作的校验
     * @param string $action
     * @param $id
     * @param $uid
     * @return bool
     */
    public static function getUniqueAction($action , $id, $uid){
        $redis = Redis::connection();
        $cacheKey = self::PREFIX_COMMENT_ACTION.$action.$id;
        $isAction = $redis->getbit($cacheKey,$uid);
        if($isAction){
            return 1;
        }
        return 0;
    }
}
