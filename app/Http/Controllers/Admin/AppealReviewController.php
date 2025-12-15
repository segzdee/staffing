<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenaltyAppeal;
use App\Models\WorkerPenalty;
use App\Notifications\AppealApprovedNotification;
use App\Notifications\AppealRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppealReviewController extends Controller
{
    /**
     * Display the admin appeal review dashboard.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $appeals = PenaltyAppeal::with([
            'penalty.shift',
            'penalty.business',
            'worker.workerProfile',
            'reviewedByAdmin'
        ])
            ->when($status === 'pending', function ($query) {
                return $query->pending();
            })
            ->when($status === 'under_review', function ($query) {
                return $query->underReview();
            })
            ->when($status === 'approved', function ($query) {
                return $query->approved();
            })
            ->when($status === 'rejected', function ($query) {
                return $query->rejected();
            })
            ->orderBy('submitted_at', 'asc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'pending' => PenaltyAppeal::pending()->count(),
            'under_review' => PenaltyAppeal::underReview()->count(),
            'approved' => PenaltyAppeal::approved()->count(),
            'rejected' => PenaltyAppeal::rejected()->count(),
        ];

        return view('admin.appeals.index', compact('appeals', 'status', 'stats'));
    }

    /**
     * Display the specified appeal for review.
     */
    public function show($appealId)
    {
        $appeal = PenaltyAppeal::with([
            'penalty.shift',
            'penalty.business.businessProfile',
            'worker.workerProfile',
            'reviewedByAdmin'
        ])->findOrFail($appealId);

        // Get worker's penalty history
        $workerPenaltyHistory = WorkerPenalty::where('worker_id', $appeal->worker_id)
            ->with('shift')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get worker's appeal history
        $workerAppealHistory = PenaltyAppeal::where('worker_id', $appeal->worker_id)
            ->where('id', '!=', $appeal->id)
            ->with('penalty')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.appeals.show', compact(
            'appeal',
            'workerPenaltyHistory',
            'workerAppealHistory'
        ));
    }

    /**
     * Assign appeal to admin for review.
     */
    public function assignToMe($appealId)
    {
        $admin = Auth::user();

        $appeal = PenaltyAppeal::where('status', 'pending')
            ->findOrFail($appealId);

        $appeal->markAsUnderReview($admin->id);

        return redirect()
            ->route('admin.appeals.show', $appeal->id)
            ->with('success', 'Appeal assigned to you for review.');
    }

    /**
     * Show the approval form for the specified appeal.
     */
    public function approveForm($appealId)
    {
        $appeal = PenaltyAppeal::with([
            'penalty.shift',
            'penalty.business',
            'worker'
        ])->findOrFail($appealId);

        if (!in_array($appeal->status, ['pending', 'under_review'])) {
            return redirect()
                ->route('admin.appeals.show', $appeal->id)
                ->with('error', 'This appeal has already been reviewed.');
        }

        return view('admin.appeals.approve', compact('appeal'));
    }

    /**
     * Approve the appeal.
     */
    public function approve(Request $request, $appealId)
    {
        $admin = Auth::user();

        $appeal = PenaltyAppeal::with('penalty')
            ->whereIn('status', ['pending', 'under_review'])
            ->findOrFail($appealId);

        // Validate request
        $validator = Validator::make($request->all(), [
            'decision_reason' => 'required|string|min:20|max:2000',
            'approval_type' => 'required|in:full_waiver,partial_reduction',
            'adjusted_amount' => 'required_if:approval_type,partial_reduction|nullable|numeric|min:0|max:' . $appeal->penalty->penalty_amount,
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request, $appeal, $admin) {
            $adjustedAmount = $request->approval_type === 'full_waiver'
                ? null
                : $request->adjusted_amount;

            // Update admin notes if provided
            if ($request->admin_notes) {
                $appeal->update(['admin_notes' => $request->admin_notes]);
            }

            // Approve the appeal
            $appeal->approve(
                $request->decision_reason,
                $adjustedAmount,
                $admin->id
            );

            // Refresh the appeal to get updated data
            $appeal->refresh();

            // Send notification to worker
            $appeal->worker->notify(new AppealApprovedNotification($appeal));
        });

        return redirect()
            ->route('admin.appeals.show', $appeal->id)
            ->with('success', 'Appeal has been approved successfully.');
    }

    /**
     * Show the rejection form for the specified appeal.
     */
    public function rejectForm($appealId)
    {
        $appeal = PenaltyAppeal::with([
            'penalty.shift',
            'penalty.business',
            'worker'
        ])->findOrFail($appealId);

        if (!in_array($appeal->status, ['pending', 'under_review'])) {
            return redirect()
                ->route('admin.appeals.show', $appeal->id)
                ->with('error', 'This appeal has already been reviewed.');
        }

        return view('admin.appeals.reject', compact('appeal'));
    }

    /**
     * Reject the appeal.
     */
    public function reject(Request $request, $appealId)
    {
        $admin = Auth::user();

        $appeal = PenaltyAppeal::with('penalty')
            ->whereIn('status', ['pending', 'under_review'])
            ->findOrFail($appealId);

        // Validate request
        $validator = Validator::make($request->all(), [
            'decision_reason' => 'required|string|min:20|max:2000',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request, $appeal, $admin) {
            // Update admin notes if provided
            if ($request->admin_notes) {
                $appeal->update(['admin_notes' => $request->admin_notes]);
            }

            // Reject the appeal
            $appeal->reject($request->decision_reason, $admin->id);

            // Refresh the appeal to get updated data
            $appeal->refresh();

            // Send notification to worker with rejection reason
            $appeal->worker->notify(new AppealRejectedNotification($appeal));
        });

        return redirect()
            ->route('admin.appeals.show', $appeal->id)
            ->with('success', 'Appeal has been rejected.');
    }

    /**
     * Add internal admin notes to an appeal.
     */
    public function addNotes(Request $request, $appealId)
    {
        $appeal = PenaltyAppeal::findOrFail($appealId);

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $appeal->update(['admin_notes' => $request->admin_notes]);

        return redirect()
            ->route('admin.appeals.show', $appeal->id)
            ->with('success', 'Internal notes have been added.');
    }

    /**
     * Display penalty management dashboard.
     */
    public function penaltyIndex(Request $request)
    {
        $status = $request->input('status', 'active');
        $type = $request->input('type');

        $penalties = WorkerPenalty::with([
            'worker.workerProfile',
            'shift',
            'business.businessProfile',
            'issuedByAdmin',
            'activeAppeal'
        ])
            ->when($status === 'active', function ($query) {
                return $query->active();
            })
            ->when($status === 'pending', function ($query) {
                return $query->pending();
            })
            ->when($status === 'paid', function ($query) {
                return $query->where('is_paid', true);
            })
            ->when($status === 'appealed', function ($query) {
                return $query->where('status', 'appealed');
            })
            ->when($status === 'overdue', function ($query) {
                return $query->overdue();
            })
            ->when($type, function ($query, $type) {
                return $query->ofType($type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'active' => WorkerPenalty::active()->count(),
            'pending' => WorkerPenalty::pending()->count(),
            'overdue' => WorkerPenalty::overdue()->count(),
            'appealed' => WorkerPenalty::where('status', 'appealed')->count(),
            'total_amount' => WorkerPenalty::unpaid()->sum('penalty_amount'),
        ];

        return view('admin.penalties.index', compact('penalties', 'status', 'type', 'stats'));
    }

    /**
     * Display the specified penalty.
     */
    public function penaltyShow($penaltyId)
    {
        $penalty = WorkerPenalty::with([
            'worker.workerProfile',
            'shift',
            'business.businessProfile',
            'issuedByAdmin',
            'appeals.reviewedByAdmin'
        ])->findOrFail($penaltyId);

        return view('admin.penalties.show', compact('penalty'));
    }

    /**
     * Show the form for creating a new penalty.
     */
    public function penaltyCreate(Request $request)
    {
        $workerId = $request->input('worker_id');
        $shiftId = $request->input('shift_id');

        return view('admin.penalties.create', compact('workerId', 'shiftId'));
    }

    /**
     * Store a newly created penalty.
     */
    public function penaltyStore(Request $request)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:users,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'business_id' => 'nullable|exists:users,id',
            'penalty_type' => 'required|in:no_show,late_cancellation,misconduct,property_damage,policy_violation,other',
            'penalty_amount' => 'required|numeric|min:0|max:10000',
            'reason' => 'required|string|min:20|max:5000',
            'evidence_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $penalty = WorkerPenalty::create([
            'worker_id' => $request->worker_id,
            'shift_id' => $request->shift_id,
            'business_id' => $request->business_id,
            'issued_by_admin_id' => $admin->id,
            'penalty_type' => $request->penalty_type,
            'penalty_amount' => $request->penalty_amount,
            'reason' => $request->reason,
            'evidence_notes' => $request->evidence_notes,
            'status' => 'active',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        // Send notification to worker (implement notification service)
        // NotificationService::notifyWorkerOfPenalty($penalty);

        return redirect()
            ->route('admin.penalties.show', $penalty->id)
            ->with('success', 'Penalty has been issued successfully.');
    }

    /**
     * Waive a penalty (admin discretion).
     */
    public function penaltyWaive(Request $request, $penaltyId)
    {
        $penalty = WorkerPenalty::findOrFail($penaltyId);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $penalty->update(['evidence_notes' => $request->reason]);
        $penalty->waive();

        return redirect()
            ->route('admin.penalties.show', $penalty->id)
            ->with('success', 'Penalty has been waived.');
    }
}
