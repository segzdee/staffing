<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Worker Activation Log Model
 * STAFF-REG-010: Worker Activation Tracking
 *
 * Tracks worker activation status and eligibility history.
 */
class WorkerActivationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'eligibility_checks',
        'all_required_complete',
        'required_steps_complete',
        'required_steps_total',
        'recommended_steps_complete',
        'recommended_steps_total',
        'profile_completeness',
        'skills_count',
        'certifications_count',
        'initial_tier',
        'initial_reliability_score',
        'referral_code_used',
        'referred_by_user_id',
        'referral_bonus_amount',
        'referral_bonus_processed',
        'referral_bonus_processed_at',
        'activated_at',
        'activated_by',
        'activation_notes',
        'days_to_activation',
        'activation_source',
    ];

    protected $casts = [
        'eligibility_checks' => 'array',
        'all_required_complete' => 'boolean',
        'referral_bonus_processed' => 'boolean',
        'profile_completeness' => 'decimal:2',
        'initial_reliability_score' => 'decimal:2',
        'referral_bonus_amount' => 'decimal:2',
        'activated_at' => 'datetime',
        'referral_bonus_processed_at' => 'datetime',
    ];

    /**
     * Activation statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'eligible' => 'Eligible for Activation',
        'activated' => 'Activated',
        'suspended' => 'Suspended',
        'deactivated' => 'Deactivated',
    ];

    /**
     * Initial tier assignments.
     */
    public const INITIAL_TIERS = [
        'bronze' => 'Bronze',
        'silver' => 'Silver',
        'gold' => 'Gold',
        'platinum' => 'Platinum',
    ];

    /**
     * Activation sources.
     */
    public const SOURCES = [
        'self' => 'Self-Activated',
        'admin' => 'Admin Activated',
        'auto' => 'Auto-Activated',
    ];

    /**
     * Get the user this log belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who activated.
     */
    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Get the referrer.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Check if worker is activated.
     */
    public function isActivated(): bool
    {
        return $this->status === 'activated';
    }

    /**
     * Check if worker is eligible.
     */
    public function isEligible(): bool
    {
        return $this->status === 'eligible' || $this->all_required_complete;
    }

    /**
     * Check if referral bonus is pending.
     */
    public function hasUnprocessedReferralBonus(): bool
    {
        return $this->referral_bonus_amount > 0
            && !$this->referral_bonus_processed;
    }

    /**
     * Get specific eligibility check result.
     */
    public function getCheckResult(string $checkName): ?array
    {
        return $this->eligibility_checks[$checkName] ?? null;
    }

    /**
     * Check if a specific eligibility check passed.
     */
    public function checkPassed(string $checkName): bool
    {
        $check = $this->getCheckResult($checkName);
        return $check && ($check['passed'] ?? false);
    }

    /**
     * Get failed eligibility checks.
     */
    public function getFailedChecks(): array
    {
        if (!$this->eligibility_checks) {
            return [];
        }

        return array_filter($this->eligibility_checks, function ($check) {
            return !($check['passed'] ?? false);
        });
    }

    /**
     * Get passed eligibility checks.
     */
    public function getPassedChecks(): array
    {
        if (!$this->eligibility_checks) {
            return [];
        }

        return array_filter($this->eligibility_checks, function ($check) {
            return $check['passed'] ?? false;
        });
    }

    /**
     * Calculate completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $total = $this->required_steps_total + $this->recommended_steps_total;
        if ($total === 0) {
            return 0;
        }

        $complete = $this->required_steps_complete + $this->recommended_steps_complete;
        return round(($complete / $total) * 100, 2);
    }

    /**
     * Mark as activated.
     */
    public function markActivated(?int $activatedBy = null, string $source = 'self', ?string $notes = null): self
    {
        $this->update([
            'status' => 'activated',
            'activated_at' => now(),
            'activated_by' => $activatedBy,
            'activation_source' => $source,
            'activation_notes' => $notes,
            'days_to_activation' => $this->user ?
                $this->user->created_at->diffInDays(now()) : null,
        ]);

        return $this;
    }

    /**
     * Process referral bonus.
     */
    public function processReferralBonus(): self
    {
        if (!$this->hasUnprocessedReferralBonus()) {
            return $this;
        }

        $this->update([
            'referral_bonus_processed' => true,
            'referral_bonus_processed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Scope: Activated workers.
     */
    public function scopeActivated($query)
    {
        return $query->where('status', 'activated');
    }

    /**
     * Scope: Eligible workers.
     */
    public function scopeEligible($query)
    {
        return $query->where('status', 'eligible');
    }

    /**
     * Scope: Pending activation.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: With pending referral bonus.
     */
    public function scopeWithPendingReferralBonus($query)
    {
        return $query->where('referral_bonus_amount', '>', 0)
            ->where('referral_bonus_processed', false);
    }

    /**
     * Scope: Activated within date range.
     */
    public function scopeActivatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('activated_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    /**
     * Get or create activation log for user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['status' => 'pending']
        );
    }
}
