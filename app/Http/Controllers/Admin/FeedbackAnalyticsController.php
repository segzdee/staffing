<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\FeatureRequest;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * QUA-003: Admin Feedback Analytics Controller
 *
 * Provides comprehensive feedback analytics dashboard for administrators.
 */
class FeedbackAnalyticsController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    /**
     * Display main feedback analytics dashboard.
     */
    public function index()
    {
        // Get overall feedback stats
        $stats = $this->feedbackService->getFeedbackStats();

        // Get NPS trend for last 6 months
        $npsTrend = $this->feedbackService->getNPSOverTime('monthly', null, 6);

        // Get top feature requests
        $topFeatureRequests = $this->feedbackService->getTopFeatureRequests(5);

        // Get recent bug reports by severity
        $bugsBySeverity = BugReport::select('severity', DB::raw('count(*) as count'))
            ->open()
            ->groupBy('severity')
            ->get()
            ->keyBy('severity');

        // Get survey response rate over time
        $responseRate = SurveyResponse::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('count(*) as responses')
        )
            ->whereDate('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get feedback by user type
        $feedbackByUserType = DB::table('survey_responses')
            ->join('users', 'survey_responses.user_id', '=', 'users.id')
            ->select('users.user_type', DB::raw('count(*) as count'))
            ->groupBy('users.user_type')
            ->get()
            ->keyBy('user_type');

        return view('admin.feedback.analytics', compact(
            'stats',
            'npsTrend',
            'topFeatureRequests',
            'bugsBySeverity',
            'responseRate',
            'feedbackByUserType'
        ));
    }

    /**
     * Display detailed NPS analytics.
     */
    public function npsDetails(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $surveyId = $request->get('survey_id');

        // Get NPS surveys
        $npsSurveys = Survey::ofType(Survey::TYPE_NPS)->get();

        // Get NPS trend
        $npsTrend = $this->feedbackService->getNPSOverTime($period, $surveyId, 12);

        // Get overall NPS
        $startDate = match ($request->get('range', '30')) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subYear(),
            default => null,
        };

        $overallNps = $this->feedbackService->calculatePlatformNPS($startDate);

        // Get feedback from detractors
        $detractorFeedback = SurveyResponse::query()
            ->detractors()
            ->whereNotNull('feedback_text')
            ->where('feedback_text', '!=', '')
            ->with('user:id,name,user_type')
            ->latest()
            ->limit(20)
            ->get();

        // Get feedback from promoters
        $promoterFeedback = SurveyResponse::query()
            ->promoters()
            ->whereNotNull('feedback_text')
            ->where('feedback_text', '!=', '')
            ->with('user:id,name,user_type')
            ->latest()
            ->limit(20)
            ->get();

        // NPS by user type
        $npsByUserType = DB::table('survey_responses')
            ->join('users', 'survey_responses.user_id', '=', 'users.id')
            ->join('surveys', 'survey_responses.survey_id', '=', 'surveys.id')
            ->where('surveys.type', Survey::TYPE_NPS)
            ->whereNotNull('survey_responses.nps_score')
            ->select(
                'users.user_type',
                DB::raw('count(*) as total'),
                DB::raw('SUM(CASE WHEN survey_responses.nps_score >= 9 THEN 1 ELSE 0 END) as promoters'),
                DB::raw('SUM(CASE WHEN survey_responses.nps_score <= 6 THEN 1 ELSE 0 END) as detractors')
            )
            ->groupBy('users.user_type')
            ->get()
            ->map(function ($item) {
                $promoterPct = $item->total > 0 ? ($item->promoters / $item->total) * 100 : 0;
                $detractorPct = $item->total > 0 ? ($item->detractors / $item->total) * 100 : 0;
                $item->nps_score = round($promoterPct - $detractorPct, 1);

                return $item;
            });

        return view('admin.feedback.nps-details', compact(
            'npsSurveys',
            'npsTrend',
            'overallNps',
            'detractorFeedback',
            'promoterFeedback',
            'npsByUserType'
        ));
    }

    /**
     * Display feature requests management.
     */
    public function featureRequests(Request $request)
    {
        $query = FeatureRequest::query()
            ->with('user:id,name,email,user_type')
            ->withCount('votes');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->withStatus($request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->inCategory($request->category);
        }

        // Sort options
        $sortBy = $request->get('sort', 'popular');
        $query->when($sortBy === 'popular', fn ($q) => $q->popular());
        $query->when($sortBy === 'newest', fn ($q) => $q->latest());
        $query->when($sortBy === 'priority', fn ($q) => $q->prioritized());

        $featureRequests = $query->paginate(20);

        // Get stats by status
        $statusStats = FeatureRequest::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Get stats by category
        $categoryStats = FeatureRequest::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        return view('admin.feedback.feature-requests', compact(
            'featureRequests',
            'statusStats',
            'categoryStats'
        ));
    }

    /**
     * Update feature request status.
     */
    public function updateFeatureRequestStatus(Request $request, $id)
    {
        $featureRequest = FeatureRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:submitted,under_review,planned,in_progress,completed,declined',
            'priority' => 'nullable|integer|min:1',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $featureRequest->update([
            'status' => $request->status,
            'priority' => $request->priority,
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Feature request updated successfully.');
    }

    /**
     * Display bug reports management.
     */
    public function bugReports(Request $request)
    {
        $query = BugReport::query()
            ->with('user:id,name,email,user_type');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->withStatus($request->status);
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->withSeverity($request->severity);
        }

        // Sort options
        $sortBy = $request->get('sort', 'severity');
        $query->when($sortBy === 'severity', fn ($q) => $q->orderBySeverity());
        $query->when($sortBy === 'newest', fn ($q) => $q->latest());
        $query->when($sortBy === 'oldest', fn ($q) => $q->oldest());

        $bugReports = $query->paginate(20);

        // Get stats by status
        $statusStats = BugReport::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Get stats by severity
        $severityStats = BugReport::select('severity', DB::raw('count(*) as count'))
            ->open()
            ->groupBy('severity')
            ->get()
            ->keyBy('severity');

        return view('admin.feedback.bug-reports', compact(
            'bugReports',
            'statusStats',
            'severityStats'
        ));
    }

    /**
     * Update bug report status.
     */
    public function updateBugReportStatus(Request $request, $id)
    {
        $bugReport = BugReport::findOrFail($id);

        $request->validate([
            'status' => 'required|in:reported,confirmed,in_progress,fixed,closed,wont_fix',
            'severity' => 'nullable|in:low,medium,high,critical',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $updateData = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ];

        if ($request->has('severity')) {
            $updateData['severity'] = $request->severity;
        }

        $bugReport->update($updateData);

        return redirect()
            ->back()
            ->with('success', 'Bug report updated successfully.');
    }

    /**
     * View individual bug report.
     */
    public function showBugReport($id)
    {
        $bugReport = BugReport::with('user:id,name,email,user_type')
            ->findOrFail($id);

        return view('admin.feedback.bug-report-details', compact('bugReport'));
    }

    /**
     * Generate feedback report.
     */
    public function generateReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Survey response stats
        $surveyStats = SurveyResponse::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('count(*) as total_responses'),
                DB::raw('AVG(nps_score) as avg_nps_score'),
                DB::raw('SUM(CASE WHEN nps_score >= 9 THEN 1 ELSE 0 END) as promoters'),
                DB::raw('SUM(CASE WHEN nps_score >= 7 AND nps_score <= 8 THEN 1 ELSE 0 END) as passives'),
                DB::raw('SUM(CASE WHEN nps_score <= 6 THEN 1 ELSE 0 END) as detractors')
            )
            ->first();

        // Feature request stats
        $featureStats = [
            'submitted' => FeatureRequest::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => FeatureRequest::whereBetween('updated_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
        ];

        // Bug report stats
        $bugStats = [
            'reported' => BugReport::whereBetween('created_at', [$startDate, $endDate])->count(),
            'fixed' => BugReport::whereBetween('updated_at', [$startDate, $endDate])
                ->whereIn('status', ['fixed', 'closed'])
                ->count(),
            'critical_open' => BugReport::open()->critical()->count(),
        ];

        // Calculate NPS
        $total = ($surveyStats->promoters ?? 0) + ($surveyStats->passives ?? 0) + ($surveyStats->detractors ?? 0);
        $npsScore = $total > 0
            ? round((($surveyStats->promoters / $total) - ($surveyStats->detractors / $total)) * 100, 1)
            : 0;

        return view('admin.feedback.report', compact(
            'startDate',
            'endDate',
            'surveyStats',
            'featureStats',
            'bugStats',
            'npsScore'
        ));
    }
}
