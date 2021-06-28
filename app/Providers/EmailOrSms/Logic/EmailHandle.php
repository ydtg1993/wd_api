<?php

namespace App\Providers\EmailOrSms\Logic;

use App\Providers\EmailOrSms\Exceptions\EmailHandleException;
use App\Providers\EmailOrSms\Logic\AbstractBaseHandle;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHandle extends AbstractBaseHandle{


    protected $mail;

    public function __construct(  )
    {
        $this->mail = new PhpEmail();
    }


    /**
     * 发送邮箱
     * @param $email
     * @param $message
     * @throws EmailHandleException
     */
    public function sendEmail($email,Closure $message )
    {
        try {
            $this->checkEmail($email);
            if(!$message instanceof Closure){
                throw new EmailHandleException("message type error");
            }
            $afterInitMessage = $message();
            $afterInitMessage->setMail($this->mail->getMail())->setEmailContent();
            $this->mail->sendEmail($email);
        }catch (EmailHandleException | \Exception $e){
            Log::error('send email:'.$e->getMessage());
            throw new EmailHandleException("send email error");
        }

    }


    /**
     * @param $email
     * @return bool
     * @throws EmailHandleException
     */
    public function checkEmail($email)
    {
        try {
            $validator = Validator()->make(['email'=>$email], [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                throw new EmailHandleException($validator->errors()->getMessageBag()->all()[0]);
            }
            return true;
        }catch (EmailHandleException | BindingResolutionException $e){
            Log::error($e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            throw new EmailHandleException($validator->errors()->getMessageBag()->all()[0]);
        }
    }


}
