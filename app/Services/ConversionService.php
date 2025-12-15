<?php

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerConversion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling worker direct hire conversions.
 * BIZ-010: Direct Hire & Conversion
 */
class ConversionService
{
    /**
     * Calculate total hours worked by a worker for a business.
     *
     * @param int $workerId
     * @param int $businessId
     * @return array
     */
    public function calculateWorkerHours(int $workerId, int $businessId): array
    {
        $assignments = ShiftAssignment::where('worker_id', $workerId)
            ->whereHas('shift', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->where('status', 'completed')
            ->with('shift')
            ->get();

        $totalHours = 0;
        $totalShifts = 0;

        foreach ($assignments as $assignment) {
            $hours = $assignment->shift->duration_hours ?? 0;
            $totalHours += $hours;
            $totalShifts++;
        }

        return [
            'total_hours' => $totalHours,
            'total_shifts' => $totalShifts,
            'assignments' => $assignments,
        ];
    }

    /**
     * Calculate conversion fee based on hours worked.
     * BIZ-010: Fee Structure
     * - 0-200 hours: €2,000
     * - 201-400 hours: €1,000
     * - 401-600 hours: €500
     * - 600+ hours: €0 (free)
     *
     * @param float $totalHours
     * @return array
     */
    public function calculateConversionFee(float $totalHours): array
    {
        if ($totalHours >= 600) {
            return [
                'fee_cents' => 0,
                'fee_dollars' => 0,
                'tier' => '600+h',
                'tier_name' => '600+ Hours (Free)',
            ];
        } elseif ($totalHours >= 401) {
            return [
                'fee_cents' => 50000, // €500
                'fee_dollars' => 500,
                'tier' => '401-600h',
                'tier_name' => '401-600 Hours',
            ];
        } elseif ($totalHours >= 201) {
            return [
                'fee_cents' => 100000, // €1,000
                'fee_dollars' => 1000,
                'tier' => '201-400h',
                'tier_name' => '201-400 Hours',
            ];
        } else {
            return [
                'fee_cents' => 200000, // €2,000
                'fee_dollars' => 2000,
                'tier' => '0-200h',
                'tier_name' => '0-200 Hours',
            ];
        }
    }

    /**
     * Get conversion eligibility for a worker-business pair.
     *
     * @param int $workerId
     * @param int $businessId
     * @return array
     */
    public function getConversionEligibility(int $workerId, int $businessId): array
    {
        // Calculate hours
        $hoursData = $this->calculateWorkerHours($workerId, $businessId);

        // Calculate fee
        $feeData = $this->calculateConversionFee($hoursData['total_hours']);

        // Check for existing conversion
        $existingConversion = WorkerConversion::where('worker_id', $workerId)
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        // Check if within non-solicitation period for another business
        $nonSolicitationViolation = WorkerConversion::where('worker_id', $workerId)
            ->where('business_id', '!=', $businessId)
            ->where('status', 'completed')
            ->whereDate('non_solicitation_expires_at', '>', now())
            ->first();

        $eligible = !$existingConversion && !$nonSolicitationViolation;

        return [
            'eligible' => $eligible,
            'total_hours' => $hoursData['total_hours'],
            'total_shifts' => $hoursData['total_shifts'],
            'conversion_fee' => $feeData,
            'existing_conversion' => $existingConversion,
            'non_solicitation_violation' => $nonSolicitationViolation,
            'reason' => $this->getIneligibilityReason($existingConversion, $nonSolicitationViolation),
        ];
    }

    /**
     * Get reason for ineligibility.
     *
     * @param WorkerConversion|null $existingConversion
     * @param WorkerConversion|null $nonSolicitationViolation
     * @return string|null
     */
    protected function getIneligibilityReason($existingConversion, $nonSolicitationViolation): ?string
    {
        if ($existingConversion) {
            if ($existingConversion->status === 'pending') {
                return 'A conversion request is already pending for this worker.';
            } elseif ($existingConversion->status === 'completed') {
                return 'This worker has already been hired through OvertimeStaff.';
            }
        }

        if ($nonSolicitationViolation) {
            $daysRemaining = $nonSolicitationViolation->getNonSolicitationDaysRemaining();
            $otherBusiness = $nonSolicitationViolation->business->name ?? 'another business';
            return "This worker is under a non-solicitation agreement with {$otherBusiness} for {$daysRemaining} more days.";
        }

        return null;
    }

    /**
     * Initiate hire intent request.
     *
     * @param int $workerId
     * @param int $businessId
     * @param array $data
     * @return WorkerConversion
     */
    public function initiateHireIntent(int $workerId, int $businessId, array $data): WorkerConversion
    {
        DB::beginTransaction();

        try {
            // Check eligibility
            $eligibility = $this->getConversionEligibility($workerId, $businessId);

            if (!$eligibility['eligible']) {
                throw new \Exception($eligibility['reason'] ?? 'Worker is not eligible for conversion.');
            }

            // Create conversion record
            $conversion = WorkerConversion::create([
                'worker_id' => $workerId,
                'business_id' => $businessId,
                'total_hours_worked' => $eligibility['total_hours'],
                'total_shifts_completed' => $eligibility['total_shifts'],
                'conversion_fee_cents' => $eligibility['conversion_fee']['fee_cents'],
                'conversion_fee_tier' => $eligibility['conversion_fee']['tier'],
                'status' => 'pending',
                'hire_intent_submitted_at' => now(),
                'hire_intent_notes' => $data['notes'] ?? null,
            ]);

            // Notify worker
            $worker = User::findOrFail($workerId);
            $worker->notify(new \App\Notifications\HireIntentNotification($conversion));

            $conversion->update([
                'worker_notified_at' => now(),
            ]);

            DB::commit();

            Log::info('Hire intent initiated', [
                'conversion_id' => $conversion->id,
                'worker_id' => $workerId,
                'business_id' => $businessId,
                'fee' => $conversion->conversion_fee_dollars,
            ]);

            return $conversion;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate hire intent', [
                'worker_id' => $workerId,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Worker accepts/declines hire intent.
     *
     * @param WorkerConversion $conversion
     * @param bool $accepted
     * @param string|null $notes
     * @return WorkerConversion
     */
    public function workerRespond(WorkerConversion $conversion, bool $accepted, ?string $notes = null): WorkerConversion
    {
        DB::beginTransaction();

        try {
            $conversion->update([
                'worker_accepted' => $accepted,
                'worker_accepted_at' => now(),
                'worker_response_notes' => $notes,
                'status' => $accepted ? 'pending_payment' : 'cancelled',
            ]);

            // Notify business
            $business = $conversion->business;
            if ($accepted) {
                $business->notify(new \App\Notifications\HireIntentAcceptedNotification($conversion));
            } else {
                $business->notify(new \App\Notifications\HireIntentDeclinedNotification($conversion));
            }

            DB::commit();

            Log::info('Worker responded to hire intent', [
                'conversion_id' => $conversion->id,
                'accepted' => $accepted,
            ]);

            return $conversion;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process worker response', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process conversion fee payment.
     *
     * @param WorkerConversion $conversion
     * @param array $paymentData
     * @return WorkerConversion
     */
    public function processPayment(WorkerConversion $conversion, array $paymentData): WorkerConversion
    {
        DB::beginTransaction();

        try {
            // In a real implementation, you would process payment via Stripe/PayPal etc.
            // For now, we'll just mark it as paid

            $conversion->update([
                'status' => 'paid',
                'payment_completed_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? 'stripe',
                'payment_transaction_id' => $paymentData['transaction_id'] ?? null,
            ]);

            DB::commit();

            Log::info('Conversion payment processed', [
                'conversion_id' => $conversion->id,
                'amount' => $conversion->conversion_fee_dollars,
            ]);

            return $conversion;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process conversion payment', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Complete conversion and activate non-solicitation period.
     *
     * @param WorkerConversion $conversion
     * @return WorkerConversion
     */
    public function completeConversion(WorkerConversion $conversion): WorkerConversion
    {
        DB::beginTransaction();

        try {
            if ($conversion->status !== 'paid') {
                throw new \Exception('Payment must be completed before finalizing conversion.');
            }

            $conversion->update([
                'status' => 'completed',
                'conversion_completed_at' => now(),
                'non_solicitation_expires_at' => now()->addMonths(6),
            ]);

            // Notify both parties
            $conversion->worker->notify(new \App\Notifications\ConversionCompletedNotification($conversion));
            $conversion->business->notify(new \App\Notifications\ConversionCompletedNotification($conversion));

            DB::commit();

            Log::info('Conversion completed', [
                'conversion_id' => $conversion->id,
                'non_solicitation_expires_at' => $conversion->non_solicitation_expires_at,
            ]);

            return $conversion;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete conversion', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get conversion dashboard for business.
     *
     * @param int $businessId
     * @return array
     */
    public function getBusinessDashboard(int $businessId): array
    {
        $conversions = WorkerConversion::where('business_id', $businessId)
            ->with('worker')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'total_conversions' => $conversions->count(),
            'pending' => $conversions->where('status', 'pending')->count(),
            'pending_payment' => $conversions->where('status', 'pending_payment')->count(),
            'completed' => $conversions->where('status', 'completed')->count(),
            'cancelled' => $conversions->where('status', 'cancelled')->count(),
            'active_non_solicitation' => $conversions->filter(function ($c) {
                return $c->isNonSolicitationActive();
            })->count(),
            'conversions' => $conversions,
        ];
    }

    /**
     * Get workers eligible for conversion by a business.
     *
     * @param int $businessId
     * @param int $minShifts Minimum shifts completed
     * @return array
     */
    public function getEligibleWorkers(int $businessId, int $minShifts = 3): array
    {
        // Get all workers who have completed shifts for this business
        $workerIds = ShiftAssignment::whereHas('shift', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
        })
            ->where('status', 'completed')
            ->distinct()
            ->pluck('worker_id')
            ->toArray();

        $eligibleWorkers = [];

        foreach ($workerIds as $workerId) {
            $eligibility = $this->getConversionEligibility($workerId, $businessId);

            // Only include if they have minimum shifts and are eligible
            if ($eligibility['total_shifts'] >= $minShifts && $eligibility['eligible']) {
                $worker = User::find($workerId);
                $eligibleWorkers[] = [
                    'worker' => $worker,
                    'eligibility' => $eligibility,
                ];
            }
        }

        // Sort by hours worked (descending)
        usort($eligibleWorkers, function ($a, $b) {
            return $b['eligibility']['total_hours'] <=> $a['eligibility']['total_hours'];
        });

        return $eligibleWorkers;
    }
}
