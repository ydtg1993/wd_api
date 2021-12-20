<?php

namespace App\Providers\EmailOrSms\Logic;

use Overtrue\EasySms\Message;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Strategies\OrderStrategy;

class RegisterSmsMessage extends Message
{
    protected $sms;
    protected $strategy = OrderStrategy::class;           // 定义本短信的网关使用策略，覆盖全局配置中的 `default.strategy`
    protected $gateways = ['huangdouban_gateway']; // 定义本短信的适用平台，覆盖全局配置中的 `default.gateways`

    public function __construct($sms)
    {
        $this->sms = $sms;
    }

    // 定义直接使用内容发送平台的内容
    public function getContent(GatewayInterface $gateway = null)
    {
        return sprintf('【黄豆瓣】您本次的验证码%s，请及时输入！', $this->sms->code);
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null)
    {
        return 'SMS_REGISTER';
    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null)
    {
        return [
            'code' => $this->sms->code
        ];
    }
}
