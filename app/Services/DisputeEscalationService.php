<?php

namespace App\Services;

use App\Models\AdminDisputeQueue;
use App\Models\DisputeEscalation;
use App\Models\PaymentAdjustment;
use App\Models\User;
use App\Notifications\DisputeEscalatedNotification;
use App\Notifications\DisputeSLABreachWarningNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisputeEscalationService
 *
 * Handles automated dispute escalation based on SLA thresholds,
 * priority levels, and escalation rules.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * SLA Thresholds:
 * - Standard (low/medium): 5 days
 * - Urgent (high): 2 days
 * - Critical: 1 day
 */
class DisputeEscalationService
{
    /**
     * SLA thresholds in hours by priority.
     */
    public const SLA_THRESHOLDS = [
        'low' => 120,      // 5 days
        'medium' => 120,   // 5 days
        'high' => 48,      // 2 days
        'urgent' => 24,    // 1 day (critical)
    ];

    /**
     * Warning threshold percentage (when to send warning notification).
     */
    public const WARNING_THRESHOLD_PERCENT = 80;

    /**
     * Escalation levels and their targets.
     */
    public const ESCALATION_LEVELS = [
        1 => 'senior_admin',
        2 => 'supervisor',
        3 => 'manager',
    ];

    /**
     * Get SLA deadline for a dispute.
     *
     * @param AdminDisputeQueue $dispute
     * @return Carbon
     */
    public function getSLADeadline(AdminDisputeQueue $dispute): Carbon
    {
        $threshold = self::SLA_THRESHOLDS[$dispute->priority] ?? self::SLA_THRESHOLDS['medium'];
        $startTime = $dispute->assigned_at ?? $dispute->filed_at;

        return Carbon::parse($startTime)->addHours($threshold);
    }

    /**
     * Get remaining SLA time in hours.
     *
     * @param AdminDisputeQueue $dispute
     * @return float
     */
    public function getRemainingHours(AdminDisputeQueue $dispute): float
    {
        $deadline = $this->getSLADeadline($dispute);
        $remaining = now()->diffInMinutes($deadline, false) / 60;

        return max(0, $remaining);
    }

    /**
     * Get SLA percentage elapsed.
     *
     * @param AdminDisputeQueue $dispute
     * @return float
     */
    public function getSLAPercentage(AdminDisputeQueue $dispute): float
    {
        $threshold = self::SLA_THRESHOLDS[$dispute->priority] ?? self::SLA_THRESHOLDS['medium'];
        $startTime = $dispute->assigned_at ?? $dispute->filed_at;
        $elapsed = Carbon::parse($startTime)->diffInHours(now());

        $percentage = ($elapsed / $threshold) * 100;

        return min(100, max(0, $percentage));
    }

    /**
     * Check if dispute is approaching SLA breach (80% of time elapsed).
     *
     * @param AdminDisputeQueue $dispute
     * @return bool
     */
    public function isApproachingBreach(AdminDisputeQueue $dispute): bool
    {
        $percentage = $this->getSLAPercentage($dispute);

        return $percentage >= self::WARNING_THRESHOLD_PERCENT && $percentage < 100;
    }

    /**
     * Check if dispute has breached SLA.
     *
     * @param AdminDisputeQueue $dispute
     * @return bool
     */
    public function hasBreachedSLA(AdminDisputeQueue $dispute): bool
    {
        return $this->getSLAPercentage($dispute) >= 100;
    }

    /**
     * Get disputes approaching SLA breach.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDisputesApproachingBreach()
    {
        return AdminDisputeQueue::whereIn('status', ['pending', 'investigating', 'evidence_review'])
            ->whereNull('escalated_at')
            ->whereNull('sla_warning_sent_at')
            ->get()
            ->filter(fn($dispute) => $this->isApproachingBreach($dispute));
    }

    /**
     * Get disputes that have breached SLA.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBreachedDisputes()
    {
        return AdminDisputeQueue::whereIn('status', ['pending', 'investigating', 'evidence_review'])
            ->get()
            ->filter(fn($dispute) => $this->hasBreachedSLA($dispute));
    }

    /**
     * Send SLA breach warning notification.
     *
     * @param AdminDisputeQueue $dispute
     * @return void
     */
    public function sendBreachWarning(AdminDisputeQueue $dispute): void
    {
        if ($dispute->sla_warning_sent_at) {
            return; // Already sent
        }

        $assignedAdmin = $dispute->assignedAdmin;

        if ($assignedAdmin) {
            $assignedAdmin->notify(new DisputeSLABreachWarningNotification($dispute));

            $dispute->update([
                'sla_warning_sent_at' => now(),
            ]);

            $this->logEscalationEvent($dispute, 'sla_warning_sent', [
                'notified_admin_id' => $assignedAdmin->id,
                'sla_percentage' => $this->getSLAPercentage($dispute),
            ]);
        }
    }

