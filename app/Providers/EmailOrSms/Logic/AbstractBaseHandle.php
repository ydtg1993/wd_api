<?php


namespace App\Providers\EmailOrSms\Logic;



use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use ReflectionClass;


abstract class AbstractBaseHandle {

    public function __construct()
    {

        Log::setDefaultDriver('emailOrSms');
        Log::debug('------start------');
//        $monolog = Logger::getLogger();
//        $monolog->popHandler();
//        $logName = (new ReflectionClass($this))->getShortName().'log';
//        Logger::useDailyFiles(storage_path('logs/EmailOrSms/'.$logName));
//        $monolog = Logger();
//           $monolog->popHandler();
//        \Illuminate\Log\Logger::useDailyFiles(storage_path('logs/EmailOrSms/'.$logName));
    }

    public function __destruct()
    {
        Log::debug('------end------');
    }


}
