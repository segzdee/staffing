<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Services\ShiftMatchingService;
use App\Services\ShiftPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftManagementController extends Controller
{
    protected $matchingService;

    protected $paymentService;

    public function __construct(ShiftMatchingService $matchingService, ShiftPaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->matchingService = $matchingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Business dashboard - View all posted shifts.
     */
    public function myShifts(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness() && ! Auth::user()->isAgency()) {
            abort(403, 'Only businesses and agencies can access this page.');
        }

        $status = $request->get('status', 'all');

        $query = Shift::where('business_id', Auth::id())
            ->with(['assignments.worker', 'applications.worker'])
            ->orderBy('shift_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Filter by status
        if ($status != 'all') {
            $query->where('status', $status);
        }

        $shifts = $query->paginate(20);

        // Calculate statistics
        $stats = [
            'total_posted' => Shift::where('business_id', Auth::id())->count(),
            'open' => Shift::where('business_id', Auth::id())->where('status', 'open')->count(),
            'in_progress' => Shift::where('business_id', Auth::id())->where('status', 'in_progress')->count(),
            'completed' => Shift::where('business_id', Auth::id())->where('status', 'completed')->count(),
            'pending_applications' => ShiftApplication::whereHas('shift', function ($q) {
                $q->where('business_id', Auth::id());
            })->where('status', 'pending')->count(),
        ];

        return view('business.shifts', compact('shifts', 'stats', 'status'));
    }

    /**
     * View a specific shift.
     */
    public function show($shiftId)
    {
        // Check authorization
        if (! Auth::user()->isBusiness() && ! Auth::user()->isAgency()) {
            abort(403, 'Only businesses and agencies can access this page.');
        }

        $shift = Shift::with(['assignments.worker', 'applications.worker', 'business', 'venue'])
            ->findOrFail($shiftId);

        // Check ownership
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only view your own shifts.');
        }

        return view('business.shifts.show', compact('shift'));
    }

    /**
     * View applications for a specific shift.
     */
    public function viewApplications($shiftId)
    {
        // Eager load relationships and use withCount to prevent N+1 queries for completed shifts count
        $shift = Shift::with([
            'applications.worker' => function ($query) {
                // Use withCount to eager load completed shifts count
                $query->with(['workerProfile', 'badges'])
                    ->withCount(['shiftAssignments as completed_shifts_count' => function ($q) {
                        $q->where('status', 'completed');
                    }]);
            },
            'assignments.worker',
        ])->findOrFail($shiftId);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only view applications for your own shifts.');
        }

        // Calculate match scores for each applicant
        foreach ($shift->applications as $application) {
            $application->match_score = $this->matchingService
                ->calculateWorkerShiftMatch($application->worker, $shift);
        }

        // Sort applications by match score
        $applications = $shift->applications->sortByDesc('match_score');

        return view('business.applications', compact('shift', 'applications'));
    }

    /**
     * Assign a worker to a shift (accept application).
     */
    public function assignWorker(Request $request, $applicationId)
    {
        $application = ShiftApplication::with('shift')->findOrFail($applicationId);
        $shift = $application->shift;

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only assign workers to your own shifts.');
        }

        // Check if shift is already full
        if ($shift->isFull()) {
            return redirect()->back()
                ->with('error', 'This shift is already fully staffed.');
        }

        // Create shift assignment
        $assignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $application->worker_id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'payment_status' => 'pending',
        ]);

        // Update application status
        $application->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Increment filled workers count
        $shift->increment('filled_workers');

        // Update shift status if now full
        if ($shift->fresh()->isFull()) {
            $shift->update([
                'status' => 'assigned',
                'filled_at' => now(),
            ]);
        }

        // Hold payment in escrow
        $escrowResult = $this->paymentService->holdInEscrow($assignment);

        if (! $escrowResult) {
            // If escrow fails, revert assignment
            $assignment->delete();
            $application->update(['status' => 'pending']);
            $shift->decrement('filled_workers');

            return redirect()->back()
                ->with('error', 'Payment processing failed. Please ensure you have a valid payment method on file.');
        }

        // Send notification to worker about assignment
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyShiftAssigned($assignment);

        return redirect()->back()
            ->with('success', 'Worker assigned successfully! Payment has been held in escrow.');
    }

    /**
     * Unassign a worker from a shift.
     */
    public function unassignWorker($assignmentId)
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);
        $shift = $assignment->shift;

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only manage your own shifts.');
        }

        // Can't unassign if shift already started
        if ($assignment->status === 'checked_in' || $assignment->status === 'completed') {
            return redirect()->back()
                ->with('error', 'Cannot unassign worker who has already checked in.');
        }

        // Update assignment
        $assignment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Decrement filled workers
        $shift->decrement('filled_workers');

        // Update shift status if it was assigned
        if ($shift->status === 'assigned' && ! $shift->isFull()) {
            $shift->update(['status' => 'open']);
        }

        // Refund escrowed payment if applicable
        $shiftPayment = $assignment->payment;
        if ($shiftPayment && $shiftPayment->status === 'in_escrow') {
            $this->paymentService->refundToBusiness($shiftPayment, $shiftPayment->amount_gross);
        }

        // Notify worker about cancellation
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyShiftCancelled($assignment, 'Assignment cancelled by business');

        return redirect()->back()
            ->with('success', 'Worker unassigned successfully. Payment refunded if applicable.');
    }

    /**
     * Invite a specific worker to apply to a shift.
     */
    public function inviteWorker(Request $request, $shiftId)
    {
        $shift = Shift::findOrFail($shiftId);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only invite workers to your own shifts.');
        }

        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $worker = User::findOrFail($request->worker_id);

        // Verify worker is a worker
        if (! $worker->isWorker()) {
            return redirect()->back()
                ->with('error', 'Invalid worker selected.');
        }

        // Check if invitation already exists
        $existingInvitation = $shift->invitations()
            ->where('worker_id', $worker->id)
            ->first();

        if ($existingInvitation) {
            return redirect()->back()
                ->with('error', 'You have already invited this worker.');
        }

        // Create invitation
        $invitation = $shift->invitations()->create([
            'worker_id' => $worker->id,
            'invited_by' => Auth::id(),
            'message' => $request->message,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        // Send notification to worker about invitation
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyShiftInvitation($invitation);

        return redirect()->back()
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Mark a shift as started.
     */
    public function markShiftStarted($shiftId)
    {
        $shift = Shift::findOrFail($shiftId);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only manage your own shifts.');
        }

        // Verify shift is assigned
        if ($shift->status !== 'assigned') {
            return redirect()->back()
                ->with('error', 'Only assigned shifts can be marked as started.');
        }

        // Update shift status
        $shift->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Notify assigned workers that shift has started
        $shift->load('assignments.worker');
        foreach ($shift->assignments as $assignment) {
            if (in_array($assignment->status, ['assigned', 'checked_in']) && $assignment->worker) {
                $assignment->worker->notify(new \App\Notifications\ShiftStartedNotification($shift));
            }
        }

        return redirect()->back()
            ->with('success', 'Shift marked as started.');
    }

    /**
     * Mark a shift as completed.
     */
    public function markShiftCompleted(Request $request, $shiftId)
    {
        $shift = Shift::findOrFail($shiftId);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only manage your own shifts.');
        }

        // Verify shift is in progress
        if ($shift->status !== 'in_progress') {
            return redirect()->back()
                ->with('error', 'Only in-progress shifts can be marked as completed.');
        }

        // Update shift status
        $shift->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update all assignments to completed
        foreach ($shift->assignments as $assignment) {
            if ($assignment->status !== 'no_show') {
                $assignment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Release payment from escrow (15-minute timer starts)
                $this->paymentService->releaseFromEscrow($assignment);
            }
        }

        return redirect()->back()
            ->with('success', 'Shift marked as completed. Workers will receive payment in 15 minutes.');
    }

    /**
     * Mark a worker as no-show.
     */
    public function markNoShow($shiftId, $assignmentId)
    {
        $shift = Shift::findOrFail($shiftId);
        $assignment = ShiftAssignment::findOrFail($assignmentId);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only manage your own shifts.');
        }

        // Update assignment
        $assignment->update([
            'status' => 'no_show',
        ]);

        // Refund escrowed payment
        $shiftPayment = $assignment->payment;
        if ($shiftPayment && $shiftPayment->status === 'in_escrow') {
            $this->paymentService->refundToBusiness($shiftPayment, $shiftPayment->amount_gross);
        }

        // Update worker's no-show count
        $workerProfile = $assignment->worker->workerProfile;
        if ($workerProfile) {
            $workerProfile->increment('total_no_shows');
            $workerProfile->updateReliabilityScore();
        }

        return redirect()->back()
            ->with('success', 'Worker marked as no-show. Payment has been refunded.');
    }

    /**
     * Reject an application.
     */
    public function rejectApplication($applicationId)
    {
        $application = ShiftApplication::with('shift')->findOrFail($applicationId);
        $shift = $application->shift;

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only manage applications for your own shifts.');
        }

        $application->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        // Notify worker about rejection
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyApplicationRejected($application);

        return redirect()->back()
            ->with('success', 'Application rejected.');
    }

    /**
     * View shift analytics and reports.
     */
    public function analytics(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isBusiness() && ! Auth::user()->isAgency()) {
            abort(403, 'Only businesses and agencies can access analytics.');
        }

        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $shifts = Shift::where('business_id', Auth::id())
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->with('assignments')
            ->get();

        $analytics = [
            'total_shifts' => $shifts->count(),
            'completed_shifts' => $shifts->where('status', 'completed')->count(),
            'cancelled_shifts' => $shifts->where('status', 'cancelled')->count(),
            'total_workers_hired' => $shifts->sum('filled_workers'),
            'fill_rate' => $shifts->count() > 0
                ? round(($shifts->where('status', '!=', 'cancelled')->sum('filled_workers') /
                   $shifts->where('status', '!=', 'cancelled')->sum('required_workers')) * 100, 2)
                : 0,
            'average_fill_time' => $this->calculateAverageFillTime($shifts),
            'total_spent' => $this->calculateTotalSpent($shifts),
            'no_show_rate' => $this->calculateNoShowRate($shifts),
        ];

        return view('business.shifts.analytics', compact('analytics', 'startDate', 'endDate'));
    }

    protected function calculateAverageFillTime($shifts)
    {
        $filledShifts = $shifts->where('status', '!=', 'cancelled')->whereNotNull('filled_at');

        if ($filledShifts->count() === 0) {
            return 0;
        }

        $totalMinutes = $filledShifts->sum(function ($shift) {
            return Carbon::parse($shift->created_at)->diffInMinutes($shift->filled_at);
        });

        $count = $filledShifts->count();

        return $count > 0 ? round($totalMinutes / $count / 60, 1) : 0; // Convert to hours
    }

    protected function calculateTotalSpent($shifts)
    {
        $total = 0;
        foreach ($shifts->where('status', 'completed') as $shift) {
            foreach ($shift->assignments as $assignment) {
                if ($assignment->payment) {
                    $total += $assignment->payment->amount_gross;
                }
            }
        }

        return $total;
    }

    protected function calculateNoShowRate($shifts)
    {
        $totalAssignments = 0;
        $noShows = 0;

        foreach ($shifts as $shift) {
            foreach ($shift->assignments as $assignment) {
                $totalAssignments++;
                if ($assignment->status === 'no_show') {
                    $noShows++;
                }
            }
        }

        return $totalAssignments > 0 ? round(($noShows / $totalAssignments) * 100, 2) : 0;
    }
}
