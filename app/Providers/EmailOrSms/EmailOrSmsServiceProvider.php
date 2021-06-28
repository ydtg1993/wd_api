<?php

namespace App\Providers\EmailOrSms;

use App\Providers\EmailOrSms\Logic\EmailHandle;
use App\Providers\EmailOrSms\Logic\CodeServiceWithDb;
use App\Providers\EmailOrSms\Logic\RegisterSmsMessage;
use App\Providers\EmailOrSms\Logic\SmsHandle;
use Illuminate\Support\ServiceProvider;


class EmailOrSmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SmsService', SmsHandle::class);
        $this->app->singleton('EmailService', EmailHandle::class);
        $this->app->alias(CodeServiceWithDb::class,'CodeServiceWithDb');
        $this->app->alias(RegisterSmsMessage::class,'registerMessage');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
