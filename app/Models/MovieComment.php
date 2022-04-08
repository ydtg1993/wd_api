<?php

namespace App\Models;

use App\Models\Filter;
use App\Services\Logic\Common;
use App\Services\Logic\Movie\CommentActionLogic;
use App\Services\Logic\RedisCache;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;

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
    public static function add($uid,$mid,$comment,$score = 0,$cid = 0)
    {
        $commentObj = new MovieComment();
        $commentObj->mid = $mid;
        $commentObj->uid = $uid;
        $commentObj->cid = $cid;
        $commentObj->score = $score;
        $commentObj->comment = $comment;
        $commentObj->audit = 1;

        //过滤词判断
        if(Filter::check($comment)==true)
        {
            $commentObj->audit = 0;
        }

        if($cid == 0)
        {
            $commentObj->reply_uid = 0;
            $commentObj->type = 1;

            //加权分，影片被评论一次，加1分
            Movie::weightAdd($mid,1);
        }
        else
        {
            $commentObjCid = MovieComment::find($cid);
            if(!$commentObjCid)
            {
                return false;
            }
            $user = App::make(UserInfoLogic::class)->getUserInfo($uid);
            if(!empty($user)) {
                $commentObj->nickname = $user['nickname'];
                $commentObj->avatar = strval(substr($user['avatar'],strlen(Common::getImgDomain())));
            }
            $commentObj->reply_uid = $commentObjCid->uid;
            $commentObj->type = 2;
        }
        $commentObj->save();

        //更新评论统计数据
        $commentNum = MovieComment::where('mid',$mid)->where('status',1)->count();
        Movie::where('id',$mid)->update([
            'comment_num' =>$commentNum,
            'is_short_comment'=>($commentNum<=0?1:2),
            'new_comment_time'=>date('Y-m-d H:i:s',time())
        ]);

        //todo  清除影片的评论缓存 还需要补充
        RedisCache::clearCacheManageAllKey('movie');
        return $commentObj->id;
    }

    /**
     * 修改评论
     */
    public static function edit($uid,$mid,$comment,$score = 0)
    {
        $audit = 1;
        //过滤词判断
        if(Filter::check($comment)==true)
        {
            $audit = 0;
        }

        MovieComment::where('mid',$mid)->where('uid',$uid)
                                        ->where('status',1)
                                        ->where('cid',0)
                                        ->update(['comment'=>$comment, 'score'=>$score,'audit'=>$audit]);
        //更新评论统计数据
        $commentNum = MovieComment::where('mid',$mid)->where('status',1)->count();
        Movie::where('id',$mid)->update([
            'comment_num' =>$commentNum,
            'is_short_comment'=>($commentNum<=0?1:2),
            'new_comment_time'=>date('Y-m-d H:i:s',time())
        ]);

        //todo  清除影片的评论缓存 还需要补充
        RedisCache::clearCacheManageAllKey('movie');
    }

    /**
     * 删除评论
     */
    public static function rm($uid,$mid)
    {
        MovieComment::where('mid',$mid)->where('uid',$uid)
                                        ->where('status',1)
                                        ->where('cid',0)
                                        ->update(['status'=>2]);
        //更新评论统计数据
        $commentNum = MovieComment::where('mid',$mid)->where('status',1)->count();
        Movie::where('id',$mid)->update([
            'comment_num' =>$commentNum,
            'is_short_comment'=>($commentNum<=0?1:2),
            'new_comment_time'=>date('Y-m-d H:i:s',time())
        ]);

        //加权分，删除评论，减1分
        Movie::weightLose($mid,1);

        //todo  清除影片的评论缓存 还需要补充
        RedisCache::clearCacheManageAllKey('movie');
    }

    /**
     * 读取数据
     */
    public function info($uid=0, $mid=0)
    {
        $query = DB::table($this->table);
        $info = $query->where('mid',$mid)->where('uid',$uid)->where('status',1)->first();
        return $info;
    }

    /**
     * 根据id读取
     */
    public static function infoById($id)
    {
        $query = DB::table('movie_comment');
        $info = $query->where('id',$id)->where('status',1)->first();
        return $info;
    }

    /**
     * 读取评论列表
     * @param    int    mid     影片id
     * @param    int    type    1=评论，2=回复
     * @param    array  ids     回复的评论id列表
     * @param    int    offset  分页
     * @param    int    limit   每页多少条
     * @param    string orderby 排序
     * @param    string fields  需要读取的字段
     */
    public function getLists($mid=0,$type=1,$ids=[],$offset=0,$limit=10,$orderby='id desc',$fields='id'){
        $wh = '';
        if($mid){
            $wh .= ' mid = '.$mid.' and ';
        }
        if($type){
            $wh .= ' type = '.$type.' and ';
        }
        if($ids){
            $cid = join(',', $ids);
            $wh .= ' cid in ('.$cid.') and ';
        }
        //$wh .= " comment<>'' and status = 1 and audit = 1 ";
        $wh .= " comment<>'' and status = 1 ";

        $res = DB::select('select '.$fields.' from '.$this->table.' where '.$wh.' order by '.$orderby.' limit '.$offset.','.$limit.';');

        return $res;
    }

    /**
     * 计算符合条件的数量
     * @param    int    mid     影片id
     * @param    int    type    1=评论，2=回复
     * @param    string ids     回复的评论id列表
     * @param    int    offset  分页
     * @param    string fields  需要读取的字段
     */
    public function getListCount($mid=0,$type=1,$ids=''){
        $wh = '';
        if($mid){
            $wh .= ' mid = '.$mid.' and ';
        }
        if($type){
            $wh .= ' type = '.$type.' and ';
        }
        if($ids){
            $wh .= ' cid in ('.$ids.') and ';
        }
        $wh .= " comment<>'' and status = 1 and audit = 1 ";

        $total = 0;
        $res = DB::select('select count(0) as nums from '.$this->table.' where '.$wh);
        if($res && isset($res[0]) && isset($res[0]->nums)){
            $total = $res[0]->nums;
        }

        return $total;
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
                'avatar'=>(strval(substr(App::make(UserInfoLogic::class)->getUserInfo($model->uid)['avatar']
                    ,strlen(Common::getImgDomain()))))??'',
            ];
            if($model->type == 1) {//评价
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

    public function replys(){
        return $this->hasMany(self::class,'cid','id');
    }

    public static function struct($comment){
        $struct = [
            'id'=>$comment->id,
            'comment'=>$comment->comment,
            'nickname'=>$comment->nickname,
            'like'=>$comment->like + $comment->m_like,
            'dislike'=>$comment->dislike + $comment->m_dislike,
            'avatar'=>$comment->avatar,
            'score'=>$comment->score,
            'type'=>$comment->type,
            'reply_uid'=>$comment->reply_uid,
            'comment_time'=>$comment->comment_time,
            'reply_comments'=>[]
        ];
        if($comment->source_type == 1){
            $struct['nickname'] = $comment->user_client_nickname??$comment->nickname;
            $struct['avatar'] = $comment->user_client_avatar??$comment->avatar;
        }
        $struct['uid'] = ($comment->source_type == 3)?-1:$comment->uid;
		$avatar = $struct['avatar'];
        $struct['avatar'] = (filter_var($avatar, FILTER_VALIDATE_URL) !== false)?$struct['avatar']:(($struct['avatar'] == '')?'':Common::getImgDomain().$struct['avatar']);
        return $struct;
    }
}
