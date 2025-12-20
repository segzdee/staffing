<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Models\ShiftAssignment;
use App\Services\TimeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Time Tracking API Controller
 *
 * Handles clock-in/out and break operations for workers.
 */
class TimeTrackingController extends Controller
{
    public function __construct(protected TimeTrackingService $timeTrackingService)
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Get worker's active shift assignment.
     *
     * GET /api/worker/shifts/active
     */
    public function getActiveShift(Request $request): JsonResponse
    {
        $user = Auth::user();

        $assignment = ShiftAssignment::with(['shift.venue', 'shift.business'])
            ->where('worker_id', $user->id)
            ->whereIn('status', ['assigned', 'checked_in'])
            ->whereHas('shift', function ($query) {
                $query->whereDate('shift_date', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->whereDate('shift_date', now()->subDay()->toDateString())
                            ->where('end_time', '<', 'start_time'); // Overnight shift
                    });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $assignment) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active shift found',
            ]);
        }

        $breakStatus = $this->timeTrackingService->getBreakStatus($assignment);

        return response()->json([
            'success' => true,
            'data' => [
                'assignment_id' => $assignment->id,
                'shift_id' => $assignment->shift_id,
                'status' => $assignment->status,
                'is_clocked_in' => ! empty($assignment->actual_clock_in) || ! empty($assignment->check_in_time),
                'clock_in_time' => $assignment->actual_clock_in ?? $assignment->check_in_time,
                'shift' => [
                    'id' => $assignment->shift->id,
                    'date' => $assignment->shift->shift_date,
                    'start_time' => $assignment->shift->start_time,
                    'end_time' => $assignment->shift->end_time,
                    'duration_hours' => $assignment->shift->duration_hours,
                    'venue' => [
                        'name' => $assignment->shift->venue?->name,
                        'address' => $assignment->shift->venue?->address,
                        'latitude' => $assignment->shift->venue?->latitude,
                        'longitude' => $assignment->shift->venue?->longitude,
                        'geofence_radius' => $assignment->shift->venue?->geofence_radius ?? 100,
                    ],
                    'business_name' => $assignment->shift->business?->businessProfile?->business_name,
                ],
                'break_status' => $breakStatus,
                'hours_worked' => $assignment->getHoursWorkedSinceClockIn(),
            ],
        ]);
    }

    /**
     * Get tracking status for a specific assignment.
     *
     * GET /api/worker/shifts/{assignment}/status
     */
    public function getTrackingStatus(ShiftAssignment $assignment): JsonResponse
    {
        $user = Auth::user();

        // Verify ownership
        if ($assignment->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        $breakStatus = $this->timeTrackingService->getBreakStatus($assignment);

        return response()->json([
            'success' => true,
            'data' => [
                'assignment_id' => $assignment->id,
                'status' => $assignment->status,
                'is_clocked_in' => ! empty($assignment->actual_clock_in) || ! empty($assignment->check_in_time),
                'clock_in_time' => $assignment->actual_clock_in ?? $assignment->check_in_time,
                'clock_out_time' => $assignment->actual_clock_out ?? $assignment->check_out_time,
                'hours_worked' => $assignment->net_hours_worked ?? $assignment->hours_worked ?? 0,
                'gross_hours' => $assignment->gross_hours ?? 0,
                'break_deduction_hours' => $assignment->break_deduction_hours ?? 0,
                'break_status' => $breakStatus,
                'late_minutes' => $assignment->late_minutes ?? 0,
                'was_late' => $assignment->was_late ?? false,
                'early_departure' => $assignment->early_departure ?? false,
                'early_departure_minutes' => $assignment->early_departure_minutes ?? 0,
                'overtime_worked' => $assignment->overtime_worked ?? false,
                'overtime_hours' => $assignment->overtime_hours ?? 0,
            ],
        ]);
    }

    /**
     * Clock in to a shift.
     *
     * POST /api/worker/shifts/{assignment}/clock-in
     */
    public function clockIn(ClockInRequest $request, ShiftAssignment $assignment): JsonResponse
    {
        $user = Auth::user();

        // Verify ownership
        if ($assignment->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        // Check if already clocked in
        if ($assignment->actual_clock_in || $assignment->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked in to this shift',
                'code' => 'ALREADY_CLOCKED_IN',
            ], 409);
        }

        $validated = $request->validated();

        // Build verification data
        $verificationData = [
            'location' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
            ],
            'device_info' => [
                'device_id' => $validated['device_id'] ?? null,
                'device_type' => $validated['device_type'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
            ],
            'timezone' => $validated['timezone'] ?? config('app.timezone'),
        ];

        // Add face data if provided
        if (! empty($validated['photo'])) {
            $verificationData['face_data'] = [
                'image' => $validated['photo'],
            ];
        }

        // Add QR code if provided
        if (! empty($validated['qr_code'])) {
            $verificationData['qr_code'] = $validated['qr_code'];
        }

        // Add supervisor code if provided
        if (! empty($validated['supervisor_code'])) {
            $verificationData['supervisor_code'] = $validated['supervisor_code'];
        }

        $result = $this->timeTrackingService->processClockIn($assignment, $verificationData);

        if (! $result['success']) {
            $statusCode = match ($result['code'] ?? 'ERROR') {
                'TIME_RESTRICTION' => 400,
                'IDENTITY_FAILED' => 401,
                'LOCATION_FAILED' => 400,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'code' => $result['code'] ?? 'ERROR',
                'details' => $result['details'] ?? null,
                'suggestion' => $result['suggestion'] ?? null,
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'time_record_id' => $result['time_record_id'],
                'on_time_status' => $result['on_time_status'],
                'verified_at' => $result['verified_at'],
            ],
        ]);
    }

    /**
     * Clock out from a shift.
     *
     * POST /api/worker/shifts/{assignment}/clock-out
     */
    public function clockOut(ClockOutRequest $request, ShiftAssignment $assignment): JsonResponse
    {
        $user = Auth::user();

        // Verify ownership
        if ($assignment->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        // Check if clocked in
        if (! $assignment->actual_clock_in && ! $assignment->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Must clock in before clocking out',
                'code' => 'NOT_CLOCKED_IN',
            ], 400);
        }

        // Check if already clocked out
        if ($assignment->actual_clock_out || $assignment->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked out from this shift',
                'code' => 'ALREADY_CLOCKED_OUT',
            ], 409);
        }

        $validated = $request->validated();

        // Build verification data
        $verificationData = [
            'device_info' => [
                'device_id' => $validated['device_id'] ?? null,
            ],
            'manual_reason' => $validated['early_departure_reason'] ?? $validated['notes'] ?? null,
        ];

        // Add location if provided
        if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
            $verificationData['location'] = [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
            ];
        }

        $result = $this->timeTrackingService->processClockOut($assignment, $verificationData);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'code' => $result['code'] ?? 'ERROR',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'time_record_id' => $result['time_record_id'],
                'actual_hours' => $result['actual_hours'],
                'overtime_minutes' => $result['overtime_minutes'],
                'early_departure' => $result['early_departure'],
                'verified_at' => $result['verified_at'],
            ],
        ]);
    }

    /**
     * Start a break.
     *
     * POST /api/worker/shifts/{assignment}/break/start
     */
    public function startBreak(ShiftAssignment $assignment): JsonResponse
    {
        $user = Auth::user();

        // Verify ownership
        if ($assignment->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        $result = $this->timeTrackingService->processBreakStart($assignment);

        if (! $result['success']) {
            $statusCode = match ($result['code'] ?? 'ERROR') {
                'NOT_CLOCKED_IN' => 400,
                'ALREADY_ON_BREAK' => 409,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'code' => $result['code'] ?? 'ERROR',
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'started_at' => $result['started_at'],
            ],
        ]);
    }

    /**
     * End a break.
     *
     * POST /api/worker/shifts/{assignment}/break/end
     */
    public function endBreak(ShiftAssignment $assignment): JsonResponse
    {
        $user = Auth::user();

        // Verify ownership
        if ($assignment->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        $result = $this->timeTrackingService->processBreakEnd($assignment);

        if (! $result['success']) {
            $statusCode = match ($result['code'] ?? 'ERROR') {
                'NOT_ON_BREAK' => 400,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'code' => $result['code'] ?? 'ERROR',
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'ended_at' => $result['ended_at'],
                'break_duration_minutes' => $result['break_duration_minutes'],
                'total_break_minutes' => $result['total_break_minutes'],
                'mandatory_break_taken' => $result['mandatory_break_taken'],
            ],
        ]);
    }
}
