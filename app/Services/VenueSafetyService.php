<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueSafetyFlag;
use App\Models\VenueSafetyRating;
use App\Notifications\VenueSafetyFlagNotification;
use App\Notifications\VenueSafetyInvestigationNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SAF-004: Venue Safety Service
 *
 * Handles all venue safety rating and flag management including:
 * - Safety rating submissions and score calculations
 * - Safety flag creation and resolution
 * - Venue safety status management
 * - Investigation triggers and notifications
 * - Safety trend analysis
 */
class VenueSafetyService
{
    /**
     * Safety score threshold for triggering investigation.
     */
    public const SAFETY_SCORE_THRESHOLD = 3.0;

    /**
     * Number of flags in 30 days to trigger investigation.
     */
    public const FLAGS_THRESHOLD_COUNT = 3;

    /**
     * Days to look back for flag count threshold.
     */
    public const FLAGS_THRESHOLD_DAYS = 30;

    /**
     * Submit a safety rating for a venue after completing a shift.
     */
    public function submitSafetyRating(User $worker, Venue $venue, ?Shift $shift, array $ratings): VenueSafetyRating
    {
        return DB::transaction(function () use ($worker, $venue, $shift, $ratings) {
            // Check if rating already exists for this combination
            $existingRating = VenueSafetyRating::where('venue_id', $venue->id)
                ->where('user_id', $worker->id)
                ->where('shift_id', $shift?->id)
                ->first();

            if ($existingRating) {
                // Update existing rating
                $existingRating->update($ratings);
                $safetyRating = $existingRating;

                Log::info('Venue safety rating updated', [
                    'rating_id' => $safetyRating->id,
                    'venue_id' => $venue->id,
                    'user_id' => $worker->id,
                ]);
            } else {
                // Create new rating
                $safetyRating = VenueSafetyRating::create([
                    'venue_id' => $venue->id,
                    'user_id' => $worker->id,
                    'shift_id' => $shift?->id,
                    'overall_safety' => $ratings['overall_safety'],
                    'lighting_rating' => $ratings['lighting_rating'] ?? null,
                    'parking_safety' => $ratings['parking_safety'] ?? null,
                    'emergency_exits' => $ratings['emergency_exits'] ?? null,
                    'staff_support' => $ratings['staff_support'] ?? null,
                    'equipment_condition' => $ratings['equipment_condition'] ?? null,
                    'safety_concerns' => $ratings['safety_concerns'] ?? null,
                    'positive_notes' => $ratings['positive_notes'] ?? null,
                    'would_return' => $ratings['would_return'] ?? true,
                    'is_anonymous' => $ratings['is_anonymous'] ?? false,
                ]);

                Log::info('Venue safety rating submitted', [
                    'rating_id' => $safetyRating->id,
                    'venue_id' => $venue->id,
                    'user_id' => $worker->id,
                    'overall_safety' => $safetyRating->overall_safety,
                ]);
            }

            // Recalculate venue safety score
            $newScore = $this->calculateSafetyScore($venue);
            $venue->update([
                'safety_score' => $newScore,
                'safety_ratings_count' => VenueSafetyRating::forVenue($venue->id)->count(),
            ]);

            // Update venue safety status based on new score
            $this->updateVenueSafetyStatus($venue);

            // Check if investigation is required
            if ($this->requiresInvestigation($venue)) {
                $this->triggerInvestigation($venue);
            }

            return $safetyRating;
        });
    }

    /**
     * Calculate the safety score for a venue.
     */
    public function calculateSafetyScore(Venue $venue): float
    {
        $average = VenueSafetyRating::forVenue($venue->id)
            ->avg('overall_safety');

        return $average ? round($average, 2) : 0.00;
    }

    /**
     * Update venue safety status based on score and flags.
     */
    public function updateVenueSafetyStatus(Venue $venue): void
    {
        $venue->refresh();

        $score = $venue->safety_score;
        $activeFlags = VenueSafetyFlag::forVenue($venue->id)->open()->count();
        $criticalFlags = VenueSafetyFlag::forVenue($venue->id)->open()->critical()->count();

        // Determine status based on score and flags
        $status = 'good';

        if ($criticalFlags > 0) {
            $status = 'restricted';
        } elseif ($activeFlags >= 3 || ($score !== null && $score < 2.5)) {
            $status = 'warning';
        } elseif ($activeFlags >= 1 || ($score !== null && $score < 3.5)) {
            $status = 'caution';
        }

        $venue->update([
            'safety_status' => $status,
            'active_safety_flags' => $activeFlags,
        ]);

        Log::info('Venue safety status updated', [
            'venue_id' => $venue->id,
            'new_status' => $status,
            'safety_score' => $score,
            'active_flags' => $activeFlags,
        ]);
    }

