<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * COM-002: Push Notification Token Model
 *
 * Stores device tokens for push notifications (FCM, APNs, Web Push).
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $platform
 * @property string|null $device_id
 * @property string|null $device_name
 * @property string|null $device_model
 * @property string|null $app_version
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PushNotificationLog> $logs
 */
class PushNotificationToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'device_name',
        'device_model',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Platform constants
     */
    public const PLATFORM_FCM = 'fcm';

    public const PLATFORM_APNS = 'apns';

    public const PLATFORM_WEB = 'web';

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification logs for this token.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(PushNotificationLog::class, 'token_id');
    }

    /**
     * Scope: Active tokens only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inactive tokens only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Filter by platform.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: FCM tokens only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFcm($query)
    {
        return $query->where('platform', self::PLATFORM_FCM);
    }

    /**
     * Scope: APNs tokens only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApns($query)
    {
        return $query->where('platform', self::PLATFORM_APNS);
    }

    /**
     * Scope: Web Push tokens only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWebPush($query)
    {
        return $query->where('platform', self::PLATFORM_WEB);
    }

    /**
     * Scope: Tokens not used within a specified number of days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotUsedSince($query, int $days)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_used_at')
                ->where('created_at', '<', now()->subDays($days));
        })->orWhere('last_used_at', '<', now()->subDays($days));
    }

    /**
     * Scope: For a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mark this token as used (update last_used_at).
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Deactivate this token.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate this token.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Check if this is an FCM token.
     */
    public function isFcm(): bool
    {
        return $this->platform === self::PLATFORM_FCM;
    }

    /**
     * Check if this is an APNs token.
     */
    public function isApns(): bool
    {
        return $this->platform === self::PLATFORM_APNS;
    }

    /**
     * Check if this is a Web Push token.
     */
    public function isWebPush(): bool
    {
        return $this->platform === self::PLATFORM_WEB;
    }

    /**
     * Get formatted device info string.
     */
    public function getDeviceInfoAttribute(): string
    {
        $parts = array_filter([
            $this->device_name,
            $this->device_model,
        ]);

        return implode(' - ', $parts) ?: 'Unknown Device';
    }
}
