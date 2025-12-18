<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-004: Venue Safety Flag Model
 *
 * Represents a safety concern flag reported by a worker for a venue.
 * Tracks the flag type, severity, status, and resolution process.
 *
 * @property int $id
 * @property int $venue_id
 * @property int $reported_by
 * @property string $flag_type
 * @property string $severity
 * @property string $description
 * @property array|null $evidence_urls
 * @property string $status
 * @property int|null $assigned_to
 * @property string|null $resolution_notes
 * @property \Carbon\Carbon|null $resolved_at
 * @property bool $business_notified
 * @property \Carbon\Carbon|null $business_response_due
 * @property string|null $business_response
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VenueSafetyFlag extends Model
{
    use HasFactory;

    /**
     * Flag type constants.
     */
    public const TYPE_HARASSMENT = 'harassment';

    public const TYPE_UNSAFE_CONDITIONS = 'unsafe_conditions';

    public const TYPE_POOR_LIGHTING = 'poor_lighting';

    public const TYPE_NO_BREAKS = 'no_breaks';

    public const TYPE_UNPAID_OVERTIME = 'unpaid_overtime';

    public const TYPE_INADEQUATE_TRAINING = 'inadequate_training';

    public const TYPE_EQUIPMENT_FAILURE = 'equipment_failure';

    public const TYPE_OTHER = 'other';

    /**
     * Flag type labels for display.
     */
    public const TYPE_LABELS = [
        self::TYPE_HARASSMENT => 'Harassment',
        self::TYPE_UNSAFE_CONDITIONS => 'Unsafe Conditions',
        self::TYPE_POOR_LIGHTING => 'Poor Lighting',
        self::TYPE_NO_BREAKS => 'No Breaks Allowed',
        self::TYPE_UNPAID_OVERTIME => 'Unpaid Overtime',
        self::TYPE_INADEQUATE_TRAINING => 'Inadequate Training',
        self::TYPE_EQUIPMENT_FAILURE => 'Equipment Failure',
        self::TYPE_OTHER => 'Other',
    ];

    /**
     * Severity level constants.
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Severity labels for display.
     */
    public const SEVERITY_LABELS = [
        self::SEVERITY_LOW => 'Low',
        self::SEVERITY_MEDIUM => 'Medium',
        self::SEVERITY_HIGH => 'High',
        self::SEVERITY_CRITICAL => 'Critical',
    ];

    /**
     * Severity colors for UI.
     */
    public const SEVERITY_COLORS = [
        self::SEVERITY_LOW => 'blue',
        self::SEVERITY_MEDIUM => 'yellow',
        self::SEVERITY_HIGH => 'orange',
        self::SEVERITY_CRITICAL => 'red',
    ];

    /**
     * Status constants.
     */
    public const STATUS_REPORTED = 'reported';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Status labels for display.
     */
    public const STATUS_LABELS = [
        self::STATUS_REPORTED => 'Reported',
        self::STATUS_INVESTIGATING => 'Under Investigation',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_DISMISSED => 'Dismissed',
    ];

    /**
     * Status colors for UI.
     */
    public const STATUS_COLORS = [
        self::STATUS_REPORTED => 'yellow',
        self::STATUS_INVESTIGATING => 'blue',
        self::STATUS_RESOLVED => 'green',
        self::STATUS_DISMISSED => 'gray',
    ];

    /**
     * Business response deadline in hours.
     */
    public const BUSINESS_RESPONSE_HOURS = 48;

    protected $fillable = [
        'venue_id',
        'reported_by',
        'flag_type',
        'severity',
        'description',
        'evidence_urls',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
        'business_notified',
        'business_response_due',
        'business_response',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'business_notified' => 'boolean',
        'resolved_at' => 'datetime',
        'business_response_due' => 'datetime',
    ];

    protected $appends = [
        'flag_type_label',
        'severity_label',
        'status_label',
        'is_open',
        'is_overdue',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the venue this flag belongs to.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the user who reported this flag.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the admin assigned to investigate this flag.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the label for the flag type.
     */
    public function getFlagTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->flag_type] ?? ucfirst($this->flag_type);
    }

    /**
     * Get the label for the severity.
     */
    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITY_LABELS[$this->severity] ?? ucfirst($this->severity);
    }

    /**
     * Get the color for the severity.
     */
    public function getSeverityColorAttribute(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? 'gray';
    }

    /**
     * Get the label for the status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the color for the status.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Check if the flag is still open.
     */
    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_REPORTED, self::STATUS_INVESTIGATING]);
    }

    /**
     * Check if business response is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->business_notified || ! $this->business_response_due) {
            return false;
        }

        return $this->business_response_due->isPast() && empty($this->business_response);
    }

    /**
     * Get the time remaining for business response.
     */
    public function getTimeToRespondAttribute(): ?string
    {
        if (! $this->business_response_due || ! empty($this->business_response)) {
            return null;
        }

        if ($this->business_response_due->isPast()) {
            return 'Overdue';
        }

        return $this->business_response_due->diffForHumans();
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope to get flags for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope to get flags by a specific user.
     */
    public function scopeByReporter($query, int $userId)
    {
        return $query->where('reported_by', $userId);
    }

    /**
     * Scope to get flags assigned to a specific admin.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get open flags (reported or investigating).
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_REPORTED, self::STATUS_INVESTIGATING]);
    }

    /**
     * Scope to get closed flags (resolved or dismissed).
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_DISMISSED]);
    }

    /**
     * Scope to get flags by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get flags by severity.
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get flags by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('flag_type', $type);
    }

    /**
     * Scope to get critical flags.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to get high severity flags.
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope to get flags with overdue business response.
     */
    public function scopeOverdue($query)
    {
        return $query->where('business_notified', true)
            ->whereNotNull('business_response_due')
            ->where('business_response_due', '<', now())
            ->whereNull('business_response');
    }

    /**
     * Scope to get unassigned flags.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope to get flags within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent flags.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Check if the flag requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL ||
            ($this->severity === self::SEVERITY_HIGH && $this->is_open);
    }

    /**
     * Set the business response deadline.
     */
    public function setBusinessResponseDeadline(): void
    {
        $this->business_response_due = now()->addHours(self::BUSINESS_RESPONSE_HOURS);
        $this->save();
    }

    /**
     * Mark the business as notified.
     */
    public function markBusinessNotified(): void
    {
        $this->business_notified = true;
        if (! $this->business_response_due) {
            $this->business_response_due = now()->addHours(self::BUSINESS_RESPONSE_HOURS);
        }
        $this->save();
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get all flag types as options array.
     */
    public static function getTypeOptions(): array
    {
        return self::TYPE_LABELS;
    }

    /**
     * Get all severity levels as options array.
     */
    public static function getSeverityOptions(): array
    {
        return self::SEVERITY_LABELS;
    }

    /**
     * Get all status options array.
     */
    public static function getStatusOptions(): array
    {
        return self::STATUS_LABELS;
    }

    /**
     * Count flags by status for a venue.
     */
    public static function countByStatusForVenue(int $venueId): array
    {
        return self::forVenue($venueId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Count flags by type for a venue.
     */
    public static function countByTypeForVenue(int $venueId): array
    {
        return self::forVenue($venueId)
            ->selectRaw('flag_type, COUNT(*) as count')
            ->groupBy('flag_type')
            ->pluck('count', 'flag_type')
            ->toArray();
    }
}