    /**
     * Flag a safety concern at a venue.
     */
    public function flagSafetyConcern(User $worker, Venue $venue, array $data): VenueSafetyFlag
    {
        return DB::transaction(function () use ($worker, $venue, $data) {
            $flag = VenueSafetyFlag::create([
                'venue_id' => $venue->id,
                'reported_by' => $worker->id,
                'flag_type' => $data['flag_type'],
                'severity' => $data['severity'] ?? VenueSafetyFlag::SEVERITY_MEDIUM,
                'description' => $data['description'],
                'evidence_urls' => $data['evidence_urls'] ?? null,
                'status' => VenueSafetyFlag::STATUS_REPORTED,
            ]);

            Log::info('Venue safety flag created', [
                'flag_id' => $flag->id,
                'venue_id' => $venue->id,
                'reported_by' => $worker->id,
                'flag_type' => $flag->flag_type,
                'severity' => $flag->severity,
            ]);

            // Update venue active flags count
            $venue->increment('active_safety_flags');
            $this->updateVenueSafetyStatus($venue);

            // Notify business of the flag
            $this->notifyBusinessOfFlag($flag);

            // Auto-trigger investigation for critical flags
            if ($flag->severity === VenueSafetyFlag::SEVERITY_CRITICAL) {
                $this->triggerInvestigation($venue);
            }

            // Check if flag threshold is met
            if ($this->requiresInvestigation($venue)) {
                $this->triggerInvestigation($venue);
            }

            return $flag;
        });
    }

    /**
     * Get a comprehensive safety summary for a venue.
     */
    public function getVenueSafetySummary(Venue $venue): array
    {
        $ratings = VenueSafetyRating::forVenue($venue->id);
        $flags = VenueSafetyFlag::forVenue($venue->id);

        return [
            'venue_id' => $venue->id,
            'safety_score' => $venue->safety_score,
            'safety_status' => $venue->safety_status,
            'safety_status_label' => $venue->safety_status_label,
            'safety_verified' => $venue->safety_verified,
            'last_safety_audit' => $venue->last_safety_audit?->toIso8601String(),
            'ratings' => [
                'total_count' => $ratings->count(),
                'average_score' => $this->calculateSafetyScore($venue),
                'distribution' => VenueSafetyRating::getRatingDistribution($venue->id),
                'aspect_summary' => VenueSafetyRating::getAspectSummary($venue->id),
                'would_return_percentage' => $this->calculateWouldReturnPercentage($venue),
                'recent_count' => $ratings->recent(30)->count(),
            ],
            'flags' => [
                'total_count' => $flags->count(),
                'open_count' => $flags->clone()->open()->count(),
                'resolved_count' => $flags->clone()->withStatus(VenueSafetyFlag::STATUS_RESOLVED)->count(),
                'by_type' => VenueSafetyFlag::countByTypeForVenue($venue->id),
                'by_status' => VenueSafetyFlag::countByStatusForVenue($venue->id),
                'critical_open' => $flags->clone()->open()->critical()->count(),
                'recent_count' => $flags->clone()->recent(30)->count(),
            ],
            'requires_investigation' => $this->requiresInvestigation($venue),
            'warning_message' => $this->getSafetyWarningForWorker($venue),
        ];
    }

    /**
     * Get safety trend for a venue over specified months.
     */
    public function getSafetyTrend(Venue $venue, int $months = 6): array
    {
        $trend = [];
        $startDate = now()->subMonths($months)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthRatings = VenueSafetyRating::forVenue($venue->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            $monthFlags = VenueSafetyFlag::forVenue($venue->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'average_score' => round($monthRatings->clone()->avg('overall_safety') ?? 0, 2),
                'rating_count' => $monthRatings->clone()->count(),
                'flag_count' => $monthFlags->clone()->count(),
                'critical_flags' => $monthFlags->clone()->critical()->count(),
            ];
        }

        return $trend;
    }

    /**
     * Get venues with safety score below threshold.
     */
    public function getUnsafeVenues(float $threshold = 3.0): Collection
    {
        return Venue::where('safety_score', '<', $threshold)
            ->whereNotNull('safety_score')
            ->with(['businessProfile', 'safetyFlags' => function ($query) {
                $query->open()->orderBy('severity', 'desc');
            }])
            ->orderBy('safety_score', 'asc')
            ->get();
    }

