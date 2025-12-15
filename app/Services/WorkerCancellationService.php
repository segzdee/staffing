<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling worker shift cancellations with reliability scoring.
 * SL-010: Complete Worker Cancellation Logic
 */
class WorkerCancellationService
{
    /**
     * Cancel a shift assignment by worker.
     *
     * @param ShiftAssignment $assignment
     * @param array $cancellationData
     * @return array
     */
    public function cancelByWorker(ShiftAssignment $assignment, array $cancellationData): array
    {
        DB::beginTransaction();

        try {
            // Validate cancellation is allowed
            $this->validateCancellation($assignment);

            // Calculate hours until shift start
            $hoursUntilShift = $this->calculateHoursUntilShift($assignment->shift);

            // Determine cancellation type
            $isExcused = $cancellationData['is_excused'] ?? false;
            $reason = $cancellationData['reason'] ?? 'No reason provided';
            $supportingDocuments = $cancellationData['documents'] ?? [];

            // Calculate reliability score impact
            $impact = $this->calculateReliabilityImpact($hoursUntilShift, $isExcused);

            // Update assignment status
            $assignment->update([
                'status' => $isExcused ? 'cancelled_excused' : 'cancelled_by_worker',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancellation_hours_notice' => $hoursUntilShift,
                'cancellation_excused' => $isExcused,
                'cancellation_documents' => $supportingDocuments,
                'cancellation_pending_review' => $isExcused, // Excused cancellations need admin review
            ]);

            // Update worker profile if not excused
            if (!$isExcused) {
                $this->applyReliabilityPenalty($assignment->worker, $impact);
                $this->checkSuspensionTriggers($assignment->worker);
            }

            // Increment shift cancellation counter
            $assignment->worker->workerProfile->increment('total_cancellations');

            // Re-open shift for applications
            $this->reopenShiftForApplications($assignment->shift);

            // Notify business of cancellation
            $this->notifyBusinessOfCancellation($assignment, $hoursUntilShift, $isExcused);

            // Apply financial penalties if applicable
            $financialImpact = $this->applyFinancialPenalties($assignment, $hoursUntilShift, $isExcused);

            DB::commit();

            Log::info('Worker cancelled shift', [
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'hours_notice' => $hoursUntilShift,
                'is_excused' => $isExcused,
                'reliability_impact' => $impact,
            ]);

            return [
                'success' => true,
                'hours_notice' => $hoursUntilShift,
                'reliability_impact' => $impact,
                'financial_impact' => $financialImpact,
                'requires_review' => $isExcused,
                'message' => $this->getCancellationMessage($hoursUntilShift, $isExcused),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process worker cancellation', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate reliability score impact based on notice period.
     * SL-010: Reliability scoring rules
     *
     * @param float $hoursUntilShift
     * @param bool $isExcused
     * @return int
     */
    public function calculateReliabilityImpact(float $hoursUntilShift, bool $isExcused): int
    {
        // Excused cancellations have no impact (pending admin review)
        if ($isExcused) {
            return 0;
        }

        // Calculate impact based on hours notice
        if ($hoursUntilShift > 72) {
            return -2; // >72 hours: -2 points
        } elseif ($hoursUntilShift > 24) {
            return -5; // 24-72 hours: -5 points
        } elseif ($hoursUntilShift > 12) {
            return -10; // 12-24 hours: -10 points
        } elseif ($hoursUntilShift > 0) {
            return -20; // <12 hours: -20 points
        } else {
            return -40; // No-show (negative hours): -40 points
        }
    }

    /**
     * Apply reliability penalty to worker profile.
     *
     * @param User $worker
     * @param int $impact
     * @return void
     */
    protected function applyReliabilityPenalty(User $worker, int $impact): void
    {
        if ($impact === 0) {
            return;
        }

        $profile = $worker->workerProfile;
        $currentScore = $profile->reliability_score ?? 100;
        $newScore = max(0, $currentScore + $impact); // Impact is negative, so this reduces score

        $profile->update([
            'reliability_score' => $newScore,
        ]);

        Log::info('Reliability penalty applied', [
            'worker_id' => $worker->id,
            'previous_score' => $currentScore,
            'new_score' => $newScore,
            'impact' => $impact,
        ]);
    }

    /**
     * Check if worker should be suspended based on reliability metrics.
     *
     * @param User $worker
     * @return void
     */
    protected function checkSuspensionTriggers(User $worker): void
    {
        $profile = $worker->workerProfile;

        // Suspension triggers
        $shouldSuspend = false;
        $suspensionReason = null;

        // Trigger 1: Reliability score below 50
        if ($profile->reliability_score < 50) {
            $shouldSuspend = true;
            $suspensionReason = 'Reliability score below minimum threshold (50)';
        }

        // Trigger 2: 3+ cancellations in last 30 days
        $recentCancellations = ShiftAssignment::where('worker_id', $worker->id)
            ->whereIn('status', ['cancelled_by_worker', 'cancelled_excused'])
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->count();

        if ($recentCancellations >= 3) {
            $shouldSuspend = true;
            $suspensionReason = '3 or more cancellations in the last 30 days';
        }

        // Trigger 3: 2+ no-shows in last 60 days
        $recentNoShows = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'no_show')
            ->where('cancelled_at', '>=', now()->subDays(60))
            ->count();

        if ($recentNoShows >= 2) {
            $shouldSuspend = true;
            $suspensionReason = '2 or more no-shows in the last 60 days';
        }

        // Apply suspension
        if ($shouldSuspend) {
            $this->suspendWorker($worker, $suspensionReason);
        }
    }

    /**
     * Suspend worker account.
     *
     * @param User $worker
     * @param string $reason
     * @return void
     */
    protected function suspendWorker(User $worker, string $reason): void
    {
        $worker->update([
            'status' => 'suspended',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);

        // Cancel all future shift assignments
        ShiftAssignment::where('worker_id', $worker->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('shift', function ($query) {
                $query->where('start_time', '>', now());
            })
            ->update([
                'status' => 'cancelled_by_system',
                'cancellation_reason' => 'Worker account suspended: ' . $reason,
                'cancelled_at' => now(),
            ]);

        // Send suspension notification
        $worker->notify(new \App\Notifications\WorkerSuspendedNotification($reason));

        Log::warning('Worker account suspended', [
            'worker_id' => $worker->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Calculate hours until shift start.
     *
     * @param Shift $shift
     * @return float
     */
    protected function calculateHoursUntilShift(Shift $shift): float
    {
        $now = Carbon::now();
        $shiftStart = Carbon::parse($shift->start_time);

        return $now->floatDiffInHours($shiftStart, false);
    }

    /**
     * Validate that cancellation is allowed.
     *
     * @param ShiftAssignment $assignment
     * @return void
     * @throws \Exception
     */
    protected function validateCancellation(ShiftAssignment $assignment): void
    {
        // Cannot cancel if shift is already in progress or completed
        if (in_array($assignment->status, ['in_progress', 'completed', 'paid'])) {
            throw new \Exception('Cannot cancel shift that is already in progress or completed.');
        }

        // Cannot cancel if already cancelled
        if (str_contains($assignment->status, 'cancelled')) {
            throw new \Exception('This shift has already been cancelled.');
        }
    }

    /**
     * Re-open shift for new applications.
     *
     * @param Shift $shift
     * @return void
     */
    protected function reopenShiftForApplications(Shift $shift): void
    {
        // If shift doesn't have enough workers, re-open it
        $confirmedWorkers = ShiftAssignment::where('shift_id', $shift->id)
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedWorkers < $shift->workers_needed) {
            $shift->update([
                'status' => 'open',
            ]);
        }
    }

    /**
     * Notify business of worker cancellation.
     *
     * @param ShiftAssignment $assignment
     * @param float $hoursNotice
     * @param bool $isExcused
     * @return void
     */
    protected function notifyBusinessOfCancellation(ShiftAssignment $assignment, float $hoursNotice, bool $isExcused): void
    {
        $business = $assignment->shift->business;
        $business->notify(new \App\Notifications\WorkerCancelledShiftNotification(
            $assignment,
            $hoursNotice,
            $isExcused
        ));
    }

    /**
     * Apply financial penalties for late cancellations.
     *
     * @param ShiftAssignment $assignment
     * @param float $hoursNotice
     * @param bool $isExcused
     * @return array
     */
    protected function applyFinancialPenalties(ShiftAssignment $assignment, float $hoursNotice, bool $isExcused): array
    {
        // No penalties for excused cancellations or >48 hour notice
        if ($isExcused || $hoursNotice > 48) {
            return [
                'penalty_applied' => false,
                'penalty_amount' => 0,
            ];
        }

        $profile = $assignment->worker->workerProfile;
        $tierBenefits = $profile->getTierBenefits();
        $penaltyReduction = $tierBenefits['cancellation_penalty_reduction'] ?? 0;

        // Base penalty: $10 for <48 hours, $25 for <12 hours
        $basePenalty = $hoursNotice < 12 ? 2500 : 1000; // in cents

        // Apply tier discount
        $finalPenalty = $basePenalty * (1 - ($penaltyReduction / 100));

        // Deduct from pending earnings (if available)
        if ($profile->pending_earnings >= ($finalPenalty / 100)) {
            $profile->decrement('pending_earnings', $finalPenalty / 100);
        }

        return [
            'penalty_applied' => true,
            'penalty_amount' => $finalPenalty / 100,
            'penalty_reduction' => $penaltyReduction,
        ];
    }

    /**
     * Get cancellation message for worker.
     *
     * @param float $hoursNotice
     * @param bool $isExcused
     * @return string
     */
    protected function getCancellationMessage(float $hoursNotice, bool $isExcused): string
    {
        if ($isExcused) {
            return 'Your cancellation request has been submitted for review. You will be notified once an admin reviews your request.';
        }

        if ($hoursNotice > 72) {
            return 'Shift cancelled successfully. Thank you for providing advance notice.';
        } elseif ($hoursNotice > 24) {
            return 'Shift cancelled. Please note that cancellations with less than 72 hours notice impact your reliability score.';
        } elseif ($hoursNotice > 12) {
            return 'Shift cancelled. Your reliability score has been significantly impacted due to short notice.';
        } else {
            return 'Shift cancelled. Your reliability score has been severely impacted. Multiple short-notice cancellations may result in account suspension.';
        }
    }

    /**
     * Submit appeal for cancellation penalty or suspension.
     *
     * @param User $worker
     * @param array $appealData
     * @return bool
     */
    public function submitAppeal(User $worker, array $appealData): bool
    {
        DB::beginTransaction();

        try {
            // Create appeal record (you would need to create an Appeal model)
            // For now, we'll just log it
            Log::info('Cancellation appeal submitted', [
                'worker_id' => $worker->id,
                'appeal_reason' => $appealData['reason'],
                'supporting_documents' => $appealData['documents'] ?? [],
            ]);

            // Update worker status to indicate pending appeal
            if ($worker->status === 'suspended') {
                $worker->update([
                    'appeal_pending' => true,
                    'appeal_submitted_at' => now(),
                ]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit appeal', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process no-show (worker didn't show up to shift).
     *
     * @param ShiftAssignment $assignment
     * @return void
     */
    public function processNoShow(ShiftAssignment $assignment): void
    {
        DB::beginTransaction();

        try {
            // Mark as no-show
            $assignment->update([
                'status' => 'no_show',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Worker did not show up to shift',
            ]);

            // Apply maximum reliability penalty (-40 points)
            $this->applyReliabilityPenalty($assignment->worker, -40);

            // Increment no-show counter
            $assignment->worker->workerProfile->increment('total_no_shows');

            // Check suspension triggers
            $this->checkSuspensionTriggers($assignment->worker);

            // Notify worker
            $assignment->worker->notify(new \App\Notifications\NoShowRecordedNotification($assignment));

            DB::commit();

            Log::warning('No-show recorded', [
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process no-show', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
