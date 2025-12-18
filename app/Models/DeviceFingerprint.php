<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * FIN-015: Device Fingerprint Model
 *
 * Tracks device fingerprints for fraud detection.
 *
 * @property int $id
 * @property int $user_id
 * @property string $fingerprint_hash
 * @property array|null $fingerprint_data
 * @property string|null $ip_address
 * @property int $use_count
 * @property bool $is_trusted
 * @property bool $is_blocked
 * @property \Illuminate\Support\Carbon $first_seen_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DeviceFingerprint extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'fingerprint_data',
        'ip_address',
        'use_count',
        'is_trusted',
        'is_blocked',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fingerprint_data' => 'array',
            'is_trusted' => 'boolean',
            'is_blocked' => 'boolean',
            'use_count' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this fingerprint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    /**
     * Scope for trusted devices.
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Scope for blocked devices.
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope for active (not blocked) devices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    /**
     * Scope by fingerprint hash.
     */
    public function scopeWithHash($query, string $hash)
    {
        return $query->where('fingerprint_hash', $hash);
    }

    /**
     * Scope for recently used devices.
     */
    public function scopeRecentlyUsed($query)
    {
        return $query->where('last_seen_at', '>=', now()->subWeek());
    }

    /**
     * Scope for frequently used devices.
     */
    public function scopeFrequentlyUsed($query, int $minCount = 5)
    {
        return $query->where('use_count', '>=', $minCount);
    }

    // ========== Static Methods ==========

    /**
     * Generate a fingerprint hash from request data.
     */
    public static function generateHash(Request $request, ?array $additionalData = null): string
    {
        $components = [
            $request->userAgent() ?? '',
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
        ];

        if ($additionalData) {
            $components = array_merge($components, array_values($additionalData));
        }

        return hash('sha256', implode('|', $components));
    }

    /**
     * Extract fingerprint data from request.
     *
     * @return array<string, mixed>
     */
    public static function extractDataFromRequest(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';

        return [
            'user_agent' => $userAgent,
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'browser' => self::parseBrowser($userAgent),
            'os' => self::parseOS($userAgent),
            'device_type' => self::parseDeviceType($userAgent),
        ];
    }

    /**
     * Parse browser from user agent.
     */
    protected static function parseBrowser(string $userAgent): string
    {
        if (stripos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        }

        if (stripos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        }

        if (stripos($userAgent, 'Safari') !== false) {
            return 'Safari';
        }

        if (stripos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }

        if (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    /**
     * Parse OS from user agent.
     */
    protected static function parseOS(string $userAgent): string
    {
        if (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        }

        if (stripos($userAgent, 'Mac') !== false) {
            return 'macOS';
        }

        if (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        if (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        }

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }

    /**
     * Parse device type from user agent.
     */
    protected static function parseDeviceType(string $userAgent): string
    {
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            if (stripos($userAgent, 'Tablet') !== false || stripos($userAgent, 'iPad') !== false) {
                return 'Tablet';
            }

            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Check if a fingerprint hash is blocked globally.
     */
    public static function isHashBlocked(string $hash): bool
    {
        return self::query()
            ->where('fingerprint_hash', $hash)
            ->where('is_blocked', true)
            ->exists();
    }

    /**
     * Get all users associated with a fingerprint hash.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function getUsersForHash(string $hash): \Illuminate\Support\Collection
    {
        return self::query()
            ->where('fingerprint_hash', $hash)
            ->pluck('user_id')
            ->unique();
    }

    // ========== Instance Methods ==========

    /**
     * Record usage of this device.
     */
    public function recordUsage(?string $ipAddress = null): void
    {
        $this->increment('use_count');
        $this->update([
            'last_seen_at' => now(),
            'ip_address' => $ipAddress ?? $this->ip_address,
        ]);
    }

    /**
     * Mark device as trusted.
     */
    public function markTrusted(): void
    {
        $this->update(['is_trusted' => true]);
    }

    /**
     * Mark device as blocked.
     */
    public function markBlocked(): void
    {
        $this->update(['is_blocked' => true]);
    }

    /**
     * Unblock device.
     */
    public function unblock(): void
    {
        $this->update(['is_blocked' => false]);
    }

    /**
     * Get browser name from fingerprint data.
     */
    public function getBrowserAttribute(): string
    {
        return $this->fingerprint_data['browser'] ?? 'Unknown';
    }

    /**
     * Get OS name from fingerprint data.
     */
    public function getOsAttribute(): string
    {
        return $this->fingerprint_data['os'] ?? 'Unknown';
    }

    /**
     * Get device type from fingerprint data.
     */
    public function getDeviceTypeAttribute(): string
    {
        return $this->fingerprint_data['device_type'] ?? 'Unknown';
    }

    /**
     * Get a friendly device description.
     */
    public function getDeviceDescriptionAttribute(): string
    {
        return sprintf('%s on %s (%s)', $this->browser, $this->os, $this->device_type);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->is_blocked) {
            return 'bg-red-600 text-white';
        }

        if ($this->is_trusted) {
            return 'bg-green-500 text-white';
        }

        return 'bg-gray-500 text-white';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_blocked) {
            return 'Blocked';
        }

        if ($this->is_trusted) {
            return 'Trusted';
        }

        return 'Unknown';
    }
}
