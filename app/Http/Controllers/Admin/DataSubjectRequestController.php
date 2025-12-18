<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\PrivacyComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GLO-005: GDPR/CCPA Compliance - Admin Data Subject Request Controller
 *
 * Admin interface for managing Data Subject Requests (DSRs).
 */
class DataSubjectRequestController extends Controller
{
    public function __construct(
        protected PrivacyComplianceService $privacyService
    ) {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of all DSRs.
     */
    public function index(Request $request)
    {
        $query = DataSubjectRequest::query()
            ->with(['user', 'assignedAdmin']);

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Filter by urgency
        if ($urgency = $request->query('urgency')) {
            $now = now();
            switch ($urgency) {
                case 'overdue':
                    $query->where('due_date', '<', $now)
                        ->whereNotIn('status', ['completed', 'rejected']);
                    break;
                case 'critical':
                    $query->whereBetween('due_date', [$now, $now->copy()->addDays(5)])
                        ->whereNotIn('status', ['completed', 'rejected']);
                    break;
                case 'high':
                    $query->whereBetween('due_date', [$now->copy()->addDays(5), $now->copy()->addDays(10)])
                        ->whereNotIn('status', ['completed', 'rejected']);
                    break;
            }
        }

        // Filter by assigned admin
        if ($assignedTo = $request->query('assigned_to')) {
            if ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $assignedTo);
            }
        }

