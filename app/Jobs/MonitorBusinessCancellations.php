<?php

namespace App\Jobs;

use App\Models\BusinessProfile;
use App\Services\CancellationPatternService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorBusinessCancellations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    /**
     * Execute the job.
     */
    public function handle(CancellationPatternService $cancellationPatternService)
    {
        // Get all active businesses
        $businesses = BusinessProfile::whereHas('user', function ($query) {
            $query->where('is_active', true);
        })->get();

        foreach ($businesses as $business) {
            $this->checkCancellationPatterns($business, $cancellationPatternService);
        }
    }

    /**
     * Check cancellation patterns for a business.
     */
    protected function checkCancellationPatterns(BusinessProfile $business, CancellationPatternService $cancellationPatternService)
    {
        // Get current metrics
        $metrics = $cancellationPatternService->getCurrentMetrics($business->id);

        // Update business profile with latest metrics
        $business->update([
            'late_cancellations_last_30_days' => $metrics['late_cancellations_30_days'],
            'cancellation_rate' => $metrics['cancellation_rate'],
        ]);

        // Get active warnings
        $warnings = $cancellationPatternService->getDashboardStats($business->id)['warnings'];

        // Log if there are new critical warnings
        if (!empty($warnings)) {
            $criticalWarnings = collect($warnings)->where('severity', 'critical');

            if ($criticalWarnings->count() > 0) {
                activity()
                    ->performedOn($business)
                    ->withProperties([
                        'warnings' => $warnings,
                        'metrics' => $metrics,
                    ])
                    ->log('Critical cancellation pattern detected');
            }
        }

        // Check if credit should be suspended
        if ($metrics['cancellation_rate'] >= CancellationPatternService::CANCELLATION_RATE_ACTION) {
            if (!$business->credit_suspended) {
                // Credit suspension is handled by the service when logging a cancellation
                // but we double-check here in case it was missed
                activity()
                    ->performedOn($business)
                    ->withProperties(['cancellation_rate' => $metrics['cancellation_rate']])
                    ->log('Business requires credit suspension review');
            }
        }

        // Check if escrow should be increased
        if ($metrics['late_cancellations_30_days'] >= CancellationPatternService::LATE_CANCEL_WARNING_THRESHOLD) {
            if (!$business->requires_increased_escrow) {
                activity()
                    ->performedOn($business)
                    ->withProperties(['late_cancellations' => $metrics['late_cancellations_30_days']])
                    ->log('Business requires escrow increase review');
            }
        }
    }
}