    /**
     * Check if a venue requires investigation.
     */
    public function requiresInvestigation(Venue $venue): bool
    {
        // Check if safety score is below threshold
        if ($venue->safety_score !== null && $venue->safety_score < self::SAFETY_SCORE_THRESHOLD) {
            return true;
        }

        // Check for critical severity flags
        $criticalFlags = VenueSafetyFlag::forVenue($venue->id)
            ->open()
            ->critical()
            ->count();

        if ($criticalFlags > 0) {
            return true;
        }

        // Check for multiple flags in last 30 days
        $recentFlags = VenueSafetyFlag::forVenue($venue->id)
            ->open()
            ->where('created_at', '>=', now()->subDays(self::FLAGS_THRESHOLD_DAYS))
            ->count();

        if ($recentFlags >= self::FLAGS_THRESHOLD_COUNT) {
            return true;
        }

        return false;
    }

    /**
     * Trigger an investigation for a venue.
     */
    public function triggerInvestigation(Venue $venue): void
    {
        Log::warning('Venue safety investigation triggered', [
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'safety_score' => $venue->safety_score,
            'active_flags' => $venue->active_safety_flags,
        ]);

        // Notify admins about the investigation
        $this->notifyAdminsOfInvestigation($venue);

        // Update venue status if not already restricted
        if ($venue->safety_status !== 'restricted') {
            $venue->update(['safety_status' => 'warning']);
        }
    }

