<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\WorkerSuspension;
use App\Services\SuspensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WKR-009: Admin Suspension Management Controller
 *
 * Handles admin operations for worker suspensions and appeals.
 */
class SuspensionManagementController extends Controller
{
    public function __construct(
        protected SuspensionService $suspensionService
    ) {}

    /**
     * Display suspension dashboard with analytics.
     */
    public function index(Request $request): View
    {
        $analytics = $this->suspensionService->getAnalytics();

        // Build query with filters
        $query = WorkerSuspension::with(['worker', 'issuer', 'relatedShift']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('reason_category', $request->category);
        }

        // Filter by worker search
        if ($request->filled('search')) {
            $query->whereHas('worker', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $suspensions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.suspensions.index', [
            'suspensions' => $suspensions,
            'analytics' => $analytics,
            'types' => WorkerSuspension::getTypes(),
            'categories' => WorkerSuspension::getReasonCategories(),
            'statuses' => WorkerSuspension::getStatuses(),
            'filters' => $request->only(['status', 'type', 'category', 'search']),
        ]);
    }

    /**
     * Show form to issue a new suspension.
     */
    public function create(Request $request): View
    {
        $worker = null;
        if ($request->filled('worker_id')) {
            $worker = User::find($request->worker_id);
        }

        $recentShifts = [];
        if ($worker) {
            $recentShifts = $worker->shiftAssignments()
                ->with('shift')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('admin.suspensions.create', [
            'worker' => $worker,
            'recentShifts' => $recentShifts,
            'types' => WorkerSuspension::getTypes(),
            'categories' => WorkerSuspension::getReasonCategories(),
            'durations' => config('suspensions.duration_by_category'),
        ]);
    }

    /**
     * Store a new suspension.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'worker_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:warning,temporary,indefinite,permanent'],
            'reason_category' => ['required', 'in:'.implode(',', array_keys(WorkerSuspension::getReasonCategories()))],
            'reason_details' => ['required', 'string', 'min:20', 'max:5000'],
            'related_shift_id' => ['nullable', 'exists:shifts,id'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:8760'], // Max 1 year
            'affects_booking' => ['boolean'],
            'affects_visibility' => ['boolean'],
        ]);

        $worker = User::findOrFail($validated['worker_id']);
        $admin = $request->user();

        // Verify worker is actually a worker type
        if ($worker->user_type !== 'worker') {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Only worker accounts can be suspended.');
        }

        try {
            $suspension = $this->suspensionService->issueSuspension($worker, [
                'type' => $validated['type'],
                'reason_category' => $validated['reason_category'],
                'reason_details' => $validated['reason_details'],
                'related_shift_id' => $validated['related_shift_id'] ?? null,
                'duration_hours' => $validated['duration_hours'] ?? null,
                'affects_booking' => $validated['affects_booking'] ?? true,
                'affects_visibility' => $validated['affects_visibility'] ?? false,
            ], $admin);

            return redirect()
                ->route('admin.suspensions.show', $suspension)
                ->with('success', 'Suspension has been issued successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to issue suspension: '.$e->getMessage());
        }
    }

    /**
     * Show suspension details.
     */
    public function show(WorkerSuspension $suspension): View
    {
        $suspension->load([
            'worker.workerProfile',
            'issuer',
            'relatedShift',
            'appeals.reviewer',
        ]);

        $workerHistory = $this->suspensionService->getSuspensionHistory($suspension->worker);

        return view('admin.suspensions.show', [
            'suspension' => $suspension,
            'workerHistory' => $workerHistory,
        ]);
    }

    /**
     * Lift a suspension manually.
     */
    public function lift(Request $request, WorkerSuspension $suspension): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($suspension->status !== WorkerSuspension::STATUS_ACTIVE) {
            return redirect()
                ->back()
                ->with('error', 'This suspension is not active and cannot be lifted.');
        }

        try {
            $this->suspensionService->liftSuspension(
                $suspension,
                $validated['notes'],
                $request->user()
            );

            return redirect()
                ->route('admin.suspensions.show', $suspension)
                ->with('success', 'Suspension has been lifted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to lift suspension: '.$e->getMessage());
        }
    }

