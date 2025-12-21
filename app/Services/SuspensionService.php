<?php

namespace App\Services;

use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\WorkerSuspension;
use App\Notifications\AppealDecisionNotification;
use App\Notifications\AppealReceivedNotification;
use App\Notifications\SuspensionIssuedNotification;
use App\Notifications\SuspensionLiftedNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WKR-009: Worker Suspension Service
 *
 * Handles all suspension-related operations including:
 * - Issuing suspensions with configurable durations
 * - Managing appeals workflow
 * - Automatic expiration and lifting of suspensions
 * - Strike tracking and escalation
 */
class SuspensionService
{
    /**
     * Issue a suspension to a worker.
     *
     * @param  array  $data  Suspension data including:
     *                       - type: warning|temporary|indefinite|permanent
     *                       - reason_category: no_show|late_cancellation|etc
     *                       - reason_details: string
     *                       - related_shift_id: optional
     *                       - duration_hours: optional (overrides calculated)
     *                       - affects_booking: optional
     *                       - affects_visibility: optional
     */
    public function issueSuspension(User $worker, array $data, User $admin): WorkerSuspension
    {
        return DB::transaction(function () use ($worker, $data, $admin) {
            // Calculate strike count and duration
            $strikeCount = $this->calculateStrikeCount($worker, $data['reason_category']);

            // Determine suspension type first if explicitly set
            $type = $data['type'] ?? null;

            // For indefinite or permanent types, duration is null
            if (in_array($type, [WorkerSuspension::TYPE_INDEFINITE, WorkerSuspension::TYPE_PERMANENT])) {
                $durationHours = null;
            } else {
                $durationHours = $data['duration_hours'] ?? $this->calculateSuspensionDuration(
                    $data['reason_category'],
                    $strikeCount
                );
            }

            // Determine suspension type if not explicitly set
            if (! $type) {
                $type = $this->determineType($durationHours, $strikeCount);
            }

            // Calculate dates
            $startsAt = Carbon::now();
            $endsAt = $durationHours ? $startsAt->copy()->addHours($durationHours) : null;

            // Get effect settings from config
            $effects = config("suspensions.effects.{$type}", [
                'affects_booking' => true,
                'affects_visibility' => false,
            ]);

            // Create the suspension record
            $suspension = WorkerSuspension::create([
                'user_id' => $worker->id,
                'type' => $type,
                'reason_category' => $data['reason_category'],
                'reason_details' => $data['reason_details'],
                'related_shift_id' => $data['related_shift_id'] ?? null,
                'issued_by' => $admin->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => WorkerSuspension::STATUS_ACTIVE,
                'affects_booking' => $data['affects_booking'] ?? $effects['affects_booking'],
                'affects_visibility' => $data['affects_visibility'] ?? $effects['affects_visibility'],
                'strike_count' => $strikeCount,
            ]);

            // Update user's suspension status
            $worker->update([
                'is_suspended' => true,
                'status' => 'suspended',
                'suspended_until' => $endsAt,
                'suspension_reason' => $data['reason_details'],
                'strike_count' => $strikeCount,
                'last_strike_at' => Carbon::now(),
                'suspension_count' => ($worker->suspension_count ?? 0) + 1,
                'last_suspended_at' => Carbon::now(),
            ]);

            // Cancel pending applications if suspension affects booking
            if ($suspension->affects_booking) {
                $this->cancelPendingApplications($worker, $suspension);
            }

            // Log the suspension
            Log::info('Worker suspension issued', [
                'suspension_id' => $suspension->id,
                'worker_id' => $worker->id,
                'admin_id' => $admin->id,
                'type' => $type,
                'reason_category' => $data['reason_category'],
                'duration_hours' => $durationHours,
                'strike_count' => $strikeCount,
            ]);

            // Send notification to worker
            $this->notifyWorkerOfSuspension($worker, $suspension);

            return $suspension;
        });
    }

    /**
     * Calculate suspension duration based on category and strike count.
     *
     * @return int|null Duration in hours, null for indefinite
     */
    public function calculateSuspensionDuration(string $category, int $strikeCount): ?int
    {
        $durations = config("suspensions.duration_by_category.{$category}", [
            '1st' => 24,
            '2nd' => 72,
            '3rd' => 168,
        ]);

        $offenseKey = match (true) {
            $strikeCount >= 3 => '3rd',
            $strikeCount === 2 => '2nd',
            default => '1st',
        };

        return $durations[$offenseKey] ?? 24;
    }

