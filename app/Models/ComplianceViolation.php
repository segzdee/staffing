<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-003: Labor Law Compliance - Compliance Violation Model
 *
 * Records violations of labor law rules.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int $labor_law_rule_id
 * @property string $violation_code
 * @property string $description
 * @property array|null $violation_data
 * @property string $severity
 * @property string $status
 * @property bool $was_blocked
 * @property bool $worker_notified
 * @property bool $business_notified
 * @property string|null $resolution_notes
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ComplianceViolation extends Model
{
    use HasFactory;

    // Severity levels
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_VIOLATION = 'violation';

    public const SEVERITY_CRITICAL = 'critical';

    // Status values
    public const STATUS_DETECTED = 'detected';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_EXEMPTED = 'exempted';

    public const STATUS_APPEALED = 'appealed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shift_id',
        'labor_law_rule_id',
        'violation_code',
        'description',
        'violation_data',
        'severity',
        'status',
        'was_blocked',
        'worker_notified',
        'business_notified',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'acknowledged_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'violation_data' => 'array',
        'was_blocked' => 'boolean',
        'worker_notified' => 'boolean',
        'business_notified' => 'boolean',
        'resolved_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user (worker) who violated the rule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user - the worker involved.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the shift associated with this violation.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the labor law rule that was violated.
     */
    public function laborLawRule(): BelongsTo
    {
        return $this->belongsTo(LaborLawRule::class);
    }

    /**
     * Alias for laborLawRule.
     */
    public function rule(): BelongsTo
    {
        return $this->laborLawRule();
    }

    /**
     * Get the user who resolved this violation.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Unresolved violations.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DETECTED,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_APPEALED,
        ]);
    }

    /**
     * Scope: Resolved violations.
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_EXEMPTED,
        ]);
    }

    /**
     * Scope: By severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Critical violations.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope: Blocking violations.
     */
    public function scopeBlocking($query)
    {
        return $query->where('was_blocked', true);
    }

    /**
     * Scope: For a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope: Recent violations (within days).
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== STATUS MANAGEMENT ====================

    /**
     * Mark violation as acknowledged.
     */
    public function acknowledge(): self
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);

        return $this;
    }

    /**
     * Resolve the violation.
     */
    public function resolve(?int $resolvedBy = null, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Mark as exempted (worker has valid exemption).
     */
    public function markExempted(?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_EXEMPTED,
            'resolved_at' => now(),
            'resolution_notes' => $notes ?? 'Worker has valid exemption for this rule.',
        ]);

        return $this;
    }

    /**
     * Mark as appealed.
     */
    public function appeal(): self
    {
        $this->update([
            'status' => self::STATUS_APPEALED,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if violation is blocking.
     */
    public function isBlocking(): bool
    {
        return $this->was_blocked;
    }

    /**
     * Check if violation is resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_EXEMPTED]);
    }

    /**
     * Check if violation is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Get actual value from violation data.
     */
    public function getActualValue(string $key = 'actual')
    {
        return $this->violation_data[$key] ?? null;
    }

    /**
     * Get limit value from violation data.
     */
    public function getLimitValue(string $key = 'limit')
    {
        return $this->violation_data[$key] ?? null;
    }

    /**
     * Get the violation difference (actual - limit).
     */
    public function getDifference(): ?float
    {
        $actual = $this->getActualValue();
        $limit = $this->getLimitValue();

        if ($actual === null || $limit === null) {
            return null;
        }

        return $actual - $limit;
    }

    /**
     * Create a violation record.
     */
    public static function createViolation(
        User $worker,
        LaborLawRule $rule,
        string $description,
        array $violationData = [],
        ?Shift $shift = null,
        bool $wasBlocked = false
    ): self {
        $severity = self::determineSeverity($rule, $violationData);

        return self::create([
            'user_id' => $worker->id,
            'shift_id' => $shift?->id,
            'labor_law_rule_id' => $rule->id,
            'violation_code' => $rule->rule_code.'_EXCEEDED',
            'description' => $description,
            'violation_data' => $violationData,
            'severity' => $severity,
            'status' => self::STATUS_DETECTED,
            'was_blocked' => $wasBlocked,
        ]);
    }

    /**
     * Determine severity based on rule and data.
     */
    protected static function determineSeverity(LaborLawRule $rule, array $data): string
    {
        // Critical if hard block rule
        if ($rule->enforcement === LaborLawRule::ENFORCEMENT_HARD_BLOCK) {
            return self::SEVERITY_CRITICAL;
        }

        // Calculate percentage over limit
        if (isset($data['actual']) && isset($data['limit']) && $data['limit'] > 0) {
            $percentOver = (($data['actual'] - $data['limit']) / $data['limit']) * 100;

            if ($percentOver > 25) {
                return self::SEVERITY_VIOLATION;
            }
            if ($percentOver > 10) {
                return self::SEVERITY_WARNING;
            }
        }

        return self::SEVERITY_INFO;
    }
}
