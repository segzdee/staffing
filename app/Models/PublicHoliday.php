<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $country_code
 * @property string|null $region_code
 * @property string $name
 * @property string|null $local_name
 * @property \Illuminate\Support\Carbon $date
 * @property bool $is_national
 * @property bool $is_observed
 * @property string $type
 * @property float $surge_multiplier
 * @property bool $shifts_restricted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static>|PublicHoliday forCountry(string $countryCode)
 * @method static Builder<static>|PublicHoliday forRegion(string $countryCode, ?string $regionCode)
 * @method static Builder<static>|PublicHoliday forDate(\Carbon\Carbon|string $date)
 * @method static Builder<static>|PublicHoliday forYear(int $year)
 * @method static Builder<static>|PublicHoliday national()
 * @method static Builder<static>|PublicHoliday observed()
 * @method static Builder<static>|PublicHoliday upcoming(int $days = 30)
 * @method static Builder<static>|PublicHoliday betweenDates(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|PublicHoliday newModelQuery()
 * @method static Builder<static>|PublicHoliday newQuery()
 * @method static Builder<static>|PublicHoliday query()
 *
 * @mixin \Eloquent
 */
class PublicHoliday extends Model
{
    use HasFactory;

    /**
     * Holiday types
     */
    public const TYPE_PUBLIC = 'public';

    public const TYPE_BANK = 'bank';

    public const TYPE_RELIGIOUS = 'religious';

    public const TYPE_CULTURAL = 'cultural';

    public const TYPE_OBSERVANCE = 'observance';

    /**
     * Default surge multiplier for holidays
     */
    public const DEFAULT_SURGE_MULTIPLIER = 1.50;

    protected $fillable = [
        'country_code',
        'region_code',
        'name',
        'local_name',
        'date',
        'is_national',
        'is_observed',
        'type',
        'surge_multiplier',
        'shifts_restricted',
    ];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
        'is_observed' => 'boolean',
        'surge_multiplier' => 'decimal:2',
        'shifts_restricted' => 'boolean',
    ];

    /**
     * Get all available holiday types
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PUBLIC => 'Public Holiday',
            self::TYPE_BANK => 'Bank Holiday',
            self::TYPE_RELIGIOUS => 'Religious Holiday',
            self::TYPE_CULTURAL => 'Cultural Holiday',
            self::TYPE_OBSERVANCE => 'Observance',
        ];
    }

    /**
     * Scope: Filter by country code
     */
    public function scopeForCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope: Filter by country and optional region
     */
    public function scopeForRegion(Builder $query, string $countryCode, ?string $regionCode = null): Builder
    {
        $query->where('country_code', strtoupper($countryCode));

        if ($regionCode) {
            $query->where(function ($q) use ($regionCode) {
                $q->whereNull('region_code')
                    ->orWhere('region_code', $regionCode);
            });
        } else {
            $query->where(function ($q) {
                $q->whereNull('region_code')
                    ->orWhere('is_national', true);
            });
        }

        return $query;
    }

    /**
     * Scope: Filter by specific date
     */
    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $query->whereDate('date', $date->toDateString());
    }

    /**
     * Scope: Filter by year
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Scope: Only national holidays
     */
    public function scopeNational(Builder $query): Builder
    {
        return $query->where('is_national', true);
    }

    /**
     * Scope: Only observed holidays (actual days off)
     */
    public function scopeObserved(Builder $query): Builder
    {
        return $query->where('is_observed', true);
    }

    /**
     * Scope: Upcoming holidays within N days
     */
    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        $today = Carbon::today();
        $endDate = Carbon::today()->addDays($days);

        return $query->whereBetween('date', [$today, $endDate])
            ->orderBy('date', 'asc');
    }

    /**
     * Scope: Holidays between two dates
     */
    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date', 'asc');
    }

    /**
     * Check if this holiday is today
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Check if this holiday is in the past
     */
    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    /**
     * Check if this holiday is in the future
     */
    public function isFuture(): bool
    {
        return $this->date->isFuture();
    }

    /**
     * Get days until this holiday
     */
    public function daysUntil(): int
    {
        return Carbon::today()->diffInDays($this->date, false);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('l, F j, Y');
    }

    /**
     * Get short formatted date
     */
    public function getShortDateAttribute(): string
    {
        return $this->date->format('M j, Y');
    }

    /**
     * Get display name (local name if available, otherwise name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->local_name ?: $this->name;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get surge percentage (e.g., 1.50 becomes 50%)
     */
    public function getSurgePercentageAttribute(): int
    {
        return (int) (($this->surge_multiplier - 1) * 100);
    }

    /**
     * Create or update a holiday from API data
     *
     * @param  array<string, mixed>  $data
     */
    public static function createOrUpdateFromAPI(array $data): self
    {
        return self::updateOrCreate(
            [
                'country_code' => strtoupper($data['country_code']),
                'region_code' => $data['region_code'] ?? null,
                'date' => $data['date'],
                'name' => $data['name'],
            ],
            [
                'local_name' => $data['local_name'] ?? null,
                'is_national' => $data['is_national'] ?? true,
                'is_observed' => $data['is_observed'] ?? true,
                'type' => $data['type'] ?? self::TYPE_PUBLIC,
                'surge_multiplier' => $data['surge_multiplier'] ?? self::DEFAULT_SURGE_MULTIPLIER,
                'shifts_restricted' => $data['shifts_restricted'] ?? false,
            ]
        );
    }

    /**
     * Get supported country codes with names
     *
     * @return array<string, string>
     */
    public static function getSupportedCountries(): array
    {
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'PT' => 'Portugal',
            'IE' => 'Ireland',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'PL' => 'Poland',
            'NZ' => 'New Zealand',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'JP' => 'Japan',
            'IN' => 'India',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'KE' => 'Kenya',
            'AE' => 'United Arab Emirates',
            'MT' => 'Malta',
        ];
    }
}
