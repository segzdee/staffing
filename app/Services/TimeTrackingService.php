<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\TimeTrackingRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Time Tracking Service
 *
 * Handles real-time clock-in/clock-out with biometric verification
 * Supports GPS geofencing, face recognition, and liveness detection
 *
 * SL-005: Clock-In Verification Protocol
 * SL-007: Clock-Out & Shift Completion
 */
class TimeTrackingService
{
    /**
     * Multi-factor verification sequence for clock-in
     */
    public function processClockIn(ShiftAssignment $assignment, array $verificationData): array
    {
        try {
            $shift = $assignment->shift;
            $worker = $assignment->worker;

            // Check if already clocked in FIRST (before time validation)
            if ($assignment->actual_clock_in || $assignment->check_in_time || $assignment->status === 'checked_in') {
                return [
                    'success' => false,
                    'error' => 'Already clocked in to this shift',
                    'code' => 'ALREADY_CLOCKED_IN',
                ];
            }

            // Validate time window
            $timeValidation = $this->validateClockInTime($shift);
            if (! $timeValidation['allowed']) {
                return [
                    'success' => false,
                    'error' => $timeValidation['message'],
                    'code' => 'TIME_RESTRICTION',
                ];
            }

            // Initialize verification sequence
            $verificationResults = [];

            // Step 1: Identity Verification (Biometric)
            if (isset($verificationData['face_data'])) {
                $identityResult = $this->verifyIdentity($worker, $verificationData['face_data']);
                $verificationResults['identity'] = $identityResult;

                if (! $identityResult['verified']) {
                    return [
                        'success' => false,
                        'error' => 'Identity verification failed',
                        'details' => $identityResult['reason'],
                        'code' => 'IDENTITY_FAILED',
                    ];
                }
            }

            // Step 2: Location Verification (Geofencing)
            if (isset($verificationData['location'])) {
                $locationResult = $this->verifyLocation($shift, $verificationData['location']);
                $verificationResults['location'] = $locationResult;

                if (! $locationResult['verified']) {
                    return [
                        'success' => false,
                        'error' => 'Location verification failed',
                        'details' => $locationResult['message'],
                        'suggestion' => 'Move closer to the venue and try again',
                        'code' => 'LOCATION_FAILED',
                    ];
                }
            }

            // Step 3: Optional verification methods
            if (isset($verificationData['qr_code'])) {
                $qrResult = $this->verifyQRCode($shift, $verificationData['qr_code']);
                $verificationResults['qr_code'] = $qrResult;
            }

            if (isset($verificationData['supervisor_code'])) {
                $supervisorResult = $this->verifySupervisorCode($verificationData['supervisor_code']);
                $verificationResults['supervisor'] = $supervisorResult;
            }

            // Create time tracking record
            $timeRecord = TimeTrackingRecord::create([
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'shift_id' => $shift->id,
                'type' => 'clock_in',
                'verified_at' => now(),
                'verification_methods' => array_keys($verificationResults),
                'verification_results' => $verificationResults,
                'location_data' => $verificationData['location'] ?? null,
                'face_confidence' => $verificationResults['identity']['confidence'] ?? null,
                'device_info' => $verificationData['device_info'] ?? null,
                'timezone' => $verificationData['timezone'] ?? config('app.timezone'),
            ]);

            // Update shift status
            $shift->update([
                'status' => 'in_progress',
                'actual_start_time' => now(),
                'on_time_status' => $timeValidation['status'],
            ]);

            // Update assignment
            $assignment->update([
                'check_in_time' => now(),
                'actual_clock_in' => now(),
                'status' => 'checked_in',
                'clock_in_verified' => true,
                'late_minutes' => $timeValidation['minutes_late'] ?? 0,
                'was_late' => ($timeValidation['status'] ?? '') === 'late',
                'lateness_flagged' => ($timeValidation['status'] ?? '') === 'very_late',
            ]);

            Log::info('Worker clocked in successfully', [
                'worker_id' => $worker->id,
                'shift_id' => $shift->id,
                'verification_methods' => array_keys($verificationResults),
                'on_time' => $timeValidation['status'],
            ]);

            return [
                'success' => true,
                'message' => 'Successfully clocked in',
                'time_record_id' => $timeRecord->id,
                'on_time_status' => $timeValidation['status'],
                'verified_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Clock-in processing failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Clock-in failed',
                'code' => 'SYSTEM_ERROR',
            ];
        }
    }

