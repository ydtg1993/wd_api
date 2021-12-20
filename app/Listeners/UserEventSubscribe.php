<?php

namespace App\Listeners;

use App\Models\Notify;
use App\Models\UserClient;
use App\Models\UserClientEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
Use Exception;
use Illuminate\Support\Facades\Redis;
use PhpParser\Error;

class UserEventSubscribe
{


    public $cachePrefix='user_event:';


    /**
     * 收到喜欢
     * @param $event
     */
    public function OnUserLike( $event )
    {
        try {
            Log::info('UserEventSubscribe OnUserLike');
            DB::beginTransaction();
            $insert = [
                'sender_id' => $event->event['sender_id'],
                'sender_name' => $event->event['sender_name'],
                'sender_avatar' => $event->event['sender_avatar'],
                'type' => config('notify.type.like'),
                'target_type' => config('notify.target_type.comment'),
                'target_id' => $event->event['target_id'],
                'uid' => $event->event['uid'],
            ];
            $ret = Notify::create($insert);
            if (empty($ret)) {
                throw new Exception("插入notify失败".json_encode($insert));
            }
            //
            UserClientEvent::firstOrCreate(array('uid' => $event->event['uid']));
            UserClientEvent::firstOrCreate(array('uid' => $event->event['sender_id']));
            UserClientEvent::where('uid', $event->event['uid'])->increment('like', 1);
            UserClientEvent::where('uid', $event->event['sender_id'])->increment('my_like', 1);
            DB::commit();
            //caches
            $redis = Redis::connection();
            $redis->hincrby($this->cachePrefix.$event->event['uid'],'like',1);
            $redis->hincrby($this->cachePrefix.$event->event['sender_id'],'my_like',1);
            return true;
        }catch (Exception $e ){
            Log::error('UserEventSubscribe OnUserLike ERROR:'.$e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    /**
     * 收到的踩
     * @param $event
     */
    public function OnUserDislike( $event )
    {
        try {
            Log::info('UserEventSubscribe OnUserDislike');
            DB::beginTransaction();
            $insert = [
                'sender_id' => $event->event['sender_id'],
                'sender_name' => $event->event['sender_name'],
                'sender_avatar' => $event->event['sender_avatar'],
                'type' => config('notify.type.dislike'),
                'target_type' => config('notify.target_type.comment'),
                'target_id' => $event->event['target_id'],
                'uid' => $event->event['uid'],
            ];
            $ret = Notify::create($insert);
            if (empty($ret)) {
                throw new Exception("更新event失败");
            }
            UserClientEvent::firstOrCreate(array('uid' => $event->event['uid']));
            UserClientEvent::where('uid', $event->event['uid'])->increment('dislike', 1);
            DB::commit();
            $redis = Redis::connection();
            $redis->hincrby($this->cachePrefix.$event->event['uid'],'dislike',1);
            return true;
        }catch ( Exception $e){
            Log::error('UserEventSubscribe OnUserDislike ERROR:'.$e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    /**
     * 收到的举报
     * @param $event
     */
    public function OnUserReport( $event ){
        try {
            Log::info('UserEventSubscribe OnUserReport');
            //不通知举报
            DB::beginTransaction();
            UserClientEvent::firstOrCreate(array('uid' => $event->event['uid']));
            UserClientEvent::where('uid', $event->event['uid'])->increment('report', 1);
            DB::commit();
            $redis = Redis::connection();
            $redis->hincrby($this->cachePrefix.$event->event['uid'],'report',1);
            return true;
        }catch ( Exception $e){
            Log::error('UserEventSubscribe OnUserReport ERROR:'.$e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    /**
     * 回复
     * @param $event
     */
    public function OnUserReply( $event ){
        try {

            Log::info('UserEventSubscribe OnUserReply');
            DB::beginTransaction();
            $insert = [
                'sender_id' => $event->event['sender_id'],
                'sender_name' => $event->event['sender_name'],
                'sender_avatar' => $event->event['sender_avatar'],
                'target_type' => isset($event->event['target_type'])?:config('notify.target_type.comment'),
                'type' => config('notify.type.my_replay'),
                'target_id' => $event->event['target_id'],
                'target_source_id' => $event->event['target_source_id'],
                'uid' => $event->event['uid'],
            ];
            $ret = Notify::create($insert);
            if (empty($ret)) {
                throw new Exception("更新event失败".json_encode($insert));
            }
            UserClientEvent::firstOrCreate(array('uid' => $event->event['uid']));
            UserClientEvent::firstOrCreate(array('uid' => $event->event['sender_id']));
            UserClientEvent::where('uid', $event->event['uid'])->increment('reply', 1);
            UserClientEvent::where('uid', $event->event['sender_id'])->increment('my_reply', 1);
            DB::commit();
            $redis = Redis::connection();
            $redis->hincrby($this->cachePrefix.$event->event['uid'],'replay',1);
            $redis->hincrby($this->cachePrefix.$event->event['sender_id'],'my_reply',1);
            return true;
        }catch (Exception $e){
            Log::error('UserEventSubscribe OnUserReply ERROR:'.$e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    /**
     * 评论
     * @param $event
     */
    public function OnUserComment( $event ){
        try {
            Log::info('UserEventSubscribe OnUserComment');
            DB::beginTransaction();
            $insert = [
                'sender_id' => $event->event['uid'],
                'sender_name' => $event->event['sender_name'],
                'sender_avatar' => $event->event['sender_avatar'],
                'target_type' => config('notify.target_type.comment'),
                'target_id' => $event->event['target_id'],
                'target_source_id' => $event->event['target_source_id'],
                'type' => config('notify.type.my_comment'),
                'uid' => $event->event['uid'],
            ];
            $ret = Notify::create($insert);
            if (empty($ret)) {
                throw new Exception("更新event失败".json_encode($insert));
            }
            UserClientEvent::firstOrCreate(array('uid' => $event->event['uid']));
            UserClientEvent::where('uid', $event->event['uid'])->increment('my_comment', 1);
            DB::commit();
            $redis = Redis::connection();
            $redis->hincrby($this->cachePrefix.$event->event['uid'],'my_comment',1);
            return true;
        }catch (Exception $e){
            Log::error('UserEventSubscribe OnUserComment ERROR:'.$e->getMessage());
            DB::rollBack();
            return false;
        }
    }





    /**
     * 为事件订阅者注册事件监听器
     *
     * @param $event
     */
    public function subscribe($event)
    {
        $event->listen(
            'App\Events\UserEvent\UserLikeEvent',
            'App\Listeners\UserEventSubscribe@OnUserLike'
        );
        $event->listen(
            'App\Events\UserEvent\UserDislikeEvent',
            'App\Listeners\UserEventSubscribe@OnUserDislike'
        );
        $event->listen(
            'App\Events\UserEvent\UserReportEvent',
            'App\Listeners\UserEventSubscribe@OnUserReport'
        );
        $event->listen(
            'App\Events\UserEvent\UserReplyEvent',
            'App\Listeners\UserEventSubscribe@OnUserReply'
        );
        $event->listen(
            'App\Events\UserEvent\UserCommentEvent',
            'App\Listeners\UserEventSubscribe@OnUserComment'
        );
    }
}
