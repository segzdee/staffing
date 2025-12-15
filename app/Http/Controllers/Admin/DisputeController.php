<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDisputeQueue;
use App\Models\DisputeMessage;
use App\Models\User;
use App\Notifications\DisputeMessageNotification;
use App\Notifications\DisputeResolutionNotification;
use App\Services\DisputeEscalationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * DisputeController
 *
 * Admin controller for dispute resolution with escalation, messaging, and bulk operations.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 */
class DisputeController extends Controller
{
    /**
     * @var DisputeEscalationService
     */
    protected $escalationService;

    /**
     * Create a new controller instance.
     *
     * @param DisputeEscalationService $escalationService
     */
    public function __construct(DisputeEscalationService $escalationService)
    {
        $this->escalationService = $escalationService;
    }

    /**
     * Display list of disputes with filters.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = AdminDisputeQueue::with(['worker', 'business', 'assignedAdmin', 'shiftPayment'])
            ->orderBy('filed_at', 'desc');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Assignment filter
        if ($request->filled('assigned')) {
            if ($request->assigned === 'me') {
                $query->where('assigned_to_admin', auth()->id());
            } elseif ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to_admin');
            }
        }

        // Escalation filter
        if ($request->filled('escalated') && $request->escalated === '1') {
            $query->whereNotNull('escalated_at');
        }

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('dispute_reason', 'like', "%{$search}%")
                    ->orWhereHas('worker', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('business', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $disputes = $query->paginate(25)->withQueryString();

        // Statistics
        $stats = $this->escalationService->getStatistics();

        return view('admin.disputes.index', compact('disputes', 'stats'));
    }

    /**
     * Display single dispute with full details.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $dispute = AdminDisputeQueue::with([
            'worker',
            'business',
            'assignedAdmin',
            'previousAdmin',
            'shiftPayment.assignment.shift',
            'messages.sender',
            'escalations.escalatedFromAdmin',
            'escalations.escalatedToAdmin',
            'adjustments',
        ])->findOrFail($id);

        // Mark messages as read
        $dispute->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'read_by' => auth()->id(),
            ]);

        // Get available admins for assignment
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // SLA data
        $slaData = [
            'deadline' => $dispute->getSLADeadline(),
            'remaining_hours' => $dispute->getRemainingHours(),
            'percentage' => $dispute->getSLAPercentage(),
            'threshold' => DisputeEscalationService::SLA_THRESHOLDS[$dispute->priority] ?? 120,
        ];

        return view('admin.disputes.show', compact('dispute', 'admins', 'slaData'));
    }

    /**
     * Assign dispute to admin.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        if (!$dispute->isActive()) {
            return $this->errorResponse('Cannot assign a resolved or closed dispute.', $request);
        }

        $dispute->assignTo($request->admin_id);

        return $this->successResponse('Dispute assigned successfully.', $request, $dispute);
    }

    /**
     * Update dispute status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,investigating,evidence_review',
            'notes' => 'nullable|string|max:500',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        if (!$dispute->isActive()) {
            return $this->errorResponse('Cannot update status of resolved or closed dispute.', $request);
        }

        $dispute->updateStatus($request->status, $request->notes);

        return $this->successResponse('Status updated successfully.', $request, $dispute);
    }

    /**
     * Resolve dispute with outcome.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resolve(Request $request, $id)
    {
        $request->validate([
            'outcome' => 'required|in:worker_favor,business_favor,split,no_fault',
            'adjustment_amount' => 'nullable|numeric|min:0',
            'resolution_notes' => 'required|string|max:2000',
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        if (!$dispute->isActive()) {
            return $this->errorResponse('Dispute is already resolved or closed.', $request);
        }

        DB::transaction(function () use ($dispute, $request) {
            // Resolve the dispute
            $dispute->resolve(
                $request->outcome,
                $request->adjustment_amount,
                $request->resolution_notes
            );

            // Save internal notes
            if ($request->filled('internal_notes')) {
                $dispute->update(['internal_notes' => $request->internal_notes]);
            }

            // Get adjustment if one was created
            $adjustment = $dispute->adjustments()->latest()->first();

            // Notify all parties
            if ($dispute->worker) {
                $dispute->worker->notify(new DisputeResolutionNotification($dispute, $adjustment));
            }
            if ($dispute->business) {
                $dispute->business->notify(new DisputeResolutionNotification($dispute, $adjustment));
            }
        });

        return $this->successResponse('Dispute resolved successfully.', $request, $dispute);
    }

    /**
     * Close dispute without resolution.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function close(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        if (!$dispute->isActive()) {
            return $this->errorResponse('Dispute is already resolved or closed.', $request);
        }

        $dispute->close($request->notes);

        return $this->successResponse('Dispute closed successfully.', $request, $dispute);
    }

    /**
     * Manually escalate dispute.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function escalate(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        if (!$dispute->isActive()) {
            return $this->errorResponse('Cannot escalate a resolved or closed dispute.', $request);
        }

        if (($dispute->escalation_level ?? 0) >= 3) {
            return $this->errorResponse('Dispute is already at maximum escalation level.', $request);
        }

        $escalation = $this->escalationService->escalateDispute($dispute, $request->reason);

        return $this->successResponse('Dispute escalated successfully.', $request, $dispute);
    }

    /**
     * Add message to dispute thread.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function addMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'boolean',
            'message_type' => 'in:text,evidence',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        $message = $dispute->addMessage(
            auth()->id(),
            $request->message,
            $request->message_type ?? 'text',
            $request->boolean('is_internal', false)
        );

        // Notify other parties (if not internal)
        if (!$message->is_internal) {
            $this->notifyMessageParties($dispute, $message);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message->load('sender'),
            ]);
        }

        return back()->with('success', 'Message added successfully.');
    }

    /**
     * Upload evidence/attachments.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadEvidence(Request $request, $id)
    {
        $request->validate([
            'files' => 'required|array|max:5',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
            'description' => 'nullable|string|max:500',
        ]);

        $dispute = AdminDisputeQueue::findOrFail($id);

        $attachments = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('disputes/' . $dispute->id . '/evidence', 'public');

            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => Storage::url($path),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => auth()->id(),
            ];
        }

        // Create evidence message
        $message = DisputeMessage::createEvidenceMessage(
            $dispute->id,
            auth()->id(),
            $request->description ?? 'Evidence uploaded',
            $attachments
        );

        // Notify other parties
        $this->notifyMessageParties($dispute, $message);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'attachments' => $attachments,
                'message' => $message,
            ]);
        }

        return back()->with('success', 'Evidence uploaded successfully.');
    }

    /**
     * Bulk assign disputes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'dispute_ids' => 'required|array|min:1',
            'dispute_ids.*' => 'exists:admin_dispute_queue,id',
            'admin_id' => 'required|exists:users,id',
        ]);

        $disputes = AdminDisputeQueue::whereIn('id', $request->dispute_ids)
            ->active()
            ->get();

        $assigned = 0;
        foreach ($disputes as $dispute) {
            $dispute->assignTo($request->admin_id);
            $assigned++;
        }

        $message = "{$assigned} dispute(s) assigned successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $assigned]);
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk close disputes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkClose(Request $request)
    {
        $request->validate([
            'dispute_ids' => 'required|array|min:1',
            'dispute_ids.*' => 'exists:admin_dispute_queue,id',
            'notes' => 'required|string|max:2000',
        ]);

        $disputes = AdminDisputeQueue::whereIn('id', $request->dispute_ids)
            ->active()
            ->get();

        $closed = 0;
        foreach ($disputes as $dispute) {
            $dispute->close($request->notes);
            $closed++;
        }

        $message = "{$closed} dispute(s) closed successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $closed]);
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk escalate disputes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkEscalate(Request $request)
    {
        $request->validate([
            'dispute_ids' => 'required|array|min:1',
            'dispute_ids.*' => 'exists:admin_dispute_queue,id',
            'reason' => 'required|string|max:500',
        ]);

        $disputes = AdminDisputeQueue::whereIn('id', $request->dispute_ids)
            ->active()
            ->where(function ($q) {
                $q->whereNull('escalation_level')->orWhere('escalation_level', '<', 3);
            })
            ->get();

        $escalated = 0;
        foreach ($disputes as $dispute) {
            $this->escalationService->escalateDispute($dispute, $request->reason);
            $escalated++;
        }

        $message = "{$escalated} dispute(s) escalated successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $escalated]);
        }

        return back()->with('success', $message);
    }

    /**
     * Get messages for a dispute (AJAX).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $id)
    {
        $dispute = AdminDisputeQueue::findOrFail($id);

        $query = $dispute->messages()->with('sender');

        // Show internal messages only for admins
        if (!$request->boolean('include_internal', true)) {
            $query->public();
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'unread_count' => $dispute->getUnreadCount(auth()->id()),
        ]);
    }

    /**
     * Get SLA status for a dispute (AJAX).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSLAStatus($id)
    {
        $dispute = AdminDisputeQueue::findOrFail($id);

        return response()->json([
            'success' => true,
            'sla' => [
                'deadline' => $dispute->getSLADeadline()->toDateTimeString(),
                'remaining_hours' => round($dispute->getRemainingHours(), 2),
                'percentage' => round($dispute->getSLAPercentage(), 1),
                'threshold_hours' => DisputeEscalationService::SLA_THRESHOLDS[$dispute->priority] ?? 120,
                'is_breached' => $dispute->getSLAPercentage() >= 100,
                'is_warning' => $dispute->getSLAPercentage() >= 80 && $dispute->getSLAPercentage() < 100,
            ],
        ]);
    }

    /**
     * Notify parties of new message.
     *
     * @param AdminDisputeQueue $dispute
     * @param DisputeMessage $message
     * @return void
     */
    protected function notifyMessageParties(AdminDisputeQueue $dispute, DisputeMessage $message): void
    {
        $senderId = $message->sender_id;

        // Notify worker if not the sender
        if ($dispute->worker && $dispute->worker_id !== $senderId) {
            $dispute->worker->notify(new DisputeMessageNotification($dispute, $message));
        }

        // Notify business if not the sender
        if ($dispute->business && $dispute->business_id !== $senderId) {
            $dispute->business->notify(new DisputeMessageNotification($dispute, $message));
        }

        // Notify assigned admin if not the sender
        if ($dispute->assignedAdmin && $dispute->assigned_to_admin !== $senderId) {
            $dispute->assignedAdmin->notify(new DisputeMessageNotification($dispute, $message));
        }
    }

    /**
     * Return success response.
     *
     * @param string $message
     * @param Request $request
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
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
     *
     * @param string $message
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function errorResponse(string $message, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['error' => $message]);
    }
}
