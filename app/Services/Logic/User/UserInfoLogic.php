<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/11
 * Time: 16:29
 */

namespace App\Services\Logic\User;


use App\Models\UserClient;
use App\Providers\EmailOrSms\Logic\CodeServiceWithDb;
use App\Services\Logic\BaseLogic;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class UserInfoLogic extends BaseLogic
{

    protected $id = 0;
    protected $userInfo = null;

    /**
     * 手机号注册
     * @param $phone
     * @param $pwd
     * @param $code
     */
    public function registerPhone($phone,$pwd,$code,$regDevice,$pushCode)
    {
        $ret = App::make('CodeServiceWithDb')->checkCode($phone,'phone',$code);
        if($ret<=0){
            $this->errorInfo->setCode(500,$ret==-1?'验证码已失效':'验证码错误');
            return false;
        }
        //唯一性验证
        if(static::checkPhone($phone)){
            $this->errorInfo->setCode(500,'账号已存在');
            return false;
        }
        if(strlen($pwd) < 8)
        {
            $this->errorInfo->setCode(500,'密码太简单了！不能少于8位密码！');
            return false;
        }
        $data = ['phone' =>$phone,'pwd' =>$pwd,'le_phone_status'=>UserClient::PHONE_VER_STATUS_YES,'reg_device'=>$regDevice,'push_code'=>$pushCode];
        $userId = $this->addUser($data);
        if($userId == false)
        {
            $this->errorInfo->setCode(500,'注册失败！'.($this->errorInfo->msg??'未知错误'));
            return false;
        }

        //生成Token
        $redata = array();
        $redata['token'] = self::getTokenIsId($userId);
        $redata['userId'] = $userId;
        $this->errorInfo->data = $redata;
        return $redata ;
    }

    /**
     * 手机号注册
     * @param $phone
     * @param $pwd
     * @param $code
     */
    public function registerEmail($email,$pwd,$code,$regDevice='web',$pushCode='')
    {
        $ret = App::make('CodeServiceWithDb')->checkCode($email,'email',$code);
        if($ret<=0){
            $this->errorInfo->setCode(500,$ret==-1?'验证码已失效':'验证码错误');
            return false;
        }
        if(strlen($pwd) < 8)
        {
            $this->errorInfo->setCode(500,'密码太简单了！不能少于8位密码！');
            return false;
        }
        //唯一性验证
        if(static::checkEmail($email)){
            $this->errorInfo->setCode(500,'账号已存在');
            return false;
        }

        $data = ['email' =>$email,'pwd' =>$pwd,'le_email_status'=>UserClient::EMAIL_VER_STATUS_YES,'reg_device'=>$regDevice,'push_code'=>$pushCode];
        $userId = $this->addUser($data);
        if($userId == false)
        {
            $this->errorInfo->setCode(500,'注册失败！'.($this->errorInfo->msg??'未知错误'));
            return false;
        }

        //生成Token
        $redata = array();
        $redata['token'] = self::getTokenIsId($userId);
        $redata['userId'] = $userId;
        $this->errorInfo->data = $redata;
        return $redata ;
    }


    /**
     * 添加一个用户
     * @param array $data
     */
    public function addUser($data = [])
    {
        if(empty($data['pwd']))
        {
            $this->errorInfo->setCode(500,'缺少密码');
            return false;
        }

        if(!empty($data['phone']))
        {
            $userList = UserClient::where('phone',$data['phone']??'')->get();
            if(!$userList)
            {
                $this->errorInfo->setCode(500,'手机号重复！');
                return false;
            }
        }
        else if(!empty($data['email']))
        {
            $userList = UserClient::where('email',$data['email']??'')->get();
            if(!$userList)
            {
                $this->errorInfo->setCode(500,'邮箱重复！');
                return false;
            }
        }
        else
        {
            $this->errorInfo->setCode(500,'缺少手机号或者缺少邮箱！');
            return false;
        }

        //检查数据是否存在重复

        //生成用户信息
        $userObj = new UserClient();
        $userObj->phone = $data['phone']??'';
        $userObj->number = md5((uniqid().time().rand(10000,99999)));
        $userObj->email = $data['email']??'';
        $userObj->avatar = config('filesystems.avatar_path');//默认头像路径
        $userObj->pwd = Common::encodePwd($data['pwd']??'');
        $userObj->le_phone_status = $data['le_phone_status']??UserClient::PHONE_VER_STATUS_NO;
        $userObj->le_email_status = $data['le_email_status']??UserClient::EMAIL_VER_STATUS_NO;
        $userObj->nickname = (empty($data['nickname'])? (UserClient::DEFAULT_USER_NAME.Common::random_str(4)):$data['nickname']??'');
        $userObj->reg_device = $data['reg_device']??'';
        $userObj->login_device = $data['reg_device']??'';
        $userObj->push_code = $data['push_code']??'';
        $userObj->save();
        return $userObj->id;

    }

    /**
     * 读取用户数据 基础数据
     * 缓存用户数据  $is_cache = false 时可以刷新用户信息
     * @param $id
     * @param bool $is_cache
     * @return array|bool
     */
    public function getUserInfo($id = 0,$is_cache = true)
    {
        if($id <= 0)
        {
            return empty($this->userInfo)?array():$this->userInfo ;
        }

        //如果ID 与用户信息相同则直接返回
        if($this->id == $id
            && $this->userInfo != null
            && $this->id == ($this->userInfo['id']??0)
            && $is_cache
        )
        {
            return $this->userInfo;
        }

        //缓存用户数据
        $reData = RedisCache::getCacheData('userinfo','userinfo:first:',function () use ($id)
        {
            $reData = [];
            $userBase = UserClient::find($id); // 获取用户基础信息
            if(!$userBase)
            {
                return array();
            }
            $userBase = $userBase->toArray();
            if(($userBase['id']??0) <= 0)
            {
                return array();
            }
            $userBase['avatar'] = (($userBase['avatar']??'') == '')?'':(Common::getImgDomain().($userBase['avatar']??''));
            $reData = $userBase;
            return $reData;
        },['id'=>$id,],$is_cache);
        $this->setUserBase($reData);
        return $reData;
    }

    /**
     * 通过用户ID获取Token
     * @param int $id
     */
    public static function getTokenIsId($id = 0)
    {
        $userData = new  UserInfoLogic();
        $userInfo = $userData->getUserInfo($id);
        if(empty($userInfo))
        {
            return null;
        }
        //组装TOKEN携带的数据 [只携带基础数据]
        $userBase = [];
        $userBase['uid'] = $userInfo['id'];
        $userBase['nickname'] = $userInfo['nickname'];
        $userBase['avatar'] = $userInfo['avatar'];

        $data = [];
        $data['UserBase'] = $userBase;//用户数据
        $data['sysData'] = [];//系统数据
        return Common::generateToken($data);
    }

    /**
     * 手机号重复检查
     * @param $phone
     * @return bool
     */
    public static function checkPhone($phone)
    {
        $userInfo = UserClient::where('phone',$phone)->first();

        if(!$userInfo)
        {
            return false;
        }

        $userInfo = $userInfo->toArray();

        if(($userInfo['id']??0) > 0 )
        {
            return true;
        }
        return false;
    }

    /**
     * 手机号重复检查
     * @param $phone
     * @return bool
     */
    public static function getUserInfoByPhone($phone)
    {
        $userInfo = UserClient::where('phone',$phone)->first();

        if(!$userInfo)
        {
            return false;
        }

        $userInfo = $userInfo->toArray();

        if(($userInfo['id']??0) > 0 )
        {
            return $userInfo;
        }
        return false;
    }


    /**
     * 邮箱重复检查
     * @param $email
     * @return bool 存在true 不存在 false
     */
    public static function getUserInfoByEmail($email)
    {
        $userInfo = UserClient::where('email',$email)->first();
        if(!$userInfo)
        {
            return false;
        }

        $userInfo = $userInfo->toArray();
        if(($userInfo['id']??0) > 0 )
        {
            return $userInfo;
        }

        return false;
    }


    /**
     * 邮箱重复检查
     * @param $email
     * @return bool 存在true 不存在 false
     */
    public static function checkEmail($email)
    {
        $userInfo = UserClient::where('email',$email)->first();
        if(!$userInfo)
        {
            return false;
        }

        $userInfo = $userInfo->toArray();
        if(($userInfo['id']??0) > 0 )
        {
            return true;
        }

        return false;
    }

    /**
     * 前端用户信息展示处理
     * @param array $userInfo
     * @return array
     */
    public static function userDisData($userInfo = array())
    {
        $reData = array();
        empty($userInfo['number'])?($reData['number']=''):$reData['number'] = $userInfo['number'];
        empty($userInfo['phone'])?($reData['phone'] =''):$reData['phone'] = substr_replace($userInfo['phone'],'****',3,4) ;
        if(!empty($userInfo['email']))
        {
            $arr = explode('@', $userInfo['email']);
            if(strlen($arr[0]) < 4)
            {
                $arr[0] = substr_replace($arr[0],'****',0,2);
            }
            else
            {
                $arr[0] = substr_replace($arr[0],'****',0,4);
            }
            $reData['email'] = ($arr[0]??'').'@'.($arr[1]??'');
        }
        empty($userInfo['nickname'])?$reData['nickname']='':$reData['nickname'] = $userInfo['nickname'];

        empty($userInfo['sex'])?($reData['sex']=0):$reData['sex'] = $userInfo['sex'];
        empty($userInfo['age'])?($reData['age']=0):$reData['age'] = $userInfo['age'];
        empty($userInfo['attention'])?($reData['attention']=0):$reData['attention'] = $userInfo['attention'];
        empty($userInfo['fans'])?($reData['fans']=0):$reData['fans'] = $userInfo['fans'];

        empty($userInfo['avatar'])?($reData['avatar']=''):$reData['avatar'] = $userInfo['avatar'];
        empty($userInfo['intro'])?($reData['intro']=''):$reData['intro'] = $userInfo['intro'];
        return $reData;
    }

    /**
     * 设置用户信息 【这样整个用户周期都可以重用】
     * @param null $userBase
     */
    private function setUserBase($userBase = null)
    {
        $this->userInfo = ($userBase['id']??0)>0?$userBase:null;
        $this->id = ($userBase['id']??0)>0?$userBase['id']:null;
    }

    /**
     * 修改用户信息
     * @param array $data
     * @param int $id
     * @return array|bool
     */
    public function alterUserBase($data = array(),$id=0)
    {
        $userBase = UserClient::find($id);
        if(!$userBase)
        {
            return [];
        }

        (!empty($data['nickname']))?$userBase->nickname = $data['nickname']??'':null;
        (!empty($data['sex']) && in_array($data['sex'],[1,2]))?$userBase->sex = $data['sex']??1:null;
        (!empty($data['avatar']))?$userBase->avatar = $data['avatar']??'':null;
        (!empty($data['intro']))?$userBase->intro = $data['intro']??'':null;

        (!empty($data['pwd']))?$userBase->pwd = Common::encodePwd($data['pwd']??''):null;
        (!empty($data['phone']))?$userBase->phone = $data['phone']??'':null;
        (!empty($data['email']))?$userBase->email = ($data['email']??''):null;
        (!empty($data['le_phone_status']) && in_array($data['le_phone_status'],[UserClient::PHONE_VER_STATUS_NO,UserClient::PHONE_VER_STATUS_YES]))?
            $userBase->le_phone_status = ($data['le_phone_status']??UserClient::PHONE_VER_STATUS_NO):null;

        (!empty($data['le_email_status']) && in_array($data['le_email_status'],[UserClient::EMAIL_VER_STATUS_NO,UserClient::EMAIL_VER_STATUS_YES]))?
            $userBase->le_email_status = ($data['le_email_status']??UserClient::EMAIL_VER_STATUS_NO):null;

        (!empty($data['age']))?$userBase->age = ($data['age']??''):null;
        (!empty($data['login_time']))?$userBase->login_time = ($data['login_time']??''):null;
        (!empty($data['login_ip']))?$userBase->login_ip = ($data['login_ip']??''):null;

        (!empty($data['login_device']))?$userBase->login_device = ($data['login_device']??''):'web';
        (!empty($data['push_code']))?$userBase->push_code = ($data['push_code']??''):'web';
        $userBase->save();

        $userInfo = $this->refreshUserCache($userBase->id);//刷新用户相关缓存
        return $userInfo;
    }

    //获取邮箱用户
    public function getEmailUser($email = '')
    {
        if(empty($email))
        {
            return false ;
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return false ;
        }
        $userBase = UserClient::where('email',$email)->first(); // 获取用户基础信息
        if(!$userBase)
        {
            return false ;
        }
        $userBase = $userBase->toArray();
        if(($userBase['id']??0) <= 0)
        {
            return false ;
        }
        $userBase['avatar'] = Common::getImgDomain().$userBase['avatar']??'';
        $this->setUserBase($userBase);
        return $userBase;
    }

    /**
     * 获取手机用户
     * @param string $phone
     * @return array|null
     */
    public function getPhoneUser($phone = '')
    {
        if(empty($phone))
        {
            return empty($this->userInfo)?array():$this->userInfo ;
        }

        $userBase = UserClient::where('phone',$phone)->first(); // 获取用户基础信息
        if(!$userBase)
        {
            return false ;
        }
        $userBase = $userBase->toArray();
        if(($userBase['id']??0) <= 0)
        {
            return false ;
        }
        $userBase['avatar'] = Common::getImgDomain().$userBase['avatar']??'';
        $this->setUserBase($userBase);
        return $userBase;
    }

    /**
     * 刷新用户相关缓存
     * @param $uid
     */
    private function refreshUserCache($uid)
    {
        $tempUserInfo = $this->getUserInfo($uid,false);//刷新缓存
        $this->setUserBase($tempUserInfo);
        return $this->userInfo;
    }


}
