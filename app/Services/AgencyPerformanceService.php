<?php

namespace App\Services;

use App\Models\AgencyPerformanceScorecard;
use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\Dispute;
use App\Models\Rating;
use App\Models\ShiftAssignment;
use App\Models\ShiftAudit;
use App\Models\UrgentShiftRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * AgencyPerformanceService
 *
 * Generates and manages agency performance scorecards with automated warnings and sanctions.
 *
 * TASK: AGY-005 Performance Monitoring
 *
 * Features:
 * - Weekly scorecard generation with key metrics
 * - Performance targets (Fill rate >90%, No-show <3%, Rating >4.3, Complaints <2%)
 * - Automated performance alerts (Yellow/Red status)
 * - Consequence enforcement (warnings, fee increases, suspension)
 */
class AgencyPerformanceService
{
    // Performance targets
    const TARGET_FILL_RATE = 90.00;

    const TARGET_NO_SHOW_RATE = 3.00;

    const TARGET_AVERAGE_RATING = 4.30;

    const TARGET_COMPLAINT_RATE = 2.00;

    // Thresholds for yellow/red status
    const YELLOW_THRESHOLD_VARIANCE = 5.00; // 5% below target

    const RED_THRESHOLD_VARIANCE = 10.00;   // 10% below target

    /**
     * Generate weekly scorecards for all agencies.
     *
     * @param  \Carbon\Carbon|null  $periodStart
     * @param  \Carbon\Carbon|null  $periodEnd
     * @return array Summary of generated scorecards
     */
    public function generateWeeklyScorecards($periodStart = null, $periodEnd = null)
    {
        $periodStart = $periodStart ?? now()->subWeek()->startOfWeek();
        $periodEnd = $periodEnd ?? now()->subWeek()->endOfWeek();

        // Get all active agencies
        $agencies = User::whereHas('agencyProfile')->with('agencyProfile')->get();

        $summary = [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'total_agencies' => $agencies->count(),
            'green' => 0,
            'yellow' => 0,
            'red' => 0,
            'warnings_sent' => 0,
            'sanctions_applied' => 0,
        ];

        foreach ($agencies as $agency) {
            $scorecard = $this->generateScorecardForAgency(
                $agency->id,
                $periodStart,
                $periodEnd
            );

            // Update summary
            $summary[$scorecard->status]++;

            if ($scorecard->warning_sent) {
                $summary['warnings_sent']++;
            }

            if ($scorecard->sanction_applied) {
                $summary['sanctions_applied']++;
            }
        }

        Log::info('Weekly scorecards generated', $summary);

        return $summary;
    }

