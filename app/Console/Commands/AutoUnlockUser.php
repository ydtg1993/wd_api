<?php

namespace App\Console\Commands;

use App\Models\UserClientBlack;
use Illuminate\Console\Command;

class AutoUnlockUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:AutoUnlockUser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天24点之后自动解封到期的用户,计划任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mdb = new UserClientBlack();
        $total = $mdb->unlockAll();
        echo '解封用户:'.$total.PHP_EOL;
    }
}
