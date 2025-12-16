<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Time Tracking Record Model
 * 
 * Records all clock-in/clock-out events with verification details
 * Tracks biometric, location, and method verification results
 * 
 * SL-005: Clock-In Verification Protocol
 * SL-007: Clock-Out & Shift Completion
 */
class TimeTrackingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'worker_id',
        'shift_id',
        'type',
        'verified_at',
        'verification_methods',
        'verification_results',
        'location_data',
        'face_confidence',
        'device_info',
        'timezone',
        'calculated_hours',
        'early_departure_minutes',
        'early_departure_reason',
        'overtime_minutes',
        'manual_reason'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'verification_methods' => 'json',
        'verification_results' => 'json',
        'location_data' => 'json',
        'device_info' => 'json',
        'calculated_hours' => 'float',
        'face_confidence' => 'float',
        'timezone' => 'string'
    ];

    /**
     * Record Type Constants
     */
    const TYPE_CLOCK_IN = 'clock_in';
    const TYPE_CLOCK_OUT = 'clock_out';
    const TYPE_BREAK_START = 'break_start';
    const TYPE_BREAK_END = 'break_end';

    /**
     * Verification Method Constants
     */
    const METHOD_FACE_RECOGNITION = 'face_recognition';
    const METHOD_GPS_GEOFENCE = 'gps_geofence';
    const METHOD_QR_CODE = 'qr_code';
    const METHOD_SUPERVISOR_CODE = 'supervisor_code';
    const METHOD_PHOTO_CAPTURE = 'photo_capture';

    /**
     * Relationship to assignment
     */
    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Relationship to worker
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Relationship to shift
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Scope for clock-in records
     */
    public function scopeClockIn($query)
    {
        return $query->where('type', self::TYPE_CLOCK_IN);
    }

    /**
     * Scope for clock-out records
     */
    public function scopeClockOut($query)
    {
        return $query->where('type', self::TYPE_CLOCK_OUT);
    }

    /**
     * Scope for verified records
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Get verification methods list
     */
    public function getVerificationMethodsListAttribute(): array
    {
        return $this->verification_methods ?? [];
    }

    /**
     * Get face confidence percentage
     */
    public function getFaceConfidencePercentageAttribute(): string
    {
        return $this->face_confidence ? round($this->face_confidence, 1) . '%' : 'N/A';
    }

    /**
     * Get location distance from venue
     */
    public function getLocationDistanceAttribute(): ?float
    {
        if (!$this->location_data || !isset($this->location_data['distance'])) {
            return null;
        }

        return round($this->location_data['distance'], 1);
    }

    /**
     * Check if record includes face verification
     */
    public function hasFaceVerification(): bool
    {
        return in_array(self::METHOD_FACE_RECOGNITION, $this->verification_methods ?? []);
    }

    /**
     * Check if record includes location verification
     */
    public function hasLocationVerification(): bool
    {
        return in_array(self::METHOD_GPS_GEOFENCE, $this->verification_methods ?? []);
    }

    /**
     * Check if verification was successful
     */
    public function isVerificationSuccessful(string $method = null): bool
    {
        if ($method) {
            return $this->verification_results[$method]['verified'] ?? false;
        }

        // Check if all verifications were successful
        foreach ($this->verification_methods ?? [] as $verifyMethod) {
            if (!($this->verification_results[$verifyMethod]['verified'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get formatted time
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->verified_at->format('M j, Y g:i A');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CLOCK_IN => 'Clock In',
            self::TYPE_CLOCK_OUT => 'Clock Out',
            self::TYPE_BREAK_START => 'Break Start',
            self::TYPE_BREAK_END => 'Break End',
            default => 'Unknown'
        };
    }

    /**
     * Create clock-in record
     */
    public static function createClockIn(ShiftAssignment $assignment, array $verificationData): self
    {
        return static::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'shift_id' => $assignment->shift_id,
            'type' => self::TYPE_CLOCK_IN,
            'verified_at' => now(),
            'verification_methods' => array_keys($verificationData['methods'] ?? []),
            'verification_results' => $verificationData['methods'] ?? [],
            'location_data' => $verificationData['location'] ?? null,
            'face_confidence' => $verificationData['methods']['face_recognition']['confidence'] ?? null,
            'device_info' => $verificationData['device_info'] ?? null,
            'timezone' => $verificationData['timezone'] ?? config('app.timezone')
        ]);
    }

    /**
     * Create clock-out record
     */
    public static function createClockOut(ShiftAssignment $assignment, array $verificationData): self
    {
        return static::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'shift_id' => $assignment->shift_id,
            'type' => self::TYPE_CLOCK_OUT,
            'verified_at' => now(),
            'verification_methods' => array_keys($verificationData['methods'] ?? []),
            'verification_results' => $verificationData['methods'] ?? [],
            'location_data' => $verificationData['location'] ?? null,
            'device_info' => $verificationData['device_info'] ?? null,
            'calculated_hours' => $verificationData['calculated_hours'] ?? null,
            'early_departure_minutes' => $verificationData['early_departure_minutes'] ?? 0,
            'early_departure_reason' => $verificationData['early_departure_reason'] ?? null,
            'overtime_minutes' => $verificationData['overtime_minutes'] ?? 0,
            'manual_reason' => $verificationData['manual_reason'] ?? null,
            'timezone' => $verificationData['timezone'] ?? config('app.timezone')
        ]);
    }

    /**
     * Get clock-in record for assignment
     */
    public static function getClockInRecord(ShiftAssignment $assignment): ?self
    {
        return static::where('assignment_id', $assignment->id)
            ->where('type', self::TYPE_CLOCK_IN)
            ->first();
    }

    /**
     * Get clock-out record for assignment
     */
    public static function getClockOutRecord(ShiftAssignment $assignment): ?self
    {
        return static::where('assignment_id', $assignment->id)
            ->where('type', self::TYPE_CLOCK_OUT)
            ->first();
    }

    /**
     * Get all records for assignment
     */
    public static function getAssignmentRecords(ShiftAssignment $assignment): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('assignment_id', $assignment->id)
            ->orderBy('verified_at')
            ->get();
    }

    /**
     * Get worker's recent records
     */
    public static function getWorkerRecentRecords(User $worker, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('worker_id', $worker->id)
            ->where('verified_at', '>=', now()->subDays($days))
            ->with(['shift', 'assignment'])
            ->orderBy('verified_at', 'desc')
            ->get();
    }

    /**
     * Get verification statistics for worker
     */
    public static function getWorkerVerificationStats(User $worker, int $days = 30): array
    {
        $records = static::getWorkerRecentRecords($worker, $days);

        $stats = [
            'total_records' => $records->count(),
            'clock_ins' => $records->where('type', self::TYPE_CLOCK_IN)->count(),
            'clock_outs' => $records->where('type', self::TYPE_CLOCK_OUT)->count(),
            'face_verification_success' => 0,
            'face_verification_failures' => 0,
            'location_verification_success' => 0,
            'location_verification_failures' => 0,
            'average_face_confidence' => 0,
            'average_early_departure' => 0
        ];

        $faceConfidences = [];
        $earlyDepartures = [];

        foreach ($records as $record) {
            if ($record->hasFaceVerification()) {
                if ($record->isVerificationSuccessful(self::METHOD_FACE_RECOGNITION)) {
                    $stats['face_verification_success']++;
                } else {
                    $stats['face_verification_failures']++;
                }
                $faceConfidences[] = $record->face_confidence;
            }

            if ($record->hasLocationVerification()) {
                if ($record->isVerificationSuccessful(self::METHOD_GPS_GEOFENCE)) {
                    $stats['location_verification_success']++;
                } else {
                    $stats['location_verification_failures']++;
                }
            }

            if ($record->early_departure_minutes > 0) {
                $earlyDepartures[] = $record->early_departure_minutes;
            }
        }

        if (!empty($faceConfidences)) {
            $stats['average_face_confidence'] = round(array_sum($faceConfidences) / count($faceConfidences), 1);
        }

        if (!empty($earlyDepartures)) {
            $stats['average_early_departure'] = round(array_sum($earlyDepartures) / count($earlyDepartures), 1);
        }

        return $stats;
    }
}