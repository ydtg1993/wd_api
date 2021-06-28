<?php
namespace App\Providers\EmailOrSms\Config;



class SmsConfig{

    /**
     * @return array
     */
    public static function getSmsConfig(): array
    {
        return [
            // HTTP 请求的超时时间（秒）
            'timeout' => 10.0,

            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [
                    'huangdouban_gateway'
                ],
            ],
            'gateways' => [
                'huangdouban_gateway' => [
                    'app_key'=>'Qqc*.202123_Sms_req',
                    'pro_code'=>'huangdouban',
                    'area_code'=>86,//china
                ], // 你网关所需要的参数，如果没有可以不配置
                'errorlog' => [
                    'file' => '/tmp/easy-sms.log',
                ]
            ],
        ];
    }
}
