<?php

namespace App\Console;

use App\Jobs\DeleteMedia;
use App\Jobs\RebillWallet;
use App\Jobs\ChatSettingJob;
use App\Jobs\ReleaseEscrowPayments;
use App\Jobs\ProcessInstantPayouts;
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
      //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('queue:work --timeout=8600')
        ->cron('* * * * *')
        ->withoutOverlapping();

        // $schedule->command('inspire')->hourly();

        // $schedule->command('queue:work --daemon')->cron('* * * * *')->withoutOverlapping();


        $schedule->command('cache:clear')
        ->weekly()
        ->withoutOverlapping();

        // $schedule->job(new DeleteMedia)->hourly();

        $schedule->job(new RebillWallet)->everyMinute();

        // OvertimeStaff Payment Automation
        // Release payments from escrow 15 minutes after shift completion
        $schedule->job(new ReleaseEscrowPayments)->everyFiveMinutes()->withoutOverlapping();

        // Process instant payouts 15 minutes after escrow release
        $schedule->job(new ProcessInstantPayouts)->everyMinute()->withoutOverlapping();

        // ============================================================================
        // OVERTIMESTAFF AUTOMATED BACKGROUND JOBS
        // ============================================================================
        
        // Auto-release escrow payments (every 15 minutes)
        $schedule->command('shifts:auto-release-escrow')->everyFifteenMinutes()->withoutOverlapping();

        // Send shift reminders (24hr and 2hr before shift)
        $schedule->command('shifts:send-reminders')->hourly()->withoutOverlapping();

        // Process no-shows (every 30 minutes)
        $schedule->command('shifts:process-no-shows')->everyThirtyMinutes()->withoutOverlapping();

        // Expire pending applications (every hour)
        $schedule->command('applications:expire-pending')->hourly()->withoutOverlapping();

        // Calculate market statistics (every 5 minutes)
        $schedule->command('market:calculate-stats')->everyFiveMinutes()->withoutOverlapping();

        // Update worker reliability scores (daily at midnight)
        $schedule->command('workers:update-reliability')->dailyAt('00:00')->withoutOverlapping();

        // System cleanup (daily at 3 AM)
        $schedule->command('system:cleanup')->dailyAt('03:00')->withoutOverlapping();

        // Cleanup expired dev accounts (daily at 3 AM)
        $schedule->command('dev:cleanup-expired')->dailyAt('03:00')->withoutOverlapping();

      //  $schedule->command('uploadvideos:videouploadercron')->everyMinute()->runInBackground();;

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
