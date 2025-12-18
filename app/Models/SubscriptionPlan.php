<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * FIN-011: Subscription Plan Model
 *
 * Represents a subscription tier that users can subscribe to.
 * Each plan is tied to a user type (worker, business, agency)
 * and contains features, pricing, and Stripe integration details.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string $interval
 * @property float $price
 * @property string $currency
 * @property string|null $stripe_price_id
 * @property string|null $stripe_product_id
 * @property array $features
 * @property string|null $description
 * @property int $trial_days
 * @property bool $is_popular
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $max_users
 * @property int|null $max_shifts_per_month
 * @property float|null $commission_rate
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    // Plan Types
    public const TYPE_WORKER = 'worker';

    public const TYPE_BUSINESS = 'business';

    public const TYPE_AGENCY = 'agency';

    // Billing Intervals
    public const INTERVAL_MONTHLY = 'monthly';

    public const INTERVAL_QUARTERLY = 'quarterly';

    public const INTERVAL_YEARLY = 'yearly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'interval',
        'price',
        'currency',
        'stripe_price_id',
        'stripe_product_id',
        'features',
        'description',
        'trial_days',
        'is_popular',
        'is_active',
        'sort_order',
        'max_users',
        'max_shifts_per_month',
        'commission_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'trial_days' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'max_users' => 'integer',
        'max_shifts_per_month' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SubscriptionPlan $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name.'-'.$plan->interval);
            }
        });
    }

    /**
     * Get all subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get active subscriptions for this plan.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Scope to get only active plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by user type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by billing interval.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }

    /**
     * Scope to order by sort order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Check if the plan includes a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get the price in the smallest currency unit (cents).
     */
    public function getPriceInCents(): int
    {
        return (int) round($this->price * 100);
    }

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => $this->currency.' ',
        };

        return $symbol.number_format($this->price, 2);
    }

    /**
     * Get the billing interval label.
     */
    public function getIntervalLabelAttribute(): string
    {
        return match ($this->interval) {
            self::INTERVAL_MONTHLY => 'month',
            self::INTERVAL_QUARTERLY => 'quarter',
            self::INTERVAL_YEARLY => 'year',
            default => $this->interval,
        };
    }

    /**
     * Get the full billing description.
     */
    public function getBillingDescriptionAttribute(): string
    {
        return $this->formatted_price.'/'.$this->interval_label;
    }

    /**
     * Calculate yearly cost for comparison.
     */
    public function getYearlyCostAttribute(): float
    {
        return match ($this->interval) {
            self::INTERVAL_MONTHLY => $this->price * 12,
            self::INTERVAL_QUARTERLY => $this->price * 4,
            self::INTERVAL_YEARLY => $this->price,
            default => $this->price * 12,
        };
    }

    /**
     * Calculate monthly equivalent cost.
     */
    public function getMonthlyEquivalentAttribute(): float
    {
        return match ($this->interval) {
            self::INTERVAL_MONTHLY => $this->price,
            self::INTERVAL_QUARTERLY => round($this->price / 3, 2),
            self::INTERVAL_YEARLY => round($this->price / 12, 2),
            default => $this->price,
        };
    }

    /**
     * Get savings percentage compared to monthly billing.
     */
    public function getSavingsPercentageAttribute(): int
    {
        if ($this->interval === self::INTERVAL_MONTHLY) {
            return 0;
        }

        // Find monthly plan with same name prefix
        $monthlyPlan = static::where('type', $this->type)
            ->where('interval', self::INTERVAL_MONTHLY)
            ->where('name', 'like', Str::before($this->name, ' ').'%')
            ->first();

        if (! $monthlyPlan) {
            return 0;
        }

        $monthlyYearlyCost = $monthlyPlan->yearly_cost;

        if ($monthlyYearlyCost <= 0) {
            return 0;
        }

        return (int) round((($monthlyYearlyCost - $this->yearly_cost) / $monthlyYearlyCost) * 100);
    }

    /**
     * Check if the plan is for workers.
     */
    public function isWorkerPlan(): bool
    {
        return $this->type === self::TYPE_WORKER;
    }

    /**
     * Check if the plan is for businesses.
     */
    public function isBusinessPlan(): bool
    {
        return $this->type === self::TYPE_BUSINESS;
    }

    /**
     * Check if the plan is for agencies.
     */
    public function isAgencyPlan(): bool
    {
        return $this->type === self::TYPE_AGENCY;
    }

    /**
     * Get human-readable feature descriptions.
     */
    public function getFeatureDescriptions(): array
    {
        $descriptions = [
            // Worker features
            'priority_matching' => 'Priority shift matching',
            'earnings_analytics' => 'Advanced earnings analytics',
            'early_payout' => 'Early payout access (InstaPay)',
            'profile_boost' => 'Profile visibility boost',
            'no_commission' => 'Zero platform commission',
            'premium_support' => 'Priority support',

            // Business features
            'unlimited_posts' => 'Unlimited shift posts',
            'roster_management' => 'Advanced roster management',
            'analytics' => 'Business analytics dashboard',
            'api_access' => 'API access for integrations',
            'dedicated_support' => 'Dedicated account manager',
            'custom_branding' => 'Custom branding options',
            'bulk_posting' => 'Bulk shift posting',
            'team_management' => 'Team management tools',

            // Agency features
            'worker_management' => 'Unlimited worker management',
            'white_label' => 'White-label platform access',
            'commission_reduction' => 'Reduced platform commission',
            'multi_client' => 'Multi-client management',
            'reporting_suite' => 'Advanced reporting suite',

            // General
            'all_essential' => 'All Essential features',
            'all_growth' => 'All Growth features',
        ];

        return array_map(
            fn ($feature) => $descriptions[$feature] ?? ucwords(str_replace('_', ' ', $feature)),
            $this->features ?? []
        );
    }
}
