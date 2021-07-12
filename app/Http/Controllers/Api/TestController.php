<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:33
 */

namespace App\Http\Controllers\Api;


use App\Events\UserEvent\UserCommentEvent;
use App\Events\UserEvent\UserDislikeEvent;
use App\Events\UserEvent\UserLikeEvent;
use App\Events\UserEvent\UserReplyEvent;
use App\Events\UserEvent\UserReportEvent;
use App\Providers\EmailOrSms\Config\HuangDouBanSmsGateWay;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

class TestController extends BaseController
{
    public function index(Request $request)
    {
        //频率测试
        /*if(!Common::frequencyLimit(['test'=>1],10,3))
        {
            return $this->sendError('请求太频繁了！');
        }*/

        //缓存测试
        /*$redata = RedisCache::getCacheData('test','test1',function (){
            //return ['SS'=>'AA','SSD'=>'WW'];
            return 'sss';
        });*/
        $redata = ['uid'=>$request->userData['uid']??0];

        //$request->userData['uid'] = 0;

        return $this->sendJson($redata);
    }


    public function testSendSms(){

        App::make('EmailService')->send();
        exit;
//        pr(App::make('HDBSmsGateWay'));
//        exit;

            App::make('SmsService')->setSmsTo('xxxxxxxxx')->registerMessage(App::make(''))
                ->send();

    }

    public function testEvent(Request $request){
        $redis = Redis::connection();
        $res = $redis->mget(['Conf:friend_link','Conf:download_setting']);
        pr($res);
       // \event(new UserLikeEvent($request->all()));
        //var_dump(\event(new UserDislikeEvent($request->all())));
        //var_dump(\event(new UserReportEvent($request->all())));
        //var_dump(\event(new UserReplyEvent($request->all())));
        //var_dump(\event(new UserCommentEvent($request->all())));
    }

}
