<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LiveMarketService
{
    protected $shiftMatchingService;
    protected $demoShiftService;

    public function __construct(
        ShiftMatchingService $shiftMatchingService,
        DemoShiftService $demoShiftService
    ) {
        $this->shiftMatchingService = $shiftMatchingService;
        $this->demoShiftService = $demoShiftService;
    }

    /**
     * Get market shifts with filters and match scores.
     *
     * @param User|null $worker
     * @param array $filters
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getMarketShifts(?User $worker = null, array $filters = [], int $limit = 20)
    {
        // Get real shifts count
        $realShiftsCount = Shift::inMarket()->realShifts()->count();

        // Determine if we should show demo shifts
        $demoThreshold = config('market.demo_disable_threshold', 10);
        $useDemoShifts = $realShiftsCount < $demoThreshold && config('market.demo_enabled', true);

        // Build query for real shifts
        $query = Shift::inMarket()->realShifts()
            ->with(['business', 'agencyClient', 'postedByAgency'])
            ->orderBy('market_posted_at', 'desc')
            ->orderBy('surge_multiplier', 'desc')
            ->orderBy('shift_date', 'asc');

        // Apply filters
        if (!empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (!empty($filters['role_type'])) {
            $query->where('role_type', $filters['role_type']);
        }

        if (!empty($filters['city'])) {
            $query->where('location_city', $filters['city']);
        }

        if (!empty($filters['min_rate'])) {
            $query->where('final_rate', '>=', $filters['min_rate']);
        }

        if (!empty($filters['instant_claim'])) {
            $query->where('instant_claim_enabled', true);
        }

        if (!empty($filters['surge_only'])) {
            $query->where('surge_multiplier', '>', 1.0);
        }

        // Get real shifts
        $shifts = $query->limit($limit)->get();

        // If we need demo shifts, generate and merge them
        if ($useDemoShifts && $shifts->count() < $limit) {
            $demoCount = config('market.demo_shift_count', 15);
            $demoShifts = $this->demoShiftService->generate($demoCount);
            $shifts = $shifts->concat($demoShifts)->take($limit);
        }

        // Calculate match scores for worker
        if ($worker && $worker->role === 'worker') {
            $shifts = $shifts->map(function ($shift) use ($worker) {
                $matchScore = $this->shiftMatchingService->calculateMatchScore($worker, $shift);
                $shift->match_score = $matchScore['overall_score'];
                $shift->match_reasons = $matchScore['reasons'];
                return $shift;
            });

            // Sort by match score if present
            $shifts = $shifts->sortByDesc('match_score');
        }

        return $shifts->values();
    }

    /**
     * Get market statistics (cached).
     *
     * @param string $region
     * @return array
     */
    public function getStatistics(string $region = 'global'): array
    {
        $cacheTtl = config('market.stats_cache_ttl', 300); // 5 minutes default

        return Cache::remember("market_stats_{$region}", $cacheTtl, function () {
            $realShiftsCount = Shift::inMarket()->realShifts()->count();

            // If no real shifts, return demo statistics
            if ($realShiftsCount === 0) {
                return [
                    'shifts_live' => 247,
                    'total_value' => 42500,
                    'avg_hourly_rate' => 32,
                    'rate_change_percent' => 3.2,
                    'filled_today' => 89,
                    'workers_online' => 1247,
                ];
            }

            $stats = [
                'shifts_live' => $realShiftsCount,
                'total_value' => 0,
                'avg_hourly_rate' => 0,
                'rate_change_percent' => 0,
                'filled_today' => 0,
                'workers_online' => 0,
            ];

            // Calculate total value (sum of escrow amounts)
            $totalValue = Shift::inMarket()->realShifts()->sum('escrow_amount');
            $stats['total_value'] = $totalValue ?? 0;

            // Calculate average hourly rate
            $avgRate = Shift::inMarket()->realShifts()->avg('final_rate');
            $stats['avg_hourly_rate'] = $avgRate ?? 0;

            // Calculate rate change (compare to yesterday)
            $yesterdayAvg = Shift::where('created_at', '>=', now()->subDay()->startOfDay())
                ->where('created_at', '<', now()->startOfDay())
                ->avg('final_rate');

            if ($yesterdayAvg > 0) {
                $stats['rate_change_percent'] = (($avgRate - $yesterdayAvg) / $yesterdayAvg) * 100;
            }

            // Count shifts filled today
            $stats['filled_today'] = Shift::where('confirmed_at', '>=', now()->startOfDay())
                ->count();

            // Count workers online (total active workers)
            // Note: last_activity_at may not exist, so count active workers instead
            $stats['workers_online'] = User::where('role', 'worker')
                ->where('status', 'active')
                ->count();

            return $stats;
        });
    }

    /**
     * Worker applies to a shift.
     *
     * @param Shift $shift
     * @param User $worker
     * @param string|null $message
     * @return ShiftApplication
     */
    public function applyToShift(Shift $shift, User $worker, ?string $message = null): ShiftApplication
    {
        // Validations
        if ($shift->is_demo) {
            throw new \Exception('Cannot apply to demo shifts');
        }

        if (!$shift->isOpen()) {
            throw new \Exception('This shift is no longer open');
        }

        // Check if worker already applied
        $existingApplication = ShiftApplication::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingApplication) {
            throw new \Exception('You have already applied to this shift');
        }

        // Check max pending applications
        $maxPending = config('market.max_pending_applications', 5);
        $pendingCount = ShiftApplication::where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount >= $maxPending) {
            throw new \Exception("You have reached the maximum of {$maxPending} pending applications");
        }

        // Create application
        $application = ShiftApplication::create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
            'status' => 'pending',
            'message' => $message,
            'applied_at' => now(),
        ]);

        // Update shift metrics
        $shift->increment('application_count');
        $shift->increment('market_applications');

        if (!$shift->first_application_at) {
            $shift->update(['first_application_at' => now()]);
        }
        $shift->update(['last_application_at' => now()]);

        return $application;
    }

    /**
     * Instant claim for verified workers (4.5+ rating).
     *
     * @param Shift $shift
     * @param User $worker
     * @return ShiftAssignment
     */
    public function instantClaim(Shift $shift, User $worker): ShiftAssignment
    {
        // Validations
        if ($shift->is_demo) {
            throw new \Exception('Cannot claim demo shifts');
        }

        if (!$shift->instant_claim_enabled) {
            throw new \Exception('Instant claim is not enabled for this shift');
        }

        if (!$shift->isOpen()) {
            throw new \Exception('This shift is no longer open');
        }

        // Check worker rating
        $minRating = config('market.instant_claim_min_rating', 4.5);
        $workerProfile = WorkerProfile::where('user_id', $worker->id)->first();

        if (!$workerProfile || $workerProfile->average_rating < $minRating) {
            throw new \Exception("Instant claim requires a rating of {$minRating} or higher");
        }

        // Check if worker already assigned or applied
        $existingAssignment = ShiftAssignment::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->first();

        if ($existingAssignment) {
            throw new \Exception('You are already assigned to this shift');
        }

        DB::beginTransaction();
        try {
            // Create assignment
            $assignment = ShiftAssignment::create([
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'status' => 'confirmed',
                'assigned_at' => now(),
                'confirmed_at' => now(),
            ]);

            // Update shift
            $shift->increment('filled_workers');

            if ($shift->filled_workers >= $shift->required_workers) {
                $shift->update([
                    'status' => 'assigned',
                    'confirmed_at' => now(),
                ]);
            }

            DB::commit();
            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Agency assigns a worker to a shift for their client.
     *
     * @param Shift $shift
     * @param User $agency
     * @param User $worker
     * @return ShiftAssignment
     */
    public function agencyAssign(Shift $shift, User $agency, User $worker): ShiftAssignment
    {
        // Validations
        if ($shift->is_demo) {
            throw new \Exception('Cannot assign to demo shifts');
        }

        // Verify agency owns this shift
        if ($shift->posted_by_agency_id !== $agency->id) {
            throw new \Exception('You can only assign workers to shifts you posted');
        }

        if (!$shift->isOpen()) {
            throw new \Exception('This shift is no longer open');
        }

        // Check if worker already assigned
        $existingAssignment = ShiftAssignment::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->first();

        if ($existingAssignment) {
            throw new \Exception('This worker is already assigned to this shift');
        }

        DB::beginTransaction();
        try {
            // Create assignment
            $assignment = ShiftAssignment::create([
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'assigned_by_agency_id' => $agency->id,
                'status' => 'confirmed',
                'assigned_at' => now(),
                'confirmed_at' => now(),
            ]);

            // Update shift
            $shift->increment('filled_workers');

            if ($shift->filled_workers >= $shift->required_workers) {
                $shift->update([
                    'status' => 'assigned',
                    'confirmed_at' => now(),
                ]);
            }

            DB::commit();
            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Increment market view count.
     *
     * @param Shift $shift
     * @return void
     */
    public function incrementView(Shift $shift): void
    {
        if (!$shift->is_demo) {
            $shift->increment('market_views');
            $shift->increment('view_count');
        }
    }
}
