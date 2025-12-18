<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SAF-002: Incident Reporting Model
 *
 * @property int $id
 * @property string $incident_number
 * @property int|null $shift_id
 * @property int|null $venue_id
 * @property int $reported_by
 * @property int|null $involves_user_id
 * @property string $type
 * @property string $severity
 * @property string $description
 * @property string|null $location_description
 * @property float|null $latitude
 * @property float|null $longitude
 * @property \Illuminate\Support\Carbon $incident_time
 * @property array|null $evidence_urls
 * @property array|null $witness_info
 * @property string $status
 * @property int|null $assigned_to
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property bool $requires_insurance_claim
 * @property string|null $insurance_claim_number
 * @property bool $authorities_notified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Incident extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'incidents';

    /**
     * Incident types.
     */
    public const TYPE_INJURY = 'injury';

    public const TYPE_HARASSMENT = 'harassment';

    public const TYPE_THEFT = 'theft';

    public const TYPE_SAFETY_HAZARD = 'safety_hazard';

    public const TYPE_PROPERTY_DAMAGE = 'property_damage';

    public const TYPE_VERBAL_ABUSE = 'verbal_abuse';

    public const TYPE_OTHER = 'other';

    /**
     * All available incident types.
     */
    public const TYPES = [
        self::TYPE_INJURY,
        self::TYPE_HARASSMENT,
        self::TYPE_THEFT,
        self::TYPE_SAFETY_HAZARD,
        self::TYPE_PROPERTY_DAMAGE,
        self::TYPE_VERBAL_ABUSE,
        self::TYPE_OTHER,
    ];

    /**
     * Human-readable incident type labels.
     */
    public const TYPE_LABELS = [
        self::TYPE_INJURY => 'Injury',
        self::TYPE_HARASSMENT => 'Harassment',
        self::TYPE_THEFT => 'Theft',
        self::TYPE_SAFETY_HAZARD => 'Safety Hazard',
        self::TYPE_PROPERTY_DAMAGE => 'Property Damage',
        self::TYPE_VERBAL_ABUSE => 'Verbal Abuse',
        self::TYPE_OTHER => 'Other',
    ];

    /**
     * Severity levels.
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * All available severity levels.
     */
    public const SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    /**
     * Status values.
     */
    public const STATUS_REPORTED = 'reported';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_CLOSED = 'closed';

    /**
     * All available statuses.
     */
    public const STATUSES = [
        self::STATUS_REPORTED,
        self::STATUS_INVESTIGATING,
        self::STATUS_RESOLVED,
        self::STATUS_ESCALATED,
        self::STATUS_CLOSED,
    ];

    /**
     * Human-readable status labels.
     */
    public const STATUS_LABELS = [
        self::STATUS_REPORTED => 'Reported',
        self::STATUS_INVESTIGATING => 'Under Investigation',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_ESCALATED => 'Escalated',
        self::STATUS_CLOSED => 'Closed',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'incident_number',
        'shift_id',
        'venue_id',
        'reported_by',
        'involves_user_id',
        'type',
        'severity',
        'description',
        'location_description',
        'latitude',
        'longitude',
        'incident_time',
        'evidence_urls',
        'witness_info',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
        'requires_insurance_claim',
        'insurance_claim_number',
        'authorities_notified',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'incident_time' => 'datetime',
            'resolved_at' => 'datetime',
            'evidence_urls' => 'array',
            'witness_info' => 'array',
            'requires_insurance_claim' => 'boolean',
            'authorities_notified' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the shift associated with this incident.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the venue associated with this incident.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the user who reported this incident.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user involved in this incident.
     */
    public function involvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'involves_user_id');
    }

    /**
     * Get the admin assigned to investigate this incident.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all updates for this incident.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get public updates (visible to reporter).
     */
    public function publicUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)
            ->where('is_internal', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get internal updates (admin only).
     */
    public function internalUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)
            ->where('is_internal', true)
            ->orderBy('created_at', 'desc');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get incidents by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get incidents by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get incidents by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get open incidents (not closed).
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope to get resolved/closed incidents.
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope to get critical incidents.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to get high priority incidents.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope to get incidents assigned to a specific admin.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get unassigned incidents.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope to get incidents reported by a specific user.
     */
    public function scopeReportedBy($query, int $userId)
    {
        return $query->where('reported_by', $userId);
    }

    /**
     * Scope to get incidents for a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to get incidents for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope to get incidents requiring insurance claims.
     */
    public function scopeRequiresInsurance($query)
    {
        return $query->where('requires_insurance_claim', true);
    }

    /**
     * Scope to get incidents where authorities were notified.
     */
    public function scopeAuthoritiesNotified($query)
    {
        return $query->where('authorities_notified', true);
    }

    /**
     * Scope to get incidents within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('incident_time', [$startDate, $endDate]);
    }

    /**
     * Scope to get escalated incidents.
     */
    public function scopeEscalated($query)
    {
        return $query->where('status', self::STATUS_ESCALATED);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if incident is open.
     */
    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if incident is resolved or closed.
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if incident is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if incident is high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Check if incident is escalated.
     */
    public function isEscalated(): bool
    {
        return $this->status === self::STATUS_ESCALATED;
    }

    /**
     * Check if incident has an investigator assigned.
     */
    public function hasAssignee(): bool
    {
        return $this->assigned_to !== null;
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get severity badge color class.
     */
    public function getSeverityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'bg-gray-100 text-gray-800',
            self::SEVERITY_MEDIUM => 'bg-yellow-100 text-yellow-800',
            self::SEVERITY_HIGH => 'bg-orange-100 text-orange-800',
            self::SEVERITY_CRITICAL => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status badge color class.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_REPORTED => 'bg-blue-100 text-blue-800',
            self::STATUS_INVESTIGATING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_RESOLVED => 'bg-green-100 text-green-800',
            self::STATUS_ESCALATED => 'bg-red-100 text-red-800',
            self::STATUS_CLOSED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get evidence count.
     */
    public function getEvidenceCount(): int
    {
        return is_array($this->evidence_urls) ? count($this->evidence_urls) : 0;
    }

    /**
     * Get witness count.
     */
    public function getWitnessCount(): int
    {
        return is_array($this->witness_info) ? count($this->witness_info) : 0;
    }

    /**
     * Add evidence URL.
     */
    public function addEvidence(string $url): void
    {
        $evidence = $this->evidence_urls ?? [];
        $evidence[] = $url;
        $this->evidence_urls = $evidence;
        $this->save();
    }

    /**
     * Add witness information.
     */
    public function addWitness(array $witnessData): void
    {
        $witnesses = $this->witness_info ?? [];
        $witnesses[] = $witnessData;
        $this->witness_info = $witnesses;
        $this->save();
    }

    /**
     * Check if user can view this incident.
     */
    public function canBeViewedBy(User $user): bool
    {
        // Admins can view all incidents
        if ($user->isAdmin()) {
            return true;
        }

        // Reporter can view their own incident
        if ($this->reported_by === $user->id) {
            return true;
        }

        // Involved user can view incident
        if ($this->involves_user_id === $user->id) {
            return true;
        }

        // Business owner can view incidents at their venues/shifts
        if ($user->isBusiness() && $this->shift) {
            return $this->shift->business_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user can update this incident.
     */
    public function canBeUpdatedBy(User $user): bool
    {
        // Only admins can update incidents
        if ($user->isAdmin()) {
            return true;
        }

        // Reporter can only add updates, not change status
        if ($this->reported_by === $user->id && $this->isOpen()) {
            return true;
        }

        return false;
    }

    /**
     * Get time elapsed since incident.
     */
    public function getTimeElapsed(): string
    {
        return $this->incident_time->diffForHumans();
    }

    /**
     * Get resolution time in hours (if resolved).
     */
    public function getResolutionTimeHours(): ?float
    {
        if (! $this->resolved_at) {
            return null;
        }

        return round($this->created_at->diffInHours($this->resolved_at), 1);
    }
}