    /**
     * Process clock-out with verification
     */
    public function processClockOut(ShiftAssignment $assignment, array $verificationData): array
    {
        try {
            $shift = $assignment->shift;
            $worker = $assignment->worker;

            // Verify clock-in exists
            if (! $assignment->check_in_time) {
                return [
                    'success' => false,
                    'error' => 'No clock-in record found',
                    'code' => 'NO_CLOCK_IN',
                ];
            }

            // Location verification for clock-out
            $locationResult = null;
            if (isset($verificationData['location'])) {
                $locationResult = $this->verifyClockOutLocation($shift, $verificationData['location']);

                if (! $locationResult['verified']) {
                    // Allow clock-out with warning
                    Log::warning('Clock-out location verification failed', [
                        'worker_id' => $worker->id,
                        'shift_id' => $shift->id,
                        'reason' => $locationResult['message'],
                    ]);
                }
            }

            // Calculate hours worked
            $timeCalculation = $this->calculateWorkedHours($assignment, $verificationData);

            // Check for early departure
            $earlyDeparture = $this->checkEarlyDeparture($shift, $verificationData['manual_reason'] ?? null);

            // Check for overtime
            $overtimeCalculation = $this->calculateOvertime($shift, $timeCalculation['actual_hours']);

            // Create clock-out record
            $timeRecord = TimeTrackingRecord::create([
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'shift_id' => $shift->id,
                'type' => 'clock_out',
                'verified_at' => now(),
                'verification_methods' => ['location'],
                'verification_results' => ['location' => $locationResult],
                'location_data' => $verificationData['location'] ?? null,
                'device_info' => $verificationData['device_info'] ?? null,
                'calculated_hours' => $timeCalculation['actual_hours'],
                'early_departure_minutes' => $earlyDeparture['minutes'] ?? 0,
                'early_departure_reason' => $earlyDeparture['reason'] ?? null,
                'overtime_minutes' => $overtimeCalculation['overtime_minutes'] ?? 0,
                'manual_reason' => $verificationData['manual_reason'] ?? null,
            ]);

            // Update assignment with comprehensive time data
            $assignment->update([
                'check_out_time' => now(),
                'actual_clock_out' => now(),
                'status' => 'checked_out',
                'hours_worked' => $timeCalculation['net_hours'],
                'gross_hours' => $timeCalculation['gross_hours'],
                'break_deduction_hours' => $timeCalculation['break_minutes'] / 60,
                'net_hours_worked' => $timeCalculation['net_hours'],
                'early_departure' => ($earlyDeparture['minutes'] ?? 0) > 0,
                'early_departure_minutes' => $earlyDeparture['minutes'] ?? 0,
                'early_departure_reason' => $earlyDeparture['reason'] ?? null,
                'overtime_worked' => ($overtimeCalculation['overtime_minutes'] ?? 0) > 0,
                'overtime_hours' => ($overtimeCalculation['overtime_minutes'] ?? 0) / 60,
            ]);

            // Update shift
            $shift->update([
                'actual_end_time' => now(),
                'actual_hours' => $timeCalculation['actual_hours'],
                'status' => 'pending_verification',
            ]);

            // Handle early departure penalties if needed
            if ($earlyDeparture['penalty_applies'] ?? false) {
                $this->applyEarlyDeparturePenalty($worker, $earlyDeparture);
            }

            Log::info('Worker clocked out successfully', [
                'worker_id' => $worker->id,
                'shift_id' => $shift->id,
                'actual_hours' => $timeCalculation['actual_hours'],
                'overtime_minutes' => $overtimeCalculation['overtime_minutes'] ?? 0,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully clocked out',
                'time_record_id' => $timeRecord->id,
                'actual_hours' => $timeCalculation['actual_hours'],
                'overtime_minutes' => $overtimeCalculation['overtime_minutes'] ?? 0,
                'early_departure' => $earlyDeparture['minutes'] > 0,
                'verified_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Clock-out processing failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Clock-out failed',
                'code' => 'SYSTEM_ERROR',
            ];
        }
    }

    /**
     * Process break start
     */
    public function processBreakStart(ShiftAssignment $assignment): array
    {
        try {
            // Validate worker is clocked in
            if (! $assignment->check_in_time && ! $assignment->actual_clock_in) {
                return [
                    'success' => false,
                    'error' => 'Must be clocked in to start a break',
                    'code' => 'NOT_CLOCKED_IN',
                ];
            }

            // Check if already on break (check both current_break_started_at and breaks JSON)
            if ($assignment->current_break_started_at) {
                return [
                    'success' => false,
                    'error' => 'Already on break',
                    'code' => 'ALREADY_ON_BREAK',
                ];
            }

            $breaks = $assignment->getBreaks();
            $activeBreak = collect($breaks)->first(fn ($b) => empty($b['end_time']));

            if ($activeBreak) {
                return [
                    'success' => false,
                    'error' => 'Already on break',
                    'code' => 'ALREADY_ON_BREAK',
                ];
            }

            // Start break via model method
            $result = $assignment->startBreak();

            if (! $result) {
                return [
                    'success' => false,
                    'error' => 'Failed to start break',
                    'code' => 'BREAK_START_FAILED',
                ];
            }

            // Create time tracking record
            TimeTrackingRecord::create([
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'type' => TimeTrackingRecord::TYPE_BREAK_START,
                'verified_at' => now(),
                'verification_methods' => ['system'],
                'verification_results' => ['auto' => true],
            ]);

            Log::info('Worker started break', [
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'assignment_id' => $assignment->id,
            ]);

            return [
                'success' => true,
                'message' => 'Break started',
                'started_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Break start failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to start break',
                'code' => 'SYSTEM_ERROR',
            ];
        }
    }

    /**
     * Process break end
     */
    public function processBreakEnd(ShiftAssignment $assignment): array
    {
        try {
            // Check if on break via current_break_started_at
            if ($assignment->current_break_started_at) {
                $breakStartTime = Carbon::parse($assignment->current_break_started_at);
                $breakDuration = $breakStartTime->diffInMinutes(now());

                // Update total break minutes and clear the break start
                $assignment->update([
                    'total_break_minutes' => ($assignment->total_break_minutes ?? 0) + $breakDuration,
                    'current_break_started_at' => null,
                ]);

                // Check mandatory break compliance
                $shift = $assignment->shift;
                if ($shift && method_exists($shift, 'requiresBreak') && $shift->requiresBreak()) {
                    $requiredMinutes = method_exists($shift, 'getRequiredBreakMinutes')
                        ? $shift->getRequiredBreakMinutes()
                        : 30;
                    if ($assignment->total_break_minutes >= $requiredMinutes) {
                        $assignment->update([
                            'mandatory_break_taken' => true,
                            'break_compliance_met' => true,
                        ]);
                    }
                }

                // Create time tracking record
                TimeTrackingRecord::create([
                    'assignment_id' => $assignment->id,
                    'worker_id' => $assignment->worker_id,
                    'shift_id' => $assignment->shift_id,
                    'type' => TimeTrackingRecord::TYPE_BREAK_END,
                    'verified_at' => now(),
                    'verification_methods' => ['system'],
                    'verification_results' => [
                        'auto' => true,
                        'duration_minutes' => $breakDuration,
                    ],
                ]);

                Log::info('Worker ended break', [
                    'worker_id' => $assignment->worker_id,
                    'shift_id' => $assignment->shift_id,
                    'assignment_id' => $assignment->id,
                    'break_duration_minutes' => $breakDuration,
                    'total_break_minutes' => $assignment->total_break_minutes + $breakDuration,
                ]);

                $assignment->refresh();

                return [
                    'success' => true,
                    'message' => 'Break ended',
                    'ended_at' => now()->toIso8601String(),
                    'break_duration_minutes' => $breakDuration,
                    'total_break_minutes' => $assignment->total_break_minutes,
                    'mandatory_break_taken' => $assignment->mandatory_break_taken ?? false,
                ];
            }

            // Fallback to breaks JSON method
            $breaks = $assignment->getBreaks();
            $activeBreak = collect($breaks)->first(fn ($b) => empty($b['end_time']));

            if (! $activeBreak) {
                return [
                    'success' => false,
                    'error' => 'Not currently on break',
                    'code' => 'NOT_ON_BREAK',
                ];
            }

            // End break via model method
            $result = $assignment->endBreak();

            if (! $result) {
                return [
                    'success' => false,
                    'error' => 'Failed to end break',
                    'code' => 'BREAK_END_FAILED',
                ];
            }

            // Refresh to get updated break data
            $assignment->refresh();
            $breaks = $assignment->getBreaks();
            $lastBreak = end($breaks);
            $breakDuration = $lastBreak['duration_minutes'] ?? 0;

            // Create time tracking record
            TimeTrackingRecord::create([
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'type' => TimeTrackingRecord::TYPE_BREAK_END,
                'verified_at' => now(),
                'verification_methods' => ['system'],
                'verification_results' => [
                    'auto' => true,
                    'duration_minutes' => $breakDuration,
                ],
            ]);

            Log::info('Worker ended break', [
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'assignment_id' => $assignment->id,
                'break_duration_minutes' => $breakDuration,
                'total_break_minutes' => $assignment->total_break_minutes,
            ]);

            return [
                'success' => true,
                'message' => 'Break ended',
                'ended_at' => now()->toIso8601String(),
                'break_duration_minutes' => $breakDuration,
                'total_break_minutes' => $assignment->total_break_minutes,
                'mandatory_break_taken' => $assignment->mandatory_break_taken,
            ];

        } catch (\Exception $e) {
            Log::error('Break end failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to end break',
                'code' => 'SYSTEM_ERROR',
            ];
        }
    }

    /**
     * Get current break status for an assignment
     */
    public function getBreakStatus(ShiftAssignment $assignment): array
    {
        $breaks = $assignment->getBreaks();
        $activeBreak = collect($breaks)->first(fn ($b) => empty($b['end_time']));

        // Check if mandatory break is required based on shift duration
        $shift = $assignment->shift;
        $mandatoryBreakRequired = false;
        if ($shift && method_exists($shift, 'requiresBreak')) {
            $mandatoryBreakRequired = $shift->requiresBreak();
        } elseif ($shift && $shift->duration_hours >= 6) {
            // Default: breaks required for 6+ hour shifts
            $mandatoryBreakRequired = true;
        }

        return [
            'on_break' => ! empty($activeBreak),
            'current_break_started_at' => $activeBreak['start_time'] ?? null,
            'total_breaks' => count($breaks),
            'total_break_minutes' => $assignment->total_break_minutes ?? 0,
            'mandatory_break_required' => $mandatoryBreakRequired,
            'mandatory_break_taken' => $assignment->mandatory_break_taken ?? false,
            'break_compliance_met' => $assignment->break_compliance_met ?? false,
        ];
    }

    /**
     * Verify worker identity using face recognition
     */
    private function verifyIdentity(User $worker, array $faceData): array
    {
        try {
            // In production, this would integrate with face recognition API
            // For now, simulate verification process

            $profilePhoto = $worker->getProfilePhoto();
            if (! $profilePhoto) {
                return [
                    'verified' => false,
                    'reason' => 'No profile photo on record',
                    'confidence' => 0,
                ];
            }

            // Simulate face comparison
            $confidence = $this->simulateFaceComparison($faceData, $profilePhoto);
            $threshold = 85; // 85% confidence threshold

            if ($confidence < $threshold) {
                return [
                    'verified' => false,
                    'reason' => 'Face match confidence too low',
                    'confidence' => $confidence,
                    'threshold' => $threshold,
                ];
            }

            // Liveness check
            $livenessResult = $this->performLivenessCheck($faceData);
            if (! $livenessResult['passed']) {
                return [
                    'verified' => false,
                    'reason' => 'Liveness check failed',
                    'confidence' => $confidence,
                    'liveness_score' => $livenessResult['score'],
                ];
            }

            return [
                'verified' => true,
                'confidence' => $confidence,
                'liveness_score' => $livenessResult['score'],
            ];

        } catch (\Exception $e) {
            Log::error('Identity verification failed', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'reason' => 'Verification service unavailable',
            ];
        }
    }

    /**
     * Verify worker location against venue geofence
     */
    private function verifyLocation(Shift $shift, array $locationData): array
    {
        try {
            // Get location from venue or directly from shift
            $venue = $shift->venue;
            $targetLat = $venue?->latitude ?? $shift->location_lat ?? null;
            $targetLng = $venue?->longitude ?? $shift->location_lng ?? null;
            $geofenceRadius = $venue?->geofence_radius ?? $shift->geofence_radius ?? 100; // Default 100 meters

            // If no target location available, allow verification with warning
            if ($targetLat === null || $targetLng === null) {
                return [
                    'verified' => true,
                    'message' => 'Location verification skipped - no venue location configured',
                    'warning' => true,
                ];
            }

            // Calculate distance
            $distance = $this->calculateDistance(
                $locationData['latitude'],
                $locationData['longitude'],
                $targetLat,
                $targetLng
            );

            $isWithinGeofence = $distance <= $geofenceRadius;
            $gpsAccuracy = $locationData['accuracy'] ?? null;

            // Check GPS accuracy
            if ($gpsAccuracy && $gpsAccuracy > 50) {
                return [
                    'verified' => false,
                    'message' => 'GPS accuracy too low',
                    'distance' => $distance,
                    'geofence_radius' => $geofenceRadius,
                    'gps_accuracy' => $gpsAccuracy,
                    'suggestion' => 'Move to an area with better GPS signal',
                ];
            }

            return [
                'verified' => $isWithinGeofence,
                'distance' => $distance,
                'geofence_radius' => $geofenceRadius,
                'message' => $isWithinGeofence
                    ? 'Location verified - within geofence'
                    : 'You are '.round($distance).'m from venue. Move closer to clock in.',
            ];

        } catch (\Exception $e) {
            return [
                'verified' => false,
                'message' => 'Location verification failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify location for clock-out (more lenient than clock-in)
     * Clock-out allows location failures with warning only
     */
    private function verifyClockOutLocation(Shift $shift, array $locationData): array
    {
        // Use the same location verification logic
        $result = $this->verifyLocation($shift, $locationData);

        // Clock-out is more lenient - allow with warning if location fails
        if (! $result['verified']) {
            $result['warning'] = true;
            $result['verified'] = true; // Allow clock-out to proceed
            $result['message'] = 'Clock-out allowed with location warning: '.$result['message'];
        }

        return $result;
    }

    /**
     * Verify QR code for clock-in
     */
    private function verifyQRCode(Shift $shift, string $qrCode): array
    {
        // In production, verify against venue-specific QR codes
        $expectedCode = $shift->venue->qr_code ?? null;

        if (! $expectedCode) {
            return [
                'verified' => false,
                'reason' => 'QR code verification not configured for this venue',
            ];
        }

        return [
            'verified' => hash_equals($expectedCode, $qrCode),
            'reason' => 'QR code verification',
        ];
    }

    /**
     * Verify supervisor authorization code
     */
    private function verifySupervisorCode(string $code): array
    {
        // In production, verify against supervisor codes
        return [
            'verified' => ! empty($code),
            'reason' => 'Supervisor code verification',
        ];
    }

    /**
     * Validate clock-in time window
     */
    private function validateClockInTime(Shift $shift): array
    {
        $now = now();
        $scheduledStart = $this->getScheduledStartTime($shift);

        $earliestAllowed = $scheduledStart->copy()->subMinutes(15); // 15 minutes early
        $onTimeWindow = $scheduledStart->copy()->addMinutes(10); // 10 minutes grace
        $lateWindow = $scheduledStart->copy()->addMinutes(30); // 30 minutes late max

        if ($now < $earliestAllowed) {
            return [
                'allowed' => false,
                'message' => 'Too early to clock in',
                'status' => 'too_early',
                'allowed_at' => $earliestAllowed->toIso8601String(),
            ];
        }

        if ($now <= $onTimeWindow) {
            return [
                'allowed' => true,
                'message' => 'On time',
                'status' => 'on_time',
            ];
        }

        if ($now <= $lateWindow) {
            // Calculate minutes late (absolute value since now is after scheduledStart)
            $minutesLate = abs($now->diffInMinutes($scheduledStart, false));

            return [
                'allowed' => true,
                'message' => "Clocking in {$minutesLate} minutes late",
                'status' => 'late',
                'minutes_late' => $minutesLate,
            ];
        }

        return [
            'allowed' => false,
            'message' => 'Too late to clock in without approval',
            'status' => 'very_late',
            'requires_approval' => true,
        ];
    }

    /**
     * Calculate worked hours including breaks
     */
    private function calculateWorkedHours(ShiftAssignment $assignment, array $verificationData): array
    {
        // Ensure we have a proper Carbon instance for clock-in time
        $clockIn = $assignment->check_in_time ?? $assignment->actual_clock_in;
        if (! $clockIn) {
            return [
                'gross_hours' => 0,
                'break_minutes' => 0,
                'net_hours' => 0,
                'actual_hours' => 0,
                'gross_minutes' => 0,
                'net_minutes' => 0,
            ];
        }

        // Parse to ensure Carbon instance
        $clockIn = Carbon::parse($clockIn);
        $clockOut = now();

        // Gross time (use abs() since diffInMinutes returns signed values)
        $grossMinutes = abs($clockOut->diffInMinutes($clockIn));
        $grossHours = $grossMinutes / 60;

        // Get break time if tracked
        $breakMinutes = $this->getBreakTime($assignment) ?? 0;

        // Net hours
        $netMinutes = max(0, $grossMinutes - $breakMinutes);
        $netHours = $netMinutes / 60;

        return [
            'gross_hours' => round($grossHours, 2),
            'break_minutes' => $breakMinutes,
            'net_hours' => round($netHours, 2),
            'actual_hours' => round($netHours, 2), // Alias for backward compatibility
            'gross_minutes' => $grossMinutes,
            'net_minutes' => $netMinutes,
        ];
    }

    /**
     * Calculate overtime for the shift
     */
    private function calculateOvertime(Shift $shift, float $actualHours): array
    {
        $scheduledHours = $shift->duration_hours;
        $overtimeMinutes = 0;

        if ($actualHours > $scheduledHours) {
            $overtimeMinutes = ($actualHours - $scheduledHours) * 60;
        }

        // Check daily overtime (8+ hours in many jurisdictions)
        $dailyOvertimeMinutes = 0;
        if ($actualHours > 8) {
            $dailyOvertimeMinutes = ($actualHours - 8) * 60;
        }

        return [
            'overtime_minutes' => max($overtimeMinutes, $dailyOvertimeMinutes),
            'overtime_hours' => max($overtimeMinutes, $dailyOvertimeMinutes) / 60,
            'regular_hours' => min($actualHours, 8),
        ];
    }

    /**
     * Check for early departure
     */
    private function checkEarlyDeparture(Shift $shift, ?string $reason = null): array
    {
        $scheduledEnd = $this->getScheduledEndTime($shift);
        $actualEnd = now();

        if ($actualEnd < $scheduledEnd) {
            // Use abs() since diffInMinutes returns signed values
            $minutesEarly = abs($scheduledEnd->diffInMinutes($actualEnd));

            return [
                'minutes' => $minutesEarly,
                'reason' => $reason,
                'penalty_applies' => $minutesEarly > 30, // Penalty for >30 min early
            ];
        }

        return [
            'minutes' => 0,
            'reason' => null,
            'penalty_applies' => false,
        ];
    }

    /**
     * Calculate distance between two GPS coordinates
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in meters
    }

    /**
     * Simulate face comparison (replace with real API)
     */
    private function simulateFaceComparison(array $faceData, string $profilePhoto): float
    {
        // In production, this would call face recognition service
        // For demo, return random confidence between 70-95
        return rand(70, 95);
    }

    /**
     * Perform liveness check
     */
    private function performLivenessCheck(array $faceData): array
    {
        // In production, this would analyze for liveness indicators
        return [
            'passed' => true,
            'score' => rand(80, 100),
        ];
    }

    /**
     * Get break time for assignment
     */
    private function getBreakTime(ShiftAssignment $assignment): int
    {
        return $assignment->total_break_minutes ?? 0;
    }

    /**
     * Apply early departure penalty
     */
    private function applyEarlyDeparturePenalty(User $worker, array $earlyDeparture): void
    {
        // In production, this would create penalty record
        Log::info('Early departure penalty applied', [
            'worker_id' => $worker->id,
            'minutes_early' => $earlyDeparture['minutes'],
            'reason' => $earlyDeparture['reason'],
        ]);
    }

    /**
     * Get the scheduled start time for a shift
     * Handles both datetime casts and string values safely
     */
    private function getScheduledStartTime(Shift $shift): Carbon
    {
        // Use start_datetime if available
        if ($shift->start_datetime) {
            return Carbon::parse($shift->start_datetime);
        }

        // Otherwise combine shift_date and start_time, handling Carbon objects
        $shiftDate = $shift->shift_date instanceof Carbon
            ? $shift->shift_date->format('Y-m-d')
            : $shift->shift_date;

        $startTime = $shift->start_time instanceof Carbon
            ? $shift->start_time->format('H:i:s')
            : $shift->start_time;

        return Carbon::parse($shiftDate.' '.$startTime);
    }

    /**
     * Get the scheduled end time for a shift
     * Handles both datetime casts and string values safely
     */
    private function getScheduledEndTime(Shift $shift): Carbon
    {
        // Use end_datetime if available
        if ($shift->end_datetime) {
            return Carbon::parse($shift->end_datetime);
        }

        // Otherwise combine shift_date and end_time, handling Carbon objects
        $shiftDate = $shift->shift_date instanceof Carbon
            ? $shift->shift_date->format('Y-m-d')
            : $shift->shift_date;

        $endTime = $shift->end_time instanceof Carbon
            ? $shift->end_time->format('H:i:s')
            : $shift->end_time;

        return Carbon::parse($shiftDate.' '.$endTime);
    }
}
