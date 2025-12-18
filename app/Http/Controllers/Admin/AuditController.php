<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditChecklist;
use App\Models\MysteryShopper;
use App\Models\Shift;
use App\Models\ShiftAudit;
use App\Models\User;
use App\Services\QualityAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * AuditController
 *
 * Admin controller for quality audit management.
 * Handles shift audits, mystery shopper program, and audit checklists.
 *
 * TASK: QUA-002 Quality Audits
 */
class AuditController extends Controller
{
    /**
     * @var QualityAuditService
     */
    protected $auditService;

    /**
     * Create a new controller instance.
     */
    public function __construct(QualityAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    // =========================================
    // Audits Management
    // =========================================

    /**
     * Display list of audits with filters.
     */
    public function index(Request $request)
    {
        $query = ShiftAudit::with(['shift', 'shiftAssignment.worker', 'auditor'])
            ->orderBy('created_at', 'desc');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('audit_type', $request->type);
        }

        // Passed filter
        if ($request->filled('passed')) {
            $query->where('passed', $request->passed === '1');
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('audit_number', 'like', "%{$search}%")
                    ->orWhereHas('shift', function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shiftAssignment.worker', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $audits = $query->paginate(25)->withQueryString();

        // Statistics
        $stats = $this->auditService->getAuditStatistics(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        return view('admin.audits.index', compact('audits', 'stats'));
    }

    /**
     * Display single audit with full details.
     */
    public function show($id)
    {
        $audit = ShiftAudit::with([
            'shift.business',
            'shift.venue',
            'shiftAssignment.worker',
            'auditor',
        ])->findOrFail($id);

        // Get available auditors for assignment
        $auditors = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.audits.show', compact('audit', 'auditors'));
    }

    /**
     * Show form to create a new audit.
     */
    public function create(Request $request)
    {
        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = Shift::with(['assignments.worker', 'venue'])
                ->findOrFail($request->shift_id);
        }

        $checklists = AuditChecklist::active()->ordered()->get();

        return view('admin.audits.create', compact('shift', 'checklists'));
    }

    /**
     * Create a new audit.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'assignment_id' => 'nullable|exists:shift_assignments,id',
            'audit_type' => 'required|in:random,complaint,scheduled,mystery_shopper',
            'scheduled_at' => 'nullable|date|after:now',
            'auditor_id' => 'nullable|exists:users,id',
        ]);

        $shift = Shift::findOrFail($request->shift_id);

        $audit = $this->auditService->createAudit(
            $shift,
            $request->audit_type,
            $request->assignment_id
        );

        if ($request->filled('scheduled_at')) {
            $audit->update(['scheduled_at' => Carbon::parse($request->scheduled_at)]);
        }

        if ($request->filled('auditor_id')) {
            $this->auditService->assignAuditor($audit, User::find($request->auditor_id));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Audit created successfully.',
                'audit' => $audit,
            ]);
        }

        return redirect()
            ->route('admin.audits.show', $audit)
            ->with('success', 'Audit created successfully.');
    }

    /**
     * Assign an auditor to an audit.
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'auditor_id' => 'required|exists:users,id',
        ]);

        $audit = ShiftAudit::findOrFail($id);

        if ($audit->status === ShiftAudit::STATUS_COMPLETED) {
            return $this->errorResponse('Cannot assign auditor to completed audit.', $request);
        }

        $auditor = User::findOrFail($request->auditor_id);
        $this->auditService->assignAuditor($audit, $auditor);

        return $this->successResponse('Auditor assigned successfully.', $request, $audit);
    }

    /**
     * Submit audit results.
     */
    public function submitResults(Request $request, $id)
    {
        $request->validate([
            'checklist_items' => 'required|array',
            'checklist_items.*.id' => 'required|string',
            'checklist_items.*.passed' => 'required|boolean',
            'checklist_items.*.notes' => 'nullable|string',
            'findings' => 'nullable|string|max:5000',
            'recommendations' => 'nullable|string|max:5000',
            'evidence_urls' => 'nullable|array',
            'evidence_urls.*' => 'url',
        ]);

        $audit = ShiftAudit::findOrFail($id);

        if ($audit->status === ShiftAudit::STATUS_COMPLETED) {
            return $this->errorResponse('Audit is already completed.', $request);
        }

        $this->auditService->submitAuditResults($audit, $request->all());

        return $this->successResponse('Audit results submitted successfully.', $request, $audit->fresh());
    }

    /**
     * Cancel an audit.
     */
    public function cancel(Request $request, $id)
    {
        $audit = ShiftAudit::findOrFail($id);

        if ($audit->status === ShiftAudit::STATUS_COMPLETED) {
            return $this->errorResponse('Cannot cancel completed audit.', $request);
        }

        $audit->cancel();

        return $this->successResponse('Audit cancelled successfully.', $request, $audit);
    }

    /**
     * Flag audit for review.
     */
    public function flag(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $audit = ShiftAudit::findOrFail($id);
        $this->auditService->flagForReview($audit, $request->reason);

        return $this->successResponse('Audit flagged for review.', $request, $audit);
    }

    /**
     * Get shifts available for audit.
     */
    public function getShiftsForAudit(Request $request)
    {
        $method = $request->get('method', 'random');
        $count = min((int) $request->get('count', 10), 50);

        $shifts = $this->auditService->selectShiftsForAudit($count, $method);

        return response()->json([
            'success' => true,
            'shifts' => $shifts->load(['venue', 'business', 'assignments.worker']),
            'method' => $method,
            'count' => $shifts->count(),
        ]);
    }

    /**
     * Schedule random audits (manual trigger).
     */
    public function scheduleRandom(Request $request)
    {
        $scheduled = $this->auditService->scheduleRandomAudits();

        return $this->successResponse("{$scheduled} random audit(s) scheduled.", $request);
    }

    // =========================================
    // Checklists Management
    // =========================================

    /**
     * Display list of audit checklists.
     */
    public function checklists(Request $request)
    {
        $query = AuditChecklist::orderBy('sort_order')->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $checklists = $query->get();
        $categories = AuditChecklist::getAllCategories();

        return view('admin.audits.checklists.index', compact('checklists', 'categories'));
    }

    /**
     * Show form to create a new checklist.
     */
    public function createChecklist()
    {
        $categories = AuditChecklist::getAllCategories();

        return view('admin.audits.checklists.create', compact('categories'));
    }

    /**
     * Store a new checklist.
     */
    public function storeChecklist(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:'.implode(',', array_keys(AuditChecklist::CATEGORIES)),
            'items' => 'required|array|min:1',
            'items.*.question' => 'required|string|max:500',
            'items.*.weight' => 'required|numeric|min:0.1|max:10',
            'items.*.required' => 'boolean',
        ]);

        $items = collect($request->items)->map(function ($item) {
            return [
                'id' => uniqid('item_'),
                'question' => $item['question'],
                'weight' => (float) $item['weight'],
                'required' => $item['required'] ?? false,
            ];
        })->toArray();

        $checklist = AuditChecklist::create([
            'name' => $request->name,
            'category' => $request->category,
            'items' => $items,
            'is_active' => true,
            'sort_order' => AuditChecklist::max('sort_order') + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Checklist created successfully.',
                'checklist' => $checklist,
            ]);
        }

