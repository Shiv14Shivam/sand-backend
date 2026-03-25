<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CheckPaymentDueDates::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Runs daily at 9:00 AM IST
        $schedule->command('payments:check-due-dates')
            ->dailyAt('09:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
