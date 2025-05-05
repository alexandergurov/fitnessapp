<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\TriggerMonthly::class,
        Commands\TriggerDaily::class,
        Commands\TriggerHourly::class,
        Commands\DayTrigger::class,
        Commands\MorningTrigger::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('badges:monthly')->monthlyOn(1,'00:01');
        $schedule->command('resetcodes:hourly')->everyFifteenMinutes();
        $schedule->command('subscriptions:daily')->cron('*/5 * * * *');
        // Australia is +11, so all hour values increased by 11
        $schedule->command('notify:morning')->cron('*/5 19,20 * * *');
        $schedule->command('notify:day')->cron('*/5 19,20,21,22,23,00,1,2,3,4 * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
