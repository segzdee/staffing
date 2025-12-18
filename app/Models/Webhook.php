<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BIZ-012: Integration APIs - Outbound Webhook Model
 *
 * @property int $id
 * @property int $business_id
 * @property string $url
 * @property string|null $secret
 * @property array $events
 * @property bool $is_active
 * @property int $failure_count
 * @property \Illuminate\Support\Carbon|null $last_triggered_at
 * @property \Illuminate\Support\Carbon|null $last_success_at
 * @property \Illuminate\Support\Carbon|null $last_failure_at
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $business
 */
class Webhook extends Model
{
    use HasFactory;

    // Maximum consecutive failures before auto-disable
    public const MAX_FAILURES = 10;

    // Available webhook events
    public const EVENT_SHIFT_CREATED = 'shift.created';

    public const EVENT_SHIFT_UPDATED = 'shift.updated';

    public const EVENT_SHIFT_CANCELLED = 'shift.cancelled';

    public const EVENT_SHIFT_COMPLETED = 'shift.completed';

    public const EVENT_SHIFT_FILLED = 'shift.filled';

    public const EVENT_APPLICATION_RECEIVED = 'application.received';

    public const EVENT_APPLICATION_ACCEPTED = 'application.accepted';

    public const EVENT_APPLICATION_REJECTED = 'application.rejected';

    public const EVENT_WORKER_CHECKED_IN = 'worker.checked_in';

    public const EVENT_WORKER_CHECKED_OUT = 'worker.checked_out';

    public const EVENT_PAYMENT_RELEASED = 'payment.released';

    public const EVENT_PAYMENT_COMPLETED = 'payment.completed';

    public const EVENT_RATING_SUBMITTED = 'rating.submitted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'url',
        'secret',
        'events',
        'is_active',
        'failure_count',
        'last_triggered_at',
        'last_success_at',
        'last_failure_at',
        'last_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the business that owns this webhook.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Scope for active webhooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for webhooks subscribed to a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * Check if webhook is subscribed to an event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Check if webhook should be disabled due to failures.
     */
    public function shouldBeDisabled(): bool
    {
        return $this->failure_count >= self::MAX_FAILURES;
    }

    /**
     * Record a successful delivery.
     */
    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'last_success_at' => now(),
            'failure_count' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * Record a failed delivery.
     */
    public function recordFailure(string $error): void
    {
        $this->increment('failure_count');
        $this->update([
            'last_triggered_at' => now(),
            'last_failure_at' => now(),
            'last_error' => $error,
        ]);

        // Auto-disable if too many failures
        if ($this->shouldBeDisabled()) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Reactivate the webhook.
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'failure_count' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * Get all available events with descriptions.
     */
    public static function getAvailableEvents(): array
    {
        return [
            self::EVENT_SHIFT_CREATED => [
                'name' => 'Shift Created',
                'description' => 'Triggered when a new shift is posted',
                'category' => 'shifts',
            ],
            self::EVENT_SHIFT_UPDATED => [
                'name' => 'Shift Updated',
                'description' => 'Triggered when a shift is modified',
                'category' => 'shifts',
            ],
            self::EVENT_SHIFT_CANCELLED => [
                'name' => 'Shift Cancelled',
                'description' => 'Triggered when a shift is cancelled',
                'category' => 'shifts',
            ],
            self::EVENT_SHIFT_COMPLETED => [
                'name' => 'Shift Completed',
                'description' => 'Triggered when a shift has been completed',
                'category' => 'shifts',
            ],
            self::EVENT_SHIFT_FILLED => [
                'name' => 'Shift Filled',
                'description' => 'Triggered when all positions are filled',
                'category' => 'shifts',
            ],
            self::EVENT_APPLICATION_RECEIVED => [
                'name' => 'Application Received',
                'description' => 'Triggered when a worker applies for a shift',
                'category' => 'applications',
            ],
            self::EVENT_APPLICATION_ACCEPTED => [
                'name' => 'Application Accepted',
                'description' => 'Triggered when an application is accepted',
                'category' => 'applications',
            ],
            self::EVENT_APPLICATION_REJECTED => [
                'name' => 'Application Rejected',
                'description' => 'Triggered when an application is rejected',
                'category' => 'applications',
            ],
            self::EVENT_WORKER_CHECKED_IN => [
                'name' => 'Worker Checked In',
                'description' => 'Triggered when a worker clocks in',
                'category' => 'attendance',
            ],
            self::EVENT_WORKER_CHECKED_OUT => [
                'name' => 'Worker Checked Out',
                'description' => 'Triggered when a worker clocks out',
                'category' => 'attendance',
            ],
            self::EVENT_PAYMENT_RELEASED => [
                'name' => 'Payment Released',
                'description' => 'Triggered when payment is released from escrow',
                'category' => 'payments',
            ],
            self::EVENT_PAYMENT_COMPLETED => [
                'name' => 'Payment Completed',
                'description' => 'Triggered when payment has been processed',
                'category' => 'payments',
            ],
            self::EVENT_RATING_SUBMITTED => [
                'name' => 'Rating Submitted',
                'description' => 'Triggered when a rating is submitted',
                'category' => 'ratings',
            ],
        ];
    }

    /**
     * Get events grouped by category.
     */
    public static function getEventsGroupedByCategory(): array
    {
        $events = self::getAvailableEvents();
        $grouped = [];

        foreach ($events as $key => $event) {
            $category = $event['category'];
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$key] = $event;
        }

        return $grouped;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Disabled';
        }

        if ($this->failure_count > 0) {
            return "Active ({$this->failure_count} failures)";
        }

        return 'Active';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        if (! $this->is_active) {
            return 'red';
        }

        if ($this->failure_count >= 5) {
            return 'orange';
        }

        if ($this->failure_count > 0) {
            return 'yellow';
        }

        return 'green';
    }
}
