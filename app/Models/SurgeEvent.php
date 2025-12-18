<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * SL-008: Surge Pricing - Event-based surge pricing model
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $region
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property float $surge_multiplier
 * @property string $event_type
 * @property int|null $expected_demand_increase
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 */
class SurgeEvent extends Model
{
    use HasFactory;

    // Event types
    public const TYPE_CONCERT = 'concert';

    public const TYPE_SPORTS = 'sports';

    public const TYPE_CONFERENCE = 'conference';

    public const TYPE_FESTIVAL = 'festival';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_WEATHER = 'weather';

    public const TYPE_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'region',
        'start_date',
        'end_date',
        'surge_multiplier',
        'event_type',
        'expected_demand_increase',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'surge_multiplier' => 'decimal:2',
        'expected_demand_increase' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all available event types.
     *
     * @return array<string, string>
     */
    public static function getEventTypes(): array
    {
        return [
            self::TYPE_CONCERT => 'Concert',
            self::TYPE_SPORTS => 'Sports Event',
            self::TYPE_CONFERENCE => 'Conference',
            self::TYPE_FESTIVAL => 'Festival',
            self::TYPE_HOLIDAY => 'Holiday',
            self::TYPE_WEATHER => 'Weather Event',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get the user who created this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get events for a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Carbon\Carbon|string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    /**
     * Scope to get events for a specific region.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRegion($query, ?string $region)
    {
        return $query->where(function ($q) use ($region) {
            $q->whereNull('region') // Global events apply everywhere
                ->orWhere('region', $region);
        });
    }

    /**
     * Scope to get upcoming events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query, int $days = 14)
    {
        return $query->where('start_date', '>=', now()->toDateString())
            ->where('start_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('start_date');
    }

    /**
     * Scope to get currently active events (happening now).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();

        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Check if this event is currently active (is_active flag AND date range).
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    /**
     * Check if this event applies to a specific region.
     */
    public function appliesToRegion(?string $region): bool
    {
        // Global events (no region) apply everywhere
        if (empty($this->region)) {
            return true;
        }

        // Match against the region
        return strtolower($this->region) === strtolower($region ?? '');
    }

    /**
     * Get the display label for the event type.
     */
    public function getEventTypeLabelAttribute(): string
    {
        return self::getEventTypes()[$this->event_type] ?? 'Unknown';
    }

    /**
     * Get the duration in days.
     */
    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get the status label for display.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        $today = now()->toDateString();

        if ($this->end_date->toDateString() < $today) {
            return 'Ended';
        }

        if ($this->start_date->toDateString() > $today) {
            return 'Upcoming';
        }

        return 'Active';
    }

    /**
     * Get events affecting a specific date and region.
     */
    public static function getActiveEventsFor(Carbon $date, ?string $region): Collection
    {
        return static::query()
            ->active()
            ->forDate($date)
            ->forRegion($region)
            ->orderByDesc('surge_multiplier')
            ->get();
    }

    /**
     * Get the highest surge multiplier for a date and region.
     */
    public static function getHighestMultiplierFor(Carbon $date, ?string $region): float
    {
        $event = static::query()
            ->active()
            ->forDate($date)
            ->forRegion($region)
            ->orderByDesc('surge_multiplier')
            ->first();

        return $event ? (float) $event->surge_multiplier : 1.0;
    }
}
