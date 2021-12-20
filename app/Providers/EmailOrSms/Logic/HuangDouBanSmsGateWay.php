<?php

namespace App\Providers\EmailOrSms\Logic;

use App\Providers\EmailOrSms\Logic\RegisterSmsMessage;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

class HuangDouBanSmsGateWay extends Gateway{
    use HasHttpRequest;

    const SUCCESS_CODE = 200;

    const FUNCTION_SEND_SMS = 'send';

    const FUNCTION_BATCH_SEND_SMS = 'sendsms_batch';

    const ENDPOINT_TEMPLATE = 'http://message.lacc02.com/%s/%s';

    /**
     * Send a short message.
     *
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);

        $function = isset($data['mobiles']) ? self::FUNCTION_BATCH_SEND_SMS : self::FUNCTION_SEND_SMS;

        $endpoint = $this->buildEndpoint('sms', $function);
        $params = $this->buildParams($to, $message, $config);

        return $this->execute($endpoint, $params);
    }

    /**
     * @param $resource
     * @param $function
     *
     * @return string
     */
    protected function buildEndpoint($resource, $function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $resource, $function);
    }

    /**
     * @param PhoneNumberInterface $to
     * @param MessageInterface     $message
     * @param Config               $config
     *
     * @return array
     */
    protected function buildParams(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);

        return [
            'areaCode' => $config->get('area_code'),
            'mobile' => isset($data['mobiles']) ? $data['mobiles'] : $to->getNumber(),
            'msg' => $message->getContent($this),
            'token'=>md5(time().$config->get('app_key'). $config->get('area_code').$to->getNumber().$message->getContent($this)),
            'timestamp' => time(),
            'proCode' => $config->get('pro_code'),
        ];
    }

    /**
     * @param $endpoint
     * @param $params
     *
     * @return array
     *
     * @throws GatewayErrorException
     */
    protected function execute($endpoint, $params)
    {
        try {
            Log::error('send sms api start:',$params);
            $result = $this->post($endpoint, $params);
            if (!isset($result['code']) || self::SUCCESS_CODE !== $result['code']) {
                $code = isset($result['code']) ? $result['code'] : 0;
                $error = isset($result['msg']) ? $result['msg'] : json_encode($result, JSON_UNESCAPED_UNICODE);
                Log::error('result error sms:',[$code,$error]);
                throw new GatewayErrorException($error, $code);
            }
            return $result;
        } catch (\Exception $e) {
            throw new GatewayErrorException($e->getMessage(), $e->getCode());
        }
    }
}
