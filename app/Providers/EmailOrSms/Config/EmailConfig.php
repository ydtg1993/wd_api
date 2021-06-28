<?php
namespace App\Providers\EmailOrSms\Config;



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailConfig{

    /**
     *  基于SMTP方式
     * @return array
     */
    public static function getEmsConfig(): array
    {

        return [
            'smtp_gmail'=>[
                'smtp_host'=>'smtp.gmail.com',
                'smtp_port'=>587,
                'smtp_secure'=>PHPMailer::ENCRYPTION_STARTTLS,
                'smtp_username'=>'lhysdasky@gmail.com',
                'smtp_password'=>'rkxurivdcyahbvwv',
                'smtp_from'=>'lhysdasky#@gmail.com',
                'debug'=>SMTP::DEBUG_OFF,//
            ]
        ];

    }
}
