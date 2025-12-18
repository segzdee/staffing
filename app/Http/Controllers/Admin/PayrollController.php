<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Admin PayrollController - FIN-005: Payroll Processing System
 *
 * Handles all admin payroll operations including creating payroll runs,
 * reviewing and approving payroll, processing payments, viewing history,
 * exporting reports, and managing individual paystubs.
 */
class PayrollController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    /**
     * Display payroll dashboard with list of payroll runs.
     */
    public function index(Request $request)
    {
        $query = PayrollRun::query()
            ->with(['creator', 'approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('period_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('period_end', '<=', $request->date_to);
        }

        // Search by reference
        if ($request->filled('q')) {
            $query->where('reference', 'like', '%'.$request->q.'%');
        }

        $payrollRuns = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get summary statistics
        $stats = [
            'draft' => PayrollRun::draft()->count(),
            'pending_approval' => PayrollRun::pendingApproval()->count(),
            'approved' => PayrollRun::approved()->count(),
            'completed' => PayrollRun::completed()->count(),
            'total_pending_amount' => PayrollRun::whereIn('status', ['draft', 'pending_approval', 'approved'])
                ->sum('net_amount'),
            'total_paid_this_month' => PayrollRun::completed()
                ->whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->sum('net_amount'),
        ];

        return view('admin.payroll.index', compact('payrollRuns', 'stats'));
    }

    /**
     * Show form to create a new payroll run.
     */
    public function create()
    {
        // Calculate suggested period dates based on pay cycle
        $payCycle = config('payroll.default_pay_cycle', 'weekly');

        if ($payCycle === 'weekly') {
            $periodStart = now()->startOfWeek()->subWeek();
            $periodEnd = now()->startOfWeek()->subDay();
        } elseif ($payCycle === 'biweekly') {
            $periodStart = now()->startOfWeek()->subWeeks(2);
            $periodEnd = now()->startOfWeek()->subDay();
        } else {
            $periodStart = now()->startOfMonth()->subMonth();
            $periodEnd = now()->startOfMonth()->subDay();
        }

        $suggestedPayDate = now()->addDays(3); // 3 days from now

        return view('admin.payroll.create', compact('periodStart', 'periodEnd', 'suggestedPayDate', 'payCycle'));
    }

    /**
     * Store a new payroll run.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'pay_date' => 'required|date|after_or_equal:period_end',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payrollRun = $this->payrollService->createPayrollRun(
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            Carbon::parse($validated['pay_date']),
            auth()->user()
        );

        if ($validated['notes'] ?? false) {
            $payrollRun->update(['notes' => $validated['notes']]);
        }

        // Automatically generate items
        $itemCount = $this->payrollService->generatePayrollItems($payrollRun);

        return redirect()
            ->route('admin.payroll.show', $payrollRun)
            ->with('success', "Payroll run created with {$itemCount} items");
    }

    /**
     * Display a specific payroll run.
     */
    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['creator', 'approver']);

        $summary = $this->payrollService->getPayrollSummary($payrollRun);

        // Get items grouped by worker
        $itemsByWorker = $payrollRun->items()
            ->with(['user', 'shift'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return [
                    'worker' => $items->first()->user,
                    'items' => $items,
                    'total_gross' => $items->sum('gross_amount'),
                    'total_deductions' => $items->sum('deductions'),
                    'total_tax' => $items->sum('tax_withheld'),
                    'total_net' => $items->sum('net_amount'),
                ];
            });

        return view('admin.payroll.show', compact('payrollRun', 'summary', 'itemsByWorker'));
    }

    /**
     * Regenerate payroll items.
     */
    public function regenerateItems(PayrollRun $payrollRun)
    {
        if (! $payrollRun->canEdit()) {
            return back()->with('error', 'Cannot regenerate items for this payroll run');
        }

        $itemCount = $this->payrollService->generatePayrollItems($payrollRun);

        return back()->with('success', "Regenerated {$itemCount} payroll items");
    }

    /**
     * Submit payroll run for approval.
     */
    public function submitForApproval(PayrollRun $payrollRun)
    {
        try {
            $this->payrollService->submitForApproval($payrollRun);

            return back()->with('success', 'Payroll submitted for approval');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a payroll run.
     */
    public function approve(PayrollRun $payrollRun)
    {
        try {
            $success = $this->payrollService->approvePayrollRun($payrollRun, auth()->user());

            if ($success) {
                return back()->with('success', 'Payroll approved successfully');
            }

            return back()->with('error', 'Failed to approve payroll');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a payroll run (send back to draft).
     */
    public function reject(Request $request, PayrollRun $payrollRun)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (! $payrollRun->isPendingApproval()) {
            return back()->with('error', 'Can only reject payroll runs pending approval');
        }

        $payrollRun->update([
            'status' => PayrollRun::STATUS_DRAFT,
            'notes' => ($payrollRun->notes ? $payrollRun->notes."\n\n" : '').'Rejection reason: '.$validated['reason'],
        ]);

        return back()->with('success', 'Payroll rejected and returned to draft status');
    }

    /**
     * Show processing page for a payroll run.
     */
    public function process(PayrollRun $payrollRun)
    {
        if (! $payrollRun->canProcess() && ! $payrollRun->isProcessing()) {
            return redirect()
                ->route('admin.payroll.show', $payrollRun)
                ->with('error', 'This payroll run cannot be processed');
        }

        $summary = $this->payrollService->getPayrollSummary($payrollRun);

        return view('admin.payroll.process', compact('payrollRun', 'summary'));
    }

    /**
     * Execute payroll processing.
     */
    public function executeProcess(PayrollRun $payrollRun)
    {
        if (! $payrollRun->canProcess()) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll run cannot be processed',
            ], 400);
        }

        try {
            $results = $this->payrollService->processPayrollRun($payrollRun);

            return response()->json([
                'success' => true,
                'results' => $results,
                'status' => $payrollRun->fresh()->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get processing progress for AJAX updates.
     */
    public function getProgress(PayrollRun $payrollRun)
    {
        $payrollRun->refresh();

        return response()->json([
            'status' => $payrollRun->status,
            'progress' => $payrollRun->getProgressPercentage(),
            'by_status' => [
                'pending' => $payrollRun->items()->where('status', PayrollItem::STATUS_PENDING)->count(),
                'approved' => $payrollRun->items()->where('status', PayrollItem::STATUS_APPROVED)->count(),
                'paid' => $payrollRun->items()->where('status', PayrollItem::STATUS_PAID)->count(),
                'failed' => $payrollRun->items()->where('status', PayrollItem::STATUS_FAILED)->count(),
            ],
        ]);
    }

    /**
     * Export payroll data.
     */
    public function export(Request $request, PayrollRun $payrollRun)
    {
        $format = $request->input('format', 'csv');

        try {
            $content = $this->payrollService->exportPayroll($payrollRun, $format);

            $filename = "payroll-{$payrollRun->reference}.{$format}";
            $contentType = $format === 'csv' ? 'text/csv' : 'application/json';

            return Response::make($content, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Show individual paystub.
     */
    public function paystub(PayrollRun $payrollRun, User $worker)
    {
        $paystub = $this->payrollService->getWorkerPaystub($payrollRun, $worker);

        return view('admin.payroll.paystub', compact('payrollRun', 'worker', 'paystub'));
    }

    /**
     * Add manual item to payroll.
     */
    public function addItem(Request $request, PayrollRun $payrollRun)
    {
        $validated = $request->validate([
            'worker_id' => 'required|exists:users,id',
            'type' => 'required|in:bonus,adjustment,reimbursement',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $worker = User::findOrFail($validated['worker_id']);

            $item = $this->payrollService->addManualItem(
                $payrollRun,
                $worker,
                $validated['type'],
                $validated['description'],
                $validated['amount']
            );

            return back()->with('success', 'Manual item added successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove item from payroll.
     */
    public function removeItem(PayrollRun $payrollRun, PayrollItem $item)
    {
        if ($item->payroll_run_id !== $payrollRun->id) {
            return back()->with('error', 'Item does not belong to this payroll run');
        }

        try {
            $this->payrollService->removeItem($item);

            return back()->with('success', 'Item removed successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft payroll run.
     */
    public function destroy(PayrollRun $payrollRun)
    {
        if (! $payrollRun->isDraft()) {
            return back()->with('error', 'Can only delete draft payroll runs');
        }

        $payrollRun->delete();

        return redirect()
            ->route('admin.payroll.index')
            ->with('success', 'Payroll run deleted');
    }

    /**
     * Retry failed payments in a payroll run.
     */
    public function retryFailed(PayrollRun $payrollRun)
    {
        $failedItems = $payrollRun->items()->where('status', PayrollItem::STATUS_FAILED)->get();

        if ($failedItems->isEmpty()) {
            return back()->with('info', 'No failed items to retry');
        }

        $results = [
            'retried' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        foreach ($failedItems as $item) {
            // Reset to approved status
            $item->update(['status' => PayrollItem::STATUS_APPROVED]);
            $results['retried']++;

            try {
                $success = $this->payrollService->processPayrollItem($item);
                if ($success) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $item->markAsFailed();
                $results['failed']++;
            }
        }

        // Update run status if all items are now paid
        if ($payrollRun->items()->where('status', '!=', PayrollItem::STATUS_PAID)->count() === 0) {
            $payrollRun->markAsCompleted();
        }

        return back()->with('success', "Retried {$results['retried']} items: {$results['successful']} successful, {$results['failed']} failed");
    }
}
