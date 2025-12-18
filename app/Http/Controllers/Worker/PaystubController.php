<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Worker PaystubController - FIN-005: Payroll Processing System
 *
 * Handles worker-facing paystub operations including viewing paystub history,
 * viewing individual paystubs, and downloading PDF copies.
 */
class PaystubController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    /**
     * Display list of paystubs for the authenticated worker.
     */
    public function index(Request $request)
    {
        $worker = auth()->user();

        // Get all completed payroll runs that include this worker
        $query = PayrollRun::whereHas('items', function ($q) use ($worker) {
            $q->where('user_id', $worker->id);
        })
            ->where('status', PayrollRun::STATUS_COMPLETED);

        // Filter by year if specified
        if ($request->filled('year')) {
            $query->whereYear('pay_date', $request->year);
        }

        $payrollRuns = $query->orderBy('pay_date', 'desc')->paginate(12);

        // Calculate totals for each payroll run for this worker
        $paystubs = $payrollRuns->through(function ($run) use ($worker) {
            $items = PayrollItem::where('payroll_run_id', $run->id)
                ->where('user_id', $worker->id)
                ->get();

            return [
                'run' => $run,
                'gross' => $items->sum('gross_amount'),
                'deductions' => $items->sum('deductions'),
                'taxes' => $items->sum('tax_withheld'),
                'net' => $items->sum('net_amount'),
                'hours' => $items->sum('hours'),
                'items_count' => $items->count(),
            ];
        });

        // Get available years for filter
        $years = PayrollRun::whereHas('items', function ($q) use ($worker) {
            $q->where('user_id', $worker->id);
        })
            ->where('status', PayrollRun::STATUS_COMPLETED)
            ->selectRaw('YEAR(pay_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Calculate year-to-date totals
        $ytdItems = PayrollItem::whereHas('payrollRun', function ($q) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereYear('pay_date', now()->year);
        })
            ->where('user_id', $worker->id)
            ->get();

        $ytdTotals = [
            'gross' => $ytdItems->sum('gross_amount'),
            'deductions' => $ytdItems->sum('deductions'),
            'taxes' => $ytdItems->sum('tax_withheld'),
            'net' => $ytdItems->sum('net_amount'),
            'hours' => $ytdItems->sum('hours'),
        ];

        return view('worker.paystubs.index', compact('paystubs', 'years', 'ytdTotals'));
    }

    /**
     * Display a specific paystub.
     */
    public function show(PayrollRun $payrollRun)
    {
        $worker = auth()->user();

        // Ensure worker has items in this payroll run
        $hasItems = PayrollItem::where('payroll_run_id', $payrollRun->id)
            ->where('user_id', $worker->id)
            ->exists();

        if (! $hasItems) {
            abort(404, 'Paystub not found');
        }

        // Get paystub data
        $paystub = $this->payrollService->getWorkerPaystub($payrollRun, $worker);

        // Get year-to-date totals up to and including this pay date
        $ytdItems = PayrollItem::whereHas('payrollRun', function ($q) use ($payrollRun) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereYear('pay_date', $payrollRun->pay_date->year)
                ->where('pay_date', '<=', $payrollRun->pay_date);
        })
            ->where('user_id', $worker->id)
            ->get();

        $ytdTotals = [
            'gross' => $ytdItems->sum('gross_amount'),
            'deductions' => $ytdItems->sum('deductions'),
            'taxes' => $ytdItems->sum('tax_withheld'),
            'net' => $ytdItems->sum('net_amount'),
            'hours' => $ytdItems->sum('hours'),
        ];

        return view('worker.paystubs.show', compact('payrollRun', 'paystub', 'ytdTotals'));
    }

    /**
     * Download paystub as PDF.
     */
    public function download(PayrollRun $payrollRun)
    {
        $worker = auth()->user();

        // Ensure worker has items in this payroll run
        $hasItems = PayrollItem::where('payroll_run_id', $payrollRun->id)
            ->where('user_id', $worker->id)
            ->exists();

        if (! $hasItems) {
            abort(404, 'Paystub not found');
        }

        // Get paystub data
        $paystub = $this->payrollService->getWorkerPaystub($payrollRun, $worker);

        // Get year-to-date totals
        $ytdItems = PayrollItem::whereHas('payrollRun', function ($q) use ($payrollRun) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereYear('pay_date', $payrollRun->pay_date->year)
                ->where('pay_date', '<=', $payrollRun->pay_date);
        })
            ->where('user_id', $worker->id)
            ->get();

        $ytdTotals = [
            'gross' => $ytdItems->sum('gross_amount'),
            'deductions' => $ytdItems->sum('deductions'),
            'taxes' => $ytdItems->sum('tax_withheld'),
            'net' => $ytdItems->sum('net_amount'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('worker.paystubs.pdf', [
            'payrollRun' => $payrollRun,
            'paystub' => $paystub,
            'ytdTotals' => $ytdTotals,
            'worker' => $worker,
        ]);

        $filename = "paystub-{$payrollRun->reference}-{$worker->id}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Preview paystub PDF in browser.
     */
    public function preview(PayrollRun $payrollRun)
    {
        $worker = auth()->user();

        // Ensure worker has items in this payroll run
        $hasItems = PayrollItem::where('payroll_run_id', $payrollRun->id)
            ->where('user_id', $worker->id)
            ->exists();

        if (! $hasItems) {
            abort(404, 'Paystub not found');
        }

        // Get paystub data
        $paystub = $this->payrollService->getWorkerPaystub($payrollRun, $worker);

        // Get year-to-date totals
        $ytdItems = PayrollItem::whereHas('payrollRun', function ($q) use ($payrollRun) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereYear('pay_date', $payrollRun->pay_date->year)
                ->where('pay_date', '<=', $payrollRun->pay_date);
        })
            ->where('user_id', $worker->id)
            ->get();

        $ytdTotals = [
            'gross' => $ytdItems->sum('gross_amount'),
            'deductions' => $ytdItems->sum('deductions'),
            'taxes' => $ytdItems->sum('tax_withheld'),
            'net' => $ytdItems->sum('net_amount'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('worker.paystubs.pdf', [
            'payrollRun' => $payrollRun,
            'paystub' => $paystub,
            'ytdTotals' => $ytdTotals,
            'worker' => $worker,
        ]);

        return $pdf->stream();
    }

    /**
     * Get earnings summary for dashboard widget.
     */
    public function summary()
    {
        $worker = auth()->user();

        // Get last 3 paystubs
        $recentPaystubs = $this->payrollService->getWorkerPayrollHistory($worker, 3);

        // Get current month earnings (from completed payroll runs)
        $currentMonthItems = PayrollItem::whereHas('payrollRun', function ($q) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereMonth('pay_date', now()->month)
                ->whereYear('pay_date', now()->year);
        })
            ->where('user_id', $worker->id)
            ->get();

        $currentMonthTotal = $currentMonthItems->sum('net_amount');

        // Get year-to-date earnings
        $ytdItems = PayrollItem::whereHas('payrollRun', function ($q) {
            $q->where('status', PayrollRun::STATUS_COMPLETED)
                ->whereYear('pay_date', now()->year);
        })
            ->where('user_id', $worker->id)
            ->get();

        $ytdTotal = $ytdItems->sum('net_amount');

        return response()->json([
            'recent_paystubs' => $recentPaystubs,
            'current_month_total' => $currentMonthTotal,
            'ytd_total' => $ytdTotal,
        ]);
    }
}
