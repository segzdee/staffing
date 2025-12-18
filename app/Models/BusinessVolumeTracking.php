<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-001: Business Volume Tracking Model
 *
 * Tracks monthly volume metrics for businesses to determine
 * their discount tier eligibility.
 *
 * @property int $id
 * @property int $business_id
 * @property \Illuminate\Support\Carbon $month
 * @property int $shifts_posted
 * @property int $shifts_filled
 * @property int $shifts_completed
 * @property int $shifts_cancelled
 * @property float $total_spend
 * @property float $platform_fees_paid
 * @property float $platform_fees_without_discount
 * @property int|null $applied_tier_id
 * @property float $discount_amount
 * @property float $average_shift_value
 * @property int $unique_workers_hired
 * @property int $repeat_workers
 * @property array|null $daily_breakdown
 * @property \Illuminate\Support\Carbon|null $tier_qualified_at
 * @property \Illuminate\Support\Carbon|null $tier_notified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BusinessVolumeTracking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_volume_tracking';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'month',
        'shifts_posted',
        'shifts_filled',
        'shifts_completed',
        'shifts_cancelled',
        'total_spend',
        'platform_fees_paid',
        'platform_fees_without_discount',
        'applied_tier_id',
        'discount_amount',
        'average_shift_value',
        'unique_workers_hired',
        'repeat_workers',
        'daily_breakdown',
        'tier_qualified_at',
        'tier_notified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'month' => 'date',
        'shifts_posted' => 'integer',
        'shifts_filled' => 'integer',
        'shifts_completed' => 'integer',
        'shifts_cancelled' => 'integer',
        'total_spend' => 'decimal:2',
        'platform_fees_paid' => 'decimal:2',
        'platform_fees_without_discount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'average_shift_value' => 'decimal:2',
        'unique_workers_hired' => 'integer',
        'repeat_workers' => 'integer',
        'daily_breakdown' => 'array',
        'tier_qualified_at' => 'datetime',
        'tier_notified_at' => 'datetime',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business user for this tracking record.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the business profile for this tracking record.
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class, 'business_id', 'user_id');
    }

    /**
     * Get the applied discount tier.
     */
    public function appliedTier(): BelongsTo
    {
        return $this->belongsTo(VolumeDiscountTier::class, 'applied_tier_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get tracking for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to get tracking for a specific month.
     */
    public function scopeForMonth($query, Carbon $month)
    {
        return $query->where('month', $month->startOfMonth()->toDateString());
    }

    /**
     * Scope to get tracking for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month', Carbon::now()->startOfMonth()->toDateString());
    }

    /**
     * Scope to get tracking for the last N months.
     */
    public function scopeLastMonths($query, int $months)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        return $query->where('month', '>=', $startDate->toDateString())
            ->orderBy('month', 'desc');
    }

    /**
     * Scope to get records with a specific tier.
     */
    public function scopeWithTier($query, int $tierId)
    {
        return $query->where('applied_tier_id', $tierId);
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get or create tracking record for a business and month.
     */
    public static function getOrCreateForMonth(int $businessId, ?Carbon $month = null): self
    {
        $month = ($month ?? Carbon::now())->startOfMonth();

        // First try to find existing record - use whereDate for SQLite compatibility
        $existing = static::where('business_id', $businessId)
            ->whereDate('month', $month->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        // Try to create, handling potential race condition
        try {
            return static::create([
                'business_id' => $businessId,
                'month' => $month,
                'shifts_posted' => 0,
                'shifts_filled' => 0,
                'shifts_completed' => 0,
                'shifts_cancelled' => 0,
                'total_spend' => 0,
                'platform_fees_paid' => 0,
                'platform_fees_without_discount' => 0,
                'discount_amount' => 0,
                'average_shift_value' => 0,
                'unique_workers_hired' => 0,
                'repeat_workers' => 0,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race condition - another request created it, fetch and return
            return static::where('business_id', $businessId)
                ->whereDate('month', $month->toDateString())
                ->firstOrFail();
        }
    }

    /**
     * Get tracking history for a business.
     */
    public static function getHistory(int $businessId, int $months = 12)
    {
        return static::query()
            ->forBusiness($businessId)
            ->lastMonths($months)
            ->with('appliedTier')
            ->get();
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the month name for display.
     */
    public function getMonthNameAttribute(): string
    {
        return $this->month->format('F Y');
    }

    /**
     * Get the fill rate percentage.
     */
    public function getFillRateAttribute(): float
    {
        if ($this->shifts_posted === 0) {
            return 0;
        }

        return round(($this->shifts_filled / $this->shifts_posted) * 100, 1);
    }

    /**
     * Get the completion rate percentage.
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->shifts_filled === 0) {
            return 0;
        }

        return round(($this->shifts_completed / $this->shifts_filled) * 100, 1);
    }

    /**
     * Get the cancellation rate percentage.
     */
    public function getCancellationRateAttribute(): float
    {
        if ($this->shifts_posted === 0) {
            return 0;
        }

        return round(($this->shifts_cancelled / $this->shifts_posted) * 100, 1);
    }

    /**
     * Get total savings from volume discounts.
     */
    public function getTotalSavingsAttribute(): float
    {
        return $this->platform_fees_without_discount - $this->platform_fees_paid;
    }

    /**
     * Get the savings percentage.
     */
    public function getSavingsPercentageAttribute(): float
    {
        if ($this->platform_fees_without_discount <= 0) {
            return 0;
        }

        return round(($this->total_savings / $this->platform_fees_without_discount) * 100, 1);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Increment shift posted count.
     */
    public function incrementShiftsPosted(int $count = 1): self
    {
        $this->increment('shifts_posted', $count);
        $this->updateAverageShiftValue();

        return $this;
    }

    /**
     * Increment shift filled count.
     */
    public function incrementShiftsFilled(int $count = 1): self
    {
        $this->increment('shifts_filled', $count);

        return $this;
    }

    /**
     * Increment shift completed count.
     */
    public function incrementShiftsCompleted(int $count = 1): self
    {
        $this->increment('shifts_completed', $count);

        return $this;
    }

    /**
     * Increment shift cancelled count.
     */
    public function incrementShiftsCancelled(int $count = 1): self
    {
        $this->increment('shifts_cancelled', $count);

        return $this;
    }

    /**
     * Add to total spend.
     */
    public function addSpend(float $amount): self
    {
        $this->increment('total_spend', $amount);
        $this->updateAverageShiftValue();

        return $this;
    }

    /**
     * Add platform fees.
     */
    public function addPlatformFees(float $discountedFee, float $fullFee): self
    {
        $this->increment('platform_fees_paid', $discountedFee);
        $this->increment('platform_fees_without_discount', $fullFee);
        $this->update([
            'discount_amount' => $this->platform_fees_without_discount - $this->platform_fees_paid,
        ]);

        return $this;
    }

    /**
     * Update the average shift value.
     */
    public function updateAverageShiftValue(): self
    {
        if ($this->shifts_posted > 0) {
            $this->update([
                'average_shift_value' => $this->total_spend / $this->shifts_posted,
            ]);
        }

        return $this;
    }

    /**
     * Update the applied tier.
     */
    public function updateAppliedTier(?VolumeDiscountTier $tier): self
    {
        $previousTierId = $this->applied_tier_id;

        $this->update([
            'applied_tier_id' => $tier?->id,
            'tier_qualified_at' => $tier && $previousTierId !== $tier->id ? now() : $this->tier_qualified_at,
        ]);

        return $this;
    }

    /**
     * Mark as notified about tier change.
     */
    public function markTierNotified(): self
    {
        $this->update(['tier_notified_at' => now()]);

        return $this;
    }

    /**
     * Track a unique worker hire.
     */
    public function trackWorkerHire(int $workerId, bool $isRepeat = false): self
    {
        if ($isRepeat) {
            $this->increment('repeat_workers');
        } else {
            $this->increment('unique_workers_hired');
        }

        return $this;
    }

    /**
     * Get summary statistics.
     */
    public function getSummary(): array
    {
        return [
            'month' => $this->month_name,
            'shifts_posted' => $this->shifts_posted,
            'shifts_filled' => $this->shifts_filled,
            'shifts_completed' => $this->shifts_completed,
            'shifts_cancelled' => $this->shifts_cancelled,
            'fill_rate' => $this->fill_rate,
            'completion_rate' => $this->completion_rate,
            'cancellation_rate' => $this->cancellation_rate,
            'total_spend' => $this->total_spend,
            'platform_fees_paid' => $this->platform_fees_paid,
            'discount_amount' => $this->discount_amount,
            'savings_percentage' => $this->savings_percentage,
            'average_shift_value' => $this->average_shift_value,
            'unique_workers' => $this->unique_workers_hired,
            'repeat_workers' => $this->repeat_workers,
            'tier' => $this->appliedTier?->name ?? 'None',
        ];
    }
}