    /**
     * Display appeals list.
     */
    public function appeals(Request $request): View
    {
        $query = SuspensionAppeal::with(['suspension.worker', 'reviewer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Default to showing pending/under_review first
        if (! $request->filled('status')) {
            $query->orderByRaw("FIELD(status, 'pending', 'under_review', 'approved', 'denied')");
        }

        $appeals = $query->orderBy('created_at', 'desc')->paginate(20);

        $pendingCount = SuspensionAppeal::pending()->count();
        $underReviewCount = SuspensionAppeal::underReview()->count();

        return view('admin.suspensions.appeals', [
            'appeals' => $appeals,
            'pendingCount' => $pendingCount,
            'underReviewCount' => $underReviewCount,
            'statuses' => SuspensionAppeal::getStatuses(),
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Show appeal details for review.
     */
    public function reviewAppeal(SuspensionAppeal $appeal): View
    {
        $appeal->load([
            'suspension.worker.workerProfile',
            'suspension.issuer',
            'suspension.relatedShift',
            'reviewer',
        ]);

        // Get worker's suspension history for context
        $workerHistory = $this->suspensionService->getSuspensionHistory($appeal->suspension->worker, 5);

        return view('admin.suspensions.review-appeal', [
            'appeal' => $appeal,
            'workerHistory' => $workerHistory,
        ]);
    }

    /**
     * Mark appeal as under review.
     */
    public function startReview(Request $request, SuspensionAppeal $appeal): RedirectResponse
    {
        if (! $appeal->isPending()) {
            return redirect()
                ->back()
                ->with('error', 'This appeal is not pending and cannot be marked for review.');
        }

        $appeal->markUnderReview($request->user());

        return redirect()
            ->route('admin.suspensions.appeals.review', $appeal)
            ->with('success', 'Appeal is now under review.');
    }

    /**
     * Submit appeal decision.
     */
    public function decideAppeal(Request $request, SuspensionAppeal $appeal): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,denied'],
            'notes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($appeal->isResolved()) {
            return redirect()
                ->back()
                ->with('error', 'This appeal has already been resolved.');
        }

        try {
            $this->suspensionService->reviewAppeal(
                $appeal,
                $validated['decision'],
                $validated['notes'],
                $request->user()
            );

            $message = $validated['decision'] === 'approved'
                ? 'Appeal approved. Suspension has been overturned.'
                : 'Appeal denied. Suspension remains in effect.';

            return redirect()
                ->route('admin.suspensions.appeals')
                ->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reset strikes for a worker.
     */
    public function resetStrikes(Request $request, User $worker): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $this->suspensionService->resetStrikes($worker, $validated['notes'], $request->user());

        return redirect()
            ->back()
            ->with('success', 'Worker strikes have been reset.');
    }

    /**
     * Search workers for suspension.
     */
    public function searchWorkers(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $workers = User::where('user_type', 'worker')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            })
            ->select(['id', 'name', 'email', 'is_suspended', 'strike_count'])
            ->limit(10)
            ->get();

        return response()->json($workers);
    }

    /**
     * Export suspensions data.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = WorkerSuspension::with(['worker', 'issuer']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category')) {
            $query->where('reason_category', $request->category);
        }

        $suspensions = $query->orderBy('created_at', 'desc')->get();

        $callback = function () use ($suspensions) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID',
                'Worker Name',
                'Worker Email',
                'Type',
                'Reason Category',
                'Reason Details',
                'Status',
                'Starts At',
                'Ends At',
                'Strike Count',
                'Issued By',
                'Created At',
            ]);

            foreach ($suspensions as $suspension) {
                fputcsv($file, [
                    $suspension->id,
                    $suspension->worker->name,
                    $suspension->worker->email,
                    $suspension->type,
                    $suspension->reason_category,
                    $suspension->reason_details,
                    $suspension->status,
                    $suspension->starts_at->toDateTimeString(),
                    $suspension->ends_at?->toDateTimeString() ?? 'Indefinite',
                    $suspension->strike_count,
                    $suspension->issuer->name,
                    $suspension->created_at->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suspensions_export_'.date('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Get suspension analytics as JSON.
     */
    public function analyticsJson(): \Illuminate\Http\JsonResponse
    {
        $analytics = $this->suspensionService->getAnalytics();

        // Add trend data (last 30 days)
        $trendData = WorkerSuspension::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $analytics['trend_data'] = $trendData;

        return response()->json($analytics);
    }
}
