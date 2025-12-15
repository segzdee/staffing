<?php

namespace App\Services;

use App\Models\ComplianceReport;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Models\WorkerProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ComplianceReportService
{
    /**
     * Generate daily financial reconciliation report.
     */
    public function generateDailyReconciliation($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::yesterday();

        $report = ComplianceReport::create([
            'report_type' => 'daily_financial_reconciliation',
            'period_start' => $date->startOfDay(),
            'period_end' => $date->endOfDay(),
            'period_label' => $date->format('F j, Y'),
            'status' => 'generating',
        ]);

        try {
            $startTime = microtime(true);

            // Gather financial data
            $data = $this->collectDailyFinancialData($date);

            // Calculate summary stats
            $summaryStats = [
                'total_payments_processed' => $data['payments']->count(),
                'total_amount_processed' => $data['total_amount'],
                'total_fees_collected' => $data['total_fees'],
                'successful_transactions' => $data['successful_count'],
                'failed_transactions' => $data['failed_count'],
                'refunded_transactions' => $data['refunded_count'],
            ];

            // Update report
            $report->update([
                'status' => 'completed',
                'report_data' => $data,
                'summary_stats' => $summaryStats,
                'generated_at' => now(),
                'generation_time_seconds' => round(microtime(true) - $startTime),
            ]);

            // Generate PDF
            $this->generatePDF($report, 'daily_reconciliation');

            return $report;
        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate monthly VAT summary report.
     */
    public function generateMonthlyVATReport($month = null, $year = null)
    {
        $date = Carbon::create($year, $month, 1) ?? Carbon::now()->subMonth();

        $report = ComplianceReport::create([
            'report_type' => 'monthly_vat_summary',
            'period_start' => $date->startOfMonth(),
            'period_end' => $date->copy()->endOfMonth(),
            'period_label' => $date->format('F Y'),
            'status' => 'generating',
        ]);

        try {
            $startTime = microtime(true);

            // Collect VAT data
            $data = $this->collectMonthlyVATData($date);

            // Calculate summary
            $summaryStats = [
                'total_revenue' => $data['total_revenue'],
                'total_vat_collected' => $data['total_vat'],
                'transactions_count' => $data['transactions_count'],
                'vat_by_rate' => $data['vat_by_rate'],
            ];

            $report->update([
                'status' => 'completed',
                'report_data' => $data,
                'summary_stats' => $summaryStats,
                'generated_at' => now(),
                'generation_time_seconds' => round(microtime(true) - $startTime),
            ]);

            // Generate PDF
            $this->generatePDF($report, 'vat_summary');

            return $report;
        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate quarterly worker classification report.
     */
    public function generateQuarterlyWorkerClassification($quarter, $year)
    {
        $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfMonth();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        $report = ComplianceReport::create([
            'report_type' => 'quarterly_worker_classification',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'period_label' => "Q{$quarter} {$year}",
            'status' => 'generating',
        ]);

        try {
            $startTime = microtime(true);

            // Collect worker classification data
            $data = $this->collectWorkerClassificationData($startDate, $endDate);

            $summaryStats = [
                'total_workers' => $data['total_workers'],
                'active_workers' => $data['active_workers'],
                'workers_by_hours' => $data['workers_by_hours'],
                'potential_reclassification_needed' => $data['potential_reclassification'],
            ];

            $report->update([
                'status' => 'completed',
                'report_data' => $data,
                'summary_stats' => $summaryStats,
                'generated_at' => now(),
                'generation_time_seconds' => round(microtime(true) - $startTime),
            ]);

            // Generate PDF
            $this->generatePDF($report, 'worker_classification');

            return $report;
        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Collect daily financial data.
     */
    protected function collectDailyFinancialData($date)
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $payments = ShiftPayment::with(['shift.businessProfile', 'shift.workerProfile'])
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();

        $totalAmount = $payments->whereIn('status', ['completed', 'paid'])->sum('total_amount');
        $totalFees = $payments->whereIn('status', ['completed', 'paid'])->sum('platform_fee');

        return [
            'date' => $date->format('Y-m-d'),
            'payments' => $payments,
            'total_amount' => $totalAmount,
            'total_amount_dollars' => $totalAmount / 100,
            'total_fees' => $totalFees,
            'total_fees_dollars' => $totalFees / 100,
            'successful_count' => $payments->whereIn('status', ['completed', 'paid'])->count(),
            'failed_count' => $payments->where('status', 'failed')->count(),
            'refunded_count' => $payments->where('status', 'refunded')->count(),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'by_payment_gateway' => $payments->groupBy('payment_gateway')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            }),
        ];
    }

    /**
     * Collect monthly VAT data.
     */
    protected function collectMonthlyVATData($date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $payments = ShiftPayment::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['completed', 'paid'])
            ->get();

        $totalRevenue = $payments->sum('total_amount');
        $totalVAT = $payments->sum('tax_amount');

        // Group by VAT rate
        $vatByRate = $payments->groupBy('tax_rate')->map(function ($group, $rate) {
            return [
                'rate' => $rate,
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount'),
                'vat_collected' => $group->sum('tax_amount'),
            ];
        });

        return [
            'month' => $date->format('F Y'),
            'total_revenue' => $totalRevenue,
            'total_revenue_dollars' => $totalRevenue / 100,
            'total_vat' => $totalVAT,
            'total_vat_dollars' => $totalVAT / 100,
            'transactions_count' => $payments->count(),
            'vat_by_rate' => $vatByRate->values(),
            'daily_breakdown' => $this->getDailyBreakdown($startOfMonth, $endOfMonth),
        ];
    }

    /**
     * Collect worker classification data.
     */
    protected function collectWorkerClassificationData($startDate, $endDate)
    {
        $workers = WorkerProfile::with(['user'])->get();

        $workerData = $workers->map(function ($worker) use ($startDate, $endDate) {
            $shifts = Shift::where('worker_profile_id', $worker->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->whereIn('status', ['completed', 'paid'])
                ->get();

            $totalHours = $shifts->sum(function ($shift) {
                return Carbon::parse($shift->start_time)->diffInHours($shift->end_time);
            });

            $totalEarnings = $shifts->sum('pay_rate');

            // Determine classification risk
            // Workers with >30 hours/week average may need reclassification
            $weeksInPeriod = $startDate->diffInWeeks($endDate);
            $averageWeeklyHours = $weeksInPeriod > 0 ? $totalHours / $weeksInPeriod : 0;
            $needsReview = $averageWeeklyHours > 30;

            return [
                'worker_id' => $worker->id,
                'worker_name' => $worker->user->name,
                'total_hours' => $totalHours,
                'total_shifts' => $shifts->count(),
                'total_earnings' => $totalEarnings,
                'total_earnings_dollars' => $totalEarnings / 100,
                'average_weekly_hours' => round($averageWeeklyHours, 2),
                'needs_classification_review' => $needsReview,
            ];
        });

        $activeWorkers = $workerData->where('total_shifts', '>', 0);
        $needsReview = $workerData->where('needs_classification_review', true);

        return [
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'total_workers' => $workers->count(),
            'active_workers' => $activeWorkers->count(),
            'workers_by_hours' => [
                '0-10' => $activeWorkers->whereBetween('average_weekly_hours', [0, 10])->count(),
                '10-20' => $activeWorkers->whereBetween('average_weekly_hours', [10, 20])->count(),
                '20-30' => $activeWorkers->whereBetween('average_weekly_hours', [20, 30])->count(),
                '30+' => $activeWorkers->where('average_weekly_hours', '>', 30)->count(),
            ],
            'potential_reclassification' => $needsReview->count(),
            'worker_details' => $activeWorkers->values(),
            'workers_needing_review' => $needsReview->values(),
        ];
    }

    /**
     * Get daily breakdown for a period.
     */
    protected function getDailyBreakdown($startDate, $endDate)
    {
        $dailyData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayPayments = ShiftPayment::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['completed', 'paid'])
                ->get();

            $dailyData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'revenue' => $dayPayments->sum('total_amount'),
                'vat' => $dayPayments->sum('tax_amount'),
                'transactions' => $dayPayments->count(),
            ];

            $currentDate->addDay();
        }

        return $dailyData;
    }

    /**
     * Generate PDF for a report.
     */
    protected function generatePDF(ComplianceReport $report, $template)
    {
        $data = [
            'report' => $report,
            'data' => $report->report_data,
            'summary' => $report->summary_stats,
        ];

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView("reports.templates.{$template}", $data);

        // Save to storage
        $filename = sprintf(
            '%s_%s_%s.pdf',
            $report->report_type,
            $report->period_label,
            now()->format('YmdHis')
        );

        $path = "compliance-reports/{$report->id}/{$filename}";
        Storage::put($path, $pdf->output());

        // Update report
        $report->update([
            'file_path' => $path,
            'file_format' => 'pdf',
            'file_size' => Storage::size($path),
        ]);

        return $path;
    }

    /**
     * Export report to CSV.
     */
    public function exportToCSV(ComplianceReport $report)
    {
        $csvData = [];

        switch ($report->report_type) {
            case 'daily_financial_reconciliation':
                $csvData = $this->exportDailyReconciliationCSV($report);
                break;
            case 'monthly_vat_summary':
                $csvData = $this->exportVATSummaryCSV($report);
                break;
            case 'quarterly_worker_classification':
                $csvData = $this->exportWorkerClassificationCSV($report);
                break;
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Save to storage
        $filename = sprintf(
            '%s_%s_%s.csv',
            $report->report_type,
            $report->period_label,
            now()->format('YmdHis')
        );

        $path = "compliance-reports/{$report->id}/{$filename}";
        Storage::put($path, $csv);

        return $path;
    }

    /**
     * Export daily reconciliation to CSV format.
     */
    protected function exportDailyReconciliationCSV(ComplianceReport $report)
    {
        $data = $report->report_data;
        $csvData = [];

        $csvData[] = ['Daily Financial Reconciliation'];
        $csvData[] = ['Date', $data['date']];
        $csvData[] = [];

        $csvData[] = ['Summary'];
        $csvData[] = ['Total Amount', '$' . number_format($data['total_amount_dollars'], 2)];
        $csvData[] = ['Total Fees', '$' . number_format($data['total_fees_dollars'], 2)];
        $csvData[] = ['Successful Transactions', $data['successful_count']];
        $csvData[] = ['Failed Transactions', $data['failed_count']];
        $csvData[] = [];

        $csvData[] = ['Detailed Transactions'];
        $csvData[] = ['Payment ID', 'Shift ID', 'Amount', 'Fee', 'Status', 'Gateway', 'Time'];

        foreach ($data['payments'] as $payment) {
            $csvData[] = [
                $payment['id'],
                $payment['shift_id'],
                '$' . number_format($payment['total_amount'] / 100, 2),
                '$' . number_format($payment['platform_fee'] / 100, 2),
                $payment['status'],
                $payment['payment_gateway'],
                $payment['created_at'],
            ];
        }

        return $csvData;
    }

    /**
     * Export VAT summary to CSV format.
     */
    protected function exportVATSummaryCSV(ComplianceReport $report)
    {
        $data = $report->report_data;
        $csvData = [];

        $csvData[] = ['Monthly VAT Summary'];
        $csvData[] = ['Period', $data['month']];
        $csvData[] = [];

        $csvData[] = ['Summary'];
        $csvData[] = ['Total Revenue', '$' . number_format($data['total_revenue_dollars'], 2)];
        $csvData[] = ['Total VAT', '$' . number_format($data['total_vat_dollars'], 2)];
        $csvData[] = ['Transactions', $data['transactions_count']];
        $csvData[] = [];

        $csvData[] = ['VAT by Rate'];
        $csvData[] = ['Rate', 'Transactions', 'Revenue', 'VAT Collected'];

        foreach ($data['vat_by_rate'] as $rateData) {
            $csvData[] = [
                $rateData['rate'] . '%',
                $rateData['count'],
                '$' . number_format($rateData['revenue'] / 100, 2),
                '$' . number_format($rateData['vat_collected'] / 100, 2),
            ];
        }

        return $csvData;
    }

    /**
     * Export worker classification to CSV format.
     */
    protected function exportWorkerClassificationCSV(ComplianceReport $report)
    {
        $data = $report->report_data;
        $csvData = [];

        $csvData[] = ['Quarterly Worker Classification Report'];
        $csvData[] = ['Period', $data['period']];
        $csvData[] = [];

        $csvData[] = ['Summary'];
        $csvData[] = ['Total Workers', $data['total_workers']];
        $csvData[] = ['Active Workers', $data['active_workers']];
        $csvData[] = ['Needs Review', $data['potential_reclassification']];
        $csvData[] = [];

        $csvData[] = ['Worker Details'];
        $csvData[] = ['ID', 'Name', 'Total Hours', 'Shifts', 'Earnings', 'Avg Weekly Hours', 'Needs Review'];

        foreach ($data['worker_details'] as $worker) {
            $csvData[] = [
                $worker['worker_id'],
                $worker['worker_name'],
                $worker['total_hours'],
                $worker['total_shifts'],
                '$' . number_format($worker['total_earnings_dollars'], 2),
                $worker['average_weekly_hours'],
                $worker['needs_classification_review'] ? 'Yes' : 'No',
            ];
        }

        return $csvData;
    }
}
