<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UserEventSubscribe
{
    /**
     *
     *
     * @param $event
     */
    public function handleUserLookHistory( $event )
    {
        Log::info('UserEventSubscribe handleUserLookHistory');
    }

    /**
     * 为事件订阅者注册事件监听器
     *
     * @param $event
     */
    public function subscribe($event)
    {
        $event->listen(
            'App\Events\UserLookHistory',
            'App\Listeners\UserEventSubscribe@handleUserLookHistory'
        );
    }
}