    /**
     * Generate performance scorecard for a specific agency.
     *
     * @param  int  $agencyId
     * @param  \Carbon\Carbon  $periodStart
     * @param  \Carbon\Carbon  $periodEnd
     * @return AgencyPerformanceScorecard
     */
    public function generateScorecardForAgency($agencyId, $periodStart, $periodEnd)
    {
        $metrics = $this->calculateMetrics($agencyId, $periodStart, $periodEnd);
        $status = $this->determineStatus($metrics);
        $warnings = $this->generateWarnings($metrics);
        $flags = $this->generateFlags($metrics);

        // Create or update scorecard
        $scorecard = AgencyPerformanceScorecard::updateOrCreate(
            [
                'agency_id' => $agencyId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            array_merge($metrics, [
                'period_type' => 'weekly',
                'status' => $status,
                'warnings' => $warnings,
                'flags' => $flags,
                'target_fill_rate' => self::TARGET_FILL_RATE,
                'target_no_show_rate' => self::TARGET_NO_SHOW_RATE,
                'target_average_rating' => self::TARGET_AVERAGE_RATING,
                'target_complaint_rate' => self::TARGET_COMPLAINT_RATE,
                'generated_at' => now(),
            ])
        );

        // Apply automated actions
        $this->applyAutomatedActions($scorecard);

        return $scorecard;
    }

    /**
     * Calculate performance metrics for an agency.
     *
     * @param  int  $agencyId
     * @param  \Carbon\Carbon  $periodStart
     * @param  \Carbon\Carbon  $periodEnd
     * @return array
     */
    protected function calculateMetrics($agencyId, $periodStart, $periodEnd)
    {
        // Get all shift assignments for agency workers in this period
        $assignments = ShiftAssignment::whereHas('agencyWorker', function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with(['shift', 'worker'])
            ->get();

        $totalAssignments = $assignments->count();

        // Calculate fill rate
        $filledAssignments = $assignments->whereIn('status', ['confirmed', 'in_progress', 'completed'])->count();
        $unfilledAssignments = $totalAssignments - $filledAssignments;
        $fillRate = $totalAssignments > 0 ? ($filledAssignments / $totalAssignments) * 100 : 0;

        // Calculate no-show rate
        $noShows = $assignments->where('status', 'no_show')->count();
        $noShowRate = $totalAssignments > 0 ? ($noShows / $totalAssignments) * 100 : 0;

        // Calculate average worker rating
        $ratings = DB::table('ratings')
            ->whereIn('shift_assignment_id', $assignments->pluck('id'))
            ->where('rated_type', 'worker')
            ->get();

        $totalRatings = $ratings->count();
        $totalRatingSum = $ratings->sum('rating');
        $averageRating = $totalRatings > 0 ? $totalRatingSum / $totalRatings : 0;

        // Calculate complaint rate from multiple sources
        $complaints = $this->getComplaintsCount($agency, $periodStart, $periodEnd);
        $complaintRate = $totalAssignments > 0 ? ($complaints / $totalAssignments) * 100 : 0;

        // Calculate urgent fill metrics
        $urgentRequests = UrgentShiftRequest::where('accepted_by_agency_id', $agencyId)
            ->whereBetween('detected_at', [$periodStart, $periodEnd])
            ->get();

        $urgentFillRequests = $urgentRequests->count();
        $urgentFillsCompleted = $urgentRequests->whereIn('status', ['filled', 'accepted'])->count();
        $urgentFillRate = $urgentFillRequests > 0 ? ($urgentFillsCompleted / $urgentFillRequests) * 100 : 0;

        $averageResponseTime = $urgentRequests->whereNotNull('response_time_minutes')
            ->avg('response_time_minutes');

        return [
            'fill_rate' => round($fillRate, 2),
            'no_show_rate' => round($noShowRate, 2),
            'average_worker_rating' => round($averageRating, 2),
            'complaint_rate' => round($complaintRate, 2),
            'total_shifts_assigned' => $totalAssignments,
            'shifts_filled' => $filledAssignments,
            'shifts_unfilled' => $unfilledAssignments,
            'no_shows' => $noShows,
            'complaints_received' => $complaints,
            'total_ratings' => $totalRatings,
            'total_rating_sum' => round($totalRatingSum, 2),
            'urgent_fill_requests' => $urgentFillRequests,
            'urgent_fills_completed' => $urgentFillsCompleted,
            'urgent_fill_rate' => round($urgentFillRate, 2),
            'average_response_time_minutes' => $averageResponseTime ? round($averageResponseTime, 2) : null,
        ];
    }

    /**
     * Determine overall performance status.
     *
     * @param  array  $metrics
     * @return string green, yellow, or red
     */
    protected function determineStatus($metrics)
    {
        $criticalFailures = 0;
        $warnings = 0;

        // Check fill rate
        if ($metrics['fill_rate'] < (self::TARGET_FILL_RATE - self::RED_THRESHOLD_VARIANCE)) {
            $criticalFailures++;
        } elseif ($metrics['fill_rate'] < (self::TARGET_FILL_RATE - self::YELLOW_THRESHOLD_VARIANCE)) {
            $warnings++;
        }

        // Check no-show rate (inverse - higher is worse)
        if ($metrics['no_show_rate'] > (self::TARGET_NO_SHOW_RATE + self::YELLOW_THRESHOLD_VARIANCE)) {
            $criticalFailures++;
        } elseif ($metrics['no_show_rate'] > (self::TARGET_NO_SHOW_RATE + 1)) {
            $warnings++;
        }

        // Check average rating
        if ($metrics['average_worker_rating'] > 0) {
            if ($metrics['average_worker_rating'] < (self::TARGET_AVERAGE_RATING - 0.5)) {
                $criticalFailures++;
            } elseif ($metrics['average_worker_rating'] < (self::TARGET_AVERAGE_RATING - 0.2)) {
                $warnings++;
            }
        }

        // Check complaint rate
        if ($metrics['complaint_rate'] > (self::TARGET_COMPLAINT_RATE + self::YELLOW_THRESHOLD_VARIANCE)) {
            $criticalFailures++;
        } elseif ($metrics['complaint_rate'] > (self::TARGET_COMPLAINT_RATE + 1)) {
            $warnings++;
        }

        // Determine status
        if ($criticalFailures > 0) {
            return 'red';
        } elseif ($warnings > 1) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Generate warning messages for failed metrics.
     *
     * @param  array  $metrics
     * @return array
     */
    protected function generateWarnings($metrics)
    {
        $warnings = [];

        if ($metrics['fill_rate'] < self::TARGET_FILL_RATE) {
            $warnings[] = sprintf(
                'Fill rate (%s%%) is below target (%s%%)',
                $metrics['fill_rate'],
                self::TARGET_FILL_RATE
            );
        }

        if ($metrics['no_show_rate'] > self::TARGET_NO_SHOW_RATE) {
            $warnings[] = sprintf(
                'No-show rate (%s%%) exceeds target (%s%%)',
                $metrics['no_show_rate'],
                self::TARGET_NO_SHOW_RATE
            );
        }

        if ($metrics['average_worker_rating'] > 0 && $metrics['average_worker_rating'] < self::TARGET_AVERAGE_RATING) {
            $warnings[] = sprintf(
                'Average worker rating (%s) is below target (%s)',
                $metrics['average_worker_rating'],
                self::TARGET_AVERAGE_RATING
            );
        }

        if ($metrics['complaint_rate'] > self::TARGET_COMPLAINT_RATE) {
            $warnings[] = sprintf(
                'Complaint rate (%s%%) exceeds target (%s%%)',
                $metrics['complaint_rate'],
                self::TARGET_COMPLAINT_RATE
            );
        }

        return $warnings;
    }

    /**
     * Generate flags for metrics requiring attention.
     *
     * @param  array  $metrics
     * @return array
     */
    protected function generateFlags($metrics)
    {
        $flags = [];

        if ($metrics['fill_rate'] < (self::TARGET_FILL_RATE - self::RED_THRESHOLD_VARIANCE)) {
            $flags[] = 'critical_fill_rate';
        }

        if ($metrics['no_show_rate'] > (self::TARGET_NO_SHOW_RATE + 5)) {
            $flags[] = 'critical_no_show_rate';
        }

        if ($metrics['average_worker_rating'] > 0 && $metrics['average_worker_rating'] < 4.0) {
            $flags[] = 'low_worker_quality';
        }

        if ($metrics['complaint_rate'] > 5) {
            $flags[] = 'high_complaints';
        }

        return $flags;
    }

    /**
     * Apply automated actions based on scorecard status.
     *
     * @param  AgencyPerformanceScorecard  $scorecard
     * @return void
     */
    protected function applyAutomatedActions($scorecard)
    {
        // Yellow status: Send warning
        if ($scorecard->status === 'yellow' && ! $scorecard->warning_sent) {
            $this->sendPerformanceWarning($scorecard);
            $scorecard->sendWarning();
        }

        // Red status: Apply sanction
        if ($scorecard->status === 'red' && ! $scorecard->sanction_applied) {
            // Check consecutive red scorecards
            $consecutiveRed = $this->getConsecutiveRedCount($scorecard->agency_id, $scorecard->period_end);

            if ($consecutiveRed >= 3) {
                // Suspend agency after 3 consecutive red scorecards
                $this->applySuspension($scorecard);
            } elseif ($consecutiveRed >= 2) {
                // Increase fees after 2 consecutive red scorecards
                $this->increaseFees($scorecard);
            } else {
                // First red: Send warning
                $this->sendCriticalWarning($scorecard);
            }
        }
    }

    /**
     * Get count of consecutive red scorecards.
     *
     * @param  int  $agencyId
     * @param  \Carbon\Carbon  $beforeDate
     * @return int
     */
    protected function getConsecutiveRedCount($agencyId, $beforeDate)
    {
        $scorecards = AgencyPerformanceScorecard::where('agency_id', $agencyId)
            ->where('period_end', '<', $beforeDate)
            ->orderBy('period_end', 'desc')
            ->limit(10)
            ->get();

        $consecutiveRed = 0;
        foreach ($scorecards as $scorecard) {
            if ($scorecard->status === 'red') {
                $consecutiveRed++;
            } else {
                break;
            }
        }

        return $consecutiveRed;
    }

    /**
     * Send performance warning notification.
     *
     * @param  AgencyPerformanceScorecard  $scorecard
     * @return void
     */
    protected function sendPerformanceWarning($scorecard)
    {
        $agency = $scorecard->agency;

        if ($agency) {
            // Send performance warning notification based on status
            $performanceNotification = $scorecard->notifications()->latest()->first();
            if ($performanceNotification) {
                if ($scorecard->status === 'yellow') {
                    $agency->notify(new \App\Notifications\Agency\PerformanceYellowWarningNotification(
                        $performanceNotification,
                        $scorecard,
                        $scorecard->action_plan ?? []
                    ));
                } elseif ($scorecard->status === 'red') {
                    $agency->notify(new \App\Notifications\Agency\PerformanceRedAlertNotification(
                        $performanceNotification,
                        $scorecard,
                        $scorecard->action_plan ?? []
                    ));
                }
            }

            Log::info('Performance warning sent', [
                'agency_id' => $scorecard->agency_id,
                'scorecard_id' => $scorecard->id,
                'status' => $scorecard->status,
            ]);
        }
    }

    /**
     * Send critical performance warning.
     *
     * @param  AgencyPerformanceScorecard  $scorecard
     * @return void
     */
    protected function sendCriticalWarning($scorecard)
    {
        $scorecard->applySanction('warning', 'Critical performance issues detected');

        Log::warning('Critical performance warning issued', [
            'agency_id' => $scorecard->agency_id,
            'scorecard_id' => $scorecard->id,
        ]);
    }

    /**
     * Increase agency fees as sanction.
     *
     * @param  AgencyPerformanceScorecard  $scorecard
     * @return void
     */
    protected function increaseFees($scorecard)
    {
        $agency = AgencyProfile::where('user_id', $scorecard->agency_id)->first();

        if ($agency) {
            // Increase commission rate by 2% (capped at 20%)
            $newRate = min(20.00, $agency->commission_rate + 2.00);
            $agency->update(['commission_rate' => $newRate]);

            $scorecard->applySanction(
                'fee_increase',
                sprintf('Commission rate increased to %s%% due to poor performance', $newRate)
            );

            Log::warning('Agency fees increased', [
                'agency_id' => $scorecard->agency_id,
                'new_rate' => $newRate,
            ]);
        }
    }

    /**
     * Suspend agency as final sanction.
     *
     * @param  AgencyPerformanceScorecard  $scorecard
     * @return void
     */
    protected function applySuspension($scorecard)
    {
        $agency = User::find($scorecard->agency_id);

        if ($agency) {
            // Suspend agency (assuming suspension fields exist on users table)
            $agency->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => 'Three consecutive weeks of poor performance',
            ]);

            $scorecard->applySanction(
                'suspension',
                'Agency suspended due to sustained poor performance (3+ consecutive red scorecards)'
            );

            Log::critical('Agency suspended', [
                'agency_id' => $scorecard->agency_id,
                'scorecard_id' => $scorecard->id,
            ]);
        }
    }

    /**
     * Get complaints count from workers assigned through this agency.
     *
     * Counts complaints from multiple sources:
     * - Disputes filed against agency workers
     * - Failed quality audits (complaint-driven)
     * - Low ratings with complaint-type review text
     * - Direct business complaints/reports
     *
     * @param  \Carbon\Carbon|string  $startDate
     * @param  \Carbon\Carbon|string  $endDate
     */
    protected function getComplaintsCount(User $agency, $startDate, $endDate): int
    {
        $complaintsCount = 0;

        // Get all workers managed by this agency
        $agencyWorkerIds = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->pluck('worker_id')
            ->toArray();

        if (empty($agencyWorkerIds)) {
            return 0;
        }

        // 1. Count disputes filed against agency workers
        $disputeComplaints = Dispute::whereIn('worker_id', $agencyWorkerIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', [
                Dispute::STATUS_OPEN,
                Dispute::STATUS_INVESTIGATING,
                Dispute::STATUS_RESOLVED,
            ])
            ->count();
        $complaintsCount += $disputeComplaints;

        // 2. Count failed complaint-driven audits against agency workers
        try {
            $auditComplaints = ShiftAudit::whereHas('shiftAssignment', function ($query) use ($agencyWorkerIds) {
                $query->whereIn('worker_id', $agencyWorkerIds);
            })
                ->where('audit_type', ShiftAudit::TYPE_COMPLAINT)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $complaintsCount += $auditComplaints;
        } catch (\Exception $e) {
            // ShiftAudit model/table may not exist, skip this count
        }

        // 3. Count low ratings (<=2) that likely indicate complaints
        // These are ratings where the business gave a very poor score
        $lowRatingComplaints = Rating::whereIn('rated_id', $agencyWorkerIds)
            ->where('rater_type', 'business')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('rating', '<=', 2)
                    ->orWhere('is_flagged', true);
            })
            ->count();
        $complaintsCount += $lowRatingComplaints;

        // 4. Count shift assignments with issue flags
        $assignmentIssues = ShiftAssignment::whereIn('worker_id', $agencyWorkerIds)
            ->where('agency_id', $agency->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('status', 'no_show')
                    ->orWhere('status', 'cancelled_by_worker')
                    ->orWhere('has_complaint', true)
                    ->orWhere('was_reported', true);
            })
            ->count();
        $complaintsCount += $assignmentIssues;

        return $complaintsCount;
    }
}
