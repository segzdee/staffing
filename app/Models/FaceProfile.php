<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SL-005: Face Profile Model for Clock-In/Out Verification
 *
 * Stores enrolled face data for facial recognition during shift verification.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $face_id
 * @property string $provider
 * @property array|null $face_attributes
 * @property int $photo_count
 * @property bool $is_enrolled
 * @property \Carbon\Carbon|null $enrolled_at
 * @property \Carbon\Carbon|null $last_verified_at
 * @property int $verification_count
 * @property float|null $avg_confidence
 * @property string|null $enrollment_image_url
 * @property array|null $additional_images
 * @property string|null $collection_id
 * @property string $status
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<FaceVerificationLog> $verificationLogs
 */
class FaceProfile extends Model
{
    use HasFactory;

    /**
     * Provider constants.
     */
    public const PROVIDER_AWS = 'aws';

    public const PROVIDER_AZURE = 'azure';

    public const PROVIDER_FACEPLUSPLUS = 'faceplusplus';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DELETED = 'deleted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'face_id',
        'provider',
        'face_attributes',
        'photo_count',
        'is_enrolled',
        'enrolled_at',
        'last_verified_at',
        'verification_count',
        'avg_confidence',
        'enrollment_image_url',
        'additional_images',
        'collection_id',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'face_attributes' => 'array',
        'additional_images' => 'array',
        'is_enrolled' => 'boolean',
        'enrolled_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'avg_confidence' => 'float',
    ];

    /**
     * Get the user that owns the face profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verification logs for this face profile.
     */
    public function verificationLogs(): HasMany
    {
        return $this->hasMany(FaceVerificationLog::class, 'user_id', 'user_id');
    }

    /**
     * Scope for enrolled profiles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnrolled($query)
    {
        return $query->where('is_enrolled', true);
    }

    /**
     * Scope for active profiles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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
     * Check if the face profile is ready for verification.
     */
    public function isReadyForVerification(): bool
    {
        return $this->is_enrolled
            && $this->status === self::STATUS_ACTIVE
            && ! empty($this->face_id);
    }

    /**
     * Mark the profile as enrolled.
     */
    public function markEnrolled(string $faceId, ?string $imageUrl = null): self
    {
        $this->update([
            'face_id' => $faceId,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'status' => self::STATUS_ACTIVE,
            'enrollment_image_url' => $imageUrl ?? $this->enrollment_image_url,
            'photo_count' => $this->photo_count + 1,
        ]);

        return $this;
    }

    /**
     * Record a verification attempt.
     */
    public function recordVerification(float $confidence, bool $success): self
    {
        $totalConfidence = ($this->avg_confidence ?? 0) * $this->verification_count + $confidence;
        $newCount = $this->verification_count + 1;

        $this->update([
            'last_verified_at' => now(),
            'verification_count' => $newCount,
            'avg_confidence' => $totalConfidence / $newCount,
        ]);

        return $this;
    }

    /**
     * Add an additional enrollment image.
     */
    public function addEnrollmentImage(string $imageUrl): self
    {
        $images = $this->additional_images ?? [];
        $images[] = [
            'url' => $imageUrl,
            'added_at' => now()->toIso8601String(),
        ];

        $this->update([
            'additional_images' => $images,
            'photo_count' => $this->photo_count + 1,
        ]);

        return $this;
    }

    /**
     * Suspend the face profile (e.g., due to suspicious activity).
     */
    public function suspend(string $reason): self
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Reactivate a suspended profile.
     */
    public function reactivate(): self
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'notes' => null,
        ]);

        return $this;
    }

    /**
     * Soft delete the face profile (mark as deleted).
     */
    public function softDelete(): self
    {
        $this->update([
            'status' => self::STATUS_DELETED,
            'face_id' => null,
        ]);

        return $this;
    }

    /**
     * Get all available providers.
     *
     * @return array<string, string>
     */
    public static function getProviders(): array
    {
        return [
            self::PROVIDER_AWS => 'AWS Rekognition',
            self::PROVIDER_AZURE => 'Azure Face API',
            self::PROVIDER_FACEPLUSPLUS => 'Face++',
        ];
    }

    /**
     * Get all statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending Enrollment',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_DELETED => 'Deleted',
        ];
    }

    /**
     * Create or get face profile for a user.
     */
    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => config('face_recognition.provider', 'aws'),
                'status' => self::STATUS_PENDING,
            ]
        );
    }
}
