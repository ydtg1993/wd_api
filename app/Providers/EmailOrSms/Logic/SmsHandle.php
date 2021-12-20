<?php


namespace App\Providers\EmailOrSms\Logic;
use App\Providers\EmailOrSms\Logic\AbstractBaseHandle;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use App\Providers\EmailOrSms\Exceptions\SmsHandleException;
use App\Providers\EmailOrSms\Logic\RegisterSmsMessage;
use Overtrue\EasySms\Support\Config;
use App\Providers\EmailOrSms\Config\SmsConfig;
use \Overtrue\EasySms\Exceptions\GatewayErrorException;
    class SmsHandle extends AbstractBaseHandle{

    protected $easySms;

    protected $smsTo;

    protected $code ;

    protected $message;

    public function __construct( )
    {

        $this->easySms =  new EasySms(SmsConfig::getSmsConfig());
        // 注册  $gatewayConfig 来自配置文件里的 `gateways.mygateway`
        $this->easySms->extend('huangdouban_gateway', function($gatewayConfig){
            return  new HuangDouBanSmsGateWay($gatewayConfig);
        });
        parent::__construct();
    }



    /**
     * 检查是否是手机
     * @param $str
     * @return bool
     */
    public  static function  isMobile($str)
    {
        return (preg_match("/^1[345678]\d{9}$/", $str))?true:false;
    }

    /**
     * 发送短信
     * @return array
     * @throws SmsHandleException
     */
    public function sendSms( ){
        try {
            if(empty($this->smsTo)){
                throw  new  SmsHandleException("empty phone number");
            }
            //三方接口返回值
            return $this->easySms->send($this->smsTo, $this->message);
        }catch (GatewayErrorException | \Exception $e){
            Log::error('send register error:'.$e->getMessage().$e->getFile().$e->getLine());
            throw  new  SmsHandleException($e->getMessage());
        }
    }

    /**
     * 设置消息模板
     * @param Message $message
     * @return $this
     * @throws SmsHandleException
     */
    public function setSmsMessage(  Message $message ){
        if(!$message instanceof Message){
            throw new SmsHandleException("message tpe error");
        }
        $this->message = $message;
        return $this;
    }


    /**
     * 设置发送的电话号码
     * @param $smsTo
     * @param null $countryCode
     * @return $this
     */
    public function setSmsTo( $smsTo ,$countryCode=null)
    {
        $this->smsTo = new PhoneNumber($smsTo, $countryCode);
        return $this;
    }

    /**
     * 设置验证码
     * @param $code
     * @return $this
     */
    protected function setCode( $code  )
    {
        $this->code = $code;
        return $this;
    }
    /**
     * 获取验证码
     * @param $code
     * @return $this
     */
    public function getCode(  )
    {
        return $this->code;
    }



}