    /**
     * Escalate a dispute to senior admin.
     *
     * @param AdminDisputeQueue $dispute
     * @param string $reason
     * @return DisputeEscalation
     */
    public function escalateDispute(AdminDisputeQueue $dispute, string $reason = 'SLA breach'): DisputeEscalation
    {
        return DB::transaction(function () use ($dispute, $reason) {
            // Determine escalation level
            $currentLevel = $dispute->escalation_level ?? 0;
            $newLevel = min($currentLevel + 1, 3);

            // Find senior admin to assign
            $seniorAdmin = $this->findEscalationTarget($dispute, $newLevel);

            $previousAdmin = $dispute->assigned_to_admin;

            // Create escalation record
            $escalation = DisputeEscalation::create([
                'dispute_id' => $dispute->id,
                'escalation_level' => $newLevel,
                'escalation_reason' => $reason,
                'escalated_from_admin_id' => $previousAdmin,
                'escalated_to_admin_id' => $seniorAdmin?->id,
                'sla_hours_at_escalation' => $this->getRemainingHours($dispute),
                'escalated_at' => now(),
            ]);

            // Update dispute
            $dispute->update([
                'escalation_level' => $newLevel,
                'escalated_at' => now(),
                'previous_assigned_admin' => $previousAdmin,
                'assigned_to_admin' => $seniorAdmin?->id,
                'assigned_at' => now(),
                'priority' => $this->upgradePriority($dispute->priority),
            ]);

            // Notify senior admin
            if ($seniorAdmin) {
                $seniorAdmin->notify(new DisputeEscalatedNotification($dispute, $escalation));
            }

            // Log the escalation
            $this->logEscalationEvent($dispute, 'escalated', [
                'from_admin_id' => $previousAdmin,
                'to_admin_id' => $seniorAdmin?->id,
                'escalation_level' => $newLevel,
                'reason' => $reason,
            ]);

            return $escalation;
        });
    }

    /**
     * Find appropriate admin for escalation.
     *
     * @param AdminDisputeQueue $dispute
     * @param int $level
     * @return User|null
     */
    protected function findEscalationTarget(AdminDisputeQueue $dispute, int $level): ?User
    {
        $roleType = self::ESCALATION_LEVELS[$level] ?? 'senior_admin';

        // Find admin with appropriate role and lowest current workload
        return User::where('role', 'admin')
            ->where('status', 'active')
            ->where(function ($query) use ($roleType) {
                $query->where('admin_role', $roleType)
                    ->orWhere('admin_level', '>=', 2); // Fallback to any senior admin
            })
            ->where('id', '!=', $dispute->assigned_to_admin) // Don't reassign to same admin
            ->withCount(['assignedDisputes' => function ($query) {
                $query->whereIn('status', ['pending', 'investigating', 'evidence_review']);
            }])
            ->orderBy('assigned_disputes_count', 'asc')
            ->first();
    }

    /**
     * Upgrade priority level on escalation.
     *
     * @param string $currentPriority
     * @return string
     */
    protected function upgradePriority(string $currentPriority): string
    {
        return match ($currentPriority) {
            'low' => 'medium',
            'medium' => 'high',
            'high' => 'urgent',
            'urgent' => 'urgent', // Already at max
            default => 'high',
        };
    }

    /**
     * Auto-assign dispute to available admin.
     *
     * @param AdminDisputeQueue $dispute
     * @return User|null
     */
    public function autoAssignDispute(AdminDisputeQueue $dispute): ?User
    {
        if ($dispute->assigned_to_admin) {
            return $dispute->assignedAdmin;
        }

        $admin = User::where('role', 'admin')
            ->where('status', 'active')
            ->withCount(['assignedDisputes' => function ($query) {
                $query->whereIn('status', ['pending', 'investigating', 'evidence_review']);
            }])
            ->orderBy('assigned_disputes_count', 'asc')
            ->first();

        if ($admin) {
            $dispute->assignTo($admin->id);

            $this->logEscalationEvent($dispute, 'auto_assigned', [
                'assigned_to_id' => $admin->id,
            ]);
        }

        return $admin;
    }

