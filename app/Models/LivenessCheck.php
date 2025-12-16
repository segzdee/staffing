<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * LivenessCheck Model - STAFF-REG-004
 *
 * Manages liveness check records for identity verification.
 * Ensures the person submitting documents is physically present.
 *
 * @property int $id
 * @property int $identity_verification_id
 * @property int $user_id
 * @property string $provider
 * @property string|null $provider_check_id
 * @property string|null $provider_report_id
 * @property string $check_type
 * @property string $status
 * @property array|null $challenges
 * @property array|null $challenge_responses
 * @property int $challenges_completed
 * @property int $challenges_required
 * @property string|null $result
 * @property float|null $liveness_score
 * @property float|null $face_quality_score
 * @property array|null $result_breakdown
 * @property bool $face_match_attempted
 * @property float|null $face_similarity_score
 * @property string|null $face_match_result
 * @property array|null $spoofing_checks
 * @property bool|null $is_real_person
 * @property bool $photo_detected
 * @property bool $screen_detected
 * @property bool $mask_detected
 * @property bool $deepfake_detected
 * @property string|null $video_storage_path
 * @property array|null $frame_storage_paths
 * @property string|null $selfie_storage_path
 * @property string|null $storage_encryption_key
 * @property string|null $session_token
 * @property \Carbon\Carbon|null $session_started_at
 * @property \Carbon\Carbon|null $session_completed_at
 * @property int|null $session_duration_seconds
 * @property string|null $device_type
 * @property string|null $device_os
 * @property string|null $browser
 * @property string|null $camera_used
 * @property array|null $environment_checks
 * @property int $attempt_number
 * @property string|null $failure_reason
 * @property array|null $failure_details
 * @property string|null $ip_address
 * @property array|null $geolocation
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LivenessCheck extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'liveness_checks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'identity_verification_id',
        'user_id',
        'provider',
        'provider_check_id',
        'provider_report_id',
        'check_type',
        'status',
        'challenges',
        'challenge_responses',
        'challenges_completed',
        'challenges_required',
        'result',
        'liveness_score',
        'face_quality_score',
        'result_breakdown',
        'face_match_attempted',
        'face_similarity_score',
        'face_match_result',
        'spoofing_checks',
        'is_real_person',
        'photo_detected',
        'screen_detected',
        'mask_detected',
        'deepfake_detected',
        'video_storage_path',
        'frame_storage_paths',
        'selfie_storage_path',
        'storage_encryption_key',
        'session_token',
        'session_started_at',
        'session_completed_at',
        'session_duration_seconds',
        'device_type',
        'device_os',
        'browser',
        'camera_used',
        'environment_checks',
        'attempt_number',
        'failure_reason',
        'failure_details',
        'ip_address',
        'geolocation',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'challenges' => 'array',
        'challenge_responses' => 'array',
        'challenges_completed' => 'integer',
        'challenges_required' => 'integer',
        'liveness_score' => 'decimal:4',
        'face_quality_score' => 'decimal:4',
        'result_breakdown' => 'array',
        'face_match_attempted' => 'boolean',
        'face_similarity_score' => 'decimal:4',
        'spoofing_checks' => 'array',
        'is_real_person' => 'boolean',
        'photo_detected' => 'boolean',
        'screen_detected' => 'boolean',
        'mask_detected' => 'boolean',
        'deepfake_detected' => 'boolean',
        'frame_storage_paths' => 'array',
        'session_started_at' => 'datetime',
        'session_completed_at' => 'datetime',
        'session_duration_seconds' => 'integer',
        'environment_checks' => 'array',
        'attempt_number' => 'integer',
        'failure_details' => 'array',
        'geolocation' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'session_token',
        'storage_encryption_key',
        'video_storage_path',
        'frame_storage_paths',
        'selfie_storage_path',
    ];

    // ==================== Constants ====================

    public const TYPE_PASSIVE = 'passive';
    public const TYPE_ACTIVE = 'active';
    public const TYPE_VIDEO = 'video';
    public const TYPE_MOTION = 'motion';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const RESULT_CLEAR = 'clear';
    public const RESULT_CONSIDER = 'consider';
    public const RESULT_REJECTED = 'rejected';

    public const FACE_MATCH = 'match';
    public const FACE_NO_MATCH = 'no_match';
    public const FACE_UNABLE = 'unable_to_process';

    public const PROVIDER_ONFIDO = 'onfido';
    public const PROVIDER_JUMIO = 'jumio';
    public const PROVIDER_AWS_REKOGNITION = 'aws_rekognition';

    // ==================== Challenge Types ====================

    /**
     * Get available challenge types for active liveness.
     */
    public static function getAvailableChallenges(): array
    {
        return [
            'turn_head_left' => [
                'name' => 'Turn Head Left',
                'instruction' => 'Please slowly turn your head to the left.',
                'timeout_seconds' => 10,
            ],
            'turn_head_right' => [
                'name' => 'Turn Head Right',
                'instruction' => 'Please slowly turn your head to the right.',
                'timeout_seconds' => 10,
            ],
            'blink' => [
                'name' => 'Blink Eyes',
                'instruction' => 'Please blink your eyes naturally.',
                'timeout_seconds' => 5,
            ],
            'smile' => [
                'name' => 'Smile',
                'instruction' => 'Please smile naturally.',
                'timeout_seconds' => 5,
            ],
            'nod' => [
                'name' => 'Nod Head',
                'instruction' => 'Please nod your head up and down.',
                'timeout_seconds' => 8,
            ],
            'open_mouth' => [
                'name' => 'Open Mouth',
                'instruction' => 'Please open your mouth slightly.',
                'timeout_seconds' => 5,
            ],
            'look_up' => [
                'name' => 'Look Up',
                'instruction' => 'Please look upward briefly.',
                'timeout_seconds' => 8,
            ],
            'look_down' => [
                'name' => 'Look Down',
                'instruction' => 'Please look downward briefly.',
                'timeout_seconds' => 8,
            ],
        ];
    }

    // ==================== Relationships ====================

    /**
     * Get the identity verification this check belongs to.
     */
    public function identityVerification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'identity_verification_id');
    }

    /**
     * Get the user that owns the check.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Status Check Methods ====================

    /**
     * Check if liveness check is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if liveness check is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if liveness check passed.
     */
    public function hasPassed(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    /**
     * Check if liveness check failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if session has expired.
     */
    public function hasSessionExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        // Session expires after 15 minutes of inactivity
        if ($this->session_started_at && !$this->session_completed_at) {
            return $this->session_started_at->addMinutes(15)->isPast();
        }

        return false;
    }

    /**
     * Check if face was detected as real person.
     */
    public function isRealPerson(): bool
    {
        return $this->is_real_person === true
            && !$this->photo_detected
            && !$this->screen_detected
            && !$this->mask_detected
            && !$this->deepfake_detected;
    }

    /**
     * Check if any spoofing was detected.
     */
    public function hasSpoofingDetected(): bool
    {
        return $this->photo_detected
            || $this->screen_detected
            || $this->mask_detected
            || $this->deepfake_detected;
    }

    // ==================== Business Logic Methods ====================

    /**
     * Start the liveness check session.
     */
    public function startSession(string $token = null): self
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'session_token' => $token ?? bin2hex(random_bytes(32)),
            'session_started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Complete the liveness check session.
     */
    public function completeSession(): self
    {
        $duration = $this->session_started_at
            ? now()->diffInSeconds($this->session_started_at)
            : null;

        $this->update([
            'session_completed_at' => now(),
            'session_duration_seconds' => $duration,
        ]);

        return $this;
    }

    /**
     * Mark as processing.
     */
    public function markProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    /**
     * Mark as passed.
     */
    public function markPassed(array $results = []): self
    {
        $this->update([
            'status' => self::STATUS_PASSED,
            'result' => self::RESULT_CLEAR,
            'liveness_score' => $results['liveness_score'] ?? null,
            'face_quality_score' => $results['face_quality_score'] ?? null,
            'result_breakdown' => $results['breakdown'] ?? null,
            'is_real_person' => true,
        ]);

        $this->completeSession();

        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $reason, array $details = []): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'result' => self::RESULT_REJECTED,
            'failure_reason' => $reason,
            'failure_details' => $details,
        ]);

        $this->completeSession();

        return $this;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): self
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        return $this;
    }

    /**
     * Store spoofing check results.
     */
    public function storeSpoofingResults(array $results): self
    {
        $this->update([
            'spoofing_checks' => $results,
            'photo_detected' => $results['photo_detected'] ?? false,
            'screen_detected' => $results['screen_detected'] ?? false,
            'mask_detected' => $results['mask_detected'] ?? false,
            'deepfake_detected' => $results['deepfake_detected'] ?? false,
            'is_real_person' => $results['is_real_person'] ?? null,
        ]);

        return $this;
    }

    /**
     * Store face matching results.
     */
    public function storeFaceMatchResults(string $result, float $score = null): self
    {
        $this->update([
            'face_match_attempted' => true,
            'face_match_result' => $result,
            'face_similarity_score' => $score,
        ]);

        return $this;
    }

    /**
     * Record challenge response.
     */
    public function recordChallengeResponse(string $challengeId, bool $passed): self
    {
        $responses = $this->challenge_responses ?? [];
        $responses[$challengeId] = [
            'passed' => $passed,
            'timestamp' => now()->toIso8601String(),
        ];

        $completed = $passed ? $this->challenges_completed + 1 : $this->challenges_completed;

        $this->update([
            'challenge_responses' => $responses,
            'challenges_completed' => $completed,
        ]);

        return $this;
    }

    /**
     * Check if all challenges are completed.
     */
    public function allChallengesCompleted(): bool
    {
        return $this->challenges_completed >= $this->challenges_required;
    }

    /**
     * Generate random challenges for the session.
     */
    public function generateChallenges(int $count = 3): array
    {
        $available = array_keys(self::getAvailableChallenges());
        $selected = collect($available)->shuffle()->take($count)->values()->all();

        $challenges = [];
        foreach ($selected as $id) {
            $challenges[$id] = self::getAvailableChallenges()[$id];
        }

        $this->update([
            'challenges' => $challenges,
            'challenges_required' => $count,
        ]);

        return $challenges;
    }

    /**
     * Store device information.
     */
    public function storeDeviceInfo(array $info): self
    {
        $this->update([
            'device_type' => $info['device_type'] ?? null,
            'device_os' => $info['os'] ?? null,
            'browser' => $info['browser'] ?? null,
            'camera_used' => $info['camera'] ?? null,
            'ip_address' => $info['ip_address'] ?? null,
            'geolocation' => $info['geolocation'] ?? null,
        ]);

        return $this;
    }

    /**
     * Store environment check results.
     */
    public function storeEnvironmentChecks(array $checks): self
    {
        $this->update(['environment_checks' => $checks]);
        return $this;
    }

    // ==================== Scopes ====================

    /**
     * Scope to passed checks.
     */
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    /**
     * Scope to failed checks.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to pending/in-progress checks.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Scope to checks with spoofing detected.
     */
    public function scopeWithSpoofing($query)
    {
        return $query->where(function ($q) {
            $q->where('photo_detected', true)
              ->orWhere('screen_detected', true)
              ->orWhere('mask_detected', true)
              ->orWhere('deepfake_detected', true);
        });
    }

    /**
     * Scope to a specific provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ==================== Static Factory Methods ====================

    /**
     * Create a new liveness check for an identity verification.
     */
    public static function createForVerification(
        IdentityVerification $verification,
        string $type = self::TYPE_ACTIVE,
        string $provider = self::PROVIDER_ONFIDO
    ): self {
        return self::create([
            'identity_verification_id' => $verification->id,
            'user_id' => $verification->user_id,
            'provider' => $provider,
            'check_type' => $type,
            'status' => self::STATUS_PENDING,
            'challenges_required' => 3,
            'attempt_number' => 1,
        ]);
    }
}
