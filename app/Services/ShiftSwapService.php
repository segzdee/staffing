<?php

namespace App\Services;

use App\Models\ShiftSwap;
use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftSwapService
{
    /**
     * Validate if a worker is eligible to offer a shift swap.
     *
     * @param User $worker
     * @param ShiftAssignment $assignment
     * @return array ['eligible' => bool, 'reason' => string]
     */
    public function validateSwapEligibility(User $worker, ShiftAssignment $assignment)
    {
        // Check worker owns the assignment
        if ($assignment->worker_id !== $worker->id) {
            return [
                'eligible' => false,
                'reason' => 'You can only swap your own shifts.',
            ];
        }

        // Check assignment status
        if ($assignment->status !== 'assigned') {
            return [
                'eligible' => false,
                'reason' => 'Only assigned shifts can be swapped.',
            ];
        }

        // Check if shift is too soon (must be at least 24 hours away)
        $shift = $assignment->shift;
        $hoursUntilShift = Carbon::parse($shift->shift_date . ' ' . $shift->start_time)
            ->diffInHours(Carbon::now());

        if ($hoursUntilShift < 24) {
            return [
                'eligible' => false,
                'reason' => 'Shifts must be at least 24 hours away to swap.',
            ];
        }

        // Check if already has pending swap
        $existingSwap = ShiftSwap::where('shift_assignment_id', $assignment->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($existingSwap) {
            return [
                'eligible' => false,
                'reason' => 'This shift already has a pending swap request.',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate if a worker can accept a shift swap.
     *
     * @param User $acceptingWorker
     * @param ShiftSwap $shiftSwap
     * @return array ['eligible' => bool, 'reason' => string]
     */
    public function validateSwapAcceptance(User $acceptingWorker, ShiftSwap $shiftSwap)
    {
        $offeringAssignment = $shiftSwap->offeringAssignment;
        $shift = $offeringAssignment->shift;

        // Check worker is not the one offering
        if ($offeringAssignment->worker_id === $acceptingWorker->id) {
            return [
                'eligible' => false,
                'reason' => 'You cannot accept your own swap offer.',
            ];
        }

        // Check for conflicting assignments
        $conflictingAssignment = ShiftAssignment::where('worker_id', $acceptingWorker->id)
            ->whereHas('shift', function($q) use ($shift) {
                $q->where('shift_date', $shift->shift_date)
                  ->where(function($query) use ($shift) {
                      $query->whereBetween('start_time', [$shift->start_time, $shift->end_time])
                            ->orWhereBetween('end_time', [$shift->start_time, $shift->end_time]);
                  });
            })
            ->whereIn('status', ['assigned', 'checked_in'])
            ->exists();

        if ($conflictingAssignment) {
            return [
                'eligible' => false,
                'reason' => 'You already have a shift assigned during this time.',
            ];
        }

        // Check worker meets shift requirements
        $workerProfile = $acceptingWorker->workerProfile;
        if (!$workerProfile) {
            return [
                'eligible' => false,
                'reason' => 'Worker profile not found.',
            ];
        }

        // Check required skills
        $shiftRequirements = $shift->requirements ?? [];
        $requiredSkills = $shiftRequirements['skills'] ?? [];

        if (!empty($requiredSkills)) {
            $workerSkills = $acceptingWorker->skills()->pluck('skill_name')->toArray();
            $hasRequiredSkills = empty(array_diff($requiredSkills, $workerSkills));

            if (!$hasRequiredSkills) {
                return [
                    'eligible' => false,
                    'reason' => 'You do not meet the required skills for this shift.',
                ];
            }
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Process a shift swap after business approval.
     *
     * @param ShiftSwap $shiftSwap
     * @return bool
     */
    public function processSwap(ShiftSwap $shiftSwap)
    {
        try {
            DB::beginTransaction();

            $offeringAssignment = $shiftSwap->offeringAssignment;
            $acceptingWorker = $shiftSwap->receivingWorker;

            // Create new assignment for accepting worker
            $newAssignment = ShiftAssignment::create([
                'shift_id' => $offeringAssignment->shift_id,
                'worker_id' => $acceptingWorker->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'payment_status' => 'pending',
            ]);

            // Cancel old assignment
            $offeringAssignment->update([
                'status' => 'swapped',
                'swapped_at' => now(),
                'swapped_to_assignment_id' => $newAssignment->id,
            ]);

            // Refund original worker's escrowed payment
            $originalPayment = $offeringAssignment->payment;
            if ($originalPayment && $originalPayment->status === 'in_escrow') {
                $paymentService = app(ShiftPaymentService::class);
                $paymentService->refundToBusiness($originalPayment, $originalPayment->amount_gross);
            }

            // Hold new escrow for accepting worker
            $paymentService = app(ShiftPaymentService::class);
            $paymentService->holdInEscrow($newAssignment);

            // Update swap status
            $shiftSwap->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            Log::info("Shift swap processed successfully", [
                'swap_id' => $shiftSwap->id,
                'original_worker' => $offeringAssignment->worker_id,
                'new_worker' => $acceptingWorker->id,
            ]);

            // TODO: Notify all parties
            // event(new ShiftSwapCompleted($shiftSwap));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Shift swap processing error", [
                'swap_id' => $shiftSwap->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cancel a shift swap.
     *
     * @param ShiftSwap $shiftSwap
     * @param string $cancelledBy ('offerer', 'accepter', 'business')
     * @param string|null $reason
     * @return bool
     */
    public function cancelSwap(ShiftSwap $shiftSwap, string $cancelledBy, string $reason = null)
    {
        try {
            $shiftSwap->update([
                'status' => 'cancelled',
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            Log::info("Shift swap cancelled", [
                'swap_id' => $shiftSwap->id,
                'cancelled_by' => $cancelledBy,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Shift swap cancellation error", [
                'swap_id' => $shiftSwap->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Find available swap opportunities for a worker.
     *
     * @param User $worker
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findSwapOpportunities(User $worker)
    {
        $matchingService = app(ShiftMatchingService::class);

        // Get all pending swaps
        $availableSwaps = ShiftSwap::with(['offeringAssignment.shift.business', 'offeringAssignment.worker'])
            ->where('status', 'pending')
            ->whereHas('offeringAssignment', function($q) use ($worker) {
                // Exclude worker's own swaps
                $q->where('worker_id', '!=', $worker->id);
            })
            ->get();

        // Calculate match score for each swap
        $rankedSwaps = $availableSwaps->map(function($swap) use ($worker, $matchingService) {
            $shift = $swap->offeringAssignment->shift;
            $matchScore = $matchingService->calculateWorkerShiftMatch($worker, $shift);
            $swap->match_score = $matchScore;

            // Check eligibility
            $eligibility = $this->validateSwapAcceptance($worker, $swap);
            $swap->eligible = $eligibility['eligible'];
            $swap->ineligibility_reason = $eligibility['reason'];

            return $swap;
        });

        // Sort by match score
        return $rankedSwaps->sortByDesc('match_score');
    }
}
