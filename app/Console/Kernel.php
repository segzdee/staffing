<?php

namespace App\Console;

use App\Jobs\AutoClockOutJob;
use App\Jobs\CheckExpiringDocuments;
use App\Jobs\CheckWorkerViolations;
use App\Jobs\DeleteMedia;
use App\Jobs\EnforceBreakCompliance;
use App\Jobs\EscalateUnresolvedAppeals;
use App\Jobs\GenerateDailyReconciliation;
use App\Jobs\GenerateMonthlyVATReport;
use App\Jobs\GenerateWeeklyCreditInvoices;
use App\Jobs\MonitorBusinessBudgets;
use App\Jobs\MonitorBusinessCancellations;
use App\Jobs\MonitorCreditLimits;
use App\Jobs\MonitorDisputeSLAs;
use App\Jobs\MonitorVerificationSLAs;
use App\Jobs\ProcessAutomaticCancellationRefunds;
use App\Jobs\ProcessInstantPayouts;
use App\Jobs\ProcessInstaPayRequests;
use App\Jobs\ProcessPendingRefunds;
use App\Jobs\RebillWallet;
use App\Jobs\RecalculateReliabilityScores;
use App\Jobs\RecordSystemHealthMetrics;
use App\Jobs\ReleaseEscrowPayments;
use App\Jobs\SendPostShiftSurveys;
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

        // SL-007: Auto clock-out workers who forgot to clock out (every 5 minutes)
        // - Checks for workers still clocked in after shift ended + 30 min grace
        // - Automatically clocks them out at shift end time
        $schedule->job(new AutoClockOutJob)->everyFiveMinutes()->withoutOverlapping()->onOneServer();

        // ============================================================================
        // FINANCIAL AUTOMATION JOBS (GROUP 3)
        // ============================================================================

        // FIN-010: Process pending refunds every 15 minutes
        $schedule->job(new ProcessPendingRefunds)
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-010: Check for automatic cancellation refunds every hour
        $schedule->job(new ProcessAutomaticCancellationRefunds)
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-007: Generate weekly credit invoices (every Monday at 9 AM)
        $schedule->job(new GenerateWeeklyCreditInvoices)
            ->weeklyOn(1, '09:00')
            ->timezone('America/New_York')
            ->withoutOverlapping()
            ->onOneServer();

        // FIN-007: Monitor credit limits daily at 3:30 AM
        $schedule->job(new MonitorCreditLimits)
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
        $schedule->job(new EscalateUnresolvedAppeals)
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // ANALYTICS & MONITORING JOBS (GROUP 5)
        // ============================================================================

        // BIZ-008: Monitor business budgets daily at 8 AM
        $schedule->job(new MonitorBusinessBudgets)
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        // BIZ-009: Monitor business cancellation patterns daily at 2 AM
        $schedule->job(new MonitorBusinessCancellations)
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-005: Generate daily financial reconciliation report (6 AM)
        $schedule->job(new GenerateDailyReconciliation)
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-005: Generate monthly VAT report (1st of month at 7 AM)
        $schedule->job(new GenerateMonthlyVATReport)
            ->monthlyOn(1, '07:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ADM-004: Record system health metrics (every 5 minutes)
        $schedule->job(new RecordSystemHealthMetrics)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // DISPUTE RESOLUTION AUTOMATION (ADM-002)
        // ============================================================================

        // ADM-002: Monitor dispute SLAs and auto-escalate (hourly)
        $schedule->job(new MonitorDisputeSLAs)
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
        $schedule->job(new MonitorVerificationSLAs)
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // AGENCY COMPLIANCE & GO-LIVE AUTOMATION (AGY-REG-005)
        // ============================================================================

        // AGY-REG-005: Monitor agency compliance daily at 6 AM
        // - Checks for expiring documents (30-day warning)
        // - Detects compliance score drops
        // - Auto-restricts agencies with expired licenses
        $schedule->command('agency:monitor-compliance')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        // AGY-REG-005: Process go-live requests every 4 hours
        // - Auto-approves agencies meeting all requirements (score >= 80%)
        // - Flags agencies needing manual review
        $schedule->command('agency:process-go-live')
            ->everyFourHours()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // COM-002: PUSH NOTIFICATION MAINTENANCE
        // ============================================================================

        // COM-002: Cleanup inactive push tokens (daily at 4 AM)
        // - Removes tokens not used in 90 days (configurable)
        // - Helps maintain database performance and data hygiene
        $schedule->command('push:cleanup-tokens')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // QUA-003: FEEDBACK LOOP SYSTEM
        // ============================================================================

        // QUA-003: Send post-shift surveys 24 hours after shift completion (hourly check)
        // - Identifies workers who completed shifts 24-48 hours ago
        // - Sends survey notification if not already sent
        // - Excludes workers who have already responded
        $schedule->job(new SendPostShiftSurveys)
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // GLO-001: MULTI-CURRENCY SUPPORT
        // ============================================================================

        // GLO-001: Update exchange rates daily at 17:00 UTC (after ECB updates at 16:00 CET)
        // - Fetches latest rates from ECB or OpenExchangeRates
        // - Updates database with fresh rates for all supported currencies
        // - Clears rate cache to ensure fresh data is used
        $schedule->command('currency:update-rates')
            ->dailyAt('17:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // WKR-013: AVAILABILITY FORECASTING
        // ============================================================================

        // WKR-013: Generate availability predictions and demand forecasts (daily at 2 AM)
        // - Analyzes worker patterns from historical shift data
        // - Creates predictions for the next 14 days
        // - Generates demand forecasts by region
        // - Updates accuracy of past predictions
        $schedule->command('forecasting:generate --update-accuracy')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // SL-008: SURGE PRICING - DEMAND METRICS
        // ============================================================================

        // SL-008: Calculate demand metrics daily at 1 AM for the previous day
        // - Calculates fill rates and supply/demand ratios by region and skill
        // - Updates surge multipliers based on demand thresholds
        // - Deactivates expired surge events
        $schedule->command('surge:calculate-demand')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer();

        // SL-008: Import events from external APIs (Ticketmaster, Eventbrite) weekly
        // - Fetches upcoming events that may impact worker demand
        // - Creates surge events for major concerts, sports, festivals
        $schedule->command('surge:calculate-demand --import-events')
            ->weeklyOn(1, '05:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // FIN-004: INSTAPAY (SAME-DAY PAYOUT) PROCESSING
        // ============================================================================

        // FIN-004: Process pending InstaPay requests every 5 minutes
        // - Batch processes pending payout requests
        // - Only runs on processing days (Mon-Fri)
        // - Handles Stripe Connect instant payouts
        $schedule->job(new ProcessInstaPayRequests)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // AGY-001: AGENCY TIER SYSTEM
        // ============================================================================

        // AGY-001: Process monthly agency tier reviews (1st of each month at 2 AM)
        // - Evaluates agency metrics against tier requirements
        // - Processes upgrades for high-performing agencies
        // - Processes downgrades after grace period for underperforming agencies
        // - Updates commission rates based on tier
        $schedule->command('agency:review-tiers')
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // SL-004: BOOKING CONFIRMATION SYSTEM
        // ============================================================================

        // SL-004: Expire stale confirmations (every 15 minutes)
        // - Marks unconfirmed bookings as expired when past their expiry time
        // - Releases shift slots back to the market
        // - Triggers next worker in waitlist
        $schedule->command('confirmations:expire')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // SL-004: Send confirmation reminders (hourly)
        // - Sends reminders to workers/businesses with pending confirmations
        // - Triggered at 12 hours and 4 hours before expiry (configurable)
        $schedule->command('confirmations:remind')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // ============================================================================
        // WKR-006: EARNINGS DASHBOARD - SUMMARY REFRESH
        // ============================================================================

        // WKR-006: Refresh earnings summaries for all active workers (daily at 5 AM)
        // - Recalculates cached earnings summaries for workers with recent activity
        // - Updates daily, weekly, monthly, and yearly aggregates
        // - Only processes workers with earnings in the last 90 days
        $schedule->command('earnings:refresh-summaries --active-only')
            ->dailyAt('05:00')
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
