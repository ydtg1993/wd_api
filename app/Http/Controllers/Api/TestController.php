<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/6
 * Time: 15:33
 */

namespace App\Http\Controllers\Api;


use App\Providers\EmailOrSms\Config\HuangDouBanSmsGateWay;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

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

            App::make('SmsService')->setSmsTo('17760992641')->registerMessage(App::make(''))
                ->send();

    }

}
