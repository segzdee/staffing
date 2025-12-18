<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Subject Request Model
 *
 * Handles data subject requests under GDPR (Article 15-22) and CCPA regulations.
 *
 * @property int $id
 * @property string $request_number
 * @property int|null $user_id
 * @property string $email
 * @property string $type
 * @property string $status
 * @property string|null $description
 * @property string|null $verification_token
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon $due_date
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $completion_notes
 * @property string|null $export_file_path
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $requester_ip
 * @property string|null $requester_user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read User|null $assignedAdmin
 */
class DataSubjectRequest extends Model
{
    use HasFactory;

    // Request Types (GDPR Articles)
    public const TYPE_ACCESS = 'access';           // Article 15 - Right of access

    public const TYPE_RECTIFICATION = 'rectification'; // Article 16 - Right to rectification

    public const TYPE_ERASURE = 'erasure';         // Article 17 - Right to erasure

    public const TYPE_PORTABILITY = 'portability'; // Article 20 - Right to data portability

    public const TYPE_RESTRICTION = 'restriction'; // Article 18 - Right to restriction

    public const TYPE_OBJECTION = 'objection';     // Article 21 - Right to object

    // Request Statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFYING = 'verifying';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    // GDPR Compliance: 30 days to respond
    public const RESPONSE_DEADLINE_DAYS = 30;

    protected $fillable = [
        'request_number',
        'user_id',
        'email',
        'type',
        'status',
        'description',
        'verification_token',
        'verified_at',
        'due_date',
        'assigned_to',
        'completed_at',
        'completion_notes',
        'export_file_path',
        'metadata',
        'requester_ip',
        'requester_user_agent',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DataSubjectRequest $request) {
            // Generate unique request number
            if (empty($request->request_number)) {
                $request->request_number = self::generateRequestNumber();
            }

            // Set due date (30 days from creation)
            if (empty($request->due_date)) {
                $request->due_date = now()->addDays(self::RESPONSE_DEADLINE_DAYS);
            }

            // Generate verification token
            if (empty($request->verification_token)) {
                $request->verification_token = Str::random(64);
            }

            // Set initial status
            if (empty($request->status)) {
                $request->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * Generate a unique request number.
     */
    public static function generateRequestNumber(): string
    {
        $year = now()->year;
        $lastRequest = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRequest
            ? intval(substr($lastRequest->request_number, -5)) + 1
            : 1;

        return sprintf('DSR-%d-%05d', $year, $sequence);
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin assigned to this request.
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if the request is verified.
     */
    public function isVerified(): bool
    {
        return ! is_null($this->verified_at);
    }

    /**
     * Check if the request is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->status !== self::STATUS_REJECTED
            && now()->gt($this->due_date);
    }

    /**
     * Check if the request can be processed.
     */
    public function canProcess(): bool
    {
        return $this->isVerified()
            && $this->status === self::STATUS_VERIFYING;
    }

    /**
     * Get days remaining until due date.
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get the urgency level based on days remaining.
     */
    public function getUrgencyAttribute(): string
    {
        $days = $this->days_remaining;

        if ($days <= 0) {
            return 'overdue';
        }
        if ($days <= 5) {
            return 'critical';
        }
        if ($days <= 10) {
            return 'high';
        }
        if ($days <= 20) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get a human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ACCESS => 'Data Access Request (GDPR Art. 15)',
            self::TYPE_RECTIFICATION => 'Data Rectification Request (GDPR Art. 16)',
            self::TYPE_ERASURE => 'Data Erasure Request (GDPR Art. 17)',
            self::TYPE_PORTABILITY => 'Data Portability Request (GDPR Art. 20)',
            self::TYPE_RESTRICTION => 'Processing Restriction Request (GDPR Art. 18)',
            self::TYPE_OBJECTION => 'Processing Objection (GDPR Art. 21)',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Verification',
            self::STATUS_VERIFYING => 'Identity Verification In Progress',
            self::STATUS_PROCESSING => 'Processing Request',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Verify the request with the given token.
     */
    public function verify(string $token): bool
    {
        if ($this->verification_token === $token && ! $this->isVerified()) {
            $this->update([
                'verified_at' => now(),
                'status' => self::STATUS_VERIFYING,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Mark the request as processing.
     */
    public function startProcessing(?int $adminId = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'assigned_to' => $adminId ?? $this->assigned_to,
        ]);
    }

    /**
     * Mark the request as completed.
     */
    public function complete(string $notes = '', ?string $exportPath = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $notes,
            'export_file_path' => $exportPath,
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'completed_at' => now(),
            'completion_notes' => $reason,
        ]);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for verified requests awaiting processing.
     */
    public function scopeAwaitingProcessing($query)
    {
        return $query->where('status', self::STATUS_VERIFYING);
    }

    /**
     * Scope for requests currently being processed.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for overdue requests.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_REJECTED])
            ->where('due_date', '<', now());
    }

    /**
     * Scope for requests by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for requests assigned to a specific admin.
     */
    public function scopeAssignedTo($query, int $adminId)
    {
        return $query->where('assigned_to', $adminId);
    }

    /**
     * Get all available request types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ACCESS => 'Data Access (Article 15)',
            self::TYPE_RECTIFICATION => 'Data Rectification (Article 16)',
            self::TYPE_ERASURE => 'Data Erasure/Right to be Forgotten (Article 17)',
            self::TYPE_PORTABILITY => 'Data Portability (Article 20)',
            self::TYPE_RESTRICTION => 'Restriction of Processing (Article 18)',
            self::TYPE_OBJECTION => 'Object to Processing (Article 21)',
        ];
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_VERIFYING => 'Verifying',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }
}
