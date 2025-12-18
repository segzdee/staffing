<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\OpenDisputeRequest;
use App\Http\Requests\Worker\SubmitDisputeEvidenceRequest;
use App\Models\Dispute;
use App\Models\Shift;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DisputeController
 *
 * FIN-010: Worker-facing controller for dispute management.
 *
 * Allows workers to open disputes, submit evidence, view dispute status,
 * and track dispute resolution progress.
 */
class DisputeController extends Controller
{
    protected DisputeService $disputeService;

    /**
     * Create a new controller instance.
     */
    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService = $disputeService;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isWorker()) {
                abort(403, 'Only workers can access this section');
            }

            return $next($request);
        });
    }

    /**
     * Display list of worker's disputes.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Dispute::forWorker(auth()->id())
            ->with(['shift', 'business'])
            ->orderBy('created_at', 'desc');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $disputes = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = $this->disputeService->getStatistics(auth()->id());

        return view('worker.disputes.index', compact('disputes', 'stats'));
    }

    /**
     * Show form to open a new dispute.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $shiftId)
    {
        $shift = Shift::with(['business', 'assignments' => function ($q) {
            $q->where('worker_id', auth()->id());
        }])->findOrFail($shiftId);

        // Verify worker was assigned to this shift
        if ($shift->assignments->isEmpty()) {
            abort(403, 'You were not assigned to this shift');
        }

        // Check if shift is completed
        if (! in_array($shift->status, ['completed', 'verified', 'in_progress'])) {
            abort(403, 'Cannot dispute a shift that has not been completed');
        }

        // Check for existing active dispute
        $existingDispute = Dispute::where('shift_id', $shift->id)
            ->where('worker_id', auth()->id())
            ->active()
            ->first();

        if ($existingDispute) {
            return redirect()
                ->route('worker.disputes.show', $existingDispute)
                ->with('info', 'You already have an active dispute for this shift');
        }

        $disputeTypes = Dispute::$types;

        return view('worker.disputes.create', compact('shift', 'disputeTypes'));
    }

    /**
     * Store a new dispute.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(OpenDisputeRequest $request, int $shiftId)
    {
        $shift = Shift::findOrFail($shiftId);

        // Verify worker was assigned
        $wasAssigned = $shift->assignments()
            ->where('worker_id', auth()->id())
            ->exists();

        if (! $wasAssigned) {
            abort(403, 'You were not assigned to this shift');
        }

        try {
            $dispute = $this->disputeService->openDispute(
                $shift,
                auth()->user(),
                $request->validated()
            );

            return redirect()
                ->route('worker.disputes.show', $dispute)
                ->with('success', 'Dispute opened successfully. The business has been notified and will respond within '.config('disputes.business_response_days', 3).' days.');
        } catch (\Exception $e) {
            Log::error('Failed to open dispute', [
                'shift_id' => $shiftId,
                'worker_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display a specific dispute.
     *
     * @return \Illuminate\View\View
     */
    public function show(int $id)
    {
        $dispute = Dispute::with([
            'shift',
            'business',
            'assignedAdmin',
            'timeline.user',
        ])->forWorker(auth()->id())->findOrFail($id);

        return view('worker.disputes.show', compact('dispute'));
    }

    /**
     * Show form to submit evidence.
     *
     * @return \Illuminate\View\View
     */
    public function evidence(int $id)
    {
        $dispute = Dispute::forWorker(auth()->id())->findOrFail($id);

        if (! $dispute->canWorkerSubmitEvidence()) {
            return redirect()
                ->route('worker.disputes.show', $dispute)
                ->withErrors(['error' => 'Evidence submission is not available for this dispute']);
        }

        $allowedTypes = config('disputes.allowed_evidence_types', []);
        $maxFiles = config('disputes.max_evidence_files', 10);
        $maxFileSize = config('disputes.max_evidence_file_size_mb', 10);

        return view('worker.disputes.evidence', compact('dispute', 'allowedTypes', 'maxFiles', 'maxFileSize'));
    }

    /**
     * Submit evidence for a dispute.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitEvidence(SubmitDisputeEvidenceRequest $request, int $id)
    {
        $dispute = Dispute::forWorker(auth()->id())->findOrFail($id);

        try {
            $this->disputeService->submitEvidence(
                $dispute,
                auth()->user(),
                $request->file('files')
            );

            return redirect()
                ->route('worker.disputes.show', $dispute)
                ->with('success', 'Evidence submitted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to submit evidence', [
                'dispute_id' => $id,
                'worker_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Withdraw a dispute.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function withdraw(int $id)
    {
        $dispute = Dispute::forWorker(auth()->id())->findOrFail($id);

        if (! $dispute->isActive()) {
            return back()
                ->withErrors(['error' => 'Cannot withdraw a resolved or closed dispute']);
        }

        try {
            $dispute->update([
                'status' => Dispute::STATUS_CLOSED,
                'resolution' => Dispute::RESOLUTION_WITHDRAWN,
                'resolution_notes' => 'Withdrawn by worker',
                'resolved_at' => now(),
            ]);

            $dispute->addTimelineEntry(
                \App\Models\DisputeTimeline::ACTION_WITHDRAWN,
                auth()->id(),
                'Dispute withdrawn by worker'
            );

            return redirect()
                ->route('worker.disputes.index')
                ->with('success', 'Dispute withdrawn successfully');
        } catch (\Exception $e) {
            Log::error('Failed to withdraw dispute', [
                'dispute_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to withdraw dispute']);
        }
    }

    /**
     * Get eligible shifts for dispute (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eligibleShifts(Request $request)
    {
        // Get completed shifts from the last 30 days without active disputes
        $shifts = Shift::whereHas('assignments', function ($q) {
            $q->where('worker_id', auth()->id())
                ->whereIn('status', ['completed', 'checked_out']);
        })
            ->whereIn('status', ['completed', 'verified'])
            ->where('completed_at', '>=', now()->subDays(30))
            ->whereDoesntHave('disputes', function ($q) {
                $q->where('worker_id', auth()->id())
                    ->active();
            })
            ->with(['business', 'assignments' => function ($q) {
                $q->where('worker_id', auth()->id());
            }])
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'shifts' => $shifts->map(function ($shift) {
                $assignment = $shift->assignments->first();

                return [
                    'id' => $shift->id,
                    'title' => $shift->title,
                    'business_name' => $shift->business->name ?? 'Unknown Business',
                    'date' => $shift->shift_date->format('M d, Y'),
                    'hours_worked' => $assignment->hours_worked ?? 0,
                    'can_dispute' => true,
                ];
            }),
        ]);
    }

    /**
     * Get timeline for a dispute (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeline(int $id)
    {
        $dispute = Dispute::forWorker(auth()->id())->findOrFail($id);

        $timeline = $dispute->timeline()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'action' => $entry->action,
                    'action_label' => $entry->action_label,
                    'description' => $entry->description,
                    'actor_name' => $entry->actor_name,
                    'icon_class' => $entry->icon_class,
                    'created_at' => $entry->created_at->format('M d, Y H:i'),
                    'time_ago' => $entry->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }
}
