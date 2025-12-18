<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QUA-002: Quality Audits - Shift Audit Model
 *
 * Represents a quality audit performed on a shift assignment.
 * Supports random audits, complaint-driven audits, scheduled audits,
 * and mystery shopper evaluations.
 *
 * @property int $id
 * @property string $audit_number
 * @property int $shift_id
 * @property int|null $shift_assignment_id
 * @property int|null $auditor_id
 * @property string $audit_type
 * @property string $status
 * @property array|null $checklist_items
 * @property int|null $overall_score
 * @property string|null $findings
 * @property string|null $recommendations
 * @property array|null $evidence_urls
 * @property bool|null $passed
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ShiftAudit extends Model
{
    use HasFactory;

    /**
     * Audit types.
     */
    public const TYPE_RANDOM = 'random';

    public const TYPE_COMPLAINT = 'complaint';

    public const TYPE_SCHEDULED = 'scheduled';

    public const TYPE_MYSTERY_SHOPPER = 'mystery_shopper';

    public const TYPES = [
        self::TYPE_RANDOM => 'Random Audit',
        self::TYPE_COMPLAINT => 'Complaint-Driven',
        self::TYPE_SCHEDULED => 'Scheduled Audit',
        self::TYPE_MYSTERY_SHOPPER => 'Mystery Shopper',
    ];

    /**
     * Audit statuses.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * Score thresholds for pass/fail.
     */
    public const PASSING_SCORE = 70;

    public const EXCELLENT_SCORE = 90;

    protected $fillable = [
        'audit_number',
        'shift_id',
        'shift_assignment_id',
        'auditor_id',
        'audit_type',
        'status',
        'checklist_items',
        'overall_score',
        'findings',
        'recommendations',
        'evidence_urls',
        'passed',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'evidence_urls' => 'array',
        'passed' => 'boolean',
        'overall_score' => 'integer',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'type_label',
        'status_label',
        'score_grade',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the shift being audited.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the specific shift assignment being audited.
     */
    public function shiftAssignment()
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Get the auditor (user performing the audit).
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Get the worker being audited (via shift assignment).
     */
    public function worker()
    {
        return $this->hasOneThrough(
            User::class,
            ShiftAssignment::class,
            'id',
            'id',
            'shift_assignment_id',
            'worker_id'
        );
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the audit type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->audit_type] ?? ucfirst($this->audit_type);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the score grade (A-F).
     */
    public function getScoreGradeAttribute(): ?string
    {
        if ($this->overall_score === null) {
            return null;
        }

        return match (true) {
            $this->overall_score >= 90 => 'A',
            $this->overall_score >= 80 => 'B',
            $this->overall_score >= 70 => 'C',
            $this->overall_score >= 60 => 'D',
            default => 'F'
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => $this->passed ? 'green' : 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get the score color for UI display.
     */
    public function getScoreColorAttribute(): string
    {
        if ($this->overall_score === null) {
            return 'gray';
        }

        return match (true) {
            $this->overall_score >= self::EXCELLENT_SCORE => 'green',
            $this->overall_score >= self::PASSING_SCORE => 'blue',
            $this->overall_score >= 50 => 'yellow',
            default => 'red'
        };
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get pending audits.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get in-progress audits.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get completed audits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get audits by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('audit_type', $type);
    }

    /**
     * Scope to get passed audits.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to get failed audits.
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    /**
     * Scope to get audits scheduled within a date range.
     */
    public function scopeScheduledBetween($query, $start, $end)
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    /**
     * Scope to get audits completed within a date range.
     */
    public function scopeCompletedBetween($query, $start, $end)
    {
        return $query->whereBetween('completed_at', [$start, $end]);
    }

    /**
     * Scope to get audits for a specific worker.
     */
    public function scopeForWorker($query, int $workerId)
    {
        return $query->whereHas('shiftAssignment', function ($q) use ($workerId) {
            $q->where('worker_id', $workerId);
        });
    }

    /**
     * Scope to get audits for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->whereHas('shift', function ($q) use ($venueId) {
            $q->where('venue_id', $venueId);
        });
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Generate a unique audit number.
     */
    public static function generateAuditNumber(): string
    {
        $year = date('Y');
        $lastAudit = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAudit && preg_match('/AUD-\d{4}-(\d{5})/', $lastAudit->audit_number, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('AUD-%d-%05d', $year, $nextNumber);
    }

    /**
     * Start the audit.
     */
    public function start(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update(['status' => self::STATUS_IN_PROGRESS]);

        return true;
    }

    /**
     * Complete the audit with results.
     */
    public function complete(array $results): bool
    {
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'checklist_items' => $results['checklist_items'] ?? $this->checklist_items,
            'overall_score' => $results['overall_score'] ?? null,
            'findings' => $results['findings'] ?? null,
            'recommendations' => $results['recommendations'] ?? null,
            'evidence_urls' => $results['evidence_urls'] ?? null,
            'passed' => ($results['overall_score'] ?? 0) >= self::PASSING_SCORE,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Cancel the audit.
     */
    public function cancel(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->update(['status' => self::STATUS_CANCELLED]);

        return true;
    }

    /**
     * Add evidence URL.
     */
    public function addEvidence(string $url): void
    {
        $evidenceUrls = $this->evidence_urls ?? [];
        $evidenceUrls[] = $url;
        $this->update(['evidence_urls' => $evidenceUrls]);
    }

    /**
     * Check if the audit has passed.
     */
    public function hasPassed(): bool
    {
        return $this->passed === true;
    }

    /**
     * Check if the audit is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status !== self::STATUS_PENDING || ! $this->scheduled_at) {
            return false;
        }

        return $this->scheduled_at->isPast();
    }

    /**
     * Get checklist completion percentage.
     */
    public function getChecklistCompletionPercentage(): float
    {
        if (empty($this->checklist_items)) {
            return 0;
        }

        $total = count($this->checklist_items);
        $completed = collect($this->checklist_items)
            ->filter(fn ($item) => isset($item['passed']))
            ->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Get items that failed in the checklist.
     */
    public function getFailedItems(): array
    {
        if (empty($this->checklist_items)) {
            return [];
        }

        return collect($this->checklist_items)
            ->filter(fn ($item) => isset($item['passed']) && $item['passed'] === false)
            ->values()
            ->toArray();
    }
}
