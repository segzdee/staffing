<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\TeamShiftRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerRelationship;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftMatchingService
{
    /**
     * Calculate enhanced AI-powered match score between worker and shift
     * SL-002: AI-Powered Worker Matching & Ranking
     * Returns score from 0-100 with detailed breakdown
     */
    public function calculateWorkerShiftMatch(User $worker, Shift $shift, array $preCalc = [])
    {
        if (! $worker->workerProfile) {
            return [
                'final_score' => 0,
                'breakdown' => [],
                'weights' => [],
                'bonus_points' => 0,
            ];
        }

        $scoreBreakdown = [
            'skills_match' => $this->calculateSkillsMatch($worker, $shift),
            'proximity_score' => $this->calculateLocationMatch($worker->workerProfile, $shift),
            'reliability_score' => $preCalc['reliability'] ?? $this->getReliabilityScore($worker),
            'rating_score' => $preCalc['rating'] ?? $this->calculateRatingMatch($worker),
            'recency_score' => $preCalc['recency'] ?? $this->calculateRecencyScore($worker),
            'role_experience' => $this->calculateRoleExperience($worker, $shift),
            'availability_match' => $this->calculateAvailabilityMatch($worker->workerProfile, $shift),
        ];

        // Apply weighted scoring
        $weights = [
            'skills_match' => 0.25,
            'proximity_score' => 0.20,
            'reliability_score' => 0.30, // Highest weight - reliability is key
            'rating_score' => 0.15,
            'recency_score' => 0.05,
            'role_experience' => 0.03,
            'availability_match' => 0.02,
        ];

        $baseScore = 0;
        foreach ($scoreBreakdown as $component => $score) {
            $baseScore += $score * $weights[$component];
        }

        // Apply bonus factors
        $bonusScore = $this->calculateBonusFactors($worker, $shift);

        // Combine and cap at 100
        $finalScore = min(100, $baseScore + $bonusScore);

        return [
            'final_score' => round($finalScore, 1),
            'breakdown' => $scoreBreakdown,
            'weights' => $weights,
            'bonus_points' => $bonusScore,
        ];
    }

    /**
     * Match and rank shifts for a specific worker.
     * Returns shifts sorted by match score (highest first).
     *
     * @return \Illuminate\Support\Collection
     */
    public function matchShiftsForWorker(User $worker)
    {
        $workerProfile = $worker->workerProfile;
        if (! $workerProfile) {
            return collect([]);
        }

        // Get all open upcoming shifts
        $shifts = Shift::with(['business', 'assignments'])
            ->open()
            ->upcoming()
            ->get();

        // Pre-calculate worker metrics to avoid N+1 recalculation in the loop
        $workerReliability = $this->getReliabilityScore($worker);
        $workerRating = $this->calculateRatingMatch($worker);
        $workerRecency = $this->calculateRecencyScore($worker);

        // Calculate match score for each shift
        $rankedShifts = $shifts->map(function ($shift) use ($worker, $workerReliability, $workerRating, $workerRecency) {
            $matchScore = $this->calculateWorkerShiftMatch($worker, $shift, [
                'reliability' => $workerReliability,
                'rating' => $workerRating,
                'recency' => $workerRecency,
            ]);
            $shift->match_score = $matchScore;

            return $shift;
        });

        // Sort by match score descending
        return $rankedShifts->sortByDesc('match_score');
    }

    /**
     * Match and rank workers for a specific shift.
     * Returns workers sorted by match score (highest first).
     */
    public function matchWorkersForShift(Shift $shift)
    {
        // Get all verified workers
        $workers = User::where('user_type', 'worker')
            ->where('is_verified_worker', true)
            ->where('status', 'active')
            ->with(['workerProfile', 'skills', 'certifications'])
            ->get();

        // Calculate match score for each worker
        $rankedWorkers = $workers->map(function ($worker) use ($shift) {
            $matchScore = $this->calculateWorkerShiftMatch($worker, $shift);
            $worker->match_score = $matchScore;

            return $worker;
        });

        // Sort by match score descending
        return $rankedWorkers->sortByDesc('match_score');
    }

    /**
     * Calculate skills match score (0-40 points).
     */
    protected function calculateSkillsMatch(User $worker, Shift $shift)
    {
        // Get worker's skills
        $workerSkills = $worker->skills()->pluck('name')->toArray();

        // Get shift required skills
        $shiftRequirements = $shift->requirements ?? [];
        $requiredSkills = $shiftRequirements['skills'] ?? [];

        if (empty($requiredSkills)) {
            return 40; // No specific skills required
        }

        // Calculate percentage of required skills the worker has
        $matchedSkills = array_intersect($workerSkills, $requiredSkills);
        $matchPercentage = count($matchedSkills) / count($requiredSkills);

        return $matchPercentage * 40;
    }

    /**
     * Calculate location proximity score (0-25 points).
     */
    protected function calculateLocationMatch(WorkerProfile $workerProfile, Shift $shift)
    {
        // If no location data, return neutral score
        if (
            ! $workerProfile->location_lat || ! $workerProfile->location_lng ||
            ! $shift->location_lat || ! $shift->location_lng
        ) {
            return 15;
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $workerProfile->location_lat,
            $workerProfile->location_lng,
            $shift->location_lat,
            $shift->location_lng
        );

        // Get worker's preferred max distance
        $maxDistance = $workerProfile->preferred_radius ?? 50;

        // Score based on distance
        if ($distance <= 5) {
            return 25; // Very close
        } elseif ($distance <= 10) {
            return 20; // Close
        } elseif ($distance <= 25) {
            return 15; // Reasonable distance
        } elseif ($distance <= $maxDistance) {
            return 10; // Within preferred range
        } else {
            return 0; // Too far
        }
    }

    /**
     * Calculate availability match score (0-20 points).
     */
    protected function calculateAvailabilityMatch(WorkerProfile $workerProfile, Shift $shift)
    {
        $shiftDate = $shift->shift_date;
        $shiftDayOfWeek = Carbon::parse($shiftDate)->format('l'); // e.g., "Monday"

        // Get worker's general availability
        $availability = $workerProfile->availability ?? [];

        // Check if worker is generally available on this day
        $dayAvailable = $availability[strtolower($shiftDayOfWeek)] ?? false;

        if ($dayAvailable) {
            // Check if shift time matches worker's preferred time
            $shiftStartTime = Carbon::parse($shift->start_time);
            $preferredShifts = $availability['preferred_shifts'] ?? [];

            // Award points based on time preference
            $hour = $shiftStartTime->hour;
            if ($hour >= 6 && $hour < 14 && in_array('morning', $preferredShifts)) {
                return 20;
            } elseif ($hour >= 14 && $hour < 22 && in_array('afternoon', $preferredShifts)) {
                return 20;
            } elseif (($hour >= 22 || $hour < 6) && in_array('night', $preferredShifts)) {
                return 20;
            } else {
                return 15; // Available but not preferred time
            }
        } else {
            return 5; // Not in general availability but might still work
        }
    }

    /**
     * Calculate industry experience match score (0-10 points).
     */
    protected function calculateIndustryMatch(WorkerProfile $workerProfile, Shift $shift)
    {
        $workerIndustries = $workerProfile->industries_experience ?? [];

        if (empty($workerIndustries)) {
            return 5; // No specific experience listed
        }

        if (in_array($shift->industry, $workerIndustries)) {
            // Check experience level in this industry
            $experienceYears = $workerIndustries[$shift->industry.'_years'] ?? 0;

            if ($experienceYears >= 5) {
                return 10; // Highly experienced
            } elseif ($experienceYears >= 2) {
                return 8; // Experienced
            } elseif ($experienceYears >= 1) {
                return 6; // Some experience
            } else {
                return 4; // Listed but minimal experience
            }
        }

        return 2; // No specific experience in this industry
    }

    /**
     * Calculate rating match score (0-5 points).
     */
    protected function calculateRatingMatch(User $worker)
    {
        $rating = $worker->rating_as_worker ?? 0;

        if ($rating >= 4.5) {
            return 5;
        } elseif ($rating >= 4.0) {
            return 4;
        } elseif ($rating >= 3.5) {
            return 3;
        } elseif ($rating >= 3.0) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * Calculate dynamic rate for a shift based on multiple factors.
     * Returns adjusted hourly rate.
     */
    public function calculateDynamicRate(array $params)
    {
        $baseRate = $params['base_rate'];
        $shiftDate = $params['shift_date'];
        $industry = $params['industry'];
        $urgencyLevel = $params['urgency_level'] ?? 'normal';

        $multiplier = 1.0;

        // 1. Urgency multiplier
        switch ($urgencyLevel) {
            case 'critical':
                $multiplier += 0.50; // 50% increase
                break;
            case 'urgent':
                $multiplier += 0.30; // 30% increase
                break;
            case 'normal':
            default:
                // No increase
                break;
        }

        // 2. Time until shift (last-minute premium)
        $daysUntilShift = Carbon::parse($shiftDate)->diffInDays(Carbon::today());
        if ($daysUntilShift <= 1) {
            $multiplier += 0.25; // 25% increase for same/next day
        } elseif ($daysUntilShift <= 3) {
            $multiplier += 0.15; // 15% increase for 2-3 days
        } elseif ($daysUntilShift <= 7) {
            $multiplier += 0.10; // 10% increase for within a week
        }

        // 3. Industry demand adjustments
        $industryMultipliers = [
            'healthcare' => 1.15, // Healthcare typically pays more
            'professional' => 1.10,
            'hospitality' => 1.00,
            'retail' => 1.00,
            'events' => 1.05,
            'warehouse' => 1.05,
        ];
        $multiplier *= ($industryMultipliers[$industry] ?? 1.0);

        // 4. Day of week adjustments
        $dayOfWeek = Carbon::parse($shiftDate)->dayOfWeek;
        if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) {
            $multiplier += 0.10; // 10% weekend premium
        }

        // 5. Time of day adjustments (if start_time provided)
        if (isset($params['start_time'])) {
            $startHour = Carbon::parse($params['start_time'])->hour;
            if ($startHour >= 22 || $startHour < 6) {
                $multiplier += 0.20; // 20% night shift premium
            } elseif ($startHour >= 18) {
                $multiplier += 0.10; // 10% evening premium
            }
        }

        // Calculate final rate
        $dynamicRate = $baseRate * $multiplier;

        // Round to 2 decimal places
        return round($dynamicRate, 2);
    }

    /**
     * Calculate distance between two lat/lng points using Haversine formula.
     * Returns distance in miles.
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 3959; // miles

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Find workers who are actively broadcasting availability.
     */
    public function findAvailableWorkers($industry = null, $location = null, $date = null)
    {
        $query = DB::table('availability_broadcasts')
            ->join('users', 'availability_broadcasts.worker_id', '=', 'users.id')
            ->where('availability_broadcasts.status', 'active')
            ->where('availability_broadcasts.available_from', '<=', now())
            ->where('availability_broadcasts.available_to', '>=', now());

        if ($industry) {
            $query->whereRaw('JSON_CONTAINS(availability_broadcasts.industries, ?)', [json_encode($industry)]);
        }

        if ($date) {
            $query->where('availability_broadcasts.available_from', '<=', $date)
                ->where('availability_broadcasts.available_to', '>=', $date);
        }

        return $query->select('users.*', 'availability_broadcasts.*')->get();
    }

    /**
     * Check if a shift is likely to fill quickly (for urgency indicators).
     */
    public function predictFillTime(Shift $shift)
    {
        // Get similar shifts in the past
        $similarShifts = Shift::where('industry', $shift->industry)
            ->where('location_city', $shift->location_city)
            ->whereNotNull('filled_at')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->get();

        if ($similarShifts->count() < 5) {
            return 'unknown';
        }

        // Calculate average time to fill
        $avgMinutesToFill = $similarShifts->avg(function ($s) {
            return Carbon::parse($s->created_at)->diffInMinutes($s->filled_at);
        });

        // Adjust for current shift's urgency
        if ($shift->urgency_level == 'critical') {
            $avgMinutesToFill *= 0.5; // Expect faster fill
        } elseif ($shift->urgency_level == 'urgent') {
            $avgMinutesToFill *= 0.7;
        }

        // Categorize
        if ($avgMinutesToFill < 60) {
            return 'very_fast'; // Less than 1 hour
        } elseif ($avgMinutesToFill < 240) {
            return 'fast'; // 1-4 hours
        } elseif ($avgMinutesToFill < 1440) {
            return 'moderate'; // 4-24 hours
        } else {
            return 'slow'; // More than 24 hours
        }
    }

    /**
     * Get worker reliability score
     * SL-002: Reliability scoring (30% weight)
     */
    protected function getReliabilityScore(User $worker): float
    {
        $profile = $worker->workerProfile;

        return $profile ? $profile->reliability_score ?? 80 : 80; // Default to 80 for new workers
    }

    /**
     * Calculate recency score based on recent activity
     * SL-002: Recency scoring (10% weight)
     */
    protected function calculateRecencyScore(User $worker): float
    {
        $lastActive = $worker->last_active_at ?? $worker->updated_at;
        $daysSinceActive = $lastActive ? Carbon::now()->diffInDays($lastActive) : 30;

        return max(0, 100 - ($daysSinceActive * 10));
    }

    /**
     * Calculate role-specific experience score
     */
    protected function calculateRoleExperience(User $worker, Shift $shift): float
    {
        $roleShifts = $worker->shiftAssignments()
            ->whereHas('shift', function ($query) use ($shift) {
                $query->where('role_type', $shift->role_type);
            })
            ->where('status', 'completed')
            ->count();

        // Scale: 0 for no experience, 100 for 20+ shifts in role
        return min(100, ($roleShifts / 20) * 100);
    }

    /**
     * Calculate bonus factors for enhanced matching
     */
    protected function calculateBonusFactors(User $worker, Shift $shift): float
    {
        $bonus = 0;

        // Favorite bonus (10 points)
        if ($this->isFavoriteWorker($worker, $shift)) {
            $bonus += 10;
        }

        // Repeat business bonus (5 points)
        if ($this->hasWorkedForBusiness($worker, $shift->business_id)) {
            $bonus += 5;
        }

        // Tier bonus
        $tierBonus = $this->getTierBonus($worker);
        $bonus += $tierBonus;

        // Certification bonus (up to 5 points)
        $certBonus = $this->getCertificationBonus($worker, $shift);
        $bonus += $certBonus;

        // Language bonus (up to 3 points)
        $langBonus = $this->getLanguageBonus($worker, $shift);
        $bonus += $langBonus;

        // WKR-014: Buddy/Coworker relationship bonus (up to 15 points)
        $relationshipBonus = $this->getRelationshipBonus($worker, $shift);
        $bonus += $relationshipBonus;

        // WKR-014: Team application priority bonus (up to 10 points)
        $teamBonus = $this->getTeamApplicationBonus($worker, $shift);
        $bonus += $teamBonus;

        return $bonus;
    }

    /**
     * Check if worker is favorite for this business
     */
    protected function isFavoriteWorker(User $worker, Shift $shift): bool
    {
        return DB::table('business_worker_roster')
            ->where('business_id', $shift->business_id)
            ->where('worker_id', $worker->id)
            ->where('status', 'favorite')
            ->exists();
    }

    /**
     * Check if worker has worked for this business before
     */
    protected function hasWorkedForBusiness(User $worker, int $businessId): bool
    {
        return $worker->shiftAssignments()
            ->whereHas('shift', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get tier-based bonus points
     */
    protected function getTierBonus(User $worker): float
    {
        $tier = $worker->tier ?? 'bronze';

        return match ($tier) {
            'platinum' => 8,
            'gold' => 5,
            'silver' => 2,
            default => 0
        };
    }

    /**
     * Get certification bonus for shift requirements
     */
    protected function getCertificationBonus(User $worker, Shift $shift): float
    {
        $requiredCerts = $shift->required_certifications ?? [];
        $workerCerts = $worker->certifications()->wherePivot('verified', true)->pluck('name')->toArray();

        $matchingCerts = array_intersect($requiredCerts, $workerCerts);

        // 1 point per matching certification, max 5 points
        return min(5, count($matchingCerts));
    }

    /**
     * Get language bonus for shift requirements
     */
    protected function getLanguageBonus(User $worker, Shift $shift): float
    {
        $requiredLangs = $shift->required_languages ?? ['en'];
        $workerLangs = $worker->languages ?? ['en'];

        $matchingLangs = array_intersect($requiredLangs, $workerLangs);

        // 1 point per matching language, max 3 points
        return min(3, count($matchingLangs));
    }

    /**
     * Advanced supply/demand calculation for surge pricing
     * SL-008: Dynamic Surge Pricing Engine
     */
    public function calculateDemandSurge(Shift $shift): array
    {
        // Get available workers for this role in area
        $availableWorkers = User::where('type', 'worker')
            ->whereHas('skills', function ($query) use ($shift) {
                $query->whereIn('skill_id', $shift->required_skills ?? []);
            })
            ->where('status', 'active')
            ->whereHas('workerProfile', function ($query) use ($shift) {
                $query->where('preferred_city', $shift->location_city);
            })
            ->count();

        // Get competing shifts in same timeframe
        $competingShifts = Shift::where('role_type', $shift->role_type)
            ->where('location_city', $shift->location_city)
            ->where('shift_date', $shift->shift_date)
            ->where('status', 'open')
            ->where('id', '!=', $shift->id)
            ->count();

        $supplyDemandRatio = max(0.1, $availableWorkers / max(1, $competingShifts));

        // Calculate surge based on ratio
        $demandSurge = match (true) {
            $supplyDemandRatio > 3.0 => 0.00, // Oversupply
            $supplyDemandRatio > 2.0 => 0.00,
            $supplyDemandRatio > 1.5 => 0.05,
            $supplyDemandRatio > 1.0 => 0.10,
            $supplyDemandRatio > 0.5 => 0.15,
            default => 0.25 // Severe undersupply
        };

        return [
            'surge_multiplier' => $demandSurge,
            'available_workers' => $availableWorkers,
            'competing_shifts' => $competingShifts,
            'supply_demand_ratio' => $supplyDemandRatio,
            'market_tightness' => $supplyDemandRatio < 1.0 ? 'tight' : 'loose',
        ];
    }

    /**
     * Predict shift fill probability
     * SL-002: Performance Targets
     */
    public function predictFillProbability(Shift $shift): array
    {
        $matchAnalysis = $this->calculateDemandSurge($shift);

        // Calculate base probability from demand/supply
        $baseProbability = min(95, max(5, ($matchAnalysis['available_workers'] / max(1, $shift->required_workers)) * 100));

        // Adjust for time factors
        $timeUntilShift = Carbon::parse($shift->shift_date.' '.$shift->start_time)->diffInHours(now());

        if ($timeUntilShift < 24) {
            $baseProbability *= 0.7; // Harder to fill last minute
        } elseif ($timeUntilShift > 168) { // More than a week
            $baseProbability *= 0.9; // Slightly harder due to commitment
        }

        // Adjust for rate competitiveness
        $avgRate = $this->getAverageRateForRole($shift->role_type, $shift->location_city);
        $rateCompetitiveness = $shift->final_rate / max(1, $avgRate);

        if ($rateCompetitiveness < 0.9) {
            $baseProbability *= 0.8; // Below market rate
        } elseif ($rateCompetitiveness > 1.1) {
            $baseProbability *= 1.1; // Above market rate
        }

        return [
            'probability' => round(min(100, max(0, $baseProbability)), 1),
            'confidence' => $matchAnalysis['available_workers'] > 10 ? 'high' : 'medium',
            'factors' => [
                'demand_supply' => $matchAnalysis['supply_demand_ratio'],
                'time_factor' => $timeUntilShift,
                'rate_competitiveness' => $rateCompetitiveness,
            ],
            'recommendations' => $this->getFillRecommendations($shift, $baseProbability),
        ];
    }

    /**
     * Get recommendations to improve fill probability
     */
    protected function getFillRecommendations(Shift $shift, float $probability): array
    {
        $recommendations = [];

        if ($probability < 50) {
            $recommendations[] = 'Consider increasing the rate by 10-20%';
            $recommendations[] = 'Post shift at least 72 hours in advance';
            $recommendations[] = 'Reduce required skills if possible';
        } elseif ($probability < 75) {
            $recommendations[] = 'Consider a small rate increase (5-10%)';
            $recommendations[] = 'Highlight any premium features of the shift';
        }

        return $recommendations;
    }

    /**
     * Get average market rate for role in location
     */
    protected function getAverageRateForRole(string $role, string $city): float
    {
        // In production, this would query historical shift data
        // For now, return mock data
        $marketRates = [
            'server' => 15.00,
            'bartender' => 18.00,
            'host' => 14.00,
            'cook' => 16.00,
            'cleaner' => 13.00,
        ];

        return $marketRates[$role] ?? 15.00;
    }

    // =========================================================================
    // WKR-014: TEAM FORMATION - BUDDY/COWORKER MATCHING
    // =========================================================================

    /**
     * Get relationship bonus based on buddy/preferred coworker matching.
     * WKR-014: Team Formation
     *
     * Bonus structure:
     * - Buddy already assigned to shift: +15 points
     * - Preferred coworker assigned: +8 points
     * - Avoided worker assigned: -20 points (penalty)
     */
    protected function getRelationshipBonus(User $worker, Shift $shift): float
    {
        $bonus = 0.0;

        // Get workers already assigned to this shift
        $assignedWorkerIds = DB::table('shift_assignments')
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['assigned', 'checked_in'])
            ->pluck('worker_id')
            ->toArray();

        if (empty($assignedWorkerIds)) {
            return 0.0;
        }

        // Check for buddy relationships (mutual buddies give highest bonus)
        $buddyCount = WorkerRelationship::where('worker_id', $worker->id)
            ->whereIn('related_worker_id', $assignedWorkerIds)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->where('is_mutual', true)
            ->count();

        $bonus += $buddyCount * 15; // 15 points per buddy on shift

        // Check for preferred coworkers
        $preferredCount = WorkerRelationship::where('worker_id', $worker->id)
            ->whereIn('related_worker_id', $assignedWorkerIds)
            ->where('relationship_type', WorkerRelationship::TYPE_PREFERRED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->count();

        $bonus += $preferredCount * 8; // 8 points per preferred coworker

        // Check for avoided workers (penalty)
        $avoidedCount = WorkerRelationship::where('worker_id', $worker->id)
            ->whereIn('related_worker_id', $assignedWorkerIds)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->count();

        // Also check if any assigned worker has marked this worker as avoided
        $avoidedByCount = WorkerRelationship::whereIn('worker_id', $assignedWorkerIds)
            ->where('related_worker_id', $worker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->count();

        $totalAvoided = $avoidedCount + $avoidedByCount;
        $bonus -= $totalAvoided * 20; // -20 points per avoided worker conflict

        // Cap the bonus to reasonable range
        return max(-50, min(30, $bonus));
    }

    /**
     * Get team application bonus for workers who are part of a team applying.
     * WKR-014: Team Formation
     *
     * Workers who are part of an approved or pending team application
     * get priority in matching.
     */
    protected function getTeamApplicationBonus(User $worker, Shift $shift): float
    {
        // Check if worker is part of a team with an active application for this shift
        $teamRequest = TeamShiftRequest::where('shift_id', $shift->id)
            ->whereIn('status', [
                TeamShiftRequest::STATUS_PENDING,
                TeamShiftRequest::STATUS_PARTIAL,
                TeamShiftRequest::STATUS_APPROVED,
            ])
            ->whereJsonContains('confirmed_members', $worker->id)
            ->first();

        if (! $teamRequest) {
            return 0.0;
        }

        // Calculate bonus based on team request status and priority
        $bonus = 0.0;

        // Base bonus for being part of a team application
        $bonus += 5;

        // Additional bonus for approved team applications
        if ($teamRequest->status === TeamShiftRequest::STATUS_APPROVED) {
            $bonus += 5;
        }

        // Priority score bonus (team has worked with business before, etc.)
        $bonus += min(5, $teamRequest->priority_score / 10);

        return $bonus;
    }

    /**
     * Check if a shift assignment would create avoided worker conflicts.
     * WKR-014: Team Formation
     *
     * @return array Contains 'has_conflict' boolean and 'conflicts' array
     */
    public function checkAvoidanceConflicts(User $worker, Shift $shift): array
    {
        $assignedWorkerIds = DB::table('shift_assignments')
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['assigned', 'checked_in'])
            ->pluck('worker_id')
            ->toArray();

        if (empty($assignedWorkerIds)) {
            return ['has_conflict' => false, 'conflicts' => []];
        }

        // Workers that the candidate has marked as avoided
        $avoidedByWorker = WorkerRelationship::where('worker_id', $worker->id)
            ->whereIn('related_worker_id', $assignedWorkerIds)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->with('relatedWorker:id,name')
            ->get();

        // Assigned workers who have marked the candidate as avoided
        $workerAvoidedBy = WorkerRelationship::whereIn('worker_id', $assignedWorkerIds)
            ->where('related_worker_id', $worker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->with('worker:id,name')
            ->get();

        $conflicts = [];

        foreach ($avoidedByWorker as $relation) {
            $conflicts[] = [
                'type' => 'worker_avoids',
                'worker_id' => $relation->related_worker_id,
                'worker_name' => $relation->relatedWorker->name ?? 'Unknown',
                'reason' => 'Candidate has marked this worker as avoided',
            ];
        }

        foreach ($workerAvoidedBy as $relation) {
            $conflicts[] = [
                'type' => 'avoided_by_worker',
                'worker_id' => $relation->worker_id,
                'worker_name' => $relation->worker->name ?? 'Unknown',
                'reason' => 'This worker has marked the candidate as avoided',
            ];
        }

        return [
            'has_conflict' => ! empty($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Get buddy pairs among workers assigned to a shift.
     * WKR-014: Team Formation
     *
     * Useful for optimizing break schedules, task assignments, etc.
     */
    public function getBuddyPairsOnShift(Shift $shift): array
    {
        $assignedWorkerIds = DB::table('shift_assignments')
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['assigned', 'checked_in', 'completed'])
            ->pluck('worker_id')
            ->toArray();

        if (count($assignedWorkerIds) < 2) {
            return [];
        }

        // Find mutual buddy relationships among assigned workers
        $buddyPairs = WorkerRelationship::whereIn('worker_id', $assignedWorkerIds)
            ->whereIn('related_worker_id', $assignedWorkerIds)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->where('is_mutual', true)
            ->with(['worker:id,name', 'relatedWorker:id,name'])
            ->get()
            ->map(function ($relation) {
                // Only include each pair once (worker_id < related_worker_id)
                if ($relation->worker_id > $relation->related_worker_id) {
                    return null;
                }

                return [
                    'worker_1' => [
                        'id' => $relation->worker_id,
                        'name' => $relation->worker->name ?? 'Unknown',
                    ],
                    'worker_2' => [
                        'id' => $relation->related_worker_id,
                        'name' => $relation->relatedWorker->name ?? 'Unknown',
                    ],
                    'compatibility_score' => $relation->compatibility_score,
                    'shifts_together' => $relation->shifts_together,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return $buddyPairs;
    }

    /**
     * Suggest optimal worker assignments based on relationships.
     * WKR-014: Team Formation
     *
     * Given a shift and a list of applicants, suggests the best combination
     * considering buddy pairs and avoided worker conflicts.
     */
    public function suggestOptimalAssignments(Shift $shift, array $applicantIds): array
    {
        $requiredWorkers = $shift->required_workers - $shift->filled_workers;

        if ($requiredWorkers <= 0 || empty($applicantIds)) {
            return [];
        }

        // Score each applicant considering existing assignments
        $scoredApplicants = [];

        foreach ($applicantIds as $applicantId) {
            $worker = User::find($applicantId);
            if (! $worker) {
                continue;
            }

            $matchScore = $this->calculateWorkerShiftMatch($worker, $shift);
            $conflictCheck = $this->checkAvoidanceConflicts($worker, $shift);

            $scoredApplicants[] = [
                'worker_id' => $applicantId,
                'match_score' => $matchScore['final_score'],
                'has_conflict' => $conflictCheck['has_conflict'],
                'conflict_count' => count($conflictCheck['conflicts']),
                'conflicts' => $conflictCheck['conflicts'],
            ];
        }

        // Sort by match score (highest first), deprioritizing those with conflicts
        usort($scoredApplicants, function ($a, $b) {
            // First prioritize no-conflict workers
            if ($a['has_conflict'] !== $b['has_conflict']) {
                return $a['has_conflict'] ? 1 : -1;
            }

            // Then by conflict count (fewer is better)
            if ($a['conflict_count'] !== $b['conflict_count']) {
                return $a['conflict_count'] - $b['conflict_count'];
            }

            // Finally by match score
            return $b['match_score'] <=> $a['match_score'];
        });

        // Return top candidates up to required count
        return array_slice($scoredApplicants, 0, $requiredWorkers);
    }

    /**
     * Prioritize team applications for a shift.
     * WKR-014: Team Formation
     *
     * Returns team applications sorted by priority score.
     */
    public function getTeamApplicationsForShift(Shift $shift): array
    {
        return TeamShiftRequest::where('shift_id', $shift->id)
            ->whereIn('status', [
                TeamShiftRequest::STATUS_PENDING,
                TeamShiftRequest::STATUS_PARTIAL,
            ])
            ->with(['team', 'requester:id,name'])
            ->orderByDesc('priority_score')
            ->orderBy('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'request_id' => $request->id,
                    'team_id' => $request->team_id,
                    'team_name' => $request->team->name ?? 'Unknown Team',
                    'members_needed' => $request->members_needed,
                    'members_confirmed' => $request->members_confirmed,
                    'progress' => $request->getProgressPercentage(),
                    'priority_score' => $request->priority_score,
                    'status' => $request->status,
                    'requested_by' => $request->requester->name ?? 'Unknown',
                    'application_message' => $request->application_message,
                    'created_at' => $request->created_at->toISOString(),
                ];
            })
            ->toArray();
    }
}
