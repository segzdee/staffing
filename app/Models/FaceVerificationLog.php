<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SL-005: Face Verification Log Model for Clock-In/Out Verification
 *
 * Stores all face verification attempts for audit and analytics.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int|null $shift_assignment_id
 * @property string $action
 * @property string $provider
 * @property float|null $confidence_score
 * @property bool|null $liveness_passed
 * @property bool|null $match_result
 * @property string|null $source_image_url
 * @property array|null $provider_response
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $ip_address
 * @property string|null $device_info
 * @property string|null $failure_reason
 * @property int|null $processing_time_ms
 * @property array|null $face_attributes
 * @property bool $fallback_used
 * @property int|null $approved_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Shift|null $shift
 * @property-read ShiftAssignment|null $shiftAssignment
 * @property-read User|null $approver
 */
class FaceVerificationLog extends Model
{
    use HasFactory;

    /**
     * Action constants.
     */
    public const ACTION_ENROLL = 'enroll';

    public const ACTION_VERIFY_CLOCK_IN = 'verify_clock_in';

    public const ACTION_VERIFY_CLOCK_OUT = 'verify_clock_out';

    public const ACTION_RE_VERIFY = 're_verify';

    public const ACTION_MANUAL_OVERRIDE = 'manual_override';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_assignment_id',
        'action',
        'provider',
        'confidence_score',
        'liveness_passed',
        'match_result',
        'source_image_url',
        'provider_response',
        'latitude',
        'longitude',
        'ip_address',
        'device_info',
        'failure_reason',
        'processing_time_ms',
        'face_attributes',
        'fallback_used',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'provider_response' => 'array',
        'face_attributes' => 'array',
        'liveness_passed' => 'boolean',
        'match_result' => 'boolean',
        'fallback_used' => 'boolean',
        'confidence_score' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the user that owns the verification log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift associated with this verification.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the shift assignment associated with this verification.
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Get the admin who approved a manual override.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for successful verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('match_result', true);
    }

    /**
     * Scope for failed verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('match_result', false);
    }

    /**
     * Scope by action type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for clock-in verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClockIn($query)
    {
        return $query->where('action', self::ACTION_VERIFY_CLOCK_IN);
    }

    /**
     * Scope for clock-out verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClockOut($query)
    {
        return $query->where('action', self::ACTION_VERIFY_CLOCK_OUT);
    }

    /**
     * Scope for enrollments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnrollments($query)
    {
        return $query->where('action', self::ACTION_ENROLL);
    }

    /**
     * Scope by provider.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for logs with liveness check passed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLivenessVerified($query)
    {
        return $query->where('liveness_passed', true);
    }

    /**
     * Scope for manual overrides.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManualOverrides($query)
    {
        return $query->where('fallback_used', true);
    }

    /**
     * Check if verification was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->match_result === true;
    }

    /**
     * Check if liveness was verified.
     */
    public function livenessVerified(): bool
    {
        return $this->liveness_passed === true;
    }

    /**
     * Check if this was a fallback/manual verification.
     */
    public function wasFallback(): bool
    {
        return $this->fallback_used === true;
    }

    /**
     * Get the confidence level as a human-readable string.
     */
    public function getConfidenceLevelAttribute(): string
    {
        if ($this->confidence_score === null) {
            return 'Unknown';
        }

        return match (true) {
            $this->confidence_score >= 95 => 'Very High',
            $this->confidence_score >= 85 => 'High',
            $this->confidence_score >= 70 => 'Medium',
            $this->confidence_score >= 50 => 'Low',
            default => 'Very Low',
        };
    }

    /**
     * Create a verification log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public static function createLog(array $data): self
    {
        return self::create(array_merge($data, [
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'device_info' => $data['device_info'] ?? request()->userAgent(),
        ]));
    }

    /**
     * Log a successful enrollment.
     */
    public static function logEnrollment(
        User $user,
        string $provider,
        float $confidence,
        ?string $imageUrl = null,
        ?array $providerResponse = null
    ): self {
        return self::createLog([
            'user_id' => $user->id,
            'action' => self::ACTION_ENROLL,
            'provider' => $provider,
            'confidence_score' => $confidence,
            'match_result' => true,
            'source_image_url' => $imageUrl,
            'provider_response' => $providerResponse,
        ]);
    }

    /**
     * Log a verification attempt.
     */
    public static function logVerification(
        User $user,
        string $action,
        string $provider,
        bool $matchResult,
        ?float $confidence = null,
        ?bool $livenessPassed = null,
        ?int $shiftId = null,
        ?int $assignmentId = null,
        ?string $imageUrl = null,
        ?array $providerResponse = null,
        ?string $failureReason = null,
        ?int $processingTimeMs = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): self {
        return self::createLog([
            'user_id' => $user->id,
            'shift_id' => $shiftId,
            'shift_assignment_id' => $assignmentId,
            'action' => $action,
            'provider' => $provider,
            'confidence_score' => $confidence,
            'liveness_passed' => $livenessPassed,
            'match_result' => $matchResult,
            'source_image_url' => $imageUrl,
            'provider_response' => $providerResponse,
            'failure_reason' => $failureReason,
            'processing_time_ms' => $processingTimeMs,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * Log a manual override.
     */
    public static function logManualOverride(
        User $user,
        User $approver,
        ?int $shiftId = null,
        ?int $assignmentId = null,
        ?string $reason = null
    ): self {
        return self::createLog([
            'user_id' => $user->id,
            'shift_id' => $shiftId,
            'shift_assignment_id' => $assignmentId,
            'action' => self::ACTION_MANUAL_OVERRIDE,
            'provider' => 'manual',
            'match_result' => true,
            'fallback_used' => true,
            'approved_by' => $approver->id,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get all action types.
     *
     * @return array<string, string>
     */
    public static function getActionTypes(): array
    {
        return [
            self::ACTION_ENROLL => 'Face Enrollment',
            self::ACTION_VERIFY_CLOCK_IN => 'Clock-In Verification',
            self::ACTION_VERIFY_CLOCK_OUT => 'Clock-Out Verification',
            self::ACTION_RE_VERIFY => 'Re-Verification',
            self::ACTION_MANUAL_OVERRIDE => 'Manual Override',
        ];
    }

    /**
     * Get verification statistics for a user.
     *
     * @return array<string, mixed>
     */
    public static function getStatsForUser(User $user): array
    {
        $logs = self::where('user_id', $user->id)->get();

        return [
            'total_verifications' => $logs->count(),
            'successful' => $logs->where('match_result', true)->count(),
            'failed' => $logs->where('match_result', false)->count(),
            'average_confidence' => $logs->whereNotNull('confidence_score')->avg('confidence_score'),
            'liveness_passed_rate' => $logs->count() > 0
                ? ($logs->where('liveness_passed', true)->count() / $logs->count()) * 100
                : 0,
            'manual_overrides' => $logs->where('fallback_used', true)->count(),
            'last_verification' => $logs->sortByDesc('created_at')->first()?->created_at,
        ];
    }
}
