<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Worker Featured Status Model
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * @property int $id
 * @property int $worker_id
 * @property string $tier
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $cost_cents
 * @property string $currency
 * @property string|null $payment_reference
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 */
class WorkerFeaturedStatus extends Model
{
    use HasFactory;

    /**
     * Featured tier configurations.
     * Duration in days, cost in cents (EUR).
     */
    public const TIERS = [
        'bronze' => [
            'name' => 'Bronze',
            'duration_days' => 7,
            'cost_cents' => 2000, // 20 EUR
            'search_boost' => 1.2,
            'badge_color' => '#CD7F32',
            'features' => [
                'Featured badge on profile',
                '20% boost in search results',
                '7 days visibility',
            ],
        ],
        'silver' => [
            'name' => 'Silver',
            'duration_days' => 14,
            'cost_cents' => 3500, // 35 EUR
            'search_boost' => 1.5,
            'badge_color' => '#C0C0C0',
            'features' => [
                'Featured badge on profile',
                '50% boost in search results',
                '14 days visibility',
                'Priority in recommendations',
            ],
        ],
        'gold' => [
            'name' => 'Gold',
            'duration_days' => 30,
            'cost_cents' => 5000, // 50 EUR
            'search_boost' => 2.0,
            'badge_color' => '#FFD700',
            'features' => [
                'Premium featured badge',
                '100% boost in search results',
                '30 days visibility',
                'Priority in recommendations',
                'Top placement in category',
                'Highlighted in homepage showcase',
            ],
        ],
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'worker_id',
        'tier',
        'start_date',
        'end_date',
        'cost_cents',
        'currency',
        'payment_reference',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cost_cents' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the worker that owns this featured status.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Scope to get active featured statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope to get pending statuses.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get expired statuses.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->where('status', self::STATUS_ACTIVE)
                    ->where('end_date', '<', now());
            });
    }

    /**
     * Check if this featured status is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Check if this featured status has expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get days remaining for this featured status.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Get the cost in the main currency unit.
     */
    public function getCostAttribute(): float
    {
        return $this->cost_cents / 100;
    }

    /**
     * Get formatted cost with currency symbol.
     */
    public function getFormattedCostAttribute(): string
    {
        $symbol = match ($this->currency) {
            'EUR' => "\u{20AC}",
            'USD' => '$',
            'GBP' => "\u{00A3}",
            default => $this->currency . ' ',
        };

        return $symbol . number_format($this->cost, 2);
    }

    /**
     * Get tier configuration.
     */
    public function getTierConfigAttribute(): array
    {
        return self::TIERS[$this->tier] ?? self::TIERS['bronze'];
    }

    /**
     * Get search boost multiplier for this tier.
     */
    public function getSearchBoostAttribute(): float
    {
        return $this->tier_config['search_boost'] ?? 1.0;
    }

    /**
     * Get badge color for this tier.
     */
    public function getBadgeColorAttribute(): string
    {
        return $this->tier_config['badge_color'] ?? '#CD7F32';
    }

    /**
     * Activate the featured status.
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Cancel the featured status.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark as refunded.
     */
    public function refund(): void
    {
        $this->update(['status' => self::STATUS_REFUNDED]);
    }

    /**
     * Create a new featured status for a worker.
     */
    public static function createForWorker(int $workerId, string $tier, ?string $paymentReference = null): self
    {
        $tierConfig = self::TIERS[$tier] ?? self::TIERS['bronze'];

        return self::create([
            'worker_id' => $workerId,
            'tier' => $tier,
            'start_date' => now(),
            'end_date' => now()->addDays($tierConfig['duration_days']),
            'cost_cents' => $tierConfig['cost_cents'],
            'currency' => 'EUR',
            'payment_reference' => $paymentReference,
            'status' => $paymentReference ? self::STATUS_ACTIVE : self::STATUS_PENDING,
        ]);
    }

    /**
     * Get formatted tier details for display.
     */
    public static function getTierDetails(): array
    {
        $details = [];

        foreach (self::TIERS as $key => $tier) {
            $details[$key] = [
                'id' => $key,
                'name' => $tier['name'],
                'duration_days' => $tier['duration_days'],
                'cost' => $tier['cost_cents'] / 100,
                'cost_formatted' => "\u{20AC}" . number_format($tier['cost_cents'] / 100, 2),
                'search_boost' => $tier['search_boost'],
                'badge_color' => $tier['badge_color'],
                'features' => $tier['features'],
            ];
        }

        return $details;
    }
}
