<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FIN-011: Subscription Model
 *
 * Represents an active subscription for a user to a specific plan.
 * Tracks subscription status, billing periods, and Stripe integration.
 *
 * @property int $id
 * @property int $user_id
 * @property int $subscription_plan_id
 * @property string|null $stripe_subscription_id
 * @property string|null $stripe_customer_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $current_period_start
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $paused_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $cancel_at_period_end
 * @property string|null $cancellation_reason
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read SubscriptionPlan $plan
 */
class Subscription extends Model
{
    use HasFactory;

    // Subscription Statuses
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';

    public const STATUS_UNPAID = 'unpaid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'paused_at',
        'ends_at',
        'cancel_at_period_end',
        'cancellation_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'paused_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get all invoices for this subscription.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Get paid invoices for this subscription.
     */
    public function paidInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'paid');
    }

    /**
     * Scope to get active subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get subscriptions on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnTrial($query)
    {
        return $query->where('status', self::STATUS_TRIALING);
    }

    /**
     * Scope to get canceled subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', self::STATUS_CANCELED);
    }

    /**
     * Scope to get paused subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    /**
     * Scope to get subscriptions that are past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePastDue($query)
    {
        return $query->where('status', self::STATUS_PAST_DUE);
    }

    /**
     * Scope to get valid (usable) subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_TRIALING,
            self::STATUS_PAST_DUE, // Allow grace period
        ]);
    }

    /**
     * Scope to get subscriptions for a specific plan type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPlanType($query, string $type)
    {
        return $query->whereHas('plan', function ($q) use ($type) {
            $q->where('type', $type);
        });
    }

    /**
     * Scope to get subscriptions expiring soon.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('current_period_end', '<=', now()->addDays($days))
            ->where('current_period_end', '>', now())
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Check if subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Check if subscription is valid (can access features).
     */
    public function isValid(): bool
    {
        // Active subscriptions are valid
        if ($this->isActive()) {
            return true;
        }

        // Trialing subscriptions are valid
        if ($this->onTrial()) {
            return true;
        }

        // Past due with grace period (7 days)
        if ($this->isPastDue() && $this->current_period_end?->addDays(7)->isFuture()) {
            return true;
        }

        // Canceled but not yet ended
        if ($this->isCanceled() && $this->ends_at?->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the subscription has ended.
     */
    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Check if the subscription will cancel at period end.
     */
    public function willCancelAtPeriodEnd(): bool
    {
        return $this->cancel_at_period_end;
    }

    /**
     * Check if the subscription has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        return $this->plan?->hasFeature($feature) ?? false;
    }

    /**
     * Get the number of days remaining in the current period.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->current_period_end) {
            return null;
        }

        $days = now()->diffInDays($this->current_period_end, false);

        return max(0, $days);
    }

    /**
     * Get the number of days remaining in trial.
     */
    public function trialDaysRemaining(): ?int
    {
        if (! $this->onTrial()) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Get the status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->onTrial()) {
            $days = $this->trialDaysRemaining();

            return "Trial ({$days} days left)";
        }

        if ($this->willCancelAtPeriodEnd() && $this->isActive()) {
            return 'Canceling';
        }

        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAST_DUE => 'Past Due',
            self::STATUS_CANCELED => 'Canceled',
            self::STATUS_TRIALING => 'Trial',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_INCOMPLETE => 'Incomplete',
            self::STATUS_INCOMPLETE_EXPIRED => 'Expired',
            self::STATUS_UNPAID => 'Unpaid',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_TRIALING => 'info',
            self::STATUS_PAST_DUE => 'warning',
            self::STATUS_PAUSED => 'secondary',
            self::STATUS_CANCELED, self::STATUS_INCOMPLETE_EXPIRED, self::STATUS_UNPAID => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the total amount paid for this subscription.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->paidInvoices()->sum('total');
    }

    /**
     * Mark subscription as active.
     */
    public function markAsActive(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
        ]);
    }

    /**
     * Mark subscription as canceled.
     */
    public function markAsCanceled(?string $reason = null, bool $immediately = false): void
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
            'ends_at' => $immediately ? now() : $this->current_period_end,
        ]);
    }

    /**
     * Mark subscription as paused.
     */
    public function markAsPaused(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
        ]);
    }

    /**
     * Resume a paused or canceled subscription.
     */
    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
            'canceled_at' => null,
            'cancel_at_period_end' => false,
            'ends_at' => null,
        ]);
    }

    /**
     * Swap to a new plan.
     */
    public function swapPlan(SubscriptionPlan $newPlan): void
    {
        $this->update([
            'subscription_plan_id' => $newPlan->id,
        ]);
    }

    /**
     * Update billing period.
     */
    public function updateBillingPeriod(Carbon $start, Carbon $end): void
    {
        $this->update([
            'current_period_start' => $start,
            'current_period_end' => $end,
        ]);
    }
}
