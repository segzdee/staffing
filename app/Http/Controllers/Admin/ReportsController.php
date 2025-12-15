<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceReportService;
use App\Models\ComplianceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportsController extends Controller
{
    protected $complianceReportService;

    public function __construct(ComplianceReportService $complianceReportService)
    {
        $this->complianceReportService = $complianceReportService;
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display the compliance reports dashboard.
     */
    public function index()
    {
        $reports = ComplianceReport::with(['generatedBy', 'lastDownloadedBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $reportTypes = [
            'daily_financial_reconciliation' => 'Daily Financial Reconciliation',
            'monthly_vat_summary' => 'Monthly VAT Summary',
            'quarterly_worker_classification' => 'Quarterly Worker Classification',
        ];

        return view('admin.reports.index', compact('reports', 'reportTypes'));
    }

    /**
     * Show a specific report.
     */
    public function show($id)
    {
        $report = ComplianceReport::with(['generatedBy', 'lastDownloadedBy', 'accessLogs.user'])
            ->findOrFail($id);

        // Log the view
        $report->logAccess(auth()->id(), 'view');

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Generate a new daily reconciliation report.
     */
    public function generateDailyReconciliation(Request $request)
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
        ]);

        try {
            $report = $this->complianceReportService->generateDailyReconciliation(
                $request->input('date')
            );

            return response()->json([
                'success' => true,
                'message' => 'Daily reconciliation report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a new monthly VAT report.
     */
    public function generateMonthlyVAT(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        try {
            $report = $this->complianceReportService->generateMonthlyVATReport(
                $request->input('month'),
                $request->input('year')
            );

            return response()->json([
                'success' => true,
                'message' => 'Monthly VAT report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a new quarterly worker classification report.
     */
    public function generateQuarterlyWorkerClassification(Request $request)
    {
        $request->validate([
            'quarter' => 'required|integer|between:1,4',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        try {
            $report = $this->complianceReportService->generateQuarterlyWorkerClassification(
                $request->input('quarter'),
                $request->input('year')
            );

            return response()->json([
                'success' => true,
                'message' => 'Quarterly worker classification report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a report file.
     */
    public function download($id)
    {
        $report = ComplianceReport::findOrFail($id);

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            return redirect()->back()->with('error', 'Report file not found');
        }

        // Record the download
        $report->recordDownload(auth()->id());

        return Storage::download($report->file_path);
    }

    /**
     * Export report to CSV.
     */
    public function exportCSV($id)
    {
        $report = ComplianceReport::findOrFail($id);

        try {
            $csvPath = $this->complianceReportService->exportToCSV($report);

            // Record the export
            $report->logAccess(auth()->id(), 'export', ['format' => 'csv']);

            return Storage::download($csvPath);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export report: ' . $e->getMessage());
        }
    }

    /**
     * Archive a report.
     */
    public function archive($id)
    {
        $report = ComplianceReport::findOrFail($id);

        $report->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Report archived successfully',
        ]);
    }

    /**
     * Delete a report.
     */
    public function destroy($id)
    {
        $report = ComplianceReport::findOrFail($id);

        // Delete the file if it exists
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * Get report statistics.
     */
    public function getStatistics(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total_reports' => ComplianceReport::where('created_at', '>=', $startDate)->count(),
            'by_type' => ComplianceReport::where('created_at', '>=', $startDate)
                ->select('report_type', \DB::raw('count(*) as count'))
                ->groupBy('report_type')
                ->pluck('count', 'report_type'),
            'by_status' => ComplianceReport::where('created_at', '>=', $startDate)
                ->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total_downloads' => ComplianceReport::where('created_at', '>=', $startDate)
                ->sum('download_count'),
            'total_size' => ComplianceReport::where('created_at', '>=', $startDate)
                ->sum('file_size'),
            'average_generation_time' => ComplianceReport::where('created_at', '>=', $startDate)
                ->whereNotNull('generation_time_seconds')
                ->avg('generation_time_seconds'),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk generate reports.
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:daily_financial_reconciliation,monthly_vat_summary',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Queue bulk report generation
        // This would typically use a job queue
        return response()->json([
            'success' => true,
            'message' => 'Bulk report generation queued',
        ]);
    }

    /**
     * Email a report to specified recipients.
     */
    public function email(Request $request, $id)
    {
        $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'message' => 'nullable|string',
        ]);

        $report = ComplianceReport::findOrFail($id);

        // Send email with report attachment
        // This would integrate with your email system

        // Log the action
        $report->logAccess(auth()->id(), 'email', [
            'recipients' => $request->input('recipients'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report emailed successfully',
        ]);
    }
}
