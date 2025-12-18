<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QUA-003: BugReport Model
 *
 * Stores user-submitted bug reports with severity and status tracking.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string|null $steps_to_reproduce
 * @property string|null $expected_behavior
 * @property string|null $actual_behavior
 * @property string $severity
 * @property string $status
 * @property array|null $attachments
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $app_version
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 */
class BugReport extends Model
{
    use HasFactory;

    /**
     * Severity levels.
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Statuses.
     */
    public const STATUS_REPORTED = 'reported';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_FIXED = 'fixed';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_WONT_FIX = 'wont_fix';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'steps_to_reproduce',
        'expected_behavior',
        'actual_behavior',
        'severity',
        'status',
        'attachments',
        'browser',
        'os',
        'app_version',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * Get the user who submitted this bug report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for reports by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for reports by severity.
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for open reports.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_FIXED,
            self::STATUS_CLOSED,
            self::STATUS_WONT_FIX,
        ]);
    }

    /**
     * Scope for critical reports.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to order by severity (critical first).
     */
    public function scopeOrderBySeverity($query)
    {
        return $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')");
    }

    /**
     * Check if report is open.
     */
    public function isOpen(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_FIXED,
            self::STATUS_CLOSED,
            self::STATUS_WONT_FIX,
        ]);
    }

    /**
     * Check if report is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Get human-readable severity label.
     */
    public function getSeverityLabel(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'Low',
            self::SEVERITY_MEDIUM => 'Medium',
            self::SEVERITY_HIGH => 'High',
            self::SEVERITY_CRITICAL => 'Critical',
            default => ucfirst($this->severity),
        };
    }

    /**
     * Get severity color for UI.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'green',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REPORTED => 'Reported',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_FIXED => 'Fixed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_WONT_FIX => "Won't Fix",
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_REPORTED => 'gray',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_FIXED => 'green',
            self::STATUS_CLOSED => 'gray',
            self::STATUS_WONT_FIX => 'red',
            default => 'gray',
        };
    }

    /**
     * Add an attachment path.
     */
    public function addAttachment(string $path): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = $path;
        $this->update(['attachments' => $attachments]);
    }

    /**
     * Get attachment count.
     */
    public function getAttachmentCount(): int
    {
        return count($this->attachments ?? []);
    }
}
