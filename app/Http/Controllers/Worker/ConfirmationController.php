<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\BookingConfirmation;
use App\Services\BookingConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SL-004: Worker Confirmation Controller
 *
 * Handles the worker-side of the booking confirmation workflow.
 * Workers can view, confirm, or decline their pending shift bookings.
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
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this page.');
        }

        $status = $request->get('status', 'pending');

        $query = BookingConfirmation::forWorker(Auth::id())
            ->with(['shift', 'business']);

        switch ($status) {
            case 'pending':
                $query->awaitingWorker();
                break;
            case 'awaiting_business':
                $query->where('worker_confirmed', true)
                    ->where('business_confirmed', false)
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

        return view('worker.confirmations.index', compact('confirmations', 'status', 'stats'));
    }

    /**
     * Display a specific confirmation.
     */
    public function show($id)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this page.');
        }

        $confirmation = BookingConfirmation::forWorker(Auth::id())
            ->with(['shift.business', 'shift.attachments', 'reminders'])
            ->findOrFail($id);

        return view('worker.confirmations.show', compact('confirmation'));
    }

    /**
     * Confirm the booking (worker accepts).
     */
    public function confirm(Request $request, $id)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can confirm bookings.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $confirmation = BookingConfirmation::forWorker(Auth::id())->findOrFail($id);

        try {
            $this->confirmationService->workerConfirm(
                $confirmation,
                Auth::user(),
                $request->notes
            );

            $message = $confirmation->isFullyConfirmed()
                ? 'Booking confirmed! Both you and the business have confirmed.'
                : 'Your confirmation has been recorded. Awaiting business confirmation.';

            return redirect()
                ->route('worker.confirmations.show', $id)
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
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can decline bookings.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $confirmation = BookingConfirmation::forWorker(Auth::id())->findOrFail($id);

        try {
            $this->confirmationService->declineBooking(
                $confirmation,
                Auth::user(),
                $request->reason
            );

            return redirect()
                ->route('worker.confirmations.index')
                ->with('success', 'Booking declined. The business has been notified.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View confirmation by code (QR code scan).
     */
    public function viewByCode(Request $request, $code)
    {
        $confirmation = $this->confirmationService->getConfirmationByCode($code);

        if (! $confirmation) {
            abort(404, 'Confirmation not found.');
        }

        // If logged in and is the worker for this confirmation, redirect to full view
        if (Auth::check() && Auth::id() === $confirmation->worker_id) {
            return redirect()->route('worker.confirmations.show', $confirmation->id);
        }

        // Show a public summary view
        return view('confirmations.public', compact('confirmation'));
    }

    /**
     * Get QR code for a confirmation.
     */
    public function qrCode($id)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this.');
        }

        $confirmation = BookingConfirmation::forWorker(Auth::id())->findOrFail($id);

        // Generate QR code using Simple QR Code package or inline SVG
        $qrCodeUrl = $confirmation->getQrCodeUrl();
        $qrSize = config('booking_confirmation.qr_code.size', 300);

        // Return QR code view/image
        return view('worker.confirmations.qr-code', compact('confirmation', 'qrCodeUrl', 'qrSize'));
    }

    /**
     * API endpoint for getting pending confirmation count.
     */
    public function pendingCount()
    {
        if (! Auth::check() || ! Auth::user()->isWorker()) {
            return response()->json(['count' => 0]);
        }

        $count = BookingConfirmation::forWorker(Auth::id())
            ->awaitingWorker()
            ->count();

        return response()->json(['count' => $count]);
    }
}
