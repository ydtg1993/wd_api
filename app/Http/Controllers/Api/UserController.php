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
        }elseif ($userInfo['status']>1){
            return $this->sendError('账号已被拉黑 请联系管理员！');
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
        $uData['avatar'] = empty($uData['avatar'])?config('filesystems.avatar_path'):$uData['avatar'];
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
    public $user;

    /**
     * 忘记密码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function forgetPassword(Request $request)
    {
        if(Common::isMobile($request->input('account'))){
            $data['phone'] = $request->input('account');
        }else{
            $data['email'] = $request->input('account');
        }
        $data['pwd'] = $request->input('pwd');
        $data['code'] = $request->input('code');
        $validator = Validator()->make($data, [
            'pwd' => 'required|string',
            'code' => 'required|int',
            'phone' => [
                'string',
                function ($attribute, &$value, $fail) {
                    if(!Common::isMobile($value)){
                        $fail($attribute.' 非法手机号');
                    }
                    $user=UserInfoLogic::getUserInfoByPhone($value);
                    if(!$user) {
                        $fail($attribute . ' 不存在.');
                    }elseif ($user['le_phone_status'] ==2 ){//未认证
                        $fail($attribute . ' 未认证.');
                    }
                    $this->user = $user;
                },
            ],
            'email' => [
                'string',
                'email',
                function ($attribute, &$value, $fail) {

                    if(!UserInfoLogic::checkEmail($value)){
                        $fail($attribute.' 非法邮箱.');
                    }
                    $user = UserInfoLogic::getUserInfoByEmail($value);
                    if(!$user) {
                        $fail($attribute . ' 不存在.');
                    }elseif ($user['le_email_status'] ==2 ){//未认证
                        $fail($attribute . ' 未认证.');
                    }
                    $this->user = $user;
                },
            ],
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->getMessageBag()->all()[0]);
        }
        $ret = App::make('CodeServiceWithDb')->checkCode($request->input('account'),
            !empty($data['email'])?'email':'phone',$data['code']);
        if($ret<=0){
            return $this->sendError($ret==-1?'验证码已失效':'验证码错误');
        }
        $userInfoObj = App::make(UserInfoLogic::class);
        $pwd['pwd'] = $data['pwd'];
        try {
            $userInfoObj->alterUserBase($pwd, $this->user['id']);//更新登录信息
        }catch (\Exception $e){
            return $this->sendError('操作异常:'.$e->getMessage());
        }
        return $this->sendJson([]);

    }

}
