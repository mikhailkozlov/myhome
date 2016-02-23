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
        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\AggregateData::class,
        \App\Console\Commands\RebuildEnergyLog::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // run stats to date
        $schedule->command('data:aggregate today')
            ->everyThirtyMinutes();
        // once a day roll up
        $schedule->command('data:aggregate day')
            ->dailyAt('00:05');

        // weekly roll up
        $schedule->command('data:aggregate week')
            ->weekly()->mondays()->at('00:10');
    }
}
