<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisputeManagementController
 *
 * FIN-010: Admin controller for comprehensive dispute management.
 *
 * Provides admin functionality for viewing, filtering, assigning,
 * mediating, and resolving disputes between workers and businesses.
 */
class DisputeManagementController extends Controller
{
    protected DisputeService $disputeService;

    /**
     * Create a new controller instance.
     */
    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService = $disputeService;
    }

    /**
     * Display list of all disputes with filters.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Dispute::with(['shift', 'worker', 'business', 'assignedAdmin'])
            ->orderBy('created_at', 'desc');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Assignment filter
        if ($request->filled('assigned')) {
            if ($request->assigned === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            }
        }

        // Amount range filter
        if ($request->filled('min_amount')) {
            $query->where('disputed_amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('disputed_amount', '<=', $request->max_amount);
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('worker_description', 'like', "%{$search}%")
                    ->orWhereHas('worker', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('business', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $disputes = $query->paginate(25)->withQueryString();

        // Statistics
        $stats = $this->disputeService->getStatistics();

        // Available admins for assignment
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.disputes.management.index', compact('disputes', 'stats', 'admins'));
    }

    /**
     * Display dispute details with full timeline.
     *
     * @return \Illuminate\View\View
     */
    public function show(int $id)
    {
        $dispute = Dispute::with([
            'shift.assignments' => function ($q) use ($id) {
                $dispute = Dispute::find($id);
                if ($dispute) {
                    $q->where('worker_id', $dispute->worker_id);
                }
            },
            'worker.workerProfile',
            'business.businessProfile',
            'assignedAdmin',
            'timeline.user',
            'adminQueue.escalations',
        ])->findOrFail($id);

        // Get available admins for assignment
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Calculate recommended resolution
        $splitRecommendation = $this->disputeService->calculateResolutionSplit($dispute);

        return view('admin.disputes.management.show', compact('dispute', 'admins', 'splitRecommendation'));
    }

    /**
     * Assign mediator to dispute.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, int $id)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
        ]);

        $dispute = Dispute::findOrFail($id);
        $admin = User::findOrFail($request->admin_id);

        try {
            $this->disputeService->assignMediator($dispute, $admin);

            $message = "Dispute assigned to {$admin->name}";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to assign mediator', [
                'dispute_id' => $id,
                'admin_id' => $request->admin_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Add admin note to dispute.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function addNote(Request $request, int $id)
    {
        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $dispute = Dispute::findOrFail($id);

        try {
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_ADMIN_NOTE,
                auth()->id(),
                $request->note,
                ['is_internal' => true]
            );

            $message = 'Note added successfully';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to add note', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => 'Failed to add note']);
        }
    }

    /**
     * Resolve a dispute.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resolve(ResolveDisputeRequest $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);

        try {
            $this->disputeService->resolveDispute(
                $dispute,
                $request->resolution,
                $request->resolution_amount,
                $request->resolution_notes
            );

            $message = 'Dispute resolved successfully';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()
                ->route('admin.disputes.management.show', $dispute)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to resolve dispute', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Escalate a dispute.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function escalate(Request $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);

        try {
            $this->disputeService->escalateDispute($dispute);

            $message = 'Dispute escalated successfully';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to escalate dispute', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Close a dispute without resolution.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function close(Request $request, int $id)
    {
        $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $dispute = Dispute::findOrFail($id);

        if (! $dispute->isActive()) {
            $message = 'Dispute is already closed or resolved';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['error' => $message]);
        }

        try {
            DB::transaction(function () use ($dispute, $request) {
                $dispute->update([
                    'status' => Dispute::STATUS_CLOSED,
                    'resolution_notes' => $request->notes,
                    'resolved_at' => now(),
                ]);

                $dispute->addTimelineEntry(
                    DisputeTimeline::ACTION_CLOSED,
                    auth()->id(),
                    'Dispute closed by admin: '.$request->notes
                );
            });

            $message = 'Dispute closed successfully';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to close dispute', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => 'Failed to close dispute']);
        }
    }

    /**
     * Extend evidence deadline.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function extendDeadline(Request $request, int $id)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:14',
        ]);

        $dispute = Dispute::findOrFail($id);

        if (! $dispute->isActive()) {
            $message = 'Cannot extend deadline for inactive dispute';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['error' => $message]);
        }

        try {
            $newDeadline = ($dispute->evidence_deadline ?? now())->addDays($request->days);

            $dispute->update([
                'evidence_deadline' => $newDeadline,
            ]);

            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_DEADLINE_EXTENDED,
                auth()->id(),
                "Evidence deadline extended by {$request->days} days to {$newDeadline->format('M d, Y H:i')}",
                ['new_deadline' => $newDeadline->toDateTimeString(), 'extension_days' => $request->days]
            );

            $message = "Deadline extended to {$newDeadline->format('M d, Y H:i')}";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to extend deadline', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => 'Failed to extend deadline']);
        }
    }

    /**
     * Process resolution payment.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);

        if ($dispute->status !== Dispute::STATUS_RESOLVED) {
            $message = 'Can only process payment for resolved disputes';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['error' => $message]);
        }

        if (! $dispute->resolution_amount || $dispute->resolution_amount <= 0) {
            $message = 'No resolution amount to process';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['error' => $message]);
        }

        try {
            // This would integrate with payment processing
            // For now, log and mark as processed
            Log::info('Processing resolution payment', [
                'dispute_id' => $dispute->id,
                'resolution' => $dispute->resolution,
                'amount' => $dispute->resolution_amount,
            ]);

            $dispute->addTimelineEntry(
                'payment_processed',
                auth()->id(),
                "Resolution payment of \${$dispute->resolution_amount} queued for processing",
                ['amount' => $dispute->resolution_amount, 'resolution' => $dispute->resolution]
            );

            $message = 'Payment processing initiated';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to process payment', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => 'Failed to process payment']);
        }
    }

    /**
     * Bulk assign disputes.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'dispute_ids' => 'required|array|min:1',
            'dispute_ids.*' => 'exists:disputes,id',
            'admin_id' => 'required|exists:users,id',
        ]);

        $admin = User::findOrFail($request->admin_id);
        $assigned = 0;

        foreach ($request->dispute_ids as $disputeId) {
            try {
                $dispute = Dispute::find($disputeId);
                if ($dispute && $dispute->isActive()) {
                    $this->disputeService->assignMediator($dispute, $admin);
                    $assigned++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to bulk assign dispute', [
                    'dispute_id' => $disputeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "{$assigned} dispute(s) assigned to {$admin->name}";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $assigned]);
        }

        return back()->with('success', $message);
    }

    /**
     * Export disputes to CSV.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = Dispute::with(['shift', 'worker', 'business', 'assignedAdmin']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $disputes = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="disputes_export_'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($disputes) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID', 'Type', 'Status', 'Worker', 'Business', 'Shift',
                'Disputed Amount', 'Resolution', 'Resolution Amount',
                'Assigned To', 'Created At', 'Resolved At',
            ]);

            foreach ($disputes as $dispute) {
                fputcsv($file, [
                    $dispute->id,
                    $dispute->type_label,
                    $dispute->status_label,
                    $dispute->worker->name ?? 'N/A',
                    $dispute->business->name ?? 'N/A',
                    $dispute->shift->title ?? 'N/A',
                    $dispute->disputed_amount,
                    $dispute->resolution_label ?? 'N/A',
                    $dispute->resolution_amount ?? 'N/A',
                    $dispute->assignedAdmin->name ?? 'Unassigned',
                    $dispute->created_at->format('Y-m-d H:i:s'),
                    $dispute->resolved_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get dispute statistics (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $stats = $this->disputeService->getStatistics();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
