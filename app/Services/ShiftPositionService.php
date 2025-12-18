<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftPosition;
use App\Models\ShiftPositionAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SL-012: Multi-Position Shifts Service
 *
 * Handles all business logic related to multi-position shifts,
 * including position creation, worker assignment, matching, and status management.
 */
class ShiftPositionService
{
    /**
     * The ShiftMatchingService instance for worker matching.
     */
    protected ShiftMatchingService $matchingService;

    /**
     * Create a new service instance.
     */
    public function __construct(ShiftMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Create multiple positions for a shift.
     *
     * @param  Shift  $shift  The shift to create positions for
     * @param  array  $positions  Array of position data
     * @return Collection Collection of created ShiftPosition models
     */
    public function createPositions(Shift $shift, array $positions): Collection
    {
        $createdPositions = collect();

        DB::transaction(function () use ($shift, $positions, &$createdPositions) {
            foreach ($positions as $positionData) {
                $position = ShiftPosition::create([
                    'shift_id' => $shift->id,
                    'title' => $positionData['title'],
                    'description' => $positionData['description'] ?? null,
                    'hourly_rate' => $positionData['hourly_rate'],
                    'required_workers' => $positionData['required_workers'] ?? 1,
                    'filled_workers' => 0,
                    'required_skills' => $positionData['required_skills'] ?? null,
                    'required_certifications' => $positionData['required_certifications'] ?? null,
                    'minimum_experience_hours' => $positionData['minimum_experience_hours'] ?? 0,
                    'status' => ShiftPosition::STATUS_OPEN,
                ]);

                $createdPositions->push($position);
            }

            // Update shift's total required workers count
            $this->updateShiftWorkerCounts($shift);
        });

        return $createdPositions;
    }

    /**
     * Update a position's details.
     *
     * @param  ShiftPosition  $position  The position to update
     * @param  array  $data  The data to update
     */
    public function updatePosition(ShiftPosition $position, array $data): ShiftPosition
    {
        $position->update($data);
        $this->updatePositionStatus($position);
        $this->updateShiftWorkerCounts($position->shift);

        return $position->fresh();
    }

    /**
     * Delete a position from a shift.
     *
     * @param  ShiftPosition  $position  The position to delete
     */
    public function deletePosition(ShiftPosition $position): bool
    {
        $shift = $position->shift;

        DB::transaction(function () use ($position, $shift) {
            // Remove all position assignments
            $position->positionAssignments()->delete();

            // Delete the position
            $position->delete();

            // Update shift worker counts
            $this->updateShiftWorkerCounts($shift);
        });

        return true;
    }

    /**
     * Assign a worker to a specific position.
     *
     * @param  ShiftPosition  $position  The position to assign to
     * @param  User  $worker  The worker to assign
     * @param  ShiftAssignment|null  $shiftAssignment  Existing shift assignment, or null to create one
     *
     * @throws \Exception If position is full or worker doesn't meet requirements
     */
    public function assignWorkerToPosition(
        ShiftPosition $position,
        User $worker,
        ?ShiftAssignment $shiftAssignment = null
    ): ShiftPositionAssignment {
        // Validate position has available slots
        if (! $position->hasAvailableSlots()) {
            throw new \Exception('This position is already fully filled.');
        }

        // Validate worker meets requirements
        if (! $position->workerMeetsAllRequirements($worker)) {
            throw new \Exception('Worker does not meet all requirements for this position.');
        }

        // Check if worker is already assigned to this position
        $existingAssignment = ShiftPositionAssignment::where('shift_position_id', $position->id)
            ->where('user_id', $worker->id)
            ->first();

        if ($existingAssignment) {
            throw new \Exception('Worker is already assigned to this position.');
        }

        $positionAssignment = null;

        DB::transaction(function () use ($position, $worker, $shiftAssignment, &$positionAssignment) {
            // Create shift assignment if not provided
            if (! $shiftAssignment) {
                $shiftAssignment = ShiftAssignment::create([
                    'shift_id' => $position->shift_id,
                    'worker_id' => $worker->id,
                    'assigned_by' => auth()->id() ?? $position->shift->business_id,
                    'status' => 'assigned',
                    'payment_status' => 'pending',
                ]);
            }

            // Create position assignment
            $positionAssignment = ShiftPositionAssignment::create([
                'shift_position_id' => $position->id,
                'shift_assignment_id' => $shiftAssignment->id,
                'user_id' => $worker->id,
            ]);

            // Update position filled count and status
            $position->incrementFilledWorkers();

            // Update shift filled workers count
            $this->updateShiftWorkerCounts($position->shift);
        });

        return $positionAssignment;
    }

    /**
     * Remove a worker from a position.
     *
     * @param  ShiftPositionAssignment  $positionAssignment  The assignment to remove
     * @param  bool  $deleteShiftAssignment  Whether to also delete the shift assignment
     */
    public function removeWorkerFromPosition(
        ShiftPositionAssignment $positionAssignment,
        bool $deleteShiftAssignment = false
    ): bool {
        $position = $positionAssignment->shiftPosition;

        DB::transaction(function () use ($positionAssignment, $position, $deleteShiftAssignment) {
            // Optionally delete the shift assignment
            if ($deleteShiftAssignment) {
                $positionAssignment->shiftAssignment?->delete();
            }

            // Delete the position assignment
            $positionAssignment->delete();

            // Update position filled count and status
            $position->decrementFilledWorkers();

            // Update shift filled workers count
            $this->updateShiftWorkerCounts($position->shift);
        });

        return true;
    }

    /**
     * Get all available (not fully filled) positions for a shift.
     *
     * @param  Shift  $shift  The shift to get positions for
     */
    public function getAvailablePositions(Shift $shift): Collection
    {
        return $shift->positions()
            ->available()
            ->orderBy('hourly_rate', 'desc')
            ->get();
    }

    /**
     * Get all positions for a shift with their assignments.
     *
     * @param  Shift  $shift  The shift to get positions for
     */
    public function getPositionsWithAssignments(Shift $shift): Collection
    {
        return $shift->positions()
            ->with(['positionAssignments.user', 'positionAssignments.shiftAssignment'])
            ->orderBy('title')
            ->get();
    }

    /**
     * Match workers to positions based on skills, certifications, and experience.
     * Returns best matching workers for each position.
     *
     * @param  Shift  $shift  The shift to match workers for
     * @param  int  $limit  Maximum number of workers to return per position
     * @return array Array of position IDs mapped to their best matching workers
     */
    public function matchWorkersToPositions(Shift $shift, int $limit = 10): array
    {
        $matches = [];
        $positions = $shift->positions()->available()->get();

        // Get all eligible workers
        $workers = User::where('user_type', 'worker')
            ->where('is_verified_worker', true)
            ->where('status', 'active')
            ->with(['workerProfile', 'skills', 'certifications', 'shiftAssignments'])
            ->get();

        foreach ($positions as $position) {
            $positionMatches = [];

            foreach ($workers as $worker) {
                // Skip if worker is already assigned to this position
                $isAlreadyAssigned = ShiftPositionAssignment::where('shift_position_id', $position->id)
                    ->where('user_id', $worker->id)
                    ->exists();

                if ($isAlreadyAssigned) {
                    continue;
                }

                // Calculate position-specific match score
                $matchScore = $position->calculateWorkerMatchScore($worker);

                // Also factor in general shift matching from ShiftMatchingService
                $shiftMatch = $this->matchingService->calculateWorkerShiftMatch($worker, $shift);

                // Combine scores (position-specific weighted more heavily)
                $combinedScore = ($matchScore['final_score'] * 0.6) + ($shiftMatch['final_score'] * 0.4);

                $positionMatches[] = [
                    'worker' => $worker,
                    'position_match' => $matchScore,
                    'shift_match' => $shiftMatch,
                    'combined_score' => round($combinedScore, 1),
                    'meets_requirements' => $matchScore['meets_requirements'],
                ];
            }

            // Sort by combined score descending
            usort($positionMatches, function ($a, $b) {
                return $b['combined_score'] <=> $a['combined_score'];
            });

            // Take top matches
            $matches[$position->id] = [
                'position' => $position,
                'workers' => array_slice($positionMatches, 0, $limit),
            ];
        }

        return $matches;
    }

    /**
     * Get positions that a specific worker qualifies for within a shift.
     *
     * @param  Shift  $shift  The shift to check positions for
     * @param  User  $worker  The worker to match
     * @return Collection Collection of positions the worker qualifies for
     */
    public function getQualifyingPositionsForWorker(Shift $shift, User $worker): Collection
    {
        return $shift->positions()
            ->available()
            ->get()
            ->filter(function ($position) use ($worker) {
                return $position->workerMeetsAllRequirements($worker);
            })
            ->values();
    }

    /**
     * Update a position's status based on filled workers count.
     *
     * @param  ShiftPosition  $position  The position to update
     */
    public function updatePositionStatus(ShiftPosition $position): void
    {
        if ($position->status === ShiftPosition::STATUS_CANCELLED) {
            return; // Don't update cancelled positions
        }

        if ($position->filled_workers >= $position->required_workers) {
            $position->status = ShiftPosition::STATUS_FILLED;
        } elseif ($position->filled_workers > 0) {
            $position->status = ShiftPosition::STATUS_PARTIALLY_FILLED;
        } else {
            $position->status = ShiftPosition::STATUS_OPEN;
        }

        $position->save();
    }

    /**
     * Update the shift's total required and filled worker counts based on positions.
     *
     * @param  Shift  $shift  The shift to update
     */
    protected function updateShiftWorkerCounts(Shift $shift): void
    {
        $positions = $shift->positions()->where('status', '!=', ShiftPosition::STATUS_CANCELLED)->get();

        $totalRequired = $positions->sum('required_workers');
        $totalFilled = $positions->sum('filled_workers');

        $shift->update([
            'required_workers' => $totalRequired,
            'filled_workers' => $totalFilled,
        ]);
    }

    /**
     * Cancel a position and handle any existing assignments.
     *
     * @param  ShiftPosition  $position  The position to cancel
     * @param  string|null  $reason  Optional cancellation reason
     */
    public function cancelPosition(ShiftPosition $position, ?string $reason = null): void
    {
        DB::transaction(function () use ($position) {
            // Mark position as cancelled
            $position->cancel();

            // Update shift worker counts
            $this->updateShiftWorkerCounts($position->shift);
        });
    }

    /**
     * Get a summary of position fill status for a shift.
     *
     * @param  Shift  $shift  The shift to get summary for
     */
    public function getPositionsSummary(Shift $shift): array
    {
        $positions = $shift->positions;

        return [
            'total_positions' => $positions->count(),
            'open_positions' => $positions->where('status', ShiftPosition::STATUS_OPEN)->count(),
            'partially_filled' => $positions->where('status', ShiftPosition::STATUS_PARTIALLY_FILLED)->count(),
            'filled_positions' => $positions->where('status', ShiftPosition::STATUS_FILLED)->count(),
            'cancelled_positions' => $positions->where('status', ShiftPosition::STATUS_CANCELLED)->count(),
            'total_required_workers' => $positions->where('status', '!=', ShiftPosition::STATUS_CANCELLED)->sum('required_workers'),
            'total_filled_workers' => $positions->where('status', '!=', ShiftPosition::STATUS_CANCELLED)->sum('filled_workers'),
            'fill_percentage' => $this->calculateOverallFillPercentage($positions),
            'positions' => $positions->map(function ($position) {
                return [
                    'id' => $position->id,
                    'title' => $position->title,
                    'hourly_rate' => $position->hourly_rate,
                    'required' => $position->required_workers,
                    'filled' => $position->filled_workers,
                    'remaining' => $position->remaining_slots,
                    'status' => $position->status,
                ];
            })->toArray(),
        ];
    }

    /**
     * Calculate the overall fill percentage across all active positions.
     *
     * @param  Collection  $positions  Collection of positions
     */
    protected function calculateOverallFillPercentage(Collection $positions): float
    {
        $activePositions = $positions->where('status', '!=', ShiftPosition::STATUS_CANCELLED);

        $totalRequired = $activePositions->sum('required_workers');
        $totalFilled = $activePositions->sum('filled_workers');

        if ($totalRequired <= 0) {
            return 0;
        }

        return round(($totalFilled / $totalRequired) * 100, 1);
    }

    /**
     * Check if a shift has any multi-position configuration.
     *
     * @param  Shift  $shift  The shift to check
     */
    public function isMultiPositionShift(Shift $shift): bool
    {
        return $shift->positions()->count() > 1;
    }

    /**
     * Bulk assign workers to positions based on best matches.
     *
     * @param  Shift  $shift  The shift to assign workers for
     * @param  bool  $onlyQualified  Only assign workers who meet all requirements
     * @return array Results of the bulk assignment
     */
    public function bulkAssignBestMatches(Shift $shift, bool $onlyQualified = true): array
    {
        $results = [
            'assignments' => [],
            'skipped' => [],
            'errors' => [],
        ];

        $matches = $this->matchWorkersToPositions($shift);

        DB::transaction(function () use ($matches, $onlyQualified, &$results) {
            foreach ($matches as $positionId => $matchData) {
                $position = $matchData['position'];

                if (! $position->hasAvailableSlots()) {
                    continue;
                }

                foreach ($matchData['workers'] as $workerMatch) {
                    if (! $position->hasAvailableSlots()) {
                        break;
                    }

                    if ($onlyQualified && ! $workerMatch['meets_requirements']) {
                        $results['skipped'][] = [
                            'position_id' => $positionId,
                            'worker_id' => $workerMatch['worker']->id,
                            'reason' => 'Does not meet all requirements',
                        ];

                        continue;
                    }

                    try {
                        $assignment = $this->assignWorkerToPosition($position, $workerMatch['worker']);
                        $results['assignments'][] = [
                            'position_id' => $positionId,
                            'worker_id' => $workerMatch['worker']->id,
                            'assignment_id' => $assignment->id,
                            'score' => $workerMatch['combined_score'],
                        ];
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'position_id' => $positionId,
                            'worker_id' => $workerMatch['worker']->id,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        });

        return $results;
    }

    /**
     * Clone positions from one shift to another.
     *
     * @param  Shift  $sourceShift  The shift to clone positions from
     * @param  Shift  $targetShift  The shift to clone positions to
     * @return Collection Cloned positions
     */
    public function clonePositions(Shift $sourceShift, Shift $targetShift): Collection
    {
        $positions = $sourceShift->positions()
            ->where('status', '!=', ShiftPosition::STATUS_CANCELLED)
            ->get();

        $positionsData = $positions->map(function ($position) {
            return [
                'title' => $position->title,
                'description' => $position->description,
                'hourly_rate' => $position->hourly_rate,
                'required_workers' => $position->required_workers,
                'required_skills' => $position->required_skills,
                'required_certifications' => $position->required_certifications,
                'minimum_experience_hours' => $position->minimum_experience_hours,
            ];
        })->toArray();

        return $this->createPositions($targetShift, $positionsData);
    }
}
