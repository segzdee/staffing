<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-005: GDPR/CCPA Compliance - Consent Record Model
 *
 * Tracks user consent for various data processing purposes.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $consent_type
 * @property bool $consented
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $consent_details
 * @property string|null $consent_version
 * @property string|null $consent_source
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $withdrawn_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 */
class ConsentRecord extends Model
{
    use HasFactory;

    // Consent Types
    public const TYPE_NECESSARY = 'necessary';       // Required for platform operation

    public const TYPE_FUNCTIONAL = 'functional';     // Enhanced functionality

    public const TYPE_ANALYTICS = 'analytics';       // Analytics and statistics

    public const TYPE_MARKETING = 'marketing';       // Marketing communications

    public const TYPE_PROFILING = 'profiling';       // AI-based shift matching

    public const TYPE_THIRD_PARTY = 'third_party';   // Third-party data sharing

    // Consent Sources
    public const SOURCE_COOKIE_BANNER = 'cookie_banner';

    public const SOURCE_REGISTRATION = 'registration';

    public const SOURCE_SETTINGS = 'settings';

    public const SOURCE_API = 'api';

    public const SOURCE_MOBILE_APP = 'mobile_app';

    protected $fillable = [
        'user_id',
        'session_id',
        'consent_type',
        'consented',
        'ip_address',
        'user_agent',
        'consent_details',
        'consent_version',
        'consent_source',
        'consented_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'consented' => 'boolean',
        'consent_details' => 'array',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /**
     * Get the user who gave consent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if consent is currently active.
     */
    public function isActive(): bool
    {
        return $this->consented && is_null($this->withdrawn_at);
    }

    /**
     * Check if consent has been withdrawn.
     */
    public function isWithdrawn(): bool
    {
        return ! is_null($this->withdrawn_at);
    }

    /**
     * Withdraw consent.
     */
    public function withdraw(): void
    {
        $this->update([
            'consented' => false,
            'withdrawn_at' => now(),
        ]);
    }

    /**
     * Get a human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->consent_type) {
            self::TYPE_NECESSARY => 'Necessary Cookies',
            self::TYPE_FUNCTIONAL => 'Functional Cookies',
            self::TYPE_ANALYTICS => 'Analytics & Statistics',
            self::TYPE_MARKETING => 'Marketing Communications',
            self::TYPE_PROFILING => 'AI-based Shift Matching',
            self::TYPE_THIRD_PARTY => 'Third-Party Data Sharing',
            default => ucfirst(str_replace('_', ' ', $this->consent_type)),
        };
    }

    /**
     * Get consent type description.
     */
    public function getTypeDescriptionAttribute(): string
    {
        return match ($this->consent_type) {
            self::TYPE_NECESSARY => 'Essential cookies required for the platform to function properly.',
            self::TYPE_FUNCTIONAL => 'Cookies that enhance your experience with additional features.',
            self::TYPE_ANALYTICS => 'Cookies that help us understand how you use our platform.',
            self::TYPE_MARKETING => 'Receive promotional emails and personalized ads.',
            self::TYPE_PROFILING => 'Allow AI to analyze your profile for better shift recommendations.',
            self::TYPE_THIRD_PARTY => 'Share data with trusted third-party partners.',
            default => '',
        };
    }

    /**
     * Scope for active consents.
     */
    public function scopeActive($query)
    {
        return $query->where('consented', true)
            ->whereNull('withdrawn_at');
    }

    /**
     * Scope for withdrawn consents.
     */
    public function scopeWithdrawn($query)
    {
        return $query->whereNotNull('withdrawn_at');
    }

    /**
     * Scope for consents by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Scope for consents by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for consents by session.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Get all available consent types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_NECESSARY => [
                'label' => 'Necessary Cookies',
                'description' => 'Essential cookies required for the platform to function properly.',
                'required' => true,
            ],
            self::TYPE_FUNCTIONAL => [
                'label' => 'Functional Cookies',
                'description' => 'Cookies that enhance your experience with additional features like preferences and settings.',
                'required' => false,
            ],
            self::TYPE_ANALYTICS => [
                'label' => 'Analytics & Statistics',
                'description' => 'Cookies that help us understand how you use our platform to improve it.',
                'required' => false,
            ],
            self::TYPE_MARKETING => [
                'label' => 'Marketing Communications',
                'description' => 'Receive promotional emails, job alerts, and personalized recommendations.',
                'required' => false,
            ],
            self::TYPE_PROFILING => [
                'label' => 'AI-based Shift Matching',
                'description' => 'Allow our AI system to analyze your profile for better shift recommendations.',
                'required' => false,
            ],
            self::TYPE_THIRD_PARTY => [
                'label' => 'Third-Party Data Sharing',
                'description' => 'Share your data with trusted payment processors and verification services.',
                'required' => false,
            ],
        ];
    }

    /**
     * Get all consent sources.
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_COOKIE_BANNER => 'Cookie Banner',
            self::SOURCE_REGISTRATION => 'Registration',
            self::SOURCE_SETTINGS => 'Privacy Settings',
            self::SOURCE_API => 'API',
            self::SOURCE_MOBILE_APP => 'Mobile App',
        ];
    }

    /**
     * Record consent for a user.
     */
    public static function recordForUser(
        int $userId,
        string $type,
        bool $consented,
        ?string $source = null,
        ?array $details = null
    ): self {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'consent_type' => $type,
            ],
            [
                'consented' => $consented,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'consent_details' => $details,
                'consent_version' => config('app.privacy_policy_version', '1.0'),
                'consent_source' => $source ?? self::SOURCE_SETTINGS,
                'consented_at' => $consented ? now() : null,
                'withdrawn_at' => $consented ? null : now(),
            ]
        );
    }

    /**
     * Record consent for a session (anonymous user).
     */
    public static function recordForSession(
        string $sessionId,
        string $type,
        bool $consented,
        ?string $source = null,
        ?array $details = null
    ): self {
        return self::updateOrCreate(
            [
                'session_id' => $sessionId,
                'consent_type' => $type,
            ],
            [
                'consented' => $consented,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'consent_details' => $details,
                'consent_version' => config('app.privacy_policy_version', '1.0'),
                'consent_source' => $source ?? self::SOURCE_COOKIE_BANNER,
                'consented_at' => $consented ? now() : null,
                'withdrawn_at' => $consented ? null : now(),
            ]
        );
    }

    /**
     * Check if a user has given consent for a specific type.
     */
    public static function hasUserConsent(int $userId, string $type): bool
    {
        return self::where('user_id', $userId)
            ->where('consent_type', $type)
            ->where('consented', true)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    /**
     * Check if a session has given consent for a specific type.
     */
    public static function hasSessionConsent(string $sessionId, string $type): bool
    {
        return self::where('session_id', $sessionId)
            ->where('consent_type', $type)
            ->where('consented', true)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    /**
     * Get all active consents for a user.
     */
    public static function getActiveConsentsForUser(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('consented', true)
            ->whereNull('withdrawn_at')
            ->pluck('consent_type')
            ->toArray();
    }

    /**
     * Transfer session consents to a user (after login/registration).
     */
    public static function transferSessionToUser(string $sessionId, int $userId): void
    {
        self::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->each(function ($consent) use ($userId) {
                // Check if user already has consent record for this type
                $existing = self::where('user_id', $userId)
                    ->where('consent_type', $consent->consent_type)
                    ->first();

                if (! $existing) {
                    // Transfer the session consent to the user
                    $consent->update([
                        'user_id' => $userId,
                        'session_id' => null,
                    ]);
                }
            });
    }
}
