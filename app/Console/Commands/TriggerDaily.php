<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PaymentController;

class TriggerDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify subscription status for users.';

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
        PaymentController::massValidate();
        $this->info('Subscription statuses updated');
    }
}
