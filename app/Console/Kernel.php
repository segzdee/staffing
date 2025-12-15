<?php

namespace App\Console;

use App\Jobs\DeleteMedia;
use App\Jobs\RebillWallet;
use App\Jobs\ChatSettingJob;
use App\Jobs\ReleaseEscrowPayments;
use App\Jobs\ProcessInstantPayouts;
use App\Jobs\CheckExpiringDocuments;
use App\Jobs\CheckWorkerViolations;
use App\Jobs\RecalculateReliabilityScores;
use App\Jobs\EnforceBreakCompliance;
use App\Jobs\GenerateWeeklyCreditInvoices;
use App\Jobs\MonitorCreditLimits;
use App\Jobs\ProcessAutomaticCancellationRefunds;
use App\Jobs\ProcessPendingRefunds;
use App\Jobs\MonitorBusinessBudgets;
use App\Jobs\MonitorBusinessCancellations;
use App\Jobs\GenerateDailyReconciliation;
use App\Jobs\GenerateMonthlyVATReport;
use App\Jobs\RecordSystemHealthMetrics;
use App\Jobs\EscalateUnresolvedAppeals;
use App\Jobs\MonitorDisputeSLAs;
use App\Jobs\MonitorVerificationSLAs;
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

        // WKR-006: Check expiring documents and send reminders (daily at 2 AM)
        $schedule->job(new CheckExpiringDocuments)->dailyAt('02:00')->withoutOverlapping();

        // WKR-008: Check worker violations and auto-suspend (daily at 1 AM)
        $schedule->job(new CheckWorkerViolations)->dailyAt('01:00')->withoutOverlapping();

        // WKR-007: Recalculate reliability scores (weekly on Sundays at 4 AM)
        $schedule->job(new RecalculateReliabilityScores)->weeklyOn(0, '04:00')->withoutOverlapping();

        // SL-006: Enforce break compliance for active shifts (every 5 minutes)
        $schedule->job(new EnforceBreakCompliance)->everyFiveMinutes()->withoutOverlapping();

        // ============================================================================
        // FINANCIAL AUTOMATION JOBS (GROUP 3)
        // ============================================================================

        // FIN-010: Process pending refunds every 15 minutes
        $schedule->job(new ProcessPendingRefunds())
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-010: Check for automatic cancellation refunds every hour
        $schedule->job(new ProcessAutomaticCancellationRefunds())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-007: Generate weekly credit invoices (every Monday at 9 AM)
        $schedule->job(new GenerateWeeklyCreditInvoices())
            ->weeklyOn(1, '09:00')
            ->timezone('America/New_York')
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-007: Monitor credit limits daily at 3:30 AM
        $schedule->job(new MonitorCreditLimits())
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-007: Send invoice reminders 3 days before due date (daily at 10 AM)
        $schedule->call(function () {
            $invoices = \App\Models\CreditInvoice::unpaid()
                ->whereDate('due_date', now()->addDays(3))
                ->get();
            foreach ($invoices as $invoice) {
                // NotificationService::sendInvoiceReminder($invoice);
            }
        })->dailyAt('10:00');

        // FIN-006: Auto-activate penalties after review period (daily at 4 AM)
        $schedule->call(function () {
            $penalties = \App\Models\WorkerPenalty::where('status', 'pending')
                ->where('issued_at', '<', now()->subDays(3))
                ->whereDoesntHave('appeals')
                ->get();
            foreach ($penalties as $penalty) {
                $penalty->update(['status' => 'active']);
            }
        })->dailyAt('04:00');

        // FIN-006: Escalate unresolved appeals pending > 7 days (daily at 10 AM)
        $schedule->job(new EscalateUnresolvedAppeals())
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // ANALYTICS & MONITORING JOBS (GROUP 5)
        // ============================================================================

        // BIZ-008: Monitor business budgets daily at 8 AM
        $schedule->job(new MonitorBusinessBudgets())
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        // BIZ-009: Monitor business cancellation patterns daily at 2 AM
        $schedule->job(new MonitorBusinessCancellations())
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-005: Generate daily financial reconciliation report (6 AM)
        $schedule->job(new GenerateDailyReconciliation())
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-005: Generate monthly VAT report (1st of month at 7 AM)
        $schedule->job(new GenerateMonthlyVATReport())
            ->monthlyOn(1, '07:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-004: Record system health metrics (every 5 minutes)
        $schedule->job(new RecordSystemHealthMetrics())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // DISPUTE RESOLUTION AUTOMATION (ADM-002)
        // ============================================================================

        // ADM-002: Monitor dispute SLAs and auto-escalate (hourly)
        $schedule->job(new MonitorDisputeSLAs())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // VERIFICATION SLA MONITORING (ADM-001)
        // ============================================================================

        // ADM-001: Monitor verification queue SLAs (hourly)
        // - Updates SLA status for all pending verifications
        // - Sends warnings at 80% of SLA time elapsed
        // - Sends breach alerts when SLA is exceeded
        $schedule->job(new MonitorVerificationSLAs())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

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
