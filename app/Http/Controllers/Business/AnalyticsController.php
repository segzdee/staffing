<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\SpendAnalyticsService;
use App\Services\CancellationPatternService;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnalyticsController extends Controller
{
    protected $spendAnalyticsService;
    protected $cancellationPatternService;

    public function __construct(
        SpendAnalyticsService $spendAnalyticsService,
        CancellationPatternService $cancellationPatternService
    ) {
        $this->spendAnalyticsService = $spendAnalyticsService;
        $this->cancellationPatternService = $cancellationPatternService;
    }

    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return redirect()->route('home')->with('error', 'Business profile not found.');
        }

        $timeRange = $request->get('range', 'month');

        $analytics = $this->spendAnalyticsService->getBusinessAnalytics(
            $businessProfile->id,
            $timeRange
        );

        $cancellationStats = $this->cancellationPatternService->getDashboardStats(
            $businessProfile->id
        );

        return view('business.analytics.index', compact(
            'businessProfile',
            'analytics',
            'cancellationStats',
            'timeRange'
        ));
    }

    /**
     * Get chart data for spend trends (AJAX endpoint).
     */
    public function getTrendData(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return response()->json(['error' => 'Business profile not found'], 404);
        }

        $weeks = $request->get('weeks', 12);
        $trendData = $this->spendAnalyticsService->getTrendAnalysis($businessProfile->id, $weeks);

        // Format for Chart.js
        $chartData = [
            'labels' => collect($trendData)->pluck('week_label')->toArray(),
            'datasets' => [
                [
                    'label' => 'Weekly Spend ($)',
                    'data' => collect($trendData)->pluck('total_spend_dollars')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Shift Count',
                    'data' => collect($trendData)->pluck('shifts_count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'yAxisID' => 'y1',
                ],
            ],
        ];

        return response()->json($chartData);
    }

    /**
     * Get spend by role chart data (AJAX endpoint).
     */
    public function getSpendByRole(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return response()->json(['error' => 'Business profile not found'], 404);
        }

        $timeRange = $request->get('range', 'month');
        $spendByRole = $this->spendAnalyticsService->getSpendByRole($businessProfile->id, $timeRange);

        // Format for Chart.js pie/doughnut chart
        $chartData = [
            'labels' => $spendByRole->pluck('role')->toArray(),
            'datasets' => [
                [
                    'label' => 'Spend by Role',
                    'data' => $spendByRole->pluck('total_spend_dollars')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(236, 72, 153, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
        ];

        return response()->json([
            'chart' => $chartData,
            'table' => $spendByRole,
        ]);
    }

    /**
     * Get venue comparison data (AJAX endpoint).
     */
    public function getVenueComparison(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return response()->json(['error' => 'Business profile not found'], 404);
        }

        $timeRange = $request->get('range', 'month');
        $venueData = $this->spendAnalyticsService->getVenueComparison($businessProfile->id, $timeRange);

        // Format for Chart.js bar chart
        $chartData = [
            'labels' => $venueData->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Current Spend ($)',
                    'data' => $venueData->pluck('current_spend_dollars')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                ],
                [
                    'label' => 'Monthly Budget ($)',
                    'data' => $venueData->pluck('monthly_budget_dollars')->toArray(),
                    'backgroundColor' => 'rgba(209, 213, 219, 0.7)',
                ],
            ],
        ];

        return response()->json([
            'chart' => $chartData,
            'table' => $venueData,
        ]);
    }

    /**
     * Get budget alerts (AJAX endpoint).
     */
    public function getBudgetAlerts(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return response()->json(['error' => 'Business profile not found'], 404);
        }

        $alerts = $this->spendAnalyticsService->getBudgetAlerts($businessProfile);

        return response()->json(['alerts' => $alerts]);
    }

    /**
     * Export analytics to PDF.
     */
    public function exportPDF(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return redirect()->back()->with('error', 'Business profile not found.');
        }

        $timeRange = $request->get('range', 'month');
        $analytics = $this->spendAnalyticsService->getBusinessAnalytics($businessProfile->id, $timeRange);

        $pdf = \PDF::loadView('business.analytics.pdf', compact('businessProfile', 'analytics'));

        $filename = sprintf(
            'analytics_%s_%s.pdf',
            $businessProfile->business_name,
            now()->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Export analytics to CSV.
     */
    public function exportCSV(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return redirect()->back()->with('error', 'Business profile not found.');
        }

        $timeRange = $request->get('range', 'month');
        $csvData = $this->spendAnalyticsService->exportToCSV($businessProfile->id, $timeRange);

        $filename = sprintf(
            'analytics_%s_%s.csv',
            str_replace(' ', '_', $businessProfile->business_name),
            now()->format('Y-m-d')
        );

        $callback = function () use ($csvData) {
            $output = fopen('php://output', 'w');

            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export analytics to Excel.
     */
    public function exportExcel(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return redirect()->back()->with('error', 'Business profile not found.');
        }

        $timeRange = $request->get('range', 'month');
        $csvData = $this->spendAnalyticsService->exportToCSV($businessProfile->id, $timeRange);

        // For Excel export, you would use a package like Laravel Excel
        // For now, we'll return CSV with Excel MIME type
        $filename = sprintf(
            'analytics_%s_%s.xlsx',
            str_replace(' ', '_', $businessProfile->business_name),
            now()->format('Y-m-d')
        );

        $callback = function () use ($csvData) {
            $output = fopen('php://output', 'w');

            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get cancellation history.
     */
    public function getCancellationHistory(Request $request)
    {
        $businessProfile = $request->user()->businessProfile;

        if (!$businessProfile) {
            return response()->json(['error' => 'Business profile not found'], 404);
        }

        $days = $request->get('days', 30);
        $history = $this->cancellationPatternService->getCancellationHistory($businessProfile->id, $days);

        return response()->json(['history' => $history]);
    }
}