    /**
     * Apply resolution adjustment when dispute is resolved.
     *
     * @param AdminDisputeQueue $dispute
     * @param string $outcome
     * @param float|null $adjustmentAmount
     * @param string|null $notes
     * @return PaymentAdjustment|null
     */
    public function applyResolutionAdjustment(
        AdminDisputeQueue $dispute,
        string $outcome,
        ?float $adjustmentAmount = null,
        ?string $notes = null
    ): ?PaymentAdjustment {
        if (!$adjustmentAmount || $adjustmentAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($dispute, $outcome, $adjustmentAmount, $notes) {
            // Create adjustment record
            $adjustment = PaymentAdjustment::create([
                'dispute_id' => $dispute->id,
                'shift_payment_id' => $dispute->shift_payment_id,
                'adjustment_type' => $this->getAdjustmentType($outcome),
                'amount' => $adjustmentAmount,
                'currency' => 'USD', // Default, should be from payment
                'reason' => $notes ?? "Dispute #{$dispute->id} resolved: {$outcome}",
                'applied_to' => $outcome === 'worker_favor' ? 'worker' : 'business',
                'worker_id' => $dispute->worker_id,
                'business_id' => $dispute->business_id,
                'created_by_admin_id' => auth()->id(),
                'status' => 'pending',
            ]);

            // Update shift payment if exists
            if ($dispute->shiftPayment) {
                $payment = $dispute->shiftPayment;

                if ($outcome === 'worker_favor') {
                    // Release additional amount to worker
                    $payment->update([
                        'dispute_adjustment_amount' => $adjustmentAmount,
                        'dispute_status' => 'resolved',
                        'worker_amount' => $payment->worker_amount + $adjustmentAmount,
                    ]);
                } elseif ($outcome === 'business_favor') {
                    // Refund amount to business
                    $payment->update([
                        'dispute_adjustment_amount' => -$adjustmentAmount,
                        'dispute_status' => 'resolved',
                        'refund_amount' => ($payment->refund_amount ?? 0) + $adjustmentAmount,
                    ]);
                } elseif ($outcome === 'split') {
                    // Split adjustment between parties
                    $halfAmount = $adjustmentAmount / 2;
                    $payment->update([
                        'dispute_adjustment_amount' => $halfAmount,
                        'dispute_status' => 'resolved',
                        'worker_amount' => $payment->worker_amount + $halfAmount,
                        'refund_amount' => ($payment->refund_amount ?? 0) + $halfAmount,
                    ]);
                }
            }

            // Mark adjustment as applied
            $adjustment->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            $this->logEscalationEvent($dispute, 'adjustment_applied', [
                'adjustment_id' => $adjustment->id,
                'amount' => $adjustmentAmount,
                'outcome' => $outcome,
            ]);

            return $adjustment;
        });
    }

    /**
     * Get adjustment type from outcome.
     *
     * @param string $outcome
     * @return string
     */
    protected function getAdjustmentType(string $outcome): string
    {
        return match ($outcome) {
            'worker_favor' => 'worker_payout',
            'business_favor' => 'business_refund',
            'split' => 'split_resolution',
            'no_fault' => 'no_adjustment',
            default => 'other',
        };
    }

    /**
     * Get dispute statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $disputes = AdminDisputeQueue::all();

        return [
            'total_active' => $disputes->whereIn('status', ['pending', 'investigating', 'evidence_review'])->count(),
            'total_resolved' => $disputes->where('status', 'resolved')->count(),
            'total_escalated' => $disputes->whereNotNull('escalated_at')->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime(),
            'sla_breach_rate' => $this->calculateSLABreachRate(),
            'by_priority' => [
                'low' => $disputes->where('priority', 'low')->where('status', '!=', 'resolved')->count(),
                'medium' => $disputes->where('priority', 'medium')->where('status', '!=', 'resolved')->count(),
                'high' => $disputes->where('priority', 'high')->where('status', '!=', 'resolved')->count(),
                'urgent' => $disputes->where('priority', 'urgent')->where('status', '!=', 'resolved')->count(),
            ],
            'approaching_breach' => $this->getDisputesApproachingBreach()->count(),
            'breached' => $this->getBreachedDisputes()->count(),
        ];
    }

    /**
     * Calculate average resolution time in hours.
     *
     * @return float
     */
    protected function calculateAverageResolutionTime(): float
    {
        $resolved = AdminDisputeQueue::where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalHours = $resolved->sum(function ($dispute) {
            return Carbon::parse($dispute->filed_at)->diffInHours($dispute->resolved_at);
        });

        return round($totalHours / $resolved->count(), 1);
    }

    /**
     * Calculate SLA breach rate percentage.
     *
     * @return float
     */
    protected function calculateSLABreachRate(): float
    {
        $resolved = AdminDisputeQueue::where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolved->isEmpty()) {
            return 0;
        }

        $breached = $resolved->filter(function ($dispute) {
            $threshold = self::SLA_THRESHOLDS[$dispute->priority] ?? self::SLA_THRESHOLDS['medium'];
            $resolutionTime = Carbon::parse($dispute->filed_at)->diffInHours($dispute->resolved_at);
            return $resolutionTime > $threshold;
        })->count();

        return round(($breached / $resolved->count()) * 100, 1);
    }

    /**
     * Log escalation event for audit trail.
     *
     * @param AdminDisputeQueue $dispute
     * @param string $event
     * @param array $data
     * @return void
     */
    protected function logEscalationEvent(AdminDisputeQueue $dispute, string $event, array $data = []): void
    {
        Log::channel('disputes')->info("Dispute Escalation Event: {$event}", [
            'dispute_id' => $dispute->id,
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
