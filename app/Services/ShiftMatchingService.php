<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\WorkerProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftMatchingService
{
    /**
     * Match and rank shifts for a specific worker.
     * Returns shifts sorted by match score (highest first).
     */
    public function matchShiftsForWorker(User $worker)
    {
        if (!$worker->isWorker()) {
            return collect([]);
        }

        $workerProfile = $worker->workerProfile;
        if (!$workerProfile) {
            return collect([]);
        }

        // Get all open upcoming shifts
        $shifts = Shift::with(['business', 'assignments'])
            ->open()
            ->upcoming()
            ->get();

        // Calculate match score for each shift
        $rankedShifts = $shifts->map(function ($shift) use ($worker, $workerProfile) {
            $matchScore = $this->calculateWorkerShiftMatch($worker, $shift);
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
     * Calculate match score between a worker and a shift (0-100).
     *
     * Scoring breakdown:
     * - Skills match: 40 points
     * - Location proximity: 25 points
     * - Availability: 20 points
     * - Industry experience: 10 points
     * - Rating: 5 points
     */
    public function calculateWorkerShiftMatch(User $worker, Shift $shift)
    {
        $workerProfile = $worker->workerProfile;
        if (!$workerProfile) {
            return 0;
        }

        $score = 0;

        // 1. Skills Match (40 points)
        $score += $this->calculateSkillsMatch($worker, $shift);

        // 2. Location Proximity (25 points)
        $score += $this->calculateLocationMatch($workerProfile, $shift);

        // 3. Availability (20 points)
        $score += $this->calculateAvailabilityMatch($workerProfile, $shift);

        // 4. Industry Experience (10 points)
        $score += $this->calculateIndustryMatch($workerProfile, $shift);

        // 5. Rating (5 points)
        $score += $this->calculateRatingMatch($worker);

        return round($score, 2);
    }

    /**
     * Calculate skills match score (0-40 points).
     */
    protected function calculateSkillsMatch(User $worker, Shift $shift)
    {
        // Get worker's skills
        $workerSkills = $worker->skills()->pluck('skill_name')->toArray();

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
        if (!$workerProfile->location_lat || !$workerProfile->location_lng ||
            !$shift->location_lat || !$shift->location_lng) {
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
            $experienceYears = $workerIndustries[$shift->industry . '_years'] ?? 0;

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
            $query->whereRaw("JSON_CONTAINS(availability_broadcasts.industries, ?)", [json_encode($industry)]);
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
}
