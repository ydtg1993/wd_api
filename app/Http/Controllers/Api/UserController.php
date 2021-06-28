<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/2
 * Time: 9:32
 */

namespace App\Http\Controllers\Api;

use App\Providers\EmailOrSms\Logic\SmsHandle;
use App\Services\Logic\Common;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class UserController extends  BaseController
{

    public function login(Request $request)
    {
        $data = $request->all();

        $template = ['account'=>'','pwd'=>''];
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $userInfoObj = new UserInfoLogic();
        $userInfo = [];
        if(Common::isMobile($data['account']??''))
        {
            //手机登录
            $userInfo = $userInfoObj->getPhoneUser($data['account']??'');
            if($userInfo === false)
            {
                return $this->sendError('无效的用户！');
            }

            if( !Common::comparePwd($userInfo['pwd']??'',$data['pwd']??''))
            {
                return $this->sendError('密码错误！');
            }

        }
        else
        {

            //邮箱登录
            $userInfo = $userInfoObj->getEmailUser($data['account']??'');
            if($userInfo === false)
            {
                return $this->sendError('无效的用户！');
            }

            if( !Common::comparePwd($userInfo['pwd']??'',$data['pwd']??''))
            {
                return $this->sendError('密码错误！');
            }
        }

        $userId = $userInfo['id']??0;
        if($userId <= 0)
        {
            return $this->sendError('登录失败！');
        }

        $tempData = [
            'login_ip'=>$request->getClientIp(),
            'login_time'=>date('Y-m-d H:i:s',time()),
        ];//更新登录记录

        $userInfoObj->alterUserBase($tempData,$userId);//更新登录信息

        //返回登录信息
        $redata = array();
        $redata['token'] = UserInfoLogic::getTokenIsId($userId);
        return $this->sendJson($redata);

    }

    public function register(Request $request)
    {
        $data = $request->all();
        $template = ['account'=>'','pwd'=>'','type'=>1,'code'=>0];
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }
        $userInfo = new UserInfoLogic();
        $reData = (intval($data['type']??1) == 1) ?
            $userInfo->registerPhone($data['account']??'',$data['pwd']??'',$data['code']??''):
            $userInfo->registerEmail($data['account']??'',$data['pwd']??'',$data['code']??'');

        if($reData === false)
        {
            return $this->sendJson([],500,$userInfo->getErrorInfo()->msg??'未知错误！');
        }

        return $this->sendJson($reData);

    }

    /**
     * 修改用户信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function changeUserInfo( Request  $request)
    {

        $validator = Validator()->make($request->all(), [
            'nickname' => 'string',
            'sex' => 'int',
            'avatar' => 'string',
            'intro' => 'string',
            'le_phone_status' => 'int|between:1,2',
            'le_email_status' => 'int|between:1,2',
            'phone' => [
                'string',
                function ($attribute, $value, $fail) {
                    if(!Common::isMobile($value)){
                        $fail($attribute.' 非法手机号');
                    }
                    if(UserInfoLogic::checkPhone($value)){
                        $fail($attribute.' 已存在.');
                    }
                },
            ],
            'email' => [
                'string',
                'email',
                function ($attribute, $value, $fail) {

                    if(UserInfoLogic::checkEmail($value)){
                        $fail($attribute.' 已存在.');
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->getMessageBag()->all()[0]);
        }
        $userInfoObj = App::make(UserInfoLogic::class);
        $uData = $userInfoObj->alterUserBase($request->all(), $request->userData['uid']);//更新登录信息
        unset($uData['pwd']);
        return $this->sendJson($uData);
    }

    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo( Request  $request)
    {

        $userInfoObj = App::make(UserInfoLogic::class);
        $uData = $userInfoObj->getUserInfo($request->userData['uid']);//更新登录信息
        unset($uData['pwd']);
        return $this->sendJson($uData);
    }

    /**
     * 发送验证码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerifyCode(Request  $request){
        try {
            $data['ip'] = $request->getClientIp();
            $data['emailOrPhone'] = $request->get('emailOrPhone');
            if (SmsHandle::isMobile($request->get('emailOrPhone'))) {
                App::make('CodeServiceWithDb')->sendSmsCode($data,$request->get('emailOrPhone'),'register_message');
        } else {
                //TODO待优化
                $subject = '欢迎来到 HDB';
                $sprintfFormat = '
                    欢迎加入 HDB
                    您已注册成功，用户名为：'.$data['emailOrPhone'].'
                        %s
                    请复制上方验证码到验证页面完成激活操作
                ';
                App::make('CodeServiceWithDb')->sendEmailCode($data,$request->get('emailOrPhone'),$subject,$sprintfFormat);
            }
            return $this->sendJson([]);
        }catch (\Exception $e){
            return $this->sendError($e->getMessage());
        }
    }

}