        return redirect()
            ->route('admin.audits.checklists.index')
            ->with('success', 'Checklist created successfully.');
    }

    /**
     * Show form to edit a checklist.
     */
    public function editChecklist($id)
    {
        $checklist = AuditChecklist::findOrFail($id);
        $categories = AuditChecklist::getAllCategories();

        return view('admin.audits.checklists.edit', compact('checklist', 'categories'));
    }

    /**
     * Update a checklist.
     */
    public function updateChecklist(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:'.implode(',', array_keys(AuditChecklist::CATEGORIES)),
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|string',
            'items.*.question' => 'required|string|max:500',
            'items.*.weight' => 'required|numeric|min:0.1|max:10',
            'items.*.required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $checklist = AuditChecklist::findOrFail($id);

        $items = collect($request->items)->map(function ($item) {
            return [
                'id' => $item['id'] ?? uniqid('item_'),
                'question' => $item['question'],
                'weight' => (float) $item['weight'],
                'required' => $item['required'] ?? false,
            ];
        })->toArray();

        $checklist->update([
            'name' => $request->name,
            'category' => $request->category,
            'items' => $items,
            'is_active' => $request->boolean('is_active', $checklist->is_active),
        ]);

        return $this->successResponse('Checklist updated successfully.', $request, $checklist);
    }

    /**
     * Toggle checklist active status.
     */
    public function toggleChecklist(Request $request, $id)
    {
        $checklist = AuditChecklist::findOrFail($id);
        $checklist->update(['is_active' => ! $checklist->is_active]);

        $status = $checklist->is_active ? 'activated' : 'deactivated';

        return $this->successResponse("Checklist {$status}.", $request, $checklist);
    }

    /**
     * Delete a checklist.
     */
    public function deleteChecklist(Request $request, $id)
    {
        $checklist = AuditChecklist::findOrFail($id);
        $checklist->delete();

        return $this->successResponse('Checklist deleted successfully.', $request);
    }

    // =========================================
    // Mystery Shoppers Management
    // =========================================

    /**
     * Display list of mystery shoppers.
     */
    public function mysteryShoppers(Request $request)
    {
        $query = MysteryShopper::with('user')
            ->orderBy('audits_completed', 'desc');

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        if ($request->filled('preferred')) {
            $query->preferred();
        }

        $shoppers = $query->paginate(25)->withQueryString();

        // Statistics
        $stats = [
            'total' => MysteryShopper::count(),
            'active' => MysteryShopper::active()->count(),
            'preferred' => MysteryShopper::preferred()->count(),
            'total_audits' => ShiftAudit::where('audit_type', ShiftAudit::TYPE_MYSTERY_SHOPPER)->completed()->count(),
            'avg_score' => ShiftAudit::where('audit_type', ShiftAudit::TYPE_MYSTERY_SHOPPER)->completed()->avg('overall_score'),
        ];

        return view('admin.audits.mystery-shoppers.index', compact('shoppers', 'stats'));
    }

    /**
     * Show form to add a new mystery shopper.
     */
    public function createMysteryShopper()
    {
        // Get workers who are not already mystery shoppers
        $eligibleUsers = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->whereDoesntHave('mysteryShopper')
            ->orderBy('name')
            ->get();

        return view('admin.audits.mystery-shoppers.create', compact('eligibleUsers'));
    }

    /**
     * Store a new mystery shopper.
     */
    public function storeMysteryShopper(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:mystery_shoppers,user_id',
            'specializations' => 'nullable|array',
            'specializations.*' => 'string',
        ]);

        $shopper = MysteryShopper::create([
            'user_id' => $request->user_id,
            'is_active' => true,
            'specializations' => $request->specializations,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mystery shopper added successfully.',
                'shopper' => $shopper->load('user'),
            ]);
        }

        return redirect()
            ->route('admin.audits.mystery-shoppers.index')
            ->with('success', 'Mystery shopper added successfully.');
    }

    /**
     * Show mystery shopper details.
     */
    public function showMysteryShopper($id)
    {
        $shopper = MysteryShopper::with('user')->findOrFail($id);
        $audits = $shopper->completedAudits()
            ->with(['shift.venue'])
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();
        $stats = $shopper->getAuditStatistics();

        return view('admin.audits.mystery-shoppers.show', compact('shopper', 'audits', 'stats'));
    }

    /**
     * Update mystery shopper.
     */
    public function updateMysteryShopper(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'boolean',
            'specializations' => 'nullable|array',
            'specializations.*' => 'string',
        ]);

        $shopper = MysteryShopper::findOrFail($id);
        $shopper->update([
            'is_active' => $request->boolean('is_active', $shopper->is_active),
            'specializations' => $request->specializations,
        ]);

        return $this->successResponse('Mystery shopper updated successfully.', $request, $shopper);
    }

    /**
     * Toggle mystery shopper active status.
     */
    public function toggleMysteryShopper(Request $request, $id)
    {
        $shopper = MysteryShopper::findOrFail($id);

        if ($shopper->is_active) {
            $shopper->deactivate();
            $status = 'deactivated';
        } else {
            $shopper->activate();
            $status = 'activated';
        }

        return $this->successResponse("Mystery shopper {$status}.", $request, $shopper);
    }

    /**
     * Remove a mystery shopper.
     */
    public function deleteMysteryShopper(Request $request, $id)
    {
        $shopper = MysteryShopper::findOrFail($id);
        $shopper->delete();

        return $this->successResponse('Mystery shopper removed successfully.', $request);
    }

    // =========================================
    // Reports & Analytics
    // =========================================

    /**
     * Display audit reports dashboard.
     */
    public function reports(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $stats = $this->auditService->getAuditStatistics($startDate, $endDate);
        $trends = $this->auditService->getAuditTrends(6);
        $workersWithIssues = $this->auditService->getWorkersWithAuditIssues(10);
        $venuesWithIssues = $this->auditService->getVenuesWithAuditIssues(10);

        return view('admin.audits.reports', compact(
            'stats',
            'trends',
            'workersWithIssues',
            'venuesWithIssues',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get worker audit history.
     */
    public function workerHistory(Request $request, $workerId)
    {
        $worker = User::findOrFail($workerId);
        $audits = $this->auditService->getWorkerAuditHistory($worker);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'worker' => $worker,
                'audits' => $audits,
            ]);
        }

        return view('admin.audits.worker-history', compact('worker', 'audits'));
    }

    /**
     * Get venue audit history.
     */
    public function venueHistory(Request $request, $venueId)
    {
        $venue = \App\Models\Venue::findOrFail($venueId);
        $audits = $this->auditService->getVenueAuditHistory($venue);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'venue' => $venue,
                'audits' => $audits,
            ]);
        }

        return view('admin.audits.venue-history', compact('venue', 'audits'));
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Return success response.
     */
    protected function successResponse(string $message, Request $request, $data = null)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
        }

        return back()->with('success', $message);
    }

    /**
     * Return error response.
     */
    protected function errorResponse(string $message, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['error' => $message]);
    }
}
