<?php

namespace App\Services;

use App\Models\BusinessCancellationLog;
use App\Models\BusinessProfile;
use App\Models\Shift;
use App\Notifications\CancellationWarningNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancellationPatternService
{
    // Threshold constants
    const LATE_CANCEL_EMAIL_THRESHOLD = 3;
    const LATE_CANCEL_WARNING_THRESHOLD = 5;
    const CANCELLATION_RATE_WARNING = 15.0; // percentage
    const CANCELLATION_RATE_ACTION = 25.0; // percentage
    const ROLLING_WINDOW_DAYS = 30;

    /**
     * Log a shift cancellation and check for patterns.
     */
    public function logCancellation(Shift $shift, $cancelledByUserId, $cancellationType, $reason = null)
    {
        $businessProfile = $shift->businessProfile;

        // Calculate hours before shift
        $hoursBeforeShift = Carbon::parse($shift->start_time)->diffInHours(now(), false);
        $hoursBeforeShift = max(0, $hoursBeforeShift); // Ensure non-negative

        // Get current metrics
        $currentMetrics = $this->getCurrentMetrics($businessProfile->id);

        // Calculate cancellation fee if applicable
        $cancellationFee = $this->calculateCancellationFee($shift, $cancellationType, $hoursBeforeShift);

        // Create log entry
        $log = BusinessCancellationLog::create([
            'business_profile_id' => $businessProfile->id,
            'shift_id' => $shift->id,
            'cancelled_by_user_id' => $cancelledByUserId,
            'cancellation_type' => $cancellationType,
            'cancellation_reason' => $reason,
            'hours_before_shift' => abs($hoursBeforeShift),
            'shift_start_time' => $shift->start_time,
            'shift_end_time' => $shift->end_time,
            'shift_pay_rate' => $shift->pay_rate,
            'shift_role' => $shift->role,
            'cancellation_fee' => $cancellationFee,
            'total_cancellations_at_time' => $currentMetrics['total_cancellations'] + 1,
            'cancellations_last_30_days_at_time' => $currentMetrics['cancellations_30_days'] + 1,
            'cancellation_rate_at_time' => $this->calculateCancellationRate($businessProfile->id),
        ]);

        // Update business profile metrics
        $this->updateBusinessMetrics($businessProfile, $cancellationType);

        // Check patterns and take action if needed
        $this->checkPatternsAndTakeAction($businessProfile, $log);

        return $log;
    }

    /**
     * Get current cancellation metrics for a business.
     */
    public function getCurrentMetrics($businessProfileId)
    {
        $totalCancellations = BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->count();

        $cancellations30Days = BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->count();

        $lateCancellations30Days = BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->whereIn('cancellation_type', ['late', 'no_show'])
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->count();

        return [
            'total_cancellations' => $totalCancellations,
            'cancellations_30_days' => $cancellations30Days,
            'late_cancellations_30_days' => $lateCancellations30Days,
            'cancellation_rate' => $this->calculateCancellationRate($businessProfileId),
        ];
    }

    /**
     * Calculate cancellation rate for a business.
     */
    public function calculateCancellationRate($businessProfileId)
    {
        $totalShifts = Shift::where('business_profile_id', $businessProfileId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->count();

        if ($totalShifts === 0) {
            return 0;
        }

        $cancellations = BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->count();

        return round(($cancellations / $totalShifts) * 100, 2);
    }

    /**
     * Calculate cancellation fee based on type and notice.
     */
    protected function calculateCancellationFee(Shift $shift, $cancellationType, $hoursBeforeShift)
    {
        // No fee for on-time or emergency cancellations
        if (in_array($cancellationType, ['on_time', 'emergency'])) {
            return 0;
        }

        $baseFee = 0;

        // Late cancellation (less than 24 hours)
        if ($cancellationType === 'late' && $hoursBeforeShift < 24) {
            $baseFee = $shift->pay_rate * 0.5; // 50% of shift pay
        }

        // No-show (shift already started)
        if ($cancellationType === 'no_show') {
            $baseFee = $shift->pay_rate; // 100% of shift pay
        }

        return round($baseFee);
    }

    /**
     * Update business profile metrics.
     */
    protected function updateBusinessMetrics(BusinessProfile $businessProfile, $cancellationType)
    {
        $businessProfile->increment('total_shifts_cancelled');

        if (in_array($cancellationType, ['late', 'no_show'])) {
            $businessProfile->increment('total_late_cancellations');
            $businessProfile->last_late_cancellation_at = now();
        }

        // Update rolling 30-day count
        $lateCancellations30Days = BusinessCancellationLog::where('business_profile_id', $businessProfile->id)
            ->whereIn('cancellation_type', ['late', 'no_show'])
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->count();

        $businessProfile->late_cancellations_last_30_days = $lateCancellations30Days;
        $businessProfile->cancellation_rate = $this->calculateCancellationRate($businessProfile->id);
        $businessProfile->save();
    }

    /**
     * Check patterns and take action if thresholds are exceeded.
     */
    protected function checkPatternsAndTakeAction(BusinessProfile $businessProfile, BusinessCancellationLog $log)
    {
        $metrics = $this->getCurrentMetrics($businessProfile->id);
        $actionsRequired = [];

        // Check late cancellation count thresholds
        if ($metrics['late_cancellations_30_days'] >= self::LATE_CANCEL_EMAIL_THRESHOLD &&
            $metrics['late_cancellations_30_days'] < self::LATE_CANCEL_WARNING_THRESHOLD) {
            $actionsRequired[] = 'email_warning';
        }

        if ($metrics['late_cancellations_30_days'] >= self::LATE_CANCEL_WARNING_THRESHOLD) {
            $actionsRequired[] = 'dashboard_warning';
            $actionsRequired[] = 'increase_escrow';
        }

        // Check cancellation rate thresholds
        if ($metrics['cancellation_rate'] >= self::CANCELLATION_RATE_ACTION) {
            $actionsRequired[] = 'suspend_credit';
        } elseif ($metrics['cancellation_rate'] >= self::CANCELLATION_RATE_WARNING) {
            $actionsRequired[] = 'rate_warning';
        }

        // Execute actions
        foreach ($actionsRequired as $action) {
            $this->executeAction($businessProfile, $log, $action, $metrics);
        }
    }

    /**
     * Execute a specific action.
     */
    protected function executeAction(BusinessProfile $businessProfile, BusinessCancellationLog $log, $action, $metrics)
    {
        switch ($action) {
            case 'email_warning':
                $this->sendEmailWarning($businessProfile, $metrics);
                $log->update(['warning_issued' => true, 'action_taken_at' => now()]);
                break;

            case 'dashboard_warning':
                $this->sendDashboardWarning($businessProfile, $metrics);
                $log->update(['warning_issued' => true, 'action_taken_at' => now()]);
                break;

            case 'increase_escrow':
                $this->increaseEscrow($businessProfile);
                $log->update(['escrow_increased' => true, 'action_taken_at' => now()]);
                break;

            case 'suspend_credit':
                $this->suspendCredit($businessProfile, $metrics);
                $log->update(['credit_suspended' => true, 'action_taken_at' => now()]);
                break;

            case 'rate_warning':
                $this->sendRateWarning($businessProfile, $metrics);
                break;
        }
    }

    /**
     * Send email warning to business.
     */
    protected function sendEmailWarning(BusinessProfile $businessProfile, $metrics)
    {
        $user = $businessProfile->user;
        $user->notify(new CancellationWarningNotification($businessProfile, $metrics, 'email'));
    }

    /**
     * Send dashboard warning.
     */
    protected function sendDashboardWarning(BusinessProfile $businessProfile, $metrics)
    {
        $user = $businessProfile->user;
        $user->notify(new CancellationWarningNotification($businessProfile, $metrics, 'dashboard'));
    }

    /**
     * Increase escrow requirement for the business.
     */
    protected function increaseEscrow(BusinessProfile $businessProfile)
    {
        if (!$businessProfile->requires_increased_escrow) {
            $businessProfile->update([
                'requires_increased_escrow' => true,
            ]);

            // Log this action
            activity()
                ->performedOn($businessProfile)
                ->causedBy($businessProfile->user)
                ->withProperties(['action' => 'escrow_increased', 'reason' => 'excessive_late_cancellations'])
                ->log('Escrow requirement increased due to cancellation pattern');
        }
    }

    /**
     * Suspend credit for the business.
     */
    protected function suspendCredit(BusinessProfile $businessProfile, $metrics)
    {
        if (!$businessProfile->credit_suspended) {
            $reason = sprintf(
                'Cancellation rate of %.2f%% exceeds acceptable threshold of %.2f%%',
                $metrics['cancellation_rate'],
                self::CANCELLATION_RATE_ACTION
            );

            $businessProfile->update([
                'credit_suspended' => true,
                'credit_suspended_at' => now(),
                'credit_suspension_reason' => $reason,
            ]);

            // Send notification
            $user = $businessProfile->user;
            $user->notify(new CancellationWarningNotification($businessProfile, $metrics, 'credit_suspended'));

            // Log this action
            activity()
                ->performedOn($businessProfile)
                ->causedBy($businessProfile->user)
                ->withProperties(['action' => 'credit_suspended', 'reason' => $reason])
                ->log('Credit suspended due to high cancellation rate');
        }
    }

    /**
     * Send cancellation rate warning.
     */
    protected function sendRateWarning(BusinessProfile $businessProfile, $metrics)
    {
        $user = $businessProfile->user;
        $user->notify(new CancellationWarningNotification($businessProfile, $metrics, 'rate_warning'));
    }

    /**
     * Get cancellation history for a business.
     */
    public function getCancellationHistory($businessProfileId, $days = 30)
    {
        return BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->with(['shift', 'cancelledBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get cancellation statistics dashboard data.
     */
    public function getDashboardStats($businessProfileId)
    {
        $metrics = $this->getCurrentMetrics($businessProfileId);

        $recentLogs = BusinessCancellationLog::where('business_profile_id', $businessProfileId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::ROLLING_WINDOW_DAYS))
            ->get();

        $byType = $recentLogs->groupBy('cancellation_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_fee' => $group->sum('cancellation_fee'),
            ];
        });

        return [
            'metrics' => $metrics,
            'by_type' => $byType,
            'recent_logs' => $recentLogs->take(10),
            'warnings' => $this->getActiveWarnings($businessProfileId),
        ];
    }

    /**
     * Get active warnings for a business.
     */
    protected function getActiveWarnings($businessProfileId)
    {
        $metrics = $this->getCurrentMetrics($businessProfileId);
        $warnings = [];

        if ($metrics['late_cancellations_30_days'] >= self::LATE_CANCEL_WARNING_THRESHOLD) {
            $warnings[] = [
                'type' => 'late_cancellations',
                'severity' => 'high',
                'message' => "You have {$metrics['late_cancellations_30_days']} late cancellations in the last 30 days.",
            ];
        } elseif ($metrics['late_cancellations_30_days'] >= self::LATE_CANCEL_EMAIL_THRESHOLD) {
            $warnings[] = [
                'type' => 'late_cancellations',
                'severity' => 'medium',
                'message' => "You have {$metrics['late_cancellations_30_days']} late cancellations in the last 30 days.",
            ];
        }

        if ($metrics['cancellation_rate'] >= self::CANCELLATION_RATE_ACTION) {
            $warnings[] = [
                'type' => 'cancellation_rate',
                'severity' => 'critical',
                'message' => "Your cancellation rate of {$metrics['cancellation_rate']}% requires immediate attention.",
            ];
        } elseif ($metrics['cancellation_rate'] >= self::CANCELLATION_RATE_WARNING) {
            $warnings[] = [
                'type' => 'cancellation_rate',
                'severity' => 'high',
                'message' => "Your cancellation rate of {$metrics['cancellation_rate']}% is above the recommended threshold.",
            ];
        }

        return $warnings;
    }
}
