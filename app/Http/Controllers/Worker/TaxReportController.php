<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\TaxReport;
use App\Services\TaxReportingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * FIN-007: Tax Report Controller for Workers
 *
 * Handles viewing and downloading tax reports (1099-NEC, P60, Annual Statements)
 */
class TaxReportController extends Controller
{
    public function __construct(
        protected TaxReportingService $taxReportingService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display tax reports dashboard for the worker.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $year = $request->query('year', now()->year);

        // Get all reports for this user
        $reports = TaxReport::where('user_id', $user->id)
            ->orderBy('tax_year', 'desc')
            ->orderBy('report_type')
            ->get();

        // Get available years from reports
        $availableYears = $reports->pluck('tax_year')->unique()->sort()->reverse()->values();

        // If no reports yet, add current year
        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        // Filter reports by selected year
        $yearReports = $reports->where('tax_year', $year);

        // Get earnings summary for the year
        $earnings = $this->taxReportingService->calculateYearlyEarnings($user, (int) $year);

        // Check 1099 threshold
        $meets1099Threshold = $earnings['total_gross'] >= TaxReport::US_1099_THRESHOLD;

        return view('worker.tax.reports.index', [
            'reports' => $yearReports,
            'allReports' => $reports,
            'selectedYear' => (int) $year,
            'availableYears' => $availableYears,
            'earnings' => $earnings,
            'meets1099Threshold' => $meets1099Threshold,
            'threshold1099' => TaxReport::US_1099_THRESHOLD,
        ]);
    }

    /**
     * Display a specific tax report.
     */
    public function show(TaxReport $taxReport)
    {
        // Ensure user can only view their own reports
        if ($taxReport->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this report.');
        }

        return view('worker.tax.reports.show', [
            'report' => $taxReport,
        ]);
    }

    /**
     * Download a tax report PDF.
     */
    public function download(TaxReport $taxReport)
    {
        // Ensure user can only download their own reports
        if ($taxReport->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to download this report.');
        }

        // If document URL exists, try to download from storage
        if ($taxReport->document_url && Storage::disk('local')->exists($taxReport->document_url)) {
            return Storage::disk('local')->download(
                $taxReport->document_url,
                $this->getReportFilename($taxReport)
            );
        }

        // Generate PDF on the fly
        $pdf = $this->generateReportPdf($taxReport);

        return $pdf->download($this->getReportFilename($taxReport));
    }

    /**
     * Preview a tax report in browser.
     */
    public function preview(TaxReport $taxReport)
    {
        // Ensure user can only preview their own reports
        if ($taxReport->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this report.');
        }

        // Generate PDF
        $pdf = $this->generateReportPdf($taxReport);

        return $pdf->stream($this->getReportFilename($taxReport));
    }

    /**
     * Request a new tax report for a specific year.
     */
    public function request(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:'.now()->year],
            'report_type' => ['nullable', 'string', 'in:1099_nec,1099_k,p60,payment_summary,annual_statement'],
        ]);

        $user = auth()->user();
        $year = $request->input('year');
        $reportType = $request->input('report_type');

        try {
            // Generate the appropriate report
            $report = match ($reportType) {
                '1099_nec' => $this->taxReportingService->generate1099NEC($user, $year),
                'p60' => $this->taxReportingService->generateP60($user, $year),
                default => $this->taxReportingService->generateAnnualReport($user, $year),
            };

            return redirect()
                ->route('worker.tax-reports.show', $report)
                ->with('success', 'Your tax report has been generated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to generate tax report: '.$e->getMessage());
        }
    }

    /**
     * Acknowledge receipt of a tax report.
     */
    public function acknowledge(TaxReport $taxReport)
    {
        // Ensure user can only acknowledge their own reports
        if ($taxReport->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to acknowledge this report.');
        }

        $taxReport->markAsAcknowledged();

        return back()->with('success', 'Thank you for acknowledging receipt of your tax report.');
    }

    /**
     * Get earnings summary for AJAX requests.
     */
    public function earningsSummary(Request $request)
    {
        $year = $request->query('year', now()->year);
        $user = auth()->user();

        $earnings = $this->taxReportingService->calculateYearlyEarnings($user, (int) $year);
        $withholdings = $this->taxReportingService->getWithholdingSummary($user, (int) $year);

        return response()->json([
            'earnings' => $earnings,
            'withholdings' => $withholdings,
            'meets_1099_threshold' => $earnings['total_gross'] >= TaxReport::US_1099_THRESHOLD,
        ]);
    }

    /**
     * Generate PDF for a report.
     */
    protected function generateReportPdf(TaxReport $taxReport): \Barryvdh\DomPDF\PDF
    {
        $user = $taxReport->user;

        // Get payer and recipient information
        $payerInfo = [
            'name' => config('app.name', 'OvertimeStaff'),
            'address' => config('overtimestaff.company.address', ''),
            'city' => config('overtimestaff.company.city', ''),
            'state' => config('overtimestaff.company.state', ''),
            'zip' => config('overtimestaff.company.zip', ''),
            'country' => config('overtimestaff.company.country', 'US'),
            'ein' => config('overtimestaff.company.ein', ''),
            'phone' => config('overtimestaff.company.phone', ''),
        ];

        $profile = $user->workerProfile;
        $recipientInfo = [
            'name' => $user->name,
            'address' => $profile?->address ?? '',
            'city' => $profile?->city ?? '',
            'state' => $profile?->state ?? $profile?->state_code ?? '',
            'zip' => $profile?->postal_code ?? $profile?->zip ?? '',
            'country' => $profile?->country_code ?? $profile?->country ?? 'US',
            'ssn_last4' => $profile?->ssn_last4 ?? '****',
            'tin' => $profile?->tax_id ?? '',
        ];

        // Determine template based on report type
        $template = match ($taxReport->report_type) {
            TaxReport::TYPE_1099_NEC => 'tax.1099-nec',
            TaxReport::TYPE_P60 => 'tax.p60',
            default => 'tax.annual-statement',
        };

        return Pdf::loadView($template, [
            'report' => $taxReport,
            'user' => $user,
            'payer' => $payerInfo,
            'recipient' => $recipientInfo,
            'year' => $taxReport->tax_year,
            'generatedAt' => now(),
        ]);
    }

    /**
     * Get filename for report download.
     */
    protected function getReportFilename(TaxReport $taxReport): string
    {
        $typeMap = [
            TaxReport::TYPE_1099_NEC => '1099-NEC',
            TaxReport::TYPE_1099_K => '1099-K',
            TaxReport::TYPE_P60 => 'P60',
            TaxReport::TYPE_PAYMENT_SUMMARY => 'Payment-Summary',
            TaxReport::TYPE_ANNUAL_STATEMENT => 'Annual-Statement',
        ];

        $type = $typeMap[$taxReport->report_type] ?? 'Tax-Report';

        return sprintf('%s_%d_%s.pdf', $type, $taxReport->tax_year, now()->format('Ymd'));
    }
}