        // Search by email or request number
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('request_number', 'like', "%{$search}%");
            });
        }

        // Default sort: urgency (overdue first, then by due_date)
        $query->orderByRaw("CASE
            WHEN status IN ('completed', 'rejected') THEN 3
            WHEN due_date < NOW() THEN 0
            ELSE 1
        END")
            ->orderBy('due_date', 'asc');

        $requests = $query->paginate(20)->withQueryString();

        // Get statistics for dashboard
        $stats = $this->getStats();

        // Get admins for assignment dropdown
        $admins = User::where('role', 'admin')->get();

        return view('admin.privacy.dsr.index', compact('requests', 'stats', 'admins'));
    }

    /**
     * Display the specified DSR.
     */
    public function show(DataSubjectRequest $dataSubjectRequest)
    {
        $dataSubjectRequest->load(['user', 'assignedAdmin']);

        // Get user's data if they exist
        $userData = null;
        if ($dataSubjectRequest->user) {
            $userData = [
                'user_type' => $dataSubjectRequest->user->user_type,
                'created_at' => $dataSubjectRequest->user->created_at,
                'shifts_count' => $dataSubjectRequest->user->shiftAssignments()->count()
                    + $dataSubjectRequest->user->postedShifts()->count(),
                'payments_count' => $dataSubjectRequest->user->shiftPaymentsReceived()->count()
                    + $dataSubjectRequest->user->shiftPaymentsMade()->count(),
                'messages_count' => $dataSubjectRequest->user->sentMessages()->count()
                    + $dataSubjectRequest->user->receivedMessages()->count(),
            ];
        }

        return view('admin.privacy.dsr.show', compact('dataSubjectRequest', 'userData'));
    }

    /**
     * Assign a DSR to an admin.
     */
    public function assign(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $validated = $request->validate([
            'admin_id' => 'required|exists:users,id',
        ]);

        $dataSubjectRequest->update([
            'assigned_to' => $validated['admin_id'],
        ]);

        Log::info('DSR assigned', [
            'request_number' => $dataSubjectRequest->request_number,
            'assigned_to' => $validated['admin_id'],
            'assigned_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Request has been assigned.');
    }

    /**
     * Start processing a DSR.
     */
    public function process(DataSubjectRequest $dataSubjectRequest)
    {
        if (! $dataSubjectRequest->isVerified()) {
            return redirect()->back()->with('error', 'This request has not been verified by the user.');
        }

        if ($dataSubjectRequest->status === DataSubjectRequest::STATUS_PROCESSING) {
            return redirect()->back()->with('warning', 'This request is already being processed.');
        }

        $dataSubjectRequest->startProcessing(auth()->id());

        Log::info('DSR processing started', [
            'request_number' => $dataSubjectRequest->request_number,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Processing has been started.');
    }

    /**
     * Execute the DSR (complete the requested action).
     */
    public function execute(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        if ($dataSubjectRequest->status !== DataSubjectRequest::STATUS_PROCESSING) {
            return redirect()->back()->with('error', 'This request is not ready for execution.');
        }

        DB::beginTransaction();
        try {
            switch ($dataSubjectRequest->type) {
                case DataSubjectRequest::TYPE_ACCESS:
                    $filePath = $this->privacyService->processAccessRequest($dataSubjectRequest);
                    break;

                case DataSubjectRequest::TYPE_PORTABILITY:
                    $filePath = $this->privacyService->processPortabilityRequest($dataSubjectRequest);
                    break;

                case DataSubjectRequest::TYPE_ERASURE:
                    $this->privacyService->processErasureRequest($dataSubjectRequest);
                    break;

                case DataSubjectRequest::TYPE_RECTIFICATION:
                case DataSubjectRequest::TYPE_RESTRICTION:
                case DataSubjectRequest::TYPE_OBJECTION:
                    // These require manual handling
                    $validated = $request->validate([
                        'completion_notes' => 'required|string|max:2000',
                    ]);
                    $dataSubjectRequest->complete($validated['completion_notes']);
                    break;
            }

            DB::commit();

            Log::info('DSR executed', [
                'request_number' => $dataSubjectRequest->request_number,
                'type' => $dataSubjectRequest->type,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.privacy.dsr.show', $dataSubjectRequest)
                ->with('success', 'Request has been completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DSR execution failed', [
                'request_number' => $dataSubjectRequest->request_number,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while processing the request: '.$e->getMessage());
        }
    }

    /**
     * Reject a DSR.
     */
    public function reject(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $dataSubjectRequest->reject($validated['rejection_reason']);

        Log::info('DSR rejected', [
            'request_number' => $dataSubjectRequest->request_number,
            'reason' => $validated['rejection_reason'],
            'admin_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Request has been rejected.');
    }

    /**
     * Add a note to a DSR.
     */
    public function addNote(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $metadata = $dataSubjectRequest->metadata ?? [];
        $metadata['notes'] = $metadata['notes'] ?? [];
        $metadata['notes'][] = [
            'content' => $validated['note'],
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'created_at' => now()->toIso8601String(),
        ];

        $dataSubjectRequest->update(['metadata' => $metadata]);

        return redirect()->back()->with('success', 'Note has been added.');
    }

    /**
     * Preview user data before deletion.
     */
    public function previewData(DataSubjectRequest $dataSubjectRequest)
    {
        $user = $dataSubjectRequest->user;

        if (! $user) {
            $user = User::where('email', $dataSubjectRequest->email)->first();
        }

        if (! $user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        $preview = [
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'data_counts' => [
                'shifts' => $user->shiftAssignments()->count() + $user->postedShifts()->count(),
                'payments' => $user->shiftPaymentsReceived()->count() + $user->shiftPaymentsMade()->count(),
                'messages' => $user->sentMessages()->count() + $user->receivedMessages()->count(),
                'ratings' => $user->ratingsGiven()->count() + $user->ratingsReceived()->count(),
            ],
            'has_profiles' => [
                'worker_profile' => $user->workerProfile !== null,
                'business_profile' => $user->businessProfile !== null,
                'agency_profile' => $user->agencyProfile !== null,
            ],
        ];

        return response()->json($preview);
    }

    /**
     * Export DSR report.
     */
    public function exportReport(Request $request)
    {
        $startDate = $request->query('start_date')
            ? \Carbon\Carbon::parse($request->query('start_date'))
            : now()->subMonth();
        $endDate = $request->query('end_date')
            ? \Carbon\Carbon::parse($request->query('end_date'))
            : now();

        $report = $this->privacyService->generateAuditReport($startDate, $endDate);

        return response()->json($report);
    }

    /**
     * Get dashboard statistics.
     */
    protected function getStats(): array
    {
        return [
            'total' => DataSubjectRequest::count(),
            'pending' => DataSubjectRequest::where('status', 'pending')->count(),
            'verifying' => DataSubjectRequest::where('status', 'verifying')->count(),
            'processing' => DataSubjectRequest::where('status', 'processing')->count(),
            'completed' => DataSubjectRequest::where('status', 'completed')->count(),
            'rejected' => DataSubjectRequest::where('status', 'rejected')->count(),
            'overdue' => DataSubjectRequest::where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'rejected'])
                ->count(),
            'due_soon' => DataSubjectRequest::whereBetween('due_date', [now(), now()->addDays(5)])
                ->whereNotIn('status', ['completed', 'rejected'])
                ->count(),
            'by_type' => DataSubjectRequest::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Display the compliance dashboard.
     */
    public function dashboard()
    {
        $stats = $this->privacyService->getComplianceStats();
        $recentRequests = DataSubjectRequest::with(['user', 'assignedAdmin'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $retentionReport = $this->privacyService->getRetentionPolicyReport();

        return view('admin.privacy.dashboard', compact('stats', 'recentRequests', 'retentionReport'));
    }
}
