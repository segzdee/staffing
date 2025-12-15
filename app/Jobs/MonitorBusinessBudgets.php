<?php

namespace App\Jobs;

use App\Models\BusinessProfile;
use App\Services\SpendAnalyticsService;
use App\Notifications\BudgetAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class MonitorBusinessBudgets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    /**
     * Execute the job.
     */
    public function handle(SpendAnalyticsService $spendAnalyticsService)
    {
        // Get all businesses with budget alerts enabled
        $businesses = BusinessProfile::where('enable_budget_alerts', true)
            ->where('monthly_budget', '>', 0)
            ->get();

        foreach ($businesses as $business) {
            $this->checkBusinessBudget($business, $spendAnalyticsService);
        }
    }

    /**
     * Check budget for a specific business.
     */
    protected function checkBusinessBudget(BusinessProfile $business, SpendAnalyticsService $spendAnalyticsService)
    {
        $overview = $spendAnalyticsService->getBudgetOverview($business);
        $alerts = $spendAnalyticsService->getBudgetAlerts($business);

        // Only send alerts if there are any
        if (empty($alerts)) {
            return;
        }

        // Check if we should send an alert (don't spam)
        $lastAlertSent = $business->last_budget_alert_sent_at;
        $hoursSinceLastAlert = $lastAlertSent
            ? Carbon::parse($lastAlertSent)->diffInHours(now())
            : 25; // More than 24 hours

        // Send alert once per day maximum
        if ($hoursSinceLastAlert < 24) {
            return;
        }

        // Find the highest severity alert
        $criticalAlerts = collect($alerts)->where('level', 'critical');
        $warningAlerts = collect($alerts)->where('level', 'warning');
        $infoAlerts = collect($alerts)->where('level', 'info');

        $alertLevel = null;
        $relevantAlerts = null;

        if ($criticalAlerts->count() > 0) {
            $alertLevel = 'critical';
            $relevantAlerts = $criticalAlerts;
        } elseif ($warningAlerts->count() > 0) {
            $alertLevel = 'warning';
            $relevantAlerts = $warningAlerts;
        } elseif ($infoAlerts->count() > 0) {
            $alertLevel = 'info';
            $relevantAlerts = $infoAlerts;
        }

        if ($alertLevel) {
            // Send notification
            $business->user->notify(new BudgetAlertNotification($business, $overview, $relevantAlerts->toArray(), $alertLevel));

            // Update last alert sent time
            $business->update([
                'last_budget_alert_sent_at' => now(),
            ]);

            // Log the alert
            activity()
                ->performedOn($business)
                ->causedBy($business->user)
                ->withProperties([
                    'alert_level' => $alertLevel,
                    'utilization' => $overview['utilization_percentage'],
                    'alerts_count' => $relevantAlerts->count(),
                ])
                ->log('Budget alert sent');
        }
    }
}