    /**
     * Lift a suspension (mark as completed).
     */
    public function liftSuspension(WorkerSuspension $suspension, ?string $notes = null, ?User $admin = null): WorkerSuspension
    {
        return DB::transaction(function () use ($suspension, $notes, $admin) {
            $suspension->update([
                'status' => WorkerSuspension::STATUS_COMPLETED,
            ]);

            $worker = $suspension->worker;

            // Check if worker has any other active suspensions
            $hasOtherActive = WorkerSuspension::forWorker($worker)
                ->active()
                ->where('id', '!=', $suspension->id)
                ->currentlyEffective()
                ->exists();

            if (! $hasOtherActive) {
                $worker->update([
                    'is_suspended' => false,
                    'status' => 'active',
                    'suspended_until' => null,
                    'suspension_reason' => null,
                ]);
            }

            Log::info('Worker suspension lifted', [
                'suspension_id' => $suspension->id,
                'worker_id' => $worker->id,
                'admin_id' => $admin?->id,
                'notes' => $notes,
            ]);

            // Notify worker
            $this->notifyWorkerOfLift($worker, $suspension, $notes);

            return $suspension;
        });
    }

    /**
     * Submit an appeal for a suspension.
     *
     * @param  array  $data  Appeal data including:
     *                       - appeal_reason: string
     *                       - supporting_evidence: optional array of evidence
     */
    public function submitAppeal(WorkerSuspension $suspension, User $worker, array $data): SuspensionAppeal
    {
        // Validate that worker owns this suspension
        if ($suspension->user_id !== $worker->id) {
            throw new \InvalidArgumentException('Worker does not own this suspension');
        }

        // Check if suspension can be appealed
        if (! $suspension->canBeAppealed()) {
            throw new \InvalidArgumentException('This suspension cannot be appealed');
        }

        return DB::transaction(function () use ($suspension, $worker, $data) {
            // Create the appeal
            $appeal = SuspensionAppeal::create([
                'suspension_id' => $suspension->id,
                'user_id' => $worker->id,
                'appeal_reason' => $data['appeal_reason'],
                'supporting_evidence' => $data['supporting_evidence'] ?? null,
                'status' => SuspensionAppeal::STATUS_PENDING,
            ]);

            // Update suspension status
            $suspension->update([
                'status' => WorkerSuspension::STATUS_APPEALED,
            ]);

            Log::info('Suspension appeal submitted', [
                'appeal_id' => $appeal->id,
                'suspension_id' => $suspension->id,
                'worker_id' => $worker->id,
            ]);

            // Notify admins of new appeal
            $this->notifyAdminsOfAppeal($appeal);

            return $appeal;
        });
    }

    /**
     * Review an appeal (approve or deny).
     *
     * @param  string  $decision  'approved' or 'denied'
     */
    public function reviewAppeal(
        SuspensionAppeal $appeal,
        string $decision,
        string $notes,
        User $admin
    ): SuspensionAppeal {
        // Validate decision
        if (! in_array($decision, [SuspensionAppeal::STATUS_APPROVED, SuspensionAppeal::STATUS_DENIED])) {
            throw new \InvalidArgumentException('Invalid decision. Must be approved or denied.');
        }

        // Check if notes required for denial
        if ($decision === SuspensionAppeal::STATUS_DENIED
            && config('suspensions.admin.require_denial_notes', true)
            && empty($notes)) {
            throw new \InvalidArgumentException('Notes are required when denying an appeal');
        }

        return DB::transaction(function () use ($appeal, $decision, $notes, $admin) {
            $appeal->update([
                'status' => $decision,
                'reviewed_by' => $admin->id,
                'review_notes' => $notes,
                'reviewed_at' => Carbon::now(),
            ]);

            $suspension = $appeal->suspension;

            if ($decision === SuspensionAppeal::STATUS_APPROVED) {
                // Overturn the suspension
                $suspension->update([
                    'status' => WorkerSuspension::STATUS_OVERTURNED,
                ]);

                // Lift the suspension effects on worker directly (don't use liftSuspension which would overwrite status)
                $worker = $suspension->worker;

                // Check if worker has any other active suspensions
                $hasOtherActive = WorkerSuspension::forWorker($worker)
                    ->active()
                    ->where('id', '!=', $suspension->id)
                    ->currentlyEffective()
                    ->exists();

                if (! $hasOtherActive) {
                    $worker->update([
                        'is_suspended' => false,
                        'status' => 'active',
                        'suspended_until' => null,
                        'suspension_reason' => null,
                    ]);
                }

                // Reduce strike count
                if ($worker->strike_count > 0) {
                    $worker->decrement('strike_count');
                }

                // Send notification
                $this->notifyWorkerOfLift($worker, $suspension, 'Appeal approved: '.$notes);
            } else {
                // Denied - revert suspension status to active
                $suspension->update([
                    'status' => WorkerSuspension::STATUS_ACTIVE,
                ]);
            }

            Log::info('Suspension appeal reviewed', [
                'appeal_id' => $appeal->id,
                'suspension_id' => $suspension->id,
                'decision' => $decision,
                'admin_id' => $admin->id,
            ]);

            // Notify worker of decision
            $this->notifyWorkerOfAppealDecision($appeal);

            return $appeal;
        });
    }

