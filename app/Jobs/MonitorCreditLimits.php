<?php

namespace App\Jobs;

use App\Models\BusinessProfile;
use App\Models\CreditInvoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorCreditLimits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * Monitors credit limits and performs:
     * 1. Auto-pause accounts at 95% utilization
     * 2. Apply late payment fees to overdue invoices
     * 3. Update late payment tracking
     * 4. Send notifications for approaching limits
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting credit limit monitoring');

        $this->pauseHighUtilizationAccounts();
        $this->processOverdueInvoices();
        $this->sendLimitWarnings();

        Log::info('Credit limit monitoring complete');
    }

    /**
     * Pause accounts that have reached 95% credit utilization.
     */
    protected function pauseHighUtilizationAccounts()
    {
        $businesses = User::whereHas('businessProfile', function ($query) {
            $query->where('credit_enabled', true)
                ->where('credit_paused', false)
                ->where('credit_utilization', '>=', 95);
        })->with('businessProfile')->get();

        foreach ($businesses as $business) {
            try {
                $profile = $business->businessProfile;

                $profile->update([
                    'credit_paused' => true,
                    'credit_paused_at' => now(),
                    'credit_pause_reason' => 'Credit utilization reached 95%',
                ]);

                Log::info("Paused credit for business {$business->id}", [
                    'utilization' => $profile->credit_utilization,
                    'credit_used' => $profile->credit_used,
                    'credit_limit' => $profile->credit_limit,
                ]);

                // Send notification to business
                // NotificationService::notifyCreditPaused($business, $profile);

            } catch (\Exception $e) {
                Log::error("Failed to pause credit for business {$business->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process overdue invoices and apply late fees.
     */
    protected function processOverdueInvoices()
    {
        // Get all overdue invoices
        $overdueInvoices = CreditInvoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->whereRaw('amount_due > 0')
            ->with('business.businessProfile')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                $this->processOverdueInvoice($invoice);
            } catch (\Exception $e) {
                Log::error("Failed to process overdue invoice {$invoice->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process a single overdue invoice.
     */
    protected function processOverdueInvoice(CreditInvoice $invoice)
    {
        $business = $invoice->business;
        $profile = $business->businessProfile;

        // Mark as overdue if not already
        if ($invoice->status !== 'overdue') {
            $invoice->markAsOverdue();
        }

        // Calculate days overdue
        $daysOverdue = now()->diffInDays($invoice->due_date);

        // Apply monthly late fee (1.5% default) - calculated daily
        $interestRate = $profile->interest_rate_monthly / 100; // Convert to decimal
        $dailyRate = $interestRate / 30; // Daily rate
        $lateFee = $invoice->amount_due * $dailyRate;

        // Only apply late fee once per day
        $lastLateFeeDate = $invoice->transactions()
            ->where('transaction_type', 'late_fee')
            ->latest()
            ->first()?->created_at;

        if (! $lastLateFeeDate || $lastLateFeeDate->isYesterday() || $lastLateFeeDate->lt(now()->subDay())) {
            $invoice->addLateFee(
                $lateFee,
                "Late fee for day {$daysOverdue} overdue (1.5% monthly rate)"
            );

            Log::info("Applied late fee to invoice {$invoice->invoice_number}", [
                'business_id' => $business->id,
                'days_overdue' => $daysOverdue,
                'late_fee' => $lateFee,
            ]);
        }

        // Update business late payment tracking
        if ($daysOverdue >= 7) {
            $profile->increment('late_payment_count');
            $profile->update(['last_late_payment_at' => now()]);
            $profile->total_late_fees += $lateFee;
            $profile->save();

            // Send escalated notification every 7 days
            if ($daysOverdue % 7 === 0) {
                // NotificationService::sendOverdueNotice($business, $invoice, $daysOverdue);
            }
        }

        // Auto-pause credit if more than 30 days overdue
        if ($daysOverdue >= 30 && ! $profile->credit_paused) {
            $profile->update([
                'credit_paused' => true,
                'credit_paused_at' => now(),
                'credit_pause_reason' => 'Invoice overdue by more than 30 days',
            ]);

            Log::warning('Credit paused due to 30+ day overdue invoice', [
                'business_id' => $business->id,
                'invoice_number' => $invoice->invoice_number,
                'days_overdue' => $daysOverdue,
            ]);

            // Send urgent notification
            // NotificationService::notifyCreditPausedDueToOverdue($business, $invoice);
        }
    }

    /**
     * Send warnings to businesses approaching their credit limit.
     */
    protected function sendLimitWarnings()
    {
        // Get businesses at 75%, 85%, and 90% utilization
        $thresholds = [75, 85, 90];

        foreach ($thresholds as $threshold) {
            $businesses = User::whereHas('businessProfile', function ($query) use ($threshold) {
                $query->where('credit_enabled', true)
                    ->where('credit_paused', false)
                    ->whereBetween('credit_utilization', [$threshold, $threshold + 0.99]);
            })->with('businessProfile')->get();

            foreach ($businesses as $business) {
                $profile = $business->businessProfile;

                // Check if warning was sent in the last 24 hours
                $lastWarning = DB::table('notifications')
                    ->where('notifiable_id', $business->id)
                    ->where('type', 'CreditLimitWarning')
                    ->where('created_at', '>', now()->subDay())
                    ->exists();

                if (! $lastWarning) {
                    Log::info("Sending credit limit warning to business {$business->id}", [
                        'threshold' => $threshold,
                        'utilization' => $profile->credit_utilization,
                    ]);

                    // Send notification
                    // NotificationService::sendCreditLimitWarning($business, $profile, $threshold);
                }
            }
        }
    }

    /**
     * Recalculate credit utilization for all businesses.
     * Useful for data integrity checks.
     */
    public function recalculateAllUtilization()
    {
        $profiles = BusinessProfile::where('credit_enabled', true)->get();

        foreach ($profiles as $profile) {
            $totalOwed = CreditInvoice::where('business_id', $profile->user_id)
                ->unpaid()
                ->sum('amount_due');

            $profile->credit_used = $totalOwed;
            $profile->credit_available = $profile->credit_limit - $totalOwed;
            $profile->credit_utilization = $profile->credit_limit > 0
                ? ($totalOwed / $profile->credit_limit) * 100
                : 0;
            $profile->save();
        }

        Log::info('Recalculated credit utilization for all businesses', [
            'count' => $profiles->count(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('MonitorCreditLimits job failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
