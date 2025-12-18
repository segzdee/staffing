<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxReport;
use App\Models\User;
use App\Services\TaxReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FIN-007: Admin Tax Report Controller
 *
 * Handles bulk tax report generation, management, and 1099 compliance
 */
class TaxReportController extends Controller
{
    public function __construct(
        protected TaxReportingService $taxReportingService
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display the tax reports management dashboard.
     */
    public function index(Request $request)
    {
        $year = $request->query('year', now()->year);
        $status = $request->query('status');
        $reportType = $request->query('type');

        $query = TaxReport::with('user')
            ->where('tax_year', $year);

        if ($status) {
            $query->where('status', $status);
        }

        if ($reportType) {
            $query->where('report_type', $reportType);
        }

        $reports = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        // Get summary statistics
        $statistics = [
            'total_reports' => TaxReport::where('tax_year', $year)->count(),
            'draft' => TaxReport::where('tax_year', $year)->where('status', 'draft')->count(),
            'generated' => TaxReport::where('tax_year', $year)->where('status', 'generated')->count(),
            'sent' => TaxReport::where('tax_year', $year)->where('status', 'sent')->count(),
            'acknowledged' => TaxReport::where('tax_year', $year)->where('status', 'acknowledged')->count(),
            'total_earnings' => TaxReport::where('tax_year', $year)->sum('total_earnings'),
            'total_taxes_withheld' => TaxReport::where('tax_year', $year)->sum('total_taxes_withheld'),
        ];

        // Get workers meeting 1099 threshold without reports
        $workersNeedingReports = $this->getWorkersNeedingReports($year);

        return view('admin.tax.reports.index', [
            'reports' => $reports,
            'statistics' => $statistics,
            'selectedYear' => (int) $year,
            'selectedStatus' => $status,
            'selectedType' => $reportType,
            'availableYears' => range(now()->year, now()->year - 5),
            'statuses' => ['draft', 'generated', 'sent', 'acknowledged'],
            'reportTypes' => [
                '1099_nec' => 'Form 1099-NEC',
                '1099_k' => 'Form 1099-K',
                'p60' => 'P60',
                'payment_summary' => 'Payment Summary',
                'annual_statement' => 'Annual Statement',
            ],
            'workersNeedingReports' => $workersNeedingReports,
        ]);
    }

    /**
     * Display a specific tax report.
     */
    public function show(TaxReport $taxReport)
    {
        $taxReport->load('user.workerProfile');

        return view('admin.tax.reports.show', [
            'report' => $taxReport,
        ]);
    }

    /**
     * Show form for generating bulk reports.
     */
    public function bulkForm(Request $request)
    {
        $year = $request->query('year', now()->year - 1);

        // Get workers eligible for 1099
        $eligibleWorkers = $this->taxReportingService->getWorkersMeeting1099Threshold($year);

        // Get workers who already have reports
        $workersWithReports = TaxReport::where('tax_year', $year)
            ->where('report_type', TaxReport::TYPE_1099_NEC)
            ->pluck('user_id');

        // Filter to workers without reports
        $workersNeedingReports = $eligibleWorkers->whereNotIn('id', $workersWithReports);

        return view('admin.tax.reports.bulk', [
            'year' => (int) $year,
            'availableYears' => range(now()->year - 1, now()->year - 5),
            'eligibleWorkers' => $eligibleWorkers,
            'workersNeedingReports' => $workersNeedingReports,
            'threshold' => TaxReport::US_1099_THRESHOLD,
        ]);
    }

    /**
     * Generate bulk tax reports.
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:'.now()->year],
            'report_type' => ['nullable', 'string', 'in:1099_nec,p60,annual_statement'],
            'worker_ids' => ['nullable', 'array'],
            'worker_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $year = $request->input('year');
        $reportType = $request->input('report_type');
        $workerIds = $request->input('worker_ids');

        Log::info('Starting bulk tax report generation', [
            'year' => $year,
            'report_type' => $reportType,
            'worker_count' => $workerIds ? count($workerIds) : 'all',
            'admin_id' => auth()->id(),
        ]);

        if ($workerIds) {
            // Generate for specific workers
            $results = $this->generateForSpecificWorkers($workerIds, $year, $reportType);
        } else {
            // Generate for all eligible workers
            $results = $this->taxReportingService->generateBulkReports($year, $reportType);
        }

        Log::info('Bulk tax report generation completed', [
            'year' => $year,
            'generated' => $results['generated'],
            'skipped' => $results['skipped'],
            'errors' => count($results['errors']),
        ]);

        $message = sprintf(
            'Generated %d reports, skipped %d workers.',
            $results['generated'],
            $results['skipped']
        );

        if (count($results['errors']) > 0) {
            $message .= sprintf(' %d errors occurred.', count($results['errors']));
        }

        return redirect()
            ->route('admin.tax.reports.index', ['year' => $year])
            ->with('success', $message)
            ->with('generation_results', $results);
    }

    /**
     * Send tax reports to workers.
     */
    public function bulkSend(Request $request)
    {
        $request->validate([
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['integer', 'exists:tax_reports,id'],
        ]);

        $reportIds = $request->input('report_ids');
        $sent = 0;
        $errors = [];

        foreach ($reportIds as $reportId) {
            try {
                $report = TaxReport::find($reportId);

                if ($report && $report->status === TaxReport::STATUS_GENERATED) {
                    $this->taxReportingService->emailTaxReport($report);
                    $sent++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'report_id' => $reportId,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to send tax report {$reportId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = sprintf('Sent %d reports.', $sent);
        if (count($errors) > 0) {
            $message .= sprintf(' %d failed.', count($errors));
        }

        return back()->with('success', $message);
    }

    /**
     * Regenerate a specific report.
     */
    public function regenerate(TaxReport $taxReport)
    {
        try {
            $user = $taxReport->user;
            $year = $taxReport->tax_year;
            $type = $taxReport->report_type;

            // Delete old report
            $taxReport->delete();

            // Generate new report
            $newReport = match ($type) {
                TaxReport::TYPE_1099_NEC => $this->taxReportingService->generate1099NEC($user, $year),
                TaxReport::TYPE_P60 => $this->taxReportingService->generateP60($user, $year),
                default => $this->taxReportingService->generateAnnualReport($user, $year),
            };

            Log::info('Tax report regenerated', [
                'old_report_id' => $taxReport->id,
                'new_report_id' => $newReport->id,
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.tax.reports.show', $newReport)
                ->with('success', 'Tax report has been regenerated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to regenerate report: '.$e->getMessage());
        }
    }

    /**
     * Download a tax report.
     */
    public function download(TaxReport $taxReport)
    {
        $documentUrl = $taxReport->document_url;

        if (! $documentUrl || ! \Storage::disk('local')->exists($documentUrl)) {
            // Generate PDF on the fly
            $documentUrl = $this->taxReportingService->generatePDFReport($taxReport);
        }

        $filename = sprintf(
            '%s_%s_%d.pdf',
            $taxReport->report_type,
            $taxReport->user->name,
            $taxReport->tax_year
        );

        return \Storage::disk('local')->download($documentUrl, $filename);
    }

    /**
     * Export reports list to CSV.
     */
    public function exportCsv(Request $request)
    {
        $year = $request->query('year', now()->year);

        $reports = TaxReport::with('user')
            ->where('tax_year', $year)
            ->orderBy('user_id')
            ->get();

        $filename = sprintf('tax_reports_%d_%s.csv', $year, now()->format('Ymd'));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Report ID',
                'User ID',
                'Worker Name',
                'Worker Email',
                'Tax Year',
                'Report Type',
                'Total Earnings',
                'Total Fees',
                'Taxes Withheld',
                'Total Shifts',
                'Status',
                'Generated At',
                'Sent At',
            ]);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->user_id,
                    $report->user->name ?? 'N/A',
                    $report->user->email ?? 'N/A',
                    $report->tax_year,
                    $report->report_type,
                    $report->total_earnings,
                    $report->total_fees,
                    $report->total_taxes_withheld,
                    $report->total_shifts,
                    $report->status,
                    $report->generated_at?->format('Y-m-d H:i:s'),
                    $report->sent_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display 1099 compliance report.
     */
    public function complianceReport(Request $request)
    {
        $year = $request->query('year', now()->year - 1);

        // Get all workers who should receive 1099s
        $eligibleWorkers = $this->taxReportingService->getWorkersMeeting1099Threshold($year);

        // Get reports that have been generated
        $reportedWorkers = TaxReport::where('tax_year', $year)
            ->where('report_type', TaxReport::TYPE_1099_NEC)
            ->with('user')
            ->get()
            ->keyBy('user_id');

        // Build compliance data
        $complianceData = $eligibleWorkers->map(function ($worker) use ($reportedWorkers, $year) {
            $report = $reportedWorkers->get($worker->id);
            $earnings = $this->taxReportingService->calculateYearlyEarnings($worker, $year);

            return [
                'worker' => $worker,
                'earnings' => $earnings['total_gross'],
                'has_report' => $report !== null,
                'report' => $report,
                'report_status' => $report?->status ?? 'not_generated',
                'is_compliant' => $report && in_array($report->status, ['sent', 'acknowledged']),
            ];
        });

        $summary = [
            'total_eligible' => $complianceData->count(),
            'reports_generated' => $complianceData->where('has_report', true)->count(),
            'reports_sent' => $complianceData->where('report_status', 'sent')->count(),
            'reports_acknowledged' => $complianceData->where('report_status', 'acknowledged')->count(),
            'missing_reports' => $complianceData->where('has_report', false)->count(),
            'total_reportable_earnings' => $complianceData->sum('earnings'),
        ];

        return view('admin.tax.reports.compliance', [
            'year' => (int) $year,
            'complianceData' => $complianceData,
            'summary' => $summary,
            'threshold' => TaxReport::US_1099_THRESHOLD,
            'availableYears' => range(now()->year - 1, now()->year - 5),
        ]);
    }

    /**
     * Get workers who need reports generated.
     */
    protected function getWorkersNeedingReports(int $year): \Illuminate\Support\Collection
    {
        $eligibleWorkers = $this->taxReportingService->getWorkersMeeting1099Threshold($year);

        $workersWithReports = TaxReport::where('tax_year', $year)
            ->pluck('user_id');

        return $eligibleWorkers->whereNotIn('id', $workersWithReports);
    }

    /**
     * Generate reports for specific workers.
     */
    protected function generateForSpecificWorkers(array $workerIds, int $year, ?string $reportType): array
    {
        $results = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($workerIds as $workerId) {
            try {
                $user = User::find($workerId);
                if (! $user) {
                    continue;
                }

                $type = $reportType ?? TaxReport::getPrimaryReportTypeForCountry(
                    $user->workerProfile?->country_code ?? 'US'
                );

                $report = match ($type) {
                    TaxReport::TYPE_1099_NEC => $this->taxReportingService->generate1099NEC($user, $year),
                    TaxReport::TYPE_P60 => $this->taxReportingService->generateP60($user, $year),
                    default => $this->taxReportingService->generateAnnualReport($user, $year),
                };

                $results['generated']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $workerId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
