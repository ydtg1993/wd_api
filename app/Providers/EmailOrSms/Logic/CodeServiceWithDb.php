<?php
namespace App\Providers\EmailOrSms\Logic;

use App\Providers\EmailOrSms\Logic\EmailSimpleMessage;


use App\Providers\EmailOrSms\Entity\EmailOrSmsEntity;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 *
 * Class RegisterLogicWithDb
 */
class CodeServiceWithDb{

    const Limit_TIME = 120;
    const CODE_MAX_TIME = 60*20;


    /**
     * 有效期
     * @param $user
     * @param string $type
     * @return bool
     */
    public function frequencyLimit( $user ,$type='phone'){
            if ($type == 'phone') {
                $record = EmailOrSmsEntity::getCodeByType($user['emailOrPhone']);
            } else {
                $record = EmailOrSmsEntity::getCodeByType($user['emailOrPhone'],'email');
            }
            $time = time();
            if ($record) {
                if ($time - $record->timestamp < self::Limit_TIME) {
                    return false;
                }
            }
            return true;
    }

    /**
     * 验证注册码
     * @param $emailOrPhone
     * @param $type
     * @param $inputCode
     * @return bool
     */
    public static function checkCode($emailOrPhone,$type,$inputCode){
        $res = EmailOrSmsEntity::getCodeByType($emailOrPhone,$type);
        if(!$res) {
            return false;
        }
        if(time() - $res->timestamp >= self::CODE_MAX_TIME){
            return -1;
        }
        return $res->code==$inputCode;
    }

    /**
     * 发送短信
     * @param $user
     * @param $mobile
     * @param $messageType
     * @throws \Exception
     */
    public function sendSmsCode($user,$mobile ,$messageType ){
        try {
            $code = mt_rand(100000,999999);
            $mapMessage = [
                'register_message'=>App::make('registerMessage',['sms'=>(object)['code'=>$code]]),
            ];
            if(!$this->frequencyLimit($user)){
                throw  new \Exception(self::Limit_TIME.'秒内不能再发送短信');
            }
            $insert = [
                'emailorphone'=>$mobile,
                'message'=>$mapMessage[$messageType]->getContent(),
                'type'=>1,
                'code'=>$code,
                'ip'=>$user['ip'],
                'timestamp'=>time(),

            ];
            $ret = EmailOrSmsEntity::createEmailOrSms($insert);
            if(!$ret){
                throw new \Exception("插入数据表表失败");
            }
            App::make('SmsService')->setSmsMessage($mapMessage[$messageType])->setSmsTo($mobile)->sendSms();
        }catch (\Exception $e){
            Log::error('db sms logic:'.$e->getMessage().$e->getFile().$e->getLine());
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 发送邮箱
     * @param $user
     * @param $email
     * @param $subject
     * @param $sprintfFormat
     * @throws \Exception
     */
    public function sendEmailCode($user,$email ,$subject,$sprintfFormat ){
        try {
            $code = mt_rand(100000,999999);
            $content = sprintf($sprintfFormat,$code);
            if(!$this->frequencyLimit($user,'email')){
                throw  new \Exception(self::Limit_TIME.'秒内不能再发送短信');
            }
            $insert = [
                'emailorphone'=>$email,
                'message'=>$content,
                'type'=>2,
                'code'=>$code,
                'ip'=>$user['ip'],
                'timestamp'=>time(),

            ];
            $ret = EmailOrSmsEntity::createEmailOrSms($insert);
            if(!$ret){
                throw new \Exception("插入数据表失败");
            }

            App::make('EmailService')->sendEmail($email,function ()use($subject,$content){
                //简单消息 可以在这里APP::make() 添加自定义消息
                $message = new EmailSimpleMessage();
                return $message->setContent($content)->setSubject($subject);

            });
        }catch (\Exception $e){
            Log::error('db email logic:'.$e->getMessage().$e->getFile().$e->getLine());
            throw new \Exception($e->getMessage());
        }
    }





}