    /**
     * Notify business about a safety flag.
     */
    public function notifyBusinessOfFlag(VenueSafetyFlag $flag): void
    {
        try {
            $venue = $flag->venue;
            $businessProfile = $venue->businessProfile;

            if (! $businessProfile) {
                Log::warning('No business profile found for venue safety flag notification', [
                    'flag_id' => $flag->id,
                    'venue_id' => $venue->id,
                ]);

                return;
            }

            // Get the business owner user
            $businessOwner = $businessProfile->user;

            if ($businessOwner) {
                $businessOwner->notify(new VenueSafetyFlagNotification($flag));

                // Mark flag as business notified and set response deadline
                $flag->markBusinessNotified();

                Log::info('Business notified of safety flag', [
                    'flag_id' => $flag->id,
                    'business_id' => $businessOwner->id,
                    'response_due' => $flag->business_response_due,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify business of safety flag', [
                'flag_id' => $flag->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve a safety flag.
     */
    public function resolveFlag(VenueSafetyFlag $flag, User $admin, string $notes): void
    {
        DB::transaction(function () use ($flag, $admin, $notes) {
            $flag->update([
                'status' => VenueSafetyFlag::STATUS_RESOLVED,
                'assigned_to' => $admin->id,
                'resolution_notes' => $notes,
                'resolved_at' => now(),
            ]);

            // Update venue active flags count
            $venue = $flag->venue;
            $activeFlags = VenueSafetyFlag::forVenue($venue->id)->open()->count();
            $venue->update(['active_safety_flags' => $activeFlags]);

            // Recalculate venue safety status
            $this->updateVenueSafetyStatus($venue);

            Log::info('Venue safety flag resolved', [
                'flag_id' => $flag->id,
                'resolved_by' => $admin->id,
                'resolution_notes' => $notes,
            ]);
        });
    }

    /**
     * Dismiss a safety flag.
     */
    public function dismissFlag(VenueSafetyFlag $flag, User $admin, string $reason): void
    {
        DB::transaction(function () use ($flag, $admin, $reason) {
            $flag->update([
                'status' => VenueSafetyFlag::STATUS_DISMISSED,
                'assigned_to' => $admin->id,
                'resolution_notes' => 'Dismissed: '.$reason,
                'resolved_at' => now(),
            ]);

            // Update venue active flags count
            $venue = $flag->venue;
            $activeFlags = VenueSafetyFlag::forVenue($venue->id)->open()->count();
            $venue->update(['active_safety_flags' => $activeFlags]);

            // Recalculate venue safety status
            $this->updateVenueSafetyStatus($venue);

            Log::info('Venue safety flag dismissed', [
                'flag_id' => $flag->id,
                'dismissed_by' => $admin->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Get a safety warning message for workers viewing a venue.
     */
    public function getSafetyWarningForWorker(Venue $venue): ?string
    {
        $messages = [];

        // Check safety status
        switch ($venue->safety_status) {
            case 'restricted':
                return 'This venue is currently restricted due to safety concerns. New shifts may not be available.';

            case 'warning':
                $messages[] = 'This venue has safety concerns that are being investigated.';
                break;

            case 'caution':
                $messages[] = 'Some workers have reported safety concerns at this venue.';
                break;
        }

        // Check safety score
        if ($venue->safety_score !== null && $venue->safety_score < 3.0) {
            $messages[] = sprintf(
                'Safety rating: %s (below average)',
                $venue->safety_score_display
            );
        }

        // Check active flags
        if ($venue->active_safety_flags > 0) {
            $messages[] = sprintf(
                '%d active safety concern(s) reported.',
                $venue->active_safety_flags
            );
        }

        // Check would return percentage
        $wouldReturnPct = $this->calculateWouldReturnPercentage($venue);
        if ($wouldReturnPct !== null && $wouldReturnPct < 70) {
            $messages[] = sprintf(
                'Only %.0f%% of workers said they would return to this venue.',
                $wouldReturnPct
            );
        }

        return empty($messages) ? null : implode(' ', $messages);
    }

    /**
     * Calculate the percentage of workers who would return.
     */
    protected function calculateWouldReturnPercentage(Venue $venue): ?float
    {
        $totalRatings = VenueSafetyRating::forVenue($venue->id)->count();

        if ($totalRatings === 0) {
            return null;
        }

        $wouldReturn = VenueSafetyRating::forVenue($venue->id)
            ->where('would_return', true)
            ->count();

        return round(($wouldReturn / $totalRatings) * 100, 1);
    }

    /**
     * Notify admins about an investigation trigger.
     */
    protected function notifyAdminsOfInvestigation(Venue $venue): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new VenueSafetyInvestigationNotification($venue));
                } catch (\Exception $e) {
                    Log::warning('Failed to notify admin about safety investigation', [
                        'admin_id' => $admin->id,
                        'venue_id' => $venue->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Admins notified about venue safety investigation', [
                'venue_id' => $venue->id,
                'admins_notified' => $admins->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about safety investigation', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Assign an admin to investigate a flag.
     */
    public function assignFlagToAdmin(VenueSafetyFlag $flag, User $admin): void
    {
        $flag->update([
            'assigned_to' => $admin->id,
            'status' => VenueSafetyFlag::STATUS_INVESTIGATING,
        ]);

        Log::info('Safety flag assigned to admin', [
            'flag_id' => $flag->id,
            'assigned_to' => $admin->id,
        ]);
    }

    /**
     * Record business response to a flag.
     */
    public function recordBusinessResponse(VenueSafetyFlag $flag, string $response): void
    {
        $flag->update([
            'business_response' => $response,
        ]);

        Log::info('Business response recorded for safety flag', [
            'flag_id' => $flag->id,
            'response_length' => strlen($response),
        ]);
    }

    /**
     * Get flags requiring attention (overdue or critical).
     */
    public function getFlagsRequiringAttention(): Collection
    {
        return VenueSafetyFlag::where(function ($query) {
            $query->where('severity', VenueSafetyFlag::SEVERITY_CRITICAL)
                ->whereIn('status', [VenueSafetyFlag::STATUS_REPORTED, VenueSafetyFlag::STATUS_INVESTIGATING]);
        })
            ->orWhere(function ($query) {
                $query->where('business_notified', true)
                    ->whereNotNull('business_response_due')
                    ->where('business_response_due', '<', now())
                    ->whereNull('business_response');
            })
            ->with(['venue', 'reporter', 'assignee'])
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get safety statistics for admin dashboard.
     */
    public function getAdminDashboardStats(): array
    {
        return [
            'total_ratings' => VenueSafetyRating::count(),
            'recent_ratings' => VenueSafetyRating::recent(30)->count(),
            'average_platform_score' => round(VenueSafetyRating::avg('overall_safety') ?? 0, 2),
            'total_flags' => VenueSafetyFlag::count(),
            'open_flags' => VenueSafetyFlag::open()->count(),
            'critical_flags' => VenueSafetyFlag::open()->critical()->count(),
            'overdue_responses' => VenueSafetyFlag::overdue()->count(),
            'unassigned_flags' => VenueSafetyFlag::open()->unassigned()->count(),
            'venues_with_concerns' => Venue::where('safety_status', '!=', 'good')->count(),
            'restricted_venues' => Venue::where('safety_status', 'restricted')->count(),
            'venues_below_threshold' => Venue::where('safety_score', '<', self::SAFETY_SCORE_THRESHOLD)
                ->whereNotNull('safety_score')
                ->count(),
        ];
    }

    /**
     * Conduct a safety audit on a venue.
     */
    public function recordSafetyAudit(Venue $venue, User $admin, bool $passed): void
    {
        $venue->update([
            'safety_verified' => $passed,
            'last_safety_audit' => now(),
        ]);

        // If audit passed and venue was in warning/caution, reset to good
        if ($passed && in_array($venue->safety_status, ['caution', 'warning'])) {
            $this->updateVenueSafetyStatus($venue);
        }

        Log::info('Venue safety audit recorded', [
            'venue_id' => $venue->id,
            'audited_by' => $admin->id,
            'passed' => $passed,
        ]);
    }
}
