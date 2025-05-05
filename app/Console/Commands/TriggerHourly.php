<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationsController;
use Illuminate\Console\Command;
use App\Http\Controllers\AuthController;

class TriggerHourly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var stringH
     */
    protected $signature = 'resetcodes:hourly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired reset codes';

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
        AuthController::clearResetTokens();
        $this->info('Outdated tokens had been deleted');
    }
}
