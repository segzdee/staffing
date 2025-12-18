<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\WorkerSuspension;
use App\Services\SuspensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WKR-009: Worker Suspension Controller
 *
 * Handles worker-facing suspension views and appeal submissions.
 */
class SuspensionController extends Controller
{
    public function __construct(
        protected SuspensionService $suspensionService
    ) {}

    /**
     * Display the worker's suspension status.
     */
    public function index(Request $request): View
    {
        $worker = $request->user();

        $activeSuspension = $this->suspensionService->getActiveSuspension($worker);
        $suspensionHistory = $this->suspensionService->getSuspensionHistory($worker, 10);
        $strikeExpiry = $this->suspensionService->calculateStrikeExpiry($worker);

        return view('worker.suspensions.index', [
            'activeSuspension' => $activeSuspension,
            'suspensionHistory' => $suspensionHistory,
            'strikeCount' => $worker->strike_count ?? 0,
            'strikeExpiry' => $strikeExpiry,
            'maxStrikes' => config('suspensions.max_strikes_before_permanent', 5),
        ]);
    }

    /**
     * Show details of a specific suspension.
     */
    public function show(Request $request, WorkerSuspension $suspension): View
    {
        $worker = $request->user();

        // Ensure worker owns this suspension
        if ($suspension->user_id !== $worker->id) {
            abort(403, 'You do not have access to this suspension.');
        }

        $suspension->load(['issuer', 'relatedShift', 'appeals.reviewer']);

        return view('worker.suspensions.show', [
            'suspension' => $suspension,
            'canAppeal' => $suspension->canBeAppealed(),
            'appealDaysRemaining' => $suspension->appealDaysRemaining(),
        ]);
    }

    /**
     * Show the appeal form for a suspension.
     */
    public function appealForm(Request $request, WorkerSuspension $suspension): View|RedirectResponse
    {
        $worker = $request->user();

        // Ensure worker owns this suspension
        if ($suspension->user_id !== $worker->id) {
            abort(403, 'You do not have access to this suspension.');
        }

        // Check if can be appealed
        if (! $suspension->canBeAppealed()) {
            return redirect()
                ->route('worker.suspensions.show', $suspension)
                ->with('error', 'This suspension cannot be appealed at this time.');
        }

        return view('worker.suspensions.appeal', [
            'suspension' => $suspension,
            'appealDaysRemaining' => $suspension->appealDaysRemaining(),
        ]);
    }

    /**
     * Submit an appeal for a suspension.
     */
    public function submitAppeal(Request $request, WorkerSuspension $suspension): RedirectResponse
    {
        $worker = $request->user();

        // Ensure worker owns this suspension
        if ($suspension->user_id !== $worker->id) {
            abort(403, 'You do not have access to this suspension.');
        }

        // Check if can be appealed
        if (! $suspension->canBeAppealed()) {
            return redirect()
                ->route('worker.suspensions.show', $suspension)
                ->with('error', 'This suspension cannot be appealed at this time.');
        }

        $validated = $request->validate([
            'appeal_reason' => ['required', 'string', 'min:50', 'max:5000'],
            'supporting_evidence' => ['nullable', 'array', 'max:5'],
            'supporting_evidence.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ]);

        // Process uploaded evidence
        $evidence = [];
        if ($request->hasFile('supporting_evidence')) {
            foreach ($request->file('supporting_evidence') as $file) {
                $path = $file->store('appeals/evidence', 'public');
                $evidence[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        try {
            $this->suspensionService->submitAppeal($suspension, $worker, [
                'appeal_reason' => $validated['appeal_reason'],
                'supporting_evidence' => ! empty($evidence) ? $evidence : null,
            ]);

            return redirect()
                ->route('worker.suspensions.show', $suspension)
                ->with('success', 'Your appeal has been submitted successfully. We will review it within 48 hours.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View appeal status for a suspension.
     */
    public function appealStatus(Request $request, WorkerSuspension $suspension): View
    {
        $worker = $request->user();

        // Ensure worker owns this suspension
        if ($suspension->user_id !== $worker->id) {
            abort(403, 'You do not have access to this suspension.');
        }

        $suspension->load(['appeals.reviewer']);

        $latestAppeal = $suspension->appeals->sortByDesc('created_at')->first();

        return view('worker.suspensions.appeal-status', [
            'suspension' => $suspension,
            'appeal' => $latestAppeal,
        ]);
    }

    /**
     * Get suspension status as JSON (for AJAX requests).
     */
    public function status(Request $request): \Illuminate\Http\JsonResponse
    {
        $worker = $request->user();

        $activeSuspension = $this->suspensionService->getActiveSuspension($worker);

        if (! $activeSuspension) {
            return response()->json([
                'is_suspended' => false,
                'can_book' => true,
            ]);
        }

        return response()->json([
            'is_suspended' => true,
            'can_book' => ! $activeSuspension->affects_booking,
            'type' => $activeSuspension->type,
            'reason_category' => config('suspensions.visibility.show_reason', true)
                ? $activeSuspension->reason_category
                : null,
            'ends_at' => config('suspensions.visibility.show_end_date', true)
                ? $activeSuspension->ends_at?->toIso8601String()
                : null,
            'hours_remaining' => $activeSuspension->hoursRemaining(),
            'can_appeal' => $activeSuspension->canBeAppealed(),
            'has_pending_appeal' => $activeSuspension->pendingAppeal()->exists(),
        ]);
    }
}
