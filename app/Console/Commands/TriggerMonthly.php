<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\BadgeController;

class TriggerMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'badges:monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign badges for month passed.';

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
        BadgeController::processTriggers('monthly');
        $this->info('Badges were assigned');
    }
}
