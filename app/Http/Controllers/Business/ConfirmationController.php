<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BookingConfirmation;
use App\Services\BookingConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SL-004: Business Confirmation Controller
 *
 * Handles the business-side of the booking confirmation workflow.
 * Businesses can view, confirm, or decline worker bookings,
 * including bulk confirmation actions.
 */
class ConfirmationController extends Controller
{
    protected BookingConfirmationService $confirmationService;

    public function __construct(BookingConfirmationService $confirmationService)
    {
        $this->middleware('auth');
        $this->confirmationService = $confirmationService;
    }

    /**
     * Display list of pending confirmations.
     */
    public function index(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can access this page.');
        }

        $status = $request->get('status', 'pending');
        $shiftId = $request->get('shift_id');

        $query = BookingConfirmation::forBusiness(Auth::id())
            ->with(['shift', 'worker', 'worker.workerProfile']);

        // Filter by shift if provided
        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        switch ($status) {
            case 'pending':
                $query->awaitingBusiness();
                break;
            case 'awaiting_worker':
                $query->where('business_confirmed', true)
                    ->where('worker_confirmed', false)
                    ->whereNotIn('status', [
                        BookingConfirmation::STATUS_DECLINED,
                        BookingConfirmation::STATUS_EXPIRED,
                    ]);
                break;
            case 'confirmed':
                $query->fullyConfirmed();
                break;
            case 'declined':
                $query->where('status', BookingConfirmation::STATUS_DECLINED);
                break;
            case 'expired':
                $query->expired();
                break;
            case 'all':
                // No filter
                break;
        }

        $confirmations = $query->orderBy('expires_at', 'asc')->paginate(20);

        // Get statistics
        $stats = $this->confirmationService->getConfirmationStats(Auth::user());

