<?php

namespace App\Providers\EmailOrSms\Logic;



use App\Providers\EmailOrSms\Config\EmailConfig;
use App\Providers\EmailOrSms\Config\Logic\EmailSimpleMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class PhpEmail {


    protected $mail;

    protected $mode;

    protected $config;

    public function __construct(  )
    {
        $this->mail = new PHPMailer(true);

        try {
            $this->setMode();
            $this->config = EmailConfig::getEmsConfig()[$this->mode];
            $this->init();
        } catch (\Exception $e) {
            throw  new \Exception($e);
        }
    }

    /**
     * 切换所需的配置
     * @param string $mode
     */
    public function setMode( $mode='smtp_gmail' ){
        $this->mode = $mode;
    }

    /**
     * 初始化
     * @throws \Exception
     */
    protected function init(){
        try {
            $this->mail->SMTPDebug = $this->config['debug'];
            $this->mail->isSMTP();
            $this->mail->Host =  $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Timeout = 10;
            $this->mail->SMTPAutoTLS = true;
            $this->mail->Username   = $this->config['smtp_username'];
            $this->mail->Password   =  $this->config['smtp_password'];
            //$this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['smtp_port'];

            $this->mail->setFrom('from@example.com', '黄豆瓣官方组');
        }catch ( \Exception $e){
            throw  new \Exception("email init error");
        }
    }

    /**
     * 发送
     * @param $email
     * @param $content
     */
    public function sendEmail( $email ){
        try {
            //Recipients
            $this->mail->addAddress($email);     //Add a recipient
            //内容在message类中设置
            $this->mail->send();
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }

    /**
     * @return PHPMailer
     */
    public function getMail(): PHPMailer
    {
        return $this->mail;
    }

    /**
     * @param PHPMailer $mail
     * @return PhpEmail
     */
    public function setMail(PHPMailer $mail): PhpEmail
    {
        $this->mail = $mail;
        return $this;
    }




}
