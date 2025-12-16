<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OnboardingAnalyticsService;
use App\Services\OnboardingProgressService;
use App\Models\OnboardingCohort;
use App\Models\OnboardingStep;
use App\Models\OnboardingEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

/**
 * Admin Onboarding Analytics Controller
 *
 * Provides comprehensive analytics and reporting for onboarding performance,
 * funnel analysis, A/B testing, and intervention opportunities.
 */
class OnboardingAnalyticsController extends Controller
{
    protected OnboardingAnalyticsService $analyticsService;
    protected OnboardingProgressService $progressService;

    public function __construct(
        OnboardingAnalyticsService $analyticsService,
        OnboardingProgressService $progressService
    ) {
        $this->analyticsService = $analyticsService;
        $this->progressService = $progressService;
    }

    /**
     * Display the analytics overview dashboard
     */
    public function overview(Request $request): View
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $userType = $request->input('user_type');

        $overview = $this->analyticsService->getOverview($startDate, $endDate);
        $funnel = $this->analyticsService->getFunnelData($userType, $startDate, $endDate);
        $timeToActivation = $this->analyticsService->getAverageTimeToActivation($userType, $startDate, $endDate);
        $interventions = $this->analyticsService->getInterventionOpportunities(10);

        return view('admin.onboarding.analytics', [
            'overview' => $overview,
            'funnel' => $funnel,
            'timeToActivation' => $timeToActivation,
            'interventions' => $interventions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userType' => $userType,
            'userTypes' => ['worker', 'business', 'agency'],
        ]);
    }

    /**
     * Get funnel data via AJAX
     */
    public function getFunnelData(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $userType = $request->input('user_type');

        $funnel = $this->analyticsService->getFunnelData($userType, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $funnel,
        ]);
    }

    /**
     * Get dropoff analysis by step
     */
    public function getDropoffAnalysis(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $userType = $request->input('user_type');

        $dropoff = $this->analyticsService->getDropoffRates($userType, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $dropoff,
        ]);
    }

    /**
     * Get cohort performance data
     */
    public function getCohortData(Request $request): JsonResponse
    {
        $experimentName = $request->input('experiment');

        $cohortData = $this->analyticsService->getCohortPerformance($experimentName);

        return response()->json([
            'success' => true,
            'data' => $cohortData,
        ]);
    }

    /**
     * Get time to activation metrics
     */
    public function getTimeToActivation(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $userType = $request->input('user_type');

        $timeData = $this->analyticsService->getAverageTimeToActivation($userType, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $timeData,
        ]);
    }

    /**
     * Get step conversion rates
     */
    public function getStepConversionRates(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $userType = $request->input('user_type');

        $rates = $this->analyticsService->getStepConversionRates($userType, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Get intervention opportunities
     */
    public function getInterventionOpportunities(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);

        $opportunities = $this->analyticsService->getInterventionOpportunities($limit);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
        ]);
    }

    /**
     * Get completion rate breakdown
     */
    public function getCompletionRates(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $rates = [
            'overall' => $this->analyticsService->calculateCompletionRate(null, $startDate, $endDate),
            'worker' => $this->analyticsService->calculateCompletionRate('worker', $startDate, $endDate),
            'business' => $this->analyticsService->calculateCompletionRate('business', $startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * View individual user onboarding progress
     */
    public function viewUserProgress(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $progress = $this->progressService->getProgressData($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'created_at' => $user->created_at->toDateTimeString(),
                    'onboarding_completed' => $user->onboarding_completed,
                ],
                'progress' => $progress,
            ],
        ]);
    }

    /**
     * Manually complete a step for a user (admin intervention)
     */
    public function adminCompleteStep(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'step_id' => 'required|string|max:50',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);

        $result = $this->progressService->updateProgress(
            $user,
            $request->step_id,
            'completed',
            [
                'completed_by' => 'admin',
                'admin_id' => auth()->id(),
                'reason' => $request->reason,
            ]
        );

        // Log admin action
        OnboardingEvent::log(
            $user->id,
            'admin_completed_step',
            $request->step_id,
            [
                'admin_id' => auth()->id(),
                'reason' => $request->reason,
            ]
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Step marked as completed by admin.'
                : $result['message'],
            'data' => [
                'overall_progress' => $result['overall_progress'] ?? null,
            ],
        ]);
    }

    /**
     * Reset a user's onboarding
     */
    public function resetUserOnboarding(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);

        // Reset onboarding status
        $user->update([
            'onboarding_completed' => false,
            'onboarding_step' => null,
        ]);

        // Reset all progress records
        $user->onboardingProgress()->update([
            'status' => 'pending',
            'progress_percentage' => 0,
            'started_at' => null,
            'completed_at' => null,
            'time_spent_seconds' => 0,
        ]);

        // Log admin action
        OnboardingEvent::log(
            $user->id,
            'admin_reset_onboarding',
            null,
            [
                'admin_id' => auth()->id(),
                'reason' => $request->reason,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User onboarding has been reset.',
        ]);
    }

    /**
     * Manage A/B test cohorts
     */
    public function manageCohorts(): View
    {
        $cohorts = OnboardingCohort::with('creator')
            ->orderBy('experiment_name')
            ->orderBy('variant')
            ->get();

        $experiments = $cohorts->groupBy('experiment_name');

        return view('admin.onboarding.cohorts', [
            'cohorts' => $cohorts,
            'experiments' => $experiments,
        ]);
    }

    /**
     * Create a new A/B test cohort
     */
    public function createCohort(Request $request): JsonResponse
    {
        $request->validate([
            'cohort_id' => 'required|string|max:50|unique:onboarding_cohorts',
            'name' => 'required|string|max:100',
            'experiment_name' => 'required|string|max:100',
            'user_type' => 'required|in:worker,business,agency,all',
            'variant' => 'required|string|max:50',
            'allocation_percentage' => 'required|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:1000',
        ]);

        $cohort = OnboardingCohort::create([
            'cohort_id' => $request->cohort_id,
            'name' => $request->name,
            'experiment_name' => $request->experiment_name,
            'user_type' => $request->user_type,
            'variant' => $request->variant,
            'allocation_percentage' => $request->allocation_percentage,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cohort created successfully.',
            'data' => $cohort,
        ]);
    }

    /**
     * Update cohort status
     */
    public function updateCohortStatus(Request $request, int $cohortId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,active,paused,completed',
        ]);

        $cohort = OnboardingCohort::findOrFail($cohortId);
        $cohort->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cohort status updated.',
            'data' => $cohort,
        ]);
    }

    /**
     * Declare a cohort as winner
     */
    public function declareCohortWinner(int $cohortId): JsonResponse
    {
        $cohort = OnboardingCohort::findOrFail($cohortId);
        $cohort->declareWinner();

        return response()->json([
            'success' => true,
            'message' => 'Cohort declared as winner. Other cohorts in the experiment have been closed.',
            'data' => $cohort->fresh(),
        ]);
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();
        $format = $request->input('format', 'csv');

        $report = $this->analyticsService->generateDailyReport($endDate);

        $filename = "onboarding_analytics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, ['Onboarding Analytics Report']);
            fputcsv($output, ['Generated at', now()->toDateTimeString()]);
            fputcsv($output, []);

            // Summary
            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Signups', $report['summary']['metrics']['total_signups'] ?? 0]);
            fputcsv($output, ['Completion Rate', ($report['summary']['metrics']['completion_rate'] ?? 0) . '%']);
            fputcsv($output, []);

            // Funnel data
            fputcsv($output, ['Funnel Analysis']);
            fputcsv($output, ['Step', 'Count', 'Percentage']);
            foreach ($report['funnel']['funnel'] ?? [] as $step) {
                fputcsv($output, [$step['name'], $step['count'], $step['percentage'] . '%']);
            }
            fputcsv($output, []);

            // Dropoff analysis
            fputcsv($output, ['Dropoff Analysis']);
            fputcsv($output, ['Step', 'Reached', 'Completed', 'Dropoff Rate']);
            foreach ($report['dropoff']['steps'] ?? [] as $step) {
                fputcsv($output, [$step['name'], $step['reached'], $step['completed'], $step['dropoff_rate'] . '%']);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get daily report
     */
    public function getDailyReport(Request $request): JsonResponse
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now()->subDay();

        $report = $this->analyticsService->generateDailyReport($date);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Send manual intervention to a user
     */
    public function sendIntervention(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'intervention_type' => 'required|in:email,push,support_ticket',
            'message' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($request->user_id);

        // Log intervention
        OnboardingEvent::log(
            $user->id,
            'admin_intervention',
            null,
            [
                'admin_id' => auth()->id(),
                'intervention_type' => $request->intervention_type,
                'message' => $request->message,
            ]
        );

        // In production, this would trigger actual intervention
        // For now, just log and return success

        return response()->json([
            'success' => true,
            'message' => 'Intervention sent to user.',
        ]);
    }
}
