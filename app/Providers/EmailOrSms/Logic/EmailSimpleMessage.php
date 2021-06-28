<?php

namespace App\Providers\EmailOrSms\Logic;

class EmailSimpleMessage{

    protected $mail;

    protected $content;

    protected $subject;

    /**
     * @param mixed $content
     * @return EmailSimpleMessage
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param mixed $subject
     * @return EmailSimpleMessage
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }






//    public function __construct( $mail ){
//        $this->mail = $mail;
//    }

//    public function __construct( $mail ){
//        $this->mail = $mail;
//    }

    /**
     * AOP
     */
    public function setEmailContent(){

        $this->mail->isHTML(true);                                  //Set email format to HTML
        $this->mail->CharSet="UTF-8"; // <-- Put right encoding here
        $this->mail->Subject = $this->subject;
        $this->mail->Body = $this->content;
        $this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    }

    /**
     * @param mixed $mail
     * @return EmailSimpleMessage
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
        return $this;
    }



}
