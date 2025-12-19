<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftSwap\RejectShiftSwapRequest;
use App\Http\Requests\ShiftSwap\StoreShiftSwapRequest;
use App\Models\ShiftAssignment;
use App\Models\ShiftSwap;
use App\Services\ShiftSwapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftSwapController extends Controller
{
    protected $swapService;

    public function __construct(ShiftSwapService $swapService)
    {
        $this->middleware('auth');
        $this->swapService = $swapService;
    }

    /**
     * Browse available shift swaps (worker view).
     */
    public function index(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can browse shift swaps.');
        }

        // Get available swap opportunities
        $swaps = $this->swapService->findSwapOpportunities(Auth::user());

        // Filter by industry if specified
        if ($request->has('industry') && $request->industry != 'all') {
            $swaps = $swaps->filter(function ($swap) use ($request) {
                return $swap->offeringAssignment->shift->industry === $request->industry;
            });
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $swaps = $swaps->filter(function ($swap) use ($request) {
                return $swap->offeringAssignment->shift->shift_date >= $request->date_from;
            });
        }

        if ($request->has('date_to')) {
            $swaps = $swaps->filter(function ($swap) use ($request) {
                return $swap->offeringAssignment->shift->shift_date <= $request->date_to;
            });
        }

        // Manual pagination for filtered collection
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        // Slice the collection for current page
        $paginatedItems = $swaps->slice($offset, $perPage)->values();

        // Create a LengthAwarePaginator instance
        $swaps = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $swaps->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('swaps.index', compact('swaps'));
    }

    /**
     * Show form to offer a shift for swap.
     */
    public function create($assignmentId)
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);

        // Validate eligibility
        $eligibility = $this->swapService->validateSwapEligibility(Auth::user(), $assignment);

        if (! $eligibility['eligible']) {
            return redirect()->back()
                ->with('error', $eligibility['reason']);
        }

        return view('swaps.create', compact('assignment'));
    }

    /**
     * Offer a shift for swap.
     */
    public function store(StoreShiftSwapRequest $request, $assignmentId)
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);

        // Validate eligibility
        $eligibility = $this->swapService->validateSwapEligibility(Auth::user(), $assignment);

        if (! $eligibility['eligible']) {
            return redirect()->back()
                ->with('error', $eligibility['reason']);
        }

        // Create swap offer
        $swap = ShiftSwap::create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => Auth::id(),
            'reason' => $request->validated()['reason'] ?? null,
            'status' => 'pending',
            'offered_at' => now(),
        ]);

        return redirect()->route('worker.assignments')
            ->with('success', 'Shift swap offer posted! Other workers will be notified.');
    }

    /**
     * Accept a shift swap offer.
     */
    public function accept(Request $request, $swapId)
    {
        $swap = ShiftSwap::with(['offeringAssignment.shift'])->findOrFail($swapId);

        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can accept shift swaps.');
        }

        // Verify swap is pending
        if ($swap->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This swap is no longer available.');
        }

        // Validate acceptance eligibility
        $eligibility = $this->swapService->validateSwapAcceptance(Auth::user(), $swap);

        if (! $eligibility['eligible']) {
            return redirect()->back()
                ->with('error', $eligibility['reason']);
        }

        // Update swap with accepting worker
        $swap->update([
            'receiving_worker_id' => Auth::id(),
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Notify business for approval
        $swap->load(['offeringAssignment.shift.business', 'offeringWorker', 'receivingWorker']);
        $business = $swap->offeringAssignment?->shift?->business;
        if ($business) {
            $business->notify(new \App\Notifications\ShiftSwapPendingApprovalNotification($swap));
        }

        return redirect()->back()
            ->with('success', 'Swap request sent! Waiting for business approval.');
    }

    /**
     * Business approves a shift swap.
     */
    public function approve($swapId)
    {
        $swap = ShiftSwap::with(['offeringAssignment.shift'])->findOrFail($swapId);
        $shift = $swap->offeringAssignment->shift;

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only approve swaps for your own shifts.');
        }

        // Verify swap is accepted (waiting for business approval)
        if ($swap->status !== 'accepted') {
            return redirect()->back()
                ->with('error', 'This swap cannot be approved.');
        }

        // Process the swap
        $result = $this->swapService->processSwap($swap);

        if ($result) {
            return redirect()->back()
                ->with('success', 'Shift swap approved and processed successfully!');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to process shift swap. Please try again.');
        }
    }

    /**
     * Business rejects a shift swap.
     */
    public function reject(RejectShiftSwapRequest $request, $swapId)
    {
        $swap = ShiftSwap::with(['offeringAssignment.shift'])->findOrFail($swapId);
        $shift = $swap->offeringAssignment->shift;

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only reject swaps for your own shifts.');
        }

        // Cancel the swap
        $this->swapService->cancelSwap($swap, 'business', $request->validated()['reason'] ?? null);

        return redirect()->back()
            ->with('success', 'Shift swap rejected.');
    }

    /**
     * Worker cancels their swap offer.
     */
    public function cancel($swapId)
    {
        $swap = ShiftSwap::findOrFail($swapId);

        // Check authorization
        if ($swap->offering_worker_id !== Auth::id()) {
            abort(403, 'You can only cancel your own swap offers.');
        }

        // Can only cancel pending swaps
        if (! in_array($swap->status, ['pending', 'accepted'])) {
            return redirect()->back()
                ->with('error', 'This swap cannot be cancelled.');
        }

        $this->swapService->cancelSwap($swap, 'offerer');

        return redirect()->back()
            ->with('success', 'Swap offer cancelled.');
    }

    /**
     * Worker withdraws their acceptance of a swap.
     */
    public function withdrawAcceptance($swapId)
    {
        $swap = ShiftSwap::findOrFail($swapId);

        // Check authorization
        if ($swap->receiving_worker_id !== Auth::id()) {
            abort(403, 'You can only withdraw your own acceptance.');
        }

        // Can only withdraw if status is accepted (not yet approved by business)
        if ($swap->status !== 'accepted') {
            return redirect()->back()
                ->with('error', 'You cannot withdraw from this swap.');
        }

        // Reset swap to pending
        $swap->update([
            'receiving_worker_id' => null,
            'status' => 'pending',
            'accepted_at' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Swap acceptance withdrawn.');
    }

    /**
     * View swap details.
     */
    public function show($swapId)
    {
        $swap = ShiftSwap::with([
            'offeringAssignment.shift.business',
            'offeringAssignment.worker.workerProfile',
            'receivingWorker.workerProfile',
        ])->findOrFail($swapId);

        // Check authorization
        $canView = false;

        if (Auth::user()->isWorker()) {
            // Workers can view if: offering, accepting, or browsing pending swaps
            $canView = $swap->offering_worker_id === Auth::id() ||
                       $swap->receiving_worker_id === Auth::id() ||
                       $swap->status === 'pending';
        } elseif (Auth::user()->isBusiness() || Auth::user()->isAgency()) {
            // Business can view if it's their shift
            $canView = $swap->offeringAssignment->shift->business_id === Auth::id();
        }

        if (! $canView) {
            abort(403, 'You do not have permission to view this swap.');
        }

        // Calculate match score if viewing worker hasn't accepted yet
        $matchScore = null;
        if (Auth::user()->isWorker() && ! $swap->receiving_worker_id) {
            $matchingService = app(\App\Services\ShiftMatchingService::class);
            $matchScore = $matchingService->calculateWorkerShiftMatch(
                Auth::user(),
                $swap->offeringAssignment->shift
            );
        }

        return view('swaps.show', compact('swap', 'matchScore'));
    }

    /**
     * Business views all pending swap requests for their shifts.
     */
    public function businessSwaps(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness() && ! Auth::user()->isAgency()) {
            abort(403, 'Only businesses can access this page.');
        }

        $status = $request->get('status', 'accepted');

        $query = ShiftSwap::with([
            'offeringAssignment.shift',
            'offeringAssignment.worker',
            'receivingWorker',
        ])->whereHas('offeringAssignment.shift', function ($q) {
            $q->where('business_id', Auth::id());
        });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $swaps = $query->orderBy('accepted_at', 'desc')->paginate(20);

        return view('business.swaps.index', compact('swaps', 'status'));
    }

    /**
     * Worker views their swap history.
     */
    public function mySwaps(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this page.');
        }

        $filter = $request->get('filter', 'active');

        $query = ShiftSwap::with([
            'offeringAssignment.shift.business',
            'receivingWorker',
        ])->where(function ($q) {
            $q->where('offering_worker_id', Auth::id())
                ->orWhere('receiving_worker_id', Auth::id());
        });

        switch ($filter) {
            case 'active':
                $query->whereIn('status', ['pending', 'accepted']);
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
        }

        $swaps = $query->orderBy('offered_at', 'desc')->paginate(20);

        return view('worker.swaps.index', compact('swaps', 'filter'));
    }
}
