<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-001: Emergency Alert Model
 *
 * Represents an emergency SOS alert triggered by a user.
 * Supports location tracking, status management, and response workflow.
 *
 * @property int $id
 * @property string $alert_number
 * @property int $user_id
 * @property int|null $shift_id
 * @property int|null $venue_id
 * @property string $type
 * @property string $status
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $location_address
 * @property string|null $message
 * @property array|null $location_history
 * @property \Carbon\Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property string|null $resolution_notes
 * @property bool $emergency_services_called
 * @property bool $emergency_contacts_notified
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmergencyAlert extends Model
{
    use HasFactory;

    /**
     * Alert types.
     */
    public const TYPE_SOS = 'sos';

    public const TYPE_MEDICAL = 'medical';

    public const TYPE_SAFETY = 'safety';

    public const TYPE_HARASSMENT = 'harassment';

    public const TYPE_OTHER = 'other';

    /**
     * Alert statuses.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_FALSE_ALARM = 'false_alarm';

    /**
     * Available alert types with labels.
     */
    public const TYPES = [
        self::TYPE_SOS => 'SOS Emergency',
        self::TYPE_MEDICAL => 'Medical Emergency',
        self::TYPE_SAFETY => 'Safety Concern',
        self::TYPE_HARASSMENT => 'Harassment',
        self::TYPE_OTHER => 'Other Emergency',
    ];

    /**
     * Available statuses with labels.
     */
    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_RESPONDED => 'Responded',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_FALSE_ALARM => 'False Alarm',
    ];

    protected $fillable = [
        'alert_number',
        'user_id',
        'shift_id',
        'venue_id',
        'type',
        'status',
        'latitude',
        'longitude',
        'location_address',
        'message',
        'location_history',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'emergency_services_called',
        'emergency_contacts_notified',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_history' => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'emergency_services_called' => 'boolean',
        'emergency_contacts_notified' => 'boolean',
    ];

    protected $appends = [
        'type_label',
        'status_label',
        'is_active',
        'duration_minutes',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the user who triggered the alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift associated with the alert.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the venue associated with the alert.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the user who acknowledged the alert.
     */
    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved the alert.
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Check if alert is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get duration in minutes since alert was created.
     */
    public function getDurationMinutesAttribute(): int
    {
        $endTime = $this->resolved_at ?? now();

        return $this->created_at->diffInMinutes($endTime);
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope to active alerts only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to alerts that need response.
     */
    public function scopeNeedsResponse($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_RESPONDED]);
    }

    /**
     * Scope to resolved alerts.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope to false alarms.
     */
    public function scopeFalseAlarm($query)
    {
        return $query->where('status', self::STATUS_FALSE_ALARM);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope to alerts created in date range.
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to unacknowledged alerts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Scope to recent alerts (last N hours).
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for high priority alerts (SOS, medical).
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('type', [self::TYPE_SOS, self::TYPE_MEDICAL]);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Check if alert is of a specific type.
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Check if alert is SOS type.
     */
    public function isSOS(): bool
    {
        return $this->isType(self::TYPE_SOS);
    }

    /**
     * Check if alert is medical emergency.
     */
    public function isMedical(): bool
    {
        return $this->isType(self::TYPE_MEDICAL);
    }

    /**
     * Check if alert is high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->type, [self::TYPE_SOS, self::TYPE_MEDICAL]);
    }

    /**
     * Check if alert has been acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Check if alert has been resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if alert was marked as false alarm.
     */
    public function isFalseAlarm(): bool
    {
        return $this->status === self::STATUS_FALSE_ALARM;
    }

    /**
     * Check if alert has location data.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Add a location entry to history.
     */
    public function addLocationToHistory(float $lat, float $lng, ?int $accuracy = null): void
    {
        $history = $this->location_history ?? [];

        $history[] = [
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => $accuracy,
            'timestamp' => now()->toISOString(),
        ];

        $this->update([
            'location_history' => $history,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }

    /**
     * Get the most recent location from history.
     */
    public function getLatestLocation(): ?array
    {
        if (empty($this->location_history)) {
            if ($this->hasLocation()) {
                return [
                    'lat' => (float) $this->latitude,
                    'lng' => (float) $this->longitude,
                ];
            }

            return null;
        }

        return end($this->location_history);
    }

    /**
     * Get response time in minutes (time from creation to acknowledgement).
     */
    public function getResponseTimeMinutes(): ?int
    {
        if (! $this->acknowledged_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->acknowledged_at);
    }

    /**
     * Get resolution time in minutes (time from creation to resolution).
     */
    public function getResolutionTimeMinutes(): ?int
    {
        if (! $this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    /**
     * Get formatted alert number prefix based on year.
     */
    public static function generateAlertNumberPrefix(): string
    {
        return 'SOS-'.date('Y').'-';
    }
}
