<?php
namespace App\Console;

use App\Console\Commands\ProcessWhatsAppQueue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Register the commands for the application
    protected $commands = [
        \App\Console\Commands\UpdateHubTable::class,
        \App\Console\Commands\MigrateData::class,
        \App\Console\Commands\MigrateCsvData::class,
        \App\Console\Commands\ProcessWhatsAppQueue::class,
    ];

    // Define the application's command schedule
    protected function schedule(Schedule $schedule)
    {
        // Schedule the command to run daily at midnight
        // $schedule->command('hub:update')->everyFiveMinutes();
        // $schedule->command('whatsapp:process-queue')->everyTwoMinutes();
        $schedule->command('whatsapp:process-queue')
         ->everyTwoMinutes()
         ->appendOutputTo(storage_path('logs/whatsapp_queue.log'));

    }

    // Register the Closure-based commands for the application
    protected function commands()
    {
        require base_path('routes/console.php');
    }

    
}
