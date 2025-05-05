<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationsController;
use Illuminate\Console\Command;

class DayTrigger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications appropriate for 8am-6pm.';

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
     * @return int
     */
    public function handle()
    {
        NotificationsController::sendNotifications('day');
    }
}