    /**
     * Check and lift all expired suspensions.
     *
     * @return int Number of suspensions lifted
     */
    public function checkAndLiftExpiredSuspensions(): int
    {
        $expiredSuspensions = WorkerSuspension::expired()->get();

        $count = 0;
        foreach ($expiredSuspensions as $suspension) {
            $this->liftSuspension($suspension, 'Automatic lift - suspension period ended');
            $count++;
        }

        if ($count > 0) {
            Log::info("Automatically lifted {$count} expired suspensions");
        }

        return $count;
    }

    /**
     * Get the active suspension for a worker (if any).
     */
    public function getActiveSuspension(User $worker): ?WorkerSuspension
    {
        return WorkerSuspension::forWorker($worker)
            ->currentlyEffective()
            ->with(['issuer', 'relatedShift', 'latestAppeal'])
            ->latest()
            ->first();
    }

    /**
     * Get suspension history for a worker.
     *
     * @return Collection<WorkerSuspension>
     */
    public function getSuspensionHistory(User $worker, ?int $limit = null): Collection
    {
        $query = WorkerSuspension::forWorker($worker)
            ->with(['issuer', 'relatedShift', 'appeals'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Calculate when strikes will expire for a worker.
     */
    public function calculateStrikeExpiry(User $worker): ?Carbon
    {
        if ($worker->strike_count === 0 || ! $worker->last_strike_at) {
            return null;
        }

        $expiryMonths = config('suspensions.strike_expiry_months', 12);

        if ($expiryMonths === 0) {
            return null; // Never expires
        }

        return $worker->last_strike_at->copy()->addMonths($expiryMonths);
    }

    /**
     * Reset strikes for a worker (admin action).
     */
    public function resetStrikes(User $worker, ?string $notes = null, ?User $admin = null): void
    {
        $previousCount = $worker->strike_count;

        $worker->update([
            'strike_count' => 0,
            'last_strike_at' => null,
        ]);

        Log::info('Worker strikes reset', [
            'worker_id' => $worker->id,
            'previous_count' => $previousCount,
            'admin_id' => $admin?->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Check if a worker can book shifts (not suspended or suspension doesn't affect booking).
     */
    public function canWorkerBook(User $worker): bool
    {
        $activeSuspension = $this->getActiveSuspension($worker);

        if (! $activeSuspension) {
            return true;
        }

        return ! $activeSuspension->affects_booking;
    }

    /**
     * Check if a worker should be visible in search results.
     */
    public function isWorkerVisible(User $worker): bool
    {
        $activeSuspension = $this->getActiveSuspension($worker);

        if (! $activeSuspension) {
            return true;
        }

        return ! $activeSuspension->affects_visibility;
    }

    /**
     * Get suspension analytics for admin dashboard.
     *
     * @return array{
     *     total_active: int,
     *     pending_appeals: int,
     *     by_category: array,
     *     by_type: array,
     *     recent_suspensions: Collection
     * }
     */
    public function getAnalytics(): array
    {
        $activeCount = WorkerSuspension::active()->count();
        $pendingAppeals = SuspensionAppeal::pending()->count();

        $byCategory = WorkerSuspension::active()
            ->selectRaw('reason_category, COUNT(*) as count')
            ->groupBy('reason_category')
            ->pluck('count', 'reason_category')
            ->toArray();

        $byType = WorkerSuspension::active()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $recentSuspensions = WorkerSuspension::with(['worker', 'issuer'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $averageResolutionHours = SuspensionAppeal::resolved()
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total_active' => $activeCount,
            'pending_appeals' => $pendingAppeals,
            'by_category' => $byCategory,
            'by_type' => $byType,
            'recent_suspensions' => $recentSuspensions,
            'average_appeal_resolution_hours' => round($averageResolutionHours ?? 0, 1),
        ];
    }

    // ==================== PROTECTED METHODS ====================

    /**
     * Calculate the current strike count for a worker in a category.
     */
    protected function calculateStrikeCount(User $worker, string $category): int
    {
        $expiryMonths = config('suspensions.strike_expiry_months', 12);
        $lookbackDate = $expiryMonths > 0
            ? Carbon::now()->subMonths($expiryMonths)
            : null;

        $query = WorkerSuspension::forWorker($worker)
            ->where('reason_category', $category);

        if ($lookbackDate) {
            $query->where('created_at', '>=', $lookbackDate);
        }

        return $query->count() + 1; // +1 for the current offense
    }

    /**
     * Determine suspension type based on duration and strike count.
     */
    protected function determineType(?int $durationHours, int $strikeCount): string
    {
        $maxStrikes = config('suspensions.max_strikes_before_permanent', 5);

        if ($strikeCount >= $maxStrikes) {
            return WorkerSuspension::TYPE_PERMANENT;
        }

        if ($durationHours === null) {
            return WorkerSuspension::TYPE_INDEFINITE;
        }

        if ($durationHours === 0) {
            return WorkerSuspension::TYPE_WARNING;
        }

        return WorkerSuspension::TYPE_TEMPORARY;
    }

    /**
     * Cancel pending shift applications for a suspended worker.
     */
    protected function cancelPendingApplications(User $worker, WorkerSuspension $suspension): int
    {
        $pendingApplications = ShiftApplication::where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($pendingApplications as $application) {
            $application->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Worker suspended: '.$suspension->reason_category,
            ]);
            $count++;
        }

        // Also cancel future assignments
        $futureAssignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'assigned')
            ->whereHas('shift', function ($q) {
                $q->where('shift_date', '>', Carbon::now()->toDateString());
            })
            ->get();

        foreach ($futureAssignments as $assignment) {
            $assignment->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => 'Worker suspended: '.$suspension->reason_category,
            ]);
            $count++;
        }

        if ($count > 0) {
            Log::info("Cancelled {$count} pending applications/assignments for suspended worker", [
                'worker_id' => $worker->id,
                'suspension_id' => $suspension->id,
            ]);
        }

        return $count;
    }

    /**
     * Send suspension notification to worker.
     */
    protected function notifyWorkerOfSuspension(User $worker, WorkerSuspension $suspension): void
    {
        try {
            $worker->notify(new SuspensionIssuedNotification($suspension));
        } catch (\Exception $e) {
            Log::error('Failed to send suspension notification', [
                'worker_id' => $worker->id,
                'suspension_id' => $suspension->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send suspension lifted notification to worker.
     */
    protected function notifyWorkerOfLift(User $worker, WorkerSuspension $suspension, ?string $notes): void
    {
        try {
            $worker->notify(new SuspensionLiftedNotification($suspension, $notes));
        } catch (\Exception $e) {
            Log::error('Failed to send suspension lifted notification', [
                'worker_id' => $worker->id,
                'suspension_id' => $suspension->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins of new appeal.
     */
    protected function notifyAdminsOfAppeal(SuspensionAppeal $appeal): void
    {
        try {
            // Get admins to notify (could be configured or all admins)
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new AppealReceivedNotification($appeal));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send appeal notification to admins', [
                'appeal_id' => $appeal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify worker of appeal decision.
     */
    protected function notifyWorkerOfAppealDecision(SuspensionAppeal $appeal): void
    {
        try {
            $appeal->worker->notify(new AppealDecisionNotification($appeal));
        } catch (\Exception $e) {
            Log::error('Failed to send appeal decision notification', [
                'appeal_id' => $appeal->id,
                'worker_id' => $appeal->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
