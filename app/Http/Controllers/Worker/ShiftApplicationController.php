<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBroadcast;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Services\FaceRecognitionService;
use App\Services\ShiftMatchingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShiftApplicationController extends Controller
{
    protected $matchingService;

    protected $faceRecognitionService;

    public function __construct(
        ShiftMatchingService $matchingService,
        FaceRecognitionService $faceRecognitionService
    ) {
        $this->middleware('auth');
        $this->matchingService = $matchingService;
        $this->faceRecognitionService = $faceRecognitionService;
    }

    /**
     * Worker dashboard - View all assignments and upcoming shifts.
     */
    public function dashboard()
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this page.');
        }

        // Get upcoming assignments
        $upcomingAssignments = ShiftAssignment::with(['shift.business'])
            ->where('worker_id', Auth::id())
            ->whereIn('status', ['assigned', 'checked_in'])
            ->whereHas('shift', function ($q) {
                $q->where('shift_date', '>=', Carbon::today());
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Get completed shifts (last 30 days)
        $completedShifts = ShiftAssignment::with(['shift.business', 'payment'])
            ->where('worker_id', Auth::id())
            ->where('status', 'completed')
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('completed_at', 'desc')
            ->get();

        // Get statistics
        $workerProfile = Auth::user()->workerProfile;
        $stats = [
            'upcoming_shifts' => $upcomingAssignments->count(),
            'completed_this_month' => $completedShifts->count(),
            'earnings_this_month' => $completedShifts->sum(function ($assignment) {
                return $assignment->payment ? $assignment->payment->amount_net : 0;
            }),
            'rating' => Auth::user()->rating_as_worker ?? 0,
            'reliability_score' => $workerProfile ? $workerProfile->reliability_score * 100 : 100,
            'pending_applications' => ShiftApplication::where('worker_id', Auth::id())
                ->where('status', 'pending')
                ->count(),
        ];

        return view('worker.dashboard', compact('upcomingAssignments', 'completedShifts', 'stats'));
    }

    /**
     * Apply to a shift.
     */
    public function apply(Request $request, $shiftId)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can apply to shifts.');
        }

        $shift = Shift::findOrFail($shiftId);

        // Verify shift is open
        if ($shift->status !== 'open') {
            return redirect()->back()
                ->with('error', 'This shift is no longer accepting applications.');
        }

        // Check if shift is full
        if ($shift->isFull()) {
            return redirect()->back()
                ->with('error', 'This shift is already fully staffed.');
        }

        // Check if already applied
        $existingApplication = ShiftApplication::where('shift_id', $shiftId)
            ->where('worker_id', Auth::id())
            ->first();

        if ($existingApplication) {
            return redirect()->back()
                ->with('error', 'You have already applied to this shift.');
        }

        // Check for conflicting shifts
        $conflictingAssignment = ShiftAssignment::where('worker_id', Auth::id())
            ->whereHas('shift', function ($q) use ($shift) {
                $q->where('shift_date', $shift->shift_date)
                    ->where(function ($query) use ($shift) {
                        $query->whereBetween('start_time', [$shift->start_time, $shift->end_time])
                            ->orWhereBetween('end_time', [$shift->start_time, $shift->end_time]);
                    });
            })
            ->whereIn('status', ['assigned', 'checked_in'])
            ->exists();

        if ($conflictingAssignment) {
            return redirect()->back()
                ->with('error', 'You already have a shift assigned during this time.');
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // ===== SL-003: Worker Application & Business Selection =====

        $worker = Auth::user();
        $workerProfile = $worker->workerProfile;

        // Calculate detailed match score components (SL-002)
        $skillScore = $this->calculateSkillScore($worker, $shift);
        $proximityScore = $this->calculateProximityScore($workerProfile, $shift);
        $reliabilityScore = $workerProfile ? $workerProfile->reliability_score : 0;
        $ratingScore = $this->calculateRatingScore($worker);
        $recencyScore = $this->calculateRecencyScore($worker);

        // Calculate overall match score
        $matchScore = $skillScore + $proximityScore + $reliabilityScore + $ratingScore + $recencyScore;

        // Calculate distance in kilometers
        $distanceKm = null;
        if ($workerProfile && $workerProfile->location_lat && $shift->location_lat) {
            $distanceMiles = $this->matchingService->calculateDistance(
                $workerProfile->location_lat,
                $workerProfile->location_lng,
                $shift->location_lat,
                $shift->location_lng
            );
            $distanceKm = $distanceMiles * 1.60934; // Convert miles to km
        }

        // Determine priority tier (Bronze/Silver/Gold/Platinum)
        $priorityTier = $workerProfile ? ($workerProfile->subscription_tier ?? 'bronze') : 'bronze';

        // Create application with full SL-003 data
        $application = ShiftApplication::create([
            'shift_id' => $shiftId,
            'worker_id' => Auth::id(),
            'cover_letter' => $request->cover_letter,
            'status' => 'pending',
            'applied_at' => now(),

            // SL-002: Match score components
            'match_score' => round($matchScore, 2),
            'skill_score' => round($skillScore, 2),
            'proximity_score' => round($proximityScore, 2),
            'reliability_score' => round($reliabilityScore, 2),
            'rating_score' => round($ratingScore, 2),
            'recency_score' => round($recencyScore, 2),
            'distance_km' => $distanceKm,

            // SL-003: Priority tier and tracking
            'priority_tier' => $priorityTier,
            'notification_sent_at' => now(), // Notification to business
            'application_source' => $request->header('User-Agent') ? 'mobile_app' : 'web',
            'device_type' => $this->detectDeviceType($request),
            'app_version' => $request->header('X-App-Version'),
        ]);

        // Update shift application counter
        $shift->increment('application_count');
        if ($shift->application_count === 1) {
            $shift->update(['first_application_at' => now()]);
        }
        $shift->update(['last_application_at' => now()]);

        // Rank applications by match score
        $this->rankApplications($shift);

        // Notify business about new application using NotificationService
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyApplicationReceived($application);

        return redirect()->back()
            ->with('success', "Application submitted successfully! Match score: {$matchScore}%")
            ->with('match_breakdown', [
                'skill' => round($skillScore, 1),
                'proximity' => round($proximityScore, 1),
                'reliability' => round($reliabilityScore, 1),
                'rating' => round($ratingScore, 1),
                'recency' => round($recencyScore, 1),
            ]);
    }

    /**
     * Withdraw an application.
     */
    public function withdraw($applicationId)
    {
        $application = ShiftApplication::findOrFail($applicationId);

        // Check authorization
        if ($application->worker_id !== Auth::id()) {
            abort(403, 'You can only withdraw your own applications.');
        }

        // Can only withdraw pending applications
        if ($application->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'You can only withdraw pending applications.');
        }

        $application->update([
            'status' => 'withdrawn',
            'responded_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Application withdrawn successfully.');
    }

    /**
     * View all applications.
     */
    public function myApplications(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can view applications.');
        }

        $status = $request->get('status', 'all');

        $query = ShiftApplication::with(['shift.business'])
            ->where('worker_id', Auth::id())
            ->orderBy('applied_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->get(); // Changed to get() to match view

        return view('worker.applications', compact('applications'));
    }

    /**
     * View a specific assignment.
     */
    public function showAssignment($id)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can view assignments.');
        }

        $assignment = ShiftAssignment::with(['shift.business', 'payment', 'shift.attachments'])
            ->where('worker_id', Auth::id())
            ->findOrFail($id);

        return view('worker.assignments.show', compact('assignment'));
    }

    /**
     * View all assignments.
     */
    public function myAssignments(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can view assignments.');
        }

        // Get status filter from request (view expects $status, not $filter)
        $status = $request->get('status', 'all');
        $filter = $request->get('filter', 'upcoming');

        $query = ShiftAssignment::with(['shift.business', 'payment'])
            ->where('worker_id', Auth::id());

        // Handle status-based filtering
        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            // Handle filter-based filtering (for backward compatibility)
            switch ($filter) {
                case 'upcoming':
                    $query->whereIn('status', ['assigned', 'checked_in'])
                        ->whereHas('shift', function ($q) {
                            $q->where('shift_date', '>=', Carbon::today());
                        })
                        ->orderBy('created_at', 'asc');
                    break;
                case 'completed':
                    $query->where('status', 'completed')
                        ->orderBy('completed_at', 'desc');
                    break;
                case 'all':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        }

        $assignments = $query->paginate(20);

        return view('worker.assignments.index', compact('assignments', 'filter', 'status'));
    }

    /**
     * Check in to a shift (clock in).
     * SL-005: Clock-In Verification Protocol
     */
    public function checkIn(Request $request, $assignmentId)
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);

        // Check authorization
        if ($assignment->worker_id !== Auth::id()) {
            abort(403, 'You can only check in to your own shifts.');
        }

        // Verify assignment is in assigned status
        if ($assignment->status !== 'assigned') {
            return redirect()->back()
                ->with('error', 'You can only check in to assigned shifts.');
        }

        $shift = $assignment->shift;
        $now = Carbon::now();
        $shiftStartTime = Carbon::parse($shift->shift_date.' '.$shift->start_time);

        // ===== SL-005: Clock-In Verification Protocol =====

        // Increment clock-in attempts
        $assignment->increment('clock_in_attempts');

        // 1. Time Window Verification
        $minutesUntilStart = $now->diffInMinutes($shiftStartTime, false);
        $earlyWindow = $shift->early_clockin_minutes ?? 15; // Allow 15 min early
        $lateGrace = $shift->late_grace_minutes ?? 10; // 10 min grace period

        if ($minutesUntilStart > $earlyWindow) {
            return redirect()->back()
                ->with('error', "Too early to clock in. You can clock in {$earlyWindow} minutes before shift start.");
        }

        // Calculate lateness
        $lateMinutes = 0;
        $wasLate = false;
        $latenessFlagged = false;

        if ($minutesUntilStart < 0) {
            $lateMinutes = abs($minutesUntilStart);
            $wasLate = true;

            if ($lateMinutes > 30) {
                $latenessFlagged = true; // Critical lateness
            }

            if ($lateMinutes > $lateGrace) {
                return redirect()->back()
                    ->with('error', "Too late to clock in. You are {$lateMinutes} minutes late. Please contact the business.");
            }
        }

        // 2. GPS Geofencing Verification
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer',
        ]);

        $geofenceRadius = $shift->geofence_radius ?? 100; // Default 100 meters
        $distance = $this->matchingService->calculateDistance(
            $request->latitude,
            $request->longitude,
            $shift->location_lat,
            $shift->location_lng
        );

        $distanceMeters = $distance * 1609.34; // Convert miles to meters

        if ($distanceMeters > $geofenceRadius) {
            $assignment->update([
                'clock_in_failure_reason' => "Outside geofence: {$distanceMeters}m away (max {$geofenceRadius}m)",
            ]);

            return redirect()->back()
                ->with('error', "You must be at the shift location to clock in. You are {$distanceMeters}m away.");
        }

        // 3. Face Recognition Verification (SL-005 - Real Implementation)
        $faceMatchConfidence = null;
        $livenessPassed = false;
        $verificationMethod = 'manual_override'; // Default
        $user = Auth::user();

        // Check if face recognition is enabled and user has a face image
        $faceRecognitionEnabled = $this->faceRecognitionService->isEnabled();
        $enrollmentStatus = $this->faceRecognitionService->getEnrollmentStatus($user);

        if ($faceRecognitionEnabled && $request->has('face_image')) {
            $verificationMethod = 'face_recognition';

            // Check if user is enrolled
            if (! $enrollmentStatus['enrolled']) {
                return redirect()->back()
                    ->with('error', 'Please complete face enrollment before clocking in.')
                    ->with('enrollment_required', true);
            }

            // Perform face verification using the real service
            $verification = $this->faceRecognitionService->verifyFace(
                $user,
                $request->face_image,
                $shift->id,
                $assignment->id,
                $request->latitude,
                $request->longitude
            );

            $faceMatchConfidence = $verification['confidence'];
            $livenessPassed = $verification['liveness'];

            // Check minimum confidence threshold
            $minConfidence = config('face_recognition.min_confidence', 85.0);

            if (! $verification['match'] || $faceMatchConfidence < $minConfidence) {
                $assignment->update([
                    'clock_in_failure_reason' => "Face recognition failed: {$faceMatchConfidence}% confidence (need {$minConfidence}%+)",
                ]);

                // Check if manual override is allowed
                if ($verification['allow_manual_override'] ?? false) {
                    return redirect()->back()
                        ->with('error', 'Face verification failed. Please contact your supervisor for manual verification.')
                        ->with('allow_manual_override', true)
                        ->with('verification_confidence', $faceMatchConfidence);
                }

                return redirect()->back()
                    ->with('error', $verification['error'] ?? 'Face recognition verification failed. Please try again with better lighting.');
            }

            if (! $livenessPassed && config('face_recognition.require_liveness', true)) {
                $assignment->update([
                    'clock_in_failure_reason' => 'Liveness detection failed',
                ]);

                return redirect()->back()
                    ->with('error', 'Liveness detection failed. Please ensure you are taking a live photo.');
            }

            // Store the verification photo path
            $assignment->clock_in_photo_url = $verification['verification_image'] ?? null;
        } elseif ($request->hasFile('selfie')) {
            // Legacy file upload support
            $request->validate([
                'selfie' => 'required|image|max:10240', // Max 10MB
            ]);

            $photoPath = $request->file('selfie')->store('clock-in-photos', 'public');
            $assignment->clock_in_photo_url = $photoPath;

            // If face recognition is enabled but no base64 image provided, try to use uploaded file
            if ($faceRecognitionEnabled && $enrollmentStatus['enrolled']) {
                $imageContent = file_get_contents($request->file('selfie')->getRealPath());
                $base64Image = base64_encode($imageContent);

                $verification = $this->faceRecognitionService->verifyFace(
                    $user,
                    $base64Image,
                    $shift->id,
                    $assignment->id,
                    $request->latitude,
                    $request->longitude
                );

                $faceMatchConfidence = $verification['confidence'];
                $livenessPassed = $verification['liveness'];
                $verificationMethod = 'face_recognition';

                $minConfidence = config('face_recognition.min_confidence', 85.0);

                if (! $verification['match'] || $faceMatchConfidence < $minConfidence) {
                    $assignment->update([
                        'clock_in_failure_reason' => "Face recognition failed: {$faceMatchConfidence}% confidence",
                    ]);

                    if ($verification['allow_manual_override'] ?? false) {
                        return redirect()->back()
                            ->with('error', 'Face verification failed. Contact supervisor for manual verification.')
                            ->with('allow_manual_override', true);
                    }

                    return redirect()->back()
                        ->with('error', $verification['error'] ?? 'Face recognition failed.');
                }
            } else {
                // Face recognition disabled or not enrolled - use photo for manual verification
                $verificationMethod = 'photo_manual';
                $faceMatchConfidence = null;
                $livenessPassed = null;
            }
        } elseif ($faceRecognitionEnabled && $shift->require_face_verification ?? false) {
            // Face verification required but no image provided
            return redirect()->back()
                ->with('error', 'Face verification is required for this shift. Please capture a selfie to clock in.');
        }

        // 4. Update Assignment with SL-005 Data
        $assignment->update([
            'status' => 'checked_in',
            'check_in_time' => $now,
            'actual_clock_in' => $now,
            'clock_in_lat' => $request->latitude,
            'clock_in_lng' => $request->longitude,
            'clock_in_accuracy' => $request->accuracy,
            'clock_in_verified' => true,
            'late_minutes' => $lateMinutes,
            'was_late' => $wasLate,
            'lateness_flagged' => $latenessFlagged,
            'face_match_confidence' => $faceMatchConfidence,
            'liveness_passed' => $livenessPassed,
            'verification_method' => $verificationMethod,
            'clock_in_failure_reason' => null, // Clear any previous failures
        ]);

        // 5. Update Shift Status
        if ($shift->status === 'assigned') {
            $shift->update([
                'status' => 'in_progress',
                'started_at' => $now,
            ]);
        }

        if (! $shift->first_worker_clocked_in_at) {
            $shift->update(['first_worker_clocked_in_at' => $now]);
        }

        // 6. Update Worker Reliability Score for Lateness
        if ($wasLate) {
            $workerProfile = Auth::user()->workerProfile;
            if ($workerProfile) {
                if ($latenessFlagged) {
                    $workerProfile->decrement('reliability_score', 5); // -5 points for critical lateness
                } else {
                    $workerProfile->decrement('reliability_score', 2); // -2 points for minor lateness
                }
                $workerProfile->increment('total_late_arrivals');
            }
        }

        $message = $wasLate
            ? "Checked in successfully! Note: You were {$lateMinutes} minutes late."
            : 'Checked in successfully! Have a great shift.';

        return redirect()->back()
            ->with('success', $message)
            ->with('verification_details', [
                'method' => $verificationMethod,
                'location_verified' => true,
                'face_confidence' => $faceMatchConfidence,
                'liveness' => $livenessPassed,
            ]);
    }

    /**
     * Check out from a shift (clock out).
     * SL-006: Break Enforcement & Compliance
     * SL-007: Clock-Out & Shift Completion
     */
    public function checkOut(Request $request, $assignmentId)
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);

        // Check authorization
        if ($assignment->worker_id !== Auth::id()) {
            abort(403, 'You can only check out from your own shifts.');
        }

        // Verify assignment is checked in
        if ($assignment->status !== 'checked_in') {
            return redirect()->back()
                ->with('error', 'You must be checked in to check out.');
        }

        $shift = $assignment->shift;
        $now = Carbon::now();
        $checkInTime = Carbon::parse($assignment->actual_clock_in);

        // ===== SL-006: Break Enforcement & Compliance =====

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'breaks' => 'nullable|array',
            'breaks.*.start' => 'required|date',
            'breaks.*.end' => 'required|date|after:breaks.*.start',
            'breaks.*.type' => 'required|in:meal,rest',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        // Process breaks
        $breaks = $request->input('breaks', []);
        $totalBreakMinutes = 0;
        $processedBreaks = [];

        foreach ($breaks as $break) {
            $breakStart = Carbon::parse($break['start']);
            $breakEnd = Carbon::parse($break['end']);
            $breakDuration = $breakStart->diffInMinutes($breakEnd);

            $processedBreaks[] = [
                'start' => $breakStart->toDateTimeString(),
                'end' => $breakEnd->toDateTimeString(),
                'duration' => $breakDuration,
                'type' => $break['type'],
                'compliant' => true, // Assume compliant if reported
            ];

            $totalBreakMinutes += $breakDuration;
        }

        // Check mandatory break compliance (jurisdiction-specific)
        $grossHours = $checkInTime->diffInHours($now, true);
        $mandatoryBreakRequired = $grossHours >= 6; // Example: 6+ hour shifts require break
        $mandatoryBreakTaken = $totalBreakMinutes >= 30; // Example: Minimum 30 min break
        $breakComplianceMet = ! $mandatoryBreakRequired || $mandatoryBreakTaken;

        if ($mandatoryBreakRequired && ! $mandatoryBreakTaken) {
            return redirect()->back()
                ->with('warning', 'You worked over 6 hours but did not take a mandatory 30-minute break. Please confirm with supervisor before clocking out.');
        }

        // ===== SL-007: Clock-Out & Shift Completion =====

        // GPS Verification (if provided)
        if ($request->has('latitude') && $request->has('longitude')) {
            $assignment->clock_out_lat = $request->latitude;
            $assignment->clock_out_lng = $request->longitude;
        }

        // Upload clock-out photo (if provided)
        if ($request->hasFile('clock_out_selfie')) {
            $request->validate([
                'clock_out_selfie' => 'required|image|max:10240',
            ]);

            $photoPath = $request->file('clock_out_selfie')->store('clock-out-photos', 'public');
            $assignment->clock_out_photo_url = $photoPath;
        }

        // Calculate hours
        $grossHours = $checkInTime->diffInHours($now, true);
        $breakDeductionHours = $totalBreakMinutes / 60;
        $netHoursWorked = $grossHours - $breakDeductionHours;

        // Calculate billable hours (capped at scheduled + small buffer)
        $scheduledHours = $shift->duration_hours;
        $overtimeBuffer = 0.5; // Allow 30 min overtime without approval
        $maxBillableWithoutApproval = $scheduledHours + $overtimeBuffer;
        $billableHours = min($netHoursWorked, $maxBillableWithoutApproval);

        // Detect overtime
        $overtimeHours = max(0, $netHoursWorked - $scheduledHours);
        $overtimeWorked = $overtimeHours > 0;
        $overtimeApproved = $overtimeHours <= $overtimeBuffer; // Auto-approve small overtime

        // Detect early departure
        $earlyDeparture = false;
        $earlyDepartureMinutes = 0;
        $shiftEndTime = Carbon::parse($shift->shift_date.' '.$shift->end_time);

        if ($now->isBefore($shiftEndTime)) {
            $earlyDeparture = true;
            $earlyDepartureMinutes = $now->diffInMinutes($shiftEndTime);
        }

        // Update assignment with SL-006 & SL-007 data
        $assignment->update([
            'status' => 'checked_out',
            'check_out_time' => $now,
            'actual_clock_out' => $now,
            'completion_notes' => $request->completion_notes,

            // SL-006: Break data
            'breaks' => json_encode($processedBreaks),
            'total_break_minutes' => $totalBreakMinutes,
            'mandatory_break_taken' => $mandatoryBreakTaken,
            'break_compliance_met' => $breakComplianceMet,

            // SL-007: Hours calculations
            'hours_worked' => $grossHours, // Legacy field
            'gross_hours' => $grossHours,
            'break_deduction_hours' => $breakDeductionHours,
            'net_hours_worked' => $netHoursWorked,
            'billable_hours' => $billableHours,
            'overtime_hours' => $overtimeHours,
            'overtime_worked' => $overtimeWorked,
            'overtime_approved' => $overtimeApproved,

            // Early departure tracking
            'early_departure' => $earlyDeparture,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'early_departure_reason' => $request->early_departure_reason,

            // Payment status
            'payment_status' => 'pending_verification', // Business must verify
        ]);

        // Update shift status
        if (! $shift->last_worker_clocked_out_at || $now->isAfter($shift->last_worker_clocked_out_at)) {
            $shift->update(['last_worker_clocked_out_at' => $now]);
        }

        // Check if all workers have clocked out
        $allClockedOut = $shift->assignments()
            ->whereIn('status', ['assigned', 'checked_in'])
            ->count() === 0;

        if ($allClockedOut) {
            $shift->update([
                'status' => 'completed',
                'completed_at' => $now,
            ]);
        }

        // Update worker statistics
        $workerProfile = Auth::user()->workerProfile;
        if ($workerProfile) {
            if ($earlyDeparture && $earlyDepartureMinutes > 30) {
                $workerProfile->increment('total_early_departures');
                $workerProfile->decrement('reliability_score', 3); // -3 points for significant early departure
            }

            if (! $breakComplianceMet) {
                $workerProfile->decrement('reliability_score', 2); // -2 points for break non-compliance
            }
        }

        $message = "Checked out successfully!\n";
        $message .= 'Gross Hours: '.number_format($grossHours, 2)."\n";
        $message .= 'Break Time: '.number_format($breakDeductionHours, 2)." hours\n";
        $message .= 'Net Hours: '.number_format($netHoursWorked, 2)."\n";
        $message .= 'Billable Hours: '.number_format($billableHours, 2)."\n";

        if ($overtimeWorked && ! $overtimeApproved) {
            $message .= "\nNote: ".number_format($overtimeHours, 2).' hours of overtime requires business approval.';
        }

        if ($earlyDeparture) {
            $message .= "\nNote: You left {$earlyDepartureMinutes} minutes early.";
        }

        $message .= "\n\nPayment will be processed after business verification.";

        return redirect()->back()
            ->with('success', $message)
            ->with('hours_breakdown', [
                'gross' => $grossHours,
                'breaks' => $breakDeductionHours,
                'net' => $netHoursWorked,
                'billable' => $billableHours,
                'overtime' => $overtimeHours,
            ]);
    }

    /**
     * Respond to a shift invitation.
     */
    public function respondToInvitation(Request $request, $invitationId)
    {
        $invitation = \App\Models\ShiftInvitation::with('shift')->findOrFail($invitationId);

        // Check authorization
        if ($invitation->worker_id !== Auth::id()) {
            abort(403, 'You can only respond to your own invitations.');
        }

        $request->validate([
            'response' => 'required|in:accept,decline',
        ]);

        if ($request->response === 'accept') {
            // Apply to the shift
            $this->apply($request, $invitation->shift_id);
            $invitation->accept();

            return redirect()->route('worker.applications')
                ->with('success', 'Invitation accepted! Your application has been submitted.');
        } else {
            $invitation->decline();

            return redirect()->back()
                ->with('success', 'Invitation declined.');
        }
    }

    /**
     * Broadcast availability (worker is available for immediate shifts).
     */
    public function broadcastAvailability(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can broadcast availability.');
        }

        $validator = Validator::make($request->all(), [
            'broadcast_type' => 'required|in:immediate,today,this_week',
            'industries' => 'required|array',
            'max_distance' => 'required|integer|min:1|max:100',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Calculate availability window
        $availableFrom = now();
        $availableTo = match ($request->broadcast_type) {
            'immediate' => now()->addHours(4),
            'today' => Carbon::today()->endOfDay(),
            'this_week' => Carbon::today()->endOfWeek(),
        };

        // Cancel any existing active broadcasts
        AvailabilityBroadcast::where('worker_id', Auth::id())
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        // Create new broadcast
        $broadcast = AvailabilityBroadcast::create([
            'worker_id' => Auth::id(),
            'broadcast_type' => $request->broadcast_type,
            'available_from' => $availableFrom,
            'available_to' => $availableTo,
            'industries' => $request->industries,
            'max_distance' => $request->max_distance,
            'message' => $request->message,
            'status' => 'active',
        ]);

        // Notify matching businesses about worker availability
        $worker = Auth::user();
        $matchingBusinesses = \App\Models\User::where('user_type', 'business')
            ->whereHas('businessProfile', function ($query) use ($broadcast) {
                if (! empty($broadcast->industries)) {
                    $query->whereJsonContains('industries', $broadcast->industries[0]);
                    foreach (array_slice($broadcast->industries, 1) as $industry) {
                        $query->orWhereJsonContains('industries', $industry);
                    }
                }
            })
            ->limit(50)
            ->get();

        foreach ($matchingBusinesses as $business) {
            $business->notify(new \App\Notifications\WorkerAvailabilityBroadcastNotification($broadcast, $worker));
        }

        return redirect()->back()
            ->with('success', 'Availability broadcasted! Businesses will be notified.');
    }

    /**
     * Cancel availability broadcast.
     */
    public function cancelBroadcast($broadcastId)
    {
        $broadcast = AvailabilityBroadcast::findOrFail($broadcastId);

        // Check authorization
        if ($broadcast->worker_id !== Auth::id()) {
            abort(403, 'You can only cancel your own broadcasts.');
        }

        $broadcast->cancel();

        return redirect()->back()
            ->with('success', 'Availability broadcast cancelled.');
    }

    /**
     * View earnings and payment history.
     */
    public function earnings(Request $request)
    {
        // Check authorization
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can view earnings.');
        }

        $period = $request->get('period', 'this_month');

        $query = ShiftAssignment::with(['shift', 'payment'])
            ->where('worker_id', Auth::id())
            ->where('status', 'completed');

        switch ($period) {
            case 'this_week':
                $query->where('completed_at', '>=', Carbon::now()->startOfWeek());
                break;
            case 'this_month':
                $query->where('completed_at', '>=', Carbon::now()->startOfMonth());
                break;
            case 'this_year':
                $query->where('completed_at', '>=', Carbon::now()->startOfYear());
                break;
            case 'all_time':
                // No filter
                break;
        }

        $completedShifts = $query->orderBy('completed_at', 'desc')->get();

        $earnings = [
            'total' => $completedShifts->sum(function ($assignment) {
                return $assignment->payment ? $assignment->payment->amount_net : 0;
            }),
            'shifts_count' => $completedShifts->count(),
            'hours_worked' => $completedShifts->sum('hours_worked'),
            'average_per_shift' => $completedShifts->count() > 0
                ? $completedShifts->avg(function ($assignment) {
                    return $assignment->payment ? $assignment->payment->amount_net : 0;
                })
                : 0,
            'average_hourly' => $completedShifts->sum('hours_worked') > 0
                ? $completedShifts->sum(function ($assignment) {
                    return $assignment->payment ? $assignment->payment->amount_net : 0;
                }) / $completedShifts->sum('hours_worked')
                : 0,
        ];

        return view('worker.earnings', compact('completedShifts', 'earnings', 'period'));
    }

    // ===== SL-003: Helper Methods =====

    /**
     * Calculate skill match score (0-40 points).
     */
    protected function calculateSkillScore($worker, $shift)
    {
        $workerSkills = $worker->skills()->pluck('skill_name')->toArray();
        $shiftRequiredSkills = $shift->required_skills ?? [];

        if (empty($shiftRequiredSkills)) {
            return 40; // No specific skills required
        }

        $matchedSkills = array_intersect($workerSkills, $shiftRequiredSkills);
        $matchPercentage = count($matchedSkills) / count($shiftRequiredSkills);

        return $matchPercentage * 40;
    }

    /**
     * Calculate proximity score (0-25 points).
     */
    protected function calculateProximityScore($workerProfile, $shift)
    {
        if (! $workerProfile || ! $workerProfile->location_lat || ! $shift->location_lat) {
            return 15; // Neutral score if no location data
        }

        $distance = $this->matchingService->calculateDistance(
            $workerProfile->location_lat,
            $workerProfile->location_lng,
            $shift->location_lat,
            $shift->location_lng
        );

        // Score based on distance
        if ($distance <= 5) {
            return 25; // Very close (<5 miles)
        } elseif ($distance <= 10) {
            return 20; // Close (5-10 miles)
        } elseif ($distance <= 25) {
            return 15; // Reasonable (10-25 miles)
        } elseif ($distance <= 50) {
            return 10; // Far but within range (25-50 miles)
        } else {
            return 0; // Too far (>50 miles)
        }
    }

    /**
     * Calculate rating score (0-5 points).
     */
    protected function calculateRatingScore($worker)
    {
        $rating = $worker->rating_as_worker ?? 0;

        if ($rating >= 4.5) {
            return 5;
        } elseif ($rating >= 4.0) {
            return 4;
        } elseif ($rating >= 3.5) {
            return 3;
        } elseif ($rating >= 3.0) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * Calculate recency score (0-10 points) based on recent activity.
     */
    protected function calculateRecencyScore($worker)
    {
        // Get worker's last completed shift
        $lastShift = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        if (! $lastShift) {
            return 5; // New worker, neutral score
        }

        $daysSinceLastShift = Carbon::parse($lastShift->completed_at)->diffInDays(now());

        if ($daysSinceLastShift <= 7) {
            return 10; // Very active (within last week)
        } elseif ($daysSinceLastShift <= 30) {
            return 8; // Active (within last month)
        } elseif ($daysSinceLastShift <= 90) {
            return 6; // Moderately active (within 3 months)
        } elseif ($daysSinceLastShift <= 180) {
            return 4; // Less active (within 6 months)
        } else {
            return 2; // Inactive (over 6 months)
        }
    }

    /**
     * Detect device type from request headers.
     */
    protected function detectDeviceType($request)
    {
        $userAgent = $request->header('User-Agent');

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'iOS';
        } elseif (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        } else {
            return 'web';
        }
    }

    /**
     * Rank all applications for a shift by match score.
     */
    protected function rankApplications($shift)
    {
        $applications = ShiftApplication::where('shift_id', $shift->id)
            ->where('status', 'pending')
            ->orderBy('match_score', 'desc')
            ->orderBy('applied_at', 'asc') // Tie-breaker: earlier application wins
            ->get();

        $rank = 1;
        foreach ($applications as $application) {
            $application->rank_position = $rank;
            $application->save();
            $rank++;
        }
    }
}
