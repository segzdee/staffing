<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-003: Labor Law Compliance - Worker Exemption Model
 *
 * Stores worker opt-outs and exemptions from specific labor law rules.
 *
 * @property int $id
 * @property int $user_id
 * @property int $labor_law_rule_id
 * @property string $reason
 * @property string|null $document_url
 * @property string|null $document_type
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property int|null $rejected_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property bool $worker_acknowledged
 * @property \Illuminate\Support\Carbon|null $worker_acknowledged_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkerExemption extends Model
{
    use HasFactory;

    // Status values
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'labor_law_rule_id',
        'reason',
        'document_url',
        'document_type',
        'valid_from',
        'valid_until',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'worker_acknowledged',
        'worker_acknowledged_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'worker_acknowledged' => 'boolean',
        'worker_acknowledged_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the worker who requested the exemption.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user - the worker.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the labor law rule this exemption applies to.
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
     * Get the admin who approved the exemption.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who rejected the exemption.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Active exemptions only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope: Pending exemptions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: For a specific worker.
     */
    public function scopeForWorker($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For a specific rule.
     */
    public function scopeForRule($query, int $ruleId)
    {
        return $query->where('labor_law_rule_id', $ruleId);
    }

    /**
     * Scope: For a specific rule code.
     */
    public function scopeForRuleCode($query, string $ruleCode)
    {
        return $query->whereHas('laborLawRule', function ($q) use ($ruleCode) {
            $q->where('rule_code', $ruleCode);
        });
    }

    /**
     * Scope: Expiring soon (within days).
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays($days))
            ->where('valid_until', '>=', now());
    }

    // ==================== STATUS MANAGEMENT ====================

    /**
     * Approve the exemption.
     */
    public function approve(int $approvedBy): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Reject the exemption.
     */
    public function reject(int $rejectedBy, string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Revoke the exemption.
     */
    public function revoke(int $revokedBy, ?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'rejected_by' => $revokedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason ?? 'Exemption revoked.',
        ]);

        return $this;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Worker acknowledges the exemption terms.
     */
    public function acknowledgeByWorker(?string $ipAddress = null, ?string $userAgent = null): self
    {
        $this->update([
            'worker_acknowledged' => true,
            'worker_acknowledged_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if exemption is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $now = now();

        if ($this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if exemption is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if exemption is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if exemption will expire within given days.
     */
    public function expiresWithinDays(int $days): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return $this->valid_until->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }

    /**
     * Check if worker has valid exemption for a rule.
     */
    public static function hasValidExemption(int $userId, string $ruleCode): bool
    {
        return self::forWorker($userId)
            ->forRuleCode($ruleCode)
            ->active()
            ->exists();
    }

    /**
     * Get valid exemption for a worker and rule.
     */
    public static function getValidExemption(int $userId, string $ruleCode): ?self
    {
        return self::forWorker($userId)
            ->forRuleCode($ruleCode)
            ->active()
            ->first();
    }

    /**
     * Create a new opt-out request.
     */
    public static function createOptOut(
        User $worker,
        LaborLawRule $rule,
        string $reason,
        ?\Illuminate\Support\Carbon $validFrom = null,
        ?\Illuminate\Support\Carbon $validUntil = null,
        ?string $documentUrl = null
    ): self {
        return self::create([
            'user_id' => $worker->id,
            'labor_law_rule_id' => $rule->id,
            'reason' => $reason,
            'document_url' => $documentUrl,
            'valid_from' => $validFrom ?? now(),
            'valid_until' => $validUntil,
            'status' => self::STATUS_PENDING,
        ]);
    }
}
