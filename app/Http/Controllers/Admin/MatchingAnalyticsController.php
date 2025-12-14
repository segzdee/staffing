<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftApplication;
use App\Models\AvailabilityBroadcast;
use App\Models\ShiftInvitation;
use App\Models\User;
use App\Services\ShiftMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MatchingAnalyticsController extends Controller
{
    protected $matchingService;

    public function __construct(ShiftMatchingService $matchingService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->matchingService = $matchingService;
    }

    /**
     * Display matching algorithm analytics dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('range', '30'); // days
        $startDate = Carbon::now()->subDays($dateRange);

        // Overall Statistics
        $totalShifts = Shift::where('created_at', '>=', $startDate)->count();
        $filledShifts = Shift::where('created_at', '>=', $startDate)
            ->where('status', 'filled')
            ->count();
        $fillRate = $totalShifts > 0 ? round(($filledShifts / $totalShifts) * 100, 2) : 0;

        // Average time to fill
        $avgTimeToFill = Shift::where('created_at', '>=', $startDate)
            ->whereNotNull('filled_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, filled_at)) as avg_minutes')
            ->value('avg_minutes');

        $avgTimeToFillHours = $avgTimeToFill ? round($avgTimeToFill / 60, 1) : 0;

        // Matching Quality Metrics
        $matchScores = $this->calculateAverageMatchScores($startDate);
        $avgMatchScore = $matchScores['average'];
        $highMatchRate = $matchScores['high_match_rate'];

        // Application Statistics
        $totalApplications = ShiftApplication::where('created_at', '>=', $startDate)->count();
        $acceptedApplications = ShiftApplication::where('created_at', '>=', $startDate)
            ->where('status', 'accepted')
            ->count();
        $applicationAcceptanceRate = $totalApplications > 0
            ? round(($acceptedApplications / $totalApplications) * 100, 2)
            : 0;

        // Availability Broadcast Statistics
        $totalBroadcasts = AvailabilityBroadcast::where('created_at', '>=', $startDate)->count();
        $avgResponsesPerBroadcast = AvailabilityBroadcast::where('created_at', '>=', $startDate)
            ->avg('responses_count') ?? 0;

        // Worker Invitation Statistics
        $totalInvitations = ShiftInvitation::where('created_at', '>=', $startDate)->count();
        $acceptedInvitations = ShiftInvitation::where('created_at', '>=', $startDate)
            ->where('status', 'accepted')
            ->count();
        $invitationAcceptanceRate = $totalInvitations > 0
            ? round(($acceptedInvitations / $totalInvitations) * 100, 2)
            : 0;

        // Industry Performance
        $industryPerformance = $this->getIndustryPerformance($startDate);

        // Match Score Distribution
        $matchScoreDistribution = $this->getMatchScoreDistribution($startDate);

        // Time-based Trends
        $dailyFillRates = $this->getDailyFillRates($startDate);
        $applicationTrends = $this->getApplicationTrends($startDate);

        // Top Performing Workers (by match score)
        $topWorkers = $this->getTopMatchingWorkers($startDate);

        // Shifts by urgency level
        $shiftsByUrgency = Shift::where('created_at', '>=', $startDate)
            ->select('urgency_level', DB::raw('COUNT(*) as count'))
            ->groupBy('urgency_level')
            ->get()
            ->pluck('count', 'urgency_level');

        // Broadcast effectiveness
        $broadcastEffectiveness = $this->getBroadcastEffectiveness($startDate);

        return view('admin.matching.analytics', compact(
            'totalShifts',
            'filledShifts',
            'fillRate',
            'avgTimeToFillHours',
            'avgMatchScore',
            'highMatchRate',
            'totalApplications',
            'applicationAcceptanceRate',
            'totalBroadcasts',
            'avgResponsesPerBroadcast',
            'totalInvitations',
            'invitationAcceptanceRate',
            'industryPerformance',
            'matchScoreDistribution',
            'dailyFillRates',
            'applicationTrends',
            'topWorkers',
            'shiftsByUrgency',
            'broadcastEffectiveness',
            'dateRange'
        ));
    }

    /**
     * Calculate average match scores for accepted applications
     */
    protected function calculateAverageMatchScores($startDate)
    {
        $applications = ShiftApplication::with(['shift', 'worker'])
            ->where('created_at', '>=', $startDate)
            ->where('status', 'accepted')
            ->get();

        if ($applications->count() === 0) {
            return ['average' => 0, 'high_match_rate' => 0];
        }

        $totalScore = 0;
        $highMatchCount = 0;
        $validCount = 0;

        foreach ($applications as $application) {
            if ($application->shift && $application->worker) {
                $score = $this->matchingService->calculateWorkerShiftMatch(
                    $application->worker,
                    $application->shift
                );
                $totalScore += $score;
                $validCount++;

                if ($score >= 80) {
                    $highMatchCount++;
                }
            }
        }

        return [
            'average' => $validCount > 0 ? round($totalScore / $validCount, 2) : 0,
            'high_match_rate' => $validCount > 0 ? round(($highMatchCount / $validCount) * 100, 2) : 0,
        ];
    }

    /**
     * Get performance metrics by industry
     */
    protected function getIndustryPerformance($startDate)
    {
        $industries = ['hospitality', 'healthcare', 'retail', 'events', 'warehouse', 'professional'];
        $performance = [];

        foreach ($industries as $industry) {
            $totalShifts = Shift::where('industry', $industry)
                ->where('created_at', '>=', $startDate)
                ->count();

            $filledShifts = Shift::where('industry', $industry)
                ->where('created_at', '>=', $startDate)
                ->where('status', 'filled')
                ->count();

            $avgTimeToFill = Shift::where('industry', $industry)
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('filled_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, filled_at)) as avg_minutes')
                ->value('avg_minutes');

            $performance[$industry] = [
                'total' => $totalShifts,
                'filled' => $filledShifts,
                'fill_rate' => $totalShifts > 0 ? round(($filledShifts / $totalShifts) * 100, 2) : 0,
                'avg_time_hours' => $avgTimeToFill ? round($avgTimeToFill / 60, 1) : 0,
            ];
        }

        return $performance;
    }

    /**
     * Get match score distribution
     */
    protected function getMatchScoreDistribution($startDate)
    {
        // This would ideally be stored in database for better performance
        // For now, calculating on the fly for recent data
        $ranges = [
            '90-100' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            '0-59' => 0,
        ];

        $applications = ShiftApplication::with(['shift', 'worker'])
            ->where('created_at', '>=', $startDate)
            ->where('status', 'accepted')
            ->limit(100) // Sample for performance
            ->get();

        foreach ($applications as $application) {
            if ($application->shift && $application->worker) {
                $score = $this->matchingService->calculateWorkerShiftMatch(
                    $application->worker,
                    $application->shift
                );

                if ($score >= 90) {
                    $ranges['90-100']++;
                } elseif ($score >= 80) {
                    $ranges['80-89']++;
                } elseif ($score >= 70) {
                    $ranges['70-79']++;
                } elseif ($score >= 60) {
                    $ranges['60-69']++;
                } else {
                    $ranges['0-59']++;
                }
            }
        }

        return $ranges;
    }

    /**
     * Get daily fill rates for trend analysis
     */
    protected function getDailyFillRates($startDate)
    {
        $days = [];
        $current = $startDate->copy();

        while ($current <= Carbon::now()) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $total = Shift::whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $filled = Shift::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'filled')
                ->count();

            $days[] = [
                'date' => $current->format('Y-m-d'),
                'total' => $total,
                'filled' => $filled,
                'rate' => $total > 0 ? round(($filled / $total) * 100, 2) : 0,
            ];

            $current->addDay();
        }

        return $days;
    }

    /**
     * Get application trends
     */
    protected function getApplicationTrends($startDate)
    {
        return ShiftApplication::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count,
                         SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Get top matching workers
     */
    protected function getTopMatchingWorkers($startDate)
    {
        return User::where('user_type', 'worker')
            ->withCount([
                'assignments as completed_shifts' => function($query) use ($startDate) {
                    $query->where('status', 'completed')
                          ->where('created_at', '>=', $startDate);
                }
            ])
            ->with('workerProfile')
            ->orderBy('completed_shifts', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get broadcast effectiveness metrics
     */
    protected function getBroadcastEffectiveness($startDate)
    {
        $broadcasts = AvailabilityBroadcast::where('created_at', '>=', $startDate)->get();

        $totalResponses = $broadcasts->sum('responses_count');
        $totalBroadcasts = $broadcasts->count();
        $avgResponseRate = $totalBroadcasts > 0 ? round($totalResponses / $totalBroadcasts, 2) : 0;

        // Calculate conversion rate (responses that led to assignments)
        $responsesLeadingToWork = 0;
        // This would require tracking which invitations came from broadcasts
        // Simplified for now

        return [
            'total_broadcasts' => $totalBroadcasts,
            'total_responses' => $totalResponses,
            'avg_response_rate' => $avgResponseRate,
            'conversion_rate' => 0, // To be implemented
        ];
    }
}
