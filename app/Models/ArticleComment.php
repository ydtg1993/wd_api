<?php
namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\Logic\Common;

class ArticleComment extends Model
{
    protected $table = 'article_comment';

    /**
     * 评论
     */
    public static function add($data)
    {
        $id = self::insertGetId($data);

        return $id;
    }

    /**
     * 判断数据是否已经存在
     */
    public static function again($uid,$aid,$comment)
    {
        $id = 0 ;
        $chk = self::select('id')->where('uid',$uid)->where('aid',$aid)->where('comment',$comment)->first();
        if(isset($chk->id))
        {
            $id = $chk->id;
        }
        return $id;
    }

    public static function struct($comment){
        $struct = [
            'id'=>$comment->id,
            'comment'=>$comment->comment,
            'nickname'=>isset($comment->user_client_nickname)?$comment->user_client_nickname:'',
            'like'=>$comment->like,
            'dislike'=>$comment->dislike,
            'avatar'=>isset($comment->user_client_avatar)?$comment->user_client_avatar:'',
            'score'=>$comment->score,
            'type'=>$comment->type,
            'reply_uid'=>$comment->reply_uid,
            'comment_time'=>$comment->comment_time,
            'reply_comments'=>[]
        ];
        $struct['uid'] = $comment->uid;
        $avatar = $struct['avatar'];
        $struct['avatar'] = (filter_var($avatar, FILTER_VALIDATE_URL) !== false)?$struct['avatar']:(($struct['avatar'] == '')?'':Common::getImgDomain().$struct['avatar']);
        return $struct;
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'aid', 'id');
    }

    public function user_client()
    {
        return $this->belongsTo(UserClient::class, 'uid', 'id');
    }

}
