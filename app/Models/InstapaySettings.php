<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InstapaySettings Model
 *
 * FIN-004: InstaPay (Same-Day Payout) Feature
 *
 * Stores user preferences for InstaPay functionality.
 *
 * @property int $id
 * @property int $user_id
 * @property bool $enabled
 * @property string $preferred_method
 * @property float $minimum_amount
 * @property bool $auto_request
 * @property string $daily_cutoff
 * @property float|null $daily_limit_override
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class InstapaySettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'enabled',
        'preferred_method',
        'minimum_amount',
        'auto_request',
        'daily_cutoff',
        'daily_limit_override',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'auto_request' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'daily_limit_override' => 'decimal:2',
    ];

    /**
     * Get the user this settings belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create settings for a user.
     */
    public static function getOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => false,
                'preferred_method' => InstapayRequest::METHOD_STRIPE,
                'minimum_amount' => config('instapay.minimum_amount', 10.00),
                'auto_request' => false,
                'daily_cutoff' => config('instapay.cutoff_time', '14:00'),
            ]
        );
    }

    /**
     * Check if InstaPay is enabled for this user.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && config('instapay.enabled', true);
    }

    /**
     * Get the effective daily limit.
     * Uses custom override if set, otherwise falls back to system default.
     */
    public function getEffectiveDailyLimit(): float
    {
        return $this->daily_limit_override ?? config('instapay.daily_limit', 500.00);
    }

    /**
     * Get the effective minimum amount.
     */
    public function getEffectiveMinimumAmount(): float
    {
        return max($this->minimum_amount, config('instapay.minimum_amount', 10.00));
    }

    /**
     * Check if current time is before the daily cutoff.
     */
    public function isBeforeCutoff(): bool
    {
        $cutoffTimezone = config('instapay.cutoff_timezone', 'Europe/Malta');
        $now = now()->timezone($cutoffTimezone);
        $cutoffTime = \Carbon\Carbon::parse($this->daily_cutoff, $cutoffTimezone);

        return $now->lt($cutoffTime);
    }

    /**
     * Check if today is a processing day.
     */
    public function isProcessingDay(): bool
    {
        $processingDays = config('instapay.processing_days', [
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday',
        ]);

        $today = strtolower(now()->format('l'));

        return in_array($today, $processingDays);
    }

    /**
     * Check if instant payout is available right now.
     */
    public function isInstantPayoutAvailable(): bool
    {
        return $this->isEnabled()
            && $this->isProcessingDay()
            && $this->isBeforeCutoff();
    }

    /**
     * Get available payout methods for this user.
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        $user = $this->user;

        // Stripe - requires completed Stripe Connect onboarding
        if ($user->canReceiveInstantPayouts()) {
            $methods[InstapayRequest::METHOD_STRIPE] = 'Stripe (Instant)';
        }

        // PayPal - check if user has PayPal linked (future implementation)
        // $methods[InstapayRequest::METHOD_PAYPAL] = 'PayPal';

        // Bank Transfer - always available if user has bank details
        if ($user->hasValidPayoutMethod()) {
            $methods[InstapayRequest::METHOD_BANK_TRANSFER] = 'Bank Transfer (1-2 days)';
        }

        return $methods;
    }

    /**
     * Enable InstaPay for this user.
     */
    public function enable(): bool
    {
        return $this->update(['enabled' => true]);
    }

    /**
     * Disable InstaPay for this user.
     */
    public function disable(): bool
    {
        return $this->update(['enabled' => false]);
    }

    /**
     * Update preferred payout method.
     */
    public function setPreferredMethod(string $method): bool
    {
        return $this->update(['preferred_method' => $method]);
    }

    /**
     * Enable auto-request feature.
     */
    public function enableAutoRequest(): bool
    {
        return $this->update(['auto_request' => true]);
    }

    /**
     * Disable auto-request feature.
     */
    public function disableAutoRequest(): bool
    {
        return $this->update(['auto_request' => false]);
    }
}