        return view('business.confirmations.index', compact('confirmations', 'status', 'stats'));
    }

    /**
     * Display a specific confirmation.
     */
    public function show($id)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can access this page.');
        }

        $confirmation = BookingConfirmation::forBusiness(Auth::id())
            ->with([
                'shift',
                'worker',
                'worker.workerProfile',
                'worker.skills',
                'worker.certifications',
                'reminders',
            ])
            ->findOrFail($id);

        // Get worker's history with this business
        $workerHistory = $this->getWorkerHistory($confirmation->worker_id, Auth::id());

        return view('business.confirmations.show', compact('confirmation', 'workerHistory'));
    }

    /**
     * Confirm the booking (business accepts the worker).
     */
    public function confirm(Request $request, $id)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can confirm bookings.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $confirmation = BookingConfirmation::forBusiness(Auth::id())->findOrFail($id);

        try {
            $this->confirmationService->businessConfirm(
                $confirmation,
                Auth::user(),
                $request->notes
            );

            $message = $confirmation->fresh()->isFullyConfirmed()
                ? 'Booking confirmed! Both you and the worker have confirmed.'
                : 'Your confirmation has been recorded. Awaiting worker confirmation.';

            return redirect()
                ->route('business.confirmations.show', $id)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Decline the booking.
     */
    public function decline(Request $request, $id)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can decline bookings.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $confirmation = BookingConfirmation::forBusiness(Auth::id())->findOrFail($id);

        try {
            $this->confirmationService->declineBooking(
                $confirmation,
                Auth::user(),
                $request->reason
            );

            return redirect()
                ->route('business.confirmations.index')
                ->with('success', 'Booking declined. The worker has been notified.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk confirm multiple bookings.
     */
    public function bulkConfirm(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can bulk confirm bookings.');
        }

        $request->validate([
            'confirmation_ids' => 'required|array|min:1',
            'confirmation_ids.*' => 'integer|exists:booking_confirmations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $results = $this->confirmationService->bulkConfirm(
                $request->confirmation_ids,
                Auth::user(),
                $request->notes
            );

            $confirmedCount = count($results['confirmed']);
            $failedCount = count($results['failed']);

            $message = "{$confirmedCount} booking(s) confirmed.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} booking(s) failed.";
            }

            return redirect()
                ->route('business.confirmations.index')
                ->with('success', $message)
                ->with('bulk_results', $results);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk decline multiple bookings.
     */
    public function bulkDecline(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can bulk decline bookings.');
        }

        if (! config('booking_confirmation.bulk_confirmation.allow_bulk_decline', true)) {
            abort(403, 'Bulk decline is not enabled.');
        }

        $request->validate([
            'confirmation_ids' => 'required|array|min:1',
            'confirmation_ids.*' => 'integer|exists:booking_confirmations,id',
            'reason' => 'required|string|max:500',
        ]);

        $results = [
            'declined' => [],
            'failed' => [],
        ];

        foreach ($request->confirmation_ids as $id) {
            try {
                $confirmation = BookingConfirmation::forBusiness(Auth::id())->findOrFail($id);
                $this->confirmationService->declineBooking(
                    $confirmation,
                    Auth::user(),
                    $request->reason
                );
                $results['declined'][] = $id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $declinedCount = count($results['declined']);
        $failedCount = count($results['failed']);

        $message = "{$declinedCount} booking(s) declined.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} booking(s) failed.";
        }

        return redirect()
            ->route('business.confirmations.index')
            ->with('success', $message)
            ->with('bulk_results', $results);
    }

    /**
     * View confirmation by code (for verification at venue).
     */
    public function verifyByCode(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can verify bookings.');
        }

        $request->validate([
            'code' => 'required|string|size:8',
        ]);

        $confirmation = $this->confirmationService->getConfirmationByCode($request->code);

        if (! $confirmation) {
            return redirect()
                ->back()
                ->with('error', 'Invalid confirmation code.');
        }

        // Verify this is for the business's shift
        if ($confirmation->business_id !== Auth::id()) {
            return redirect()
                ->back()
                ->with('error', 'This confirmation is not for your business.');
        }

        return redirect()
            ->route('business.confirmations.show', $confirmation->id)
            ->with('info', 'Confirmation found.');
    }

    /**
     * View confirmations for a specific shift.
     */
    public function forShift($shiftId)
    {
        // Check authorization
        if (! Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can access this page.');
        }

        $confirmations = BookingConfirmation::forBusiness(Auth::id())
            ->where('shift_id', $shiftId)
            ->with(['worker', 'worker.workerProfile'])
            ->orderBy('status')
            ->orderBy('expires_at')
            ->get();

        $shift = \App\Models\Shift::where('business_id', Auth::id())
            ->findOrFail($shiftId);

        $stats = [
            'total' => $confirmations->count(),
            'awaiting_business' => $confirmations->where('status', BookingConfirmation::STATUS_PENDING)->count()
                + $confirmations->where('status', BookingConfirmation::STATUS_WORKER_CONFIRMED)->count(),
            'awaiting_worker' => $confirmations->where('status', BookingConfirmation::STATUS_BUSINESS_CONFIRMED)->count(),
            'confirmed' => $confirmations->where('status', BookingConfirmation::STATUS_FULLY_CONFIRMED)->count(),
            'declined' => $confirmations->where('status', BookingConfirmation::STATUS_DECLINED)->count(),
            'expired' => $confirmations->where('status', BookingConfirmation::STATUS_EXPIRED)->count(),
        ];

        return view('business.confirmations.for-shift', compact('confirmations', 'shift', 'stats'));
    }

    /**
     * API endpoint for getting pending confirmation count.
     */
    public function pendingCount()
    {
        if (! Auth::check() || ! Auth::user()->isBusiness()) {
            return response()->json(['count' => 0]);
        }

        $count = BookingConfirmation::forBusiness(Auth::id())
            ->awaitingBusiness()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get worker's history with this business.
     */
    private function getWorkerHistory(int $workerId, int $businessId): array
    {
        $completedShifts = \App\Models\ShiftAssignment::where('worker_id', $workerId)
            ->whereHas('shift', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->where('status', 'completed')
            ->count();

        $avgRating = \Illuminate\Support\Facades\DB::table('ratings')
            ->where('rated_id', $workerId)
            ->where('rater_id', $businessId)
            ->avg('rating');

        $lastWorkedAt = \App\Models\ShiftAssignment::where('worker_id', $workerId)
            ->whereHas('shift', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->where('status', 'completed')
            ->latest('check_out_time')
            ->value('check_out_time');

        return [
            'completed_shifts' => $completedShifts,
            'avg_rating' => $avgRating ? round($avgRating, 1) : null,
            'last_worked_at' => $lastWorkedAt,
            'is_returning' => $completedShifts >= config('booking_confirmation.auto_confirm_min_shifts', 3),
        ];
    }
}
