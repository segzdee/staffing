<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int|null $business_id
 * @property string $name
 * @property string $country_code
 * @property array<int>|null $included_holidays
 * @property array<int>|null $excluded_holidays
 * @property array<array{date: string, name: string, surge_multiplier: float}>|null $custom_dates
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $business
 *
 * @method static Builder<static>|HolidayCalendar forBusiness(int $businessId)
 * @method static Builder<static>|HolidayCalendar forCountry(string $countryCode)
 * @method static Builder<static>|HolidayCalendar defaults()
 * @method static Builder<static>|HolidayCalendar newModelQuery()
 * @method static Builder<static>|HolidayCalendar newQuery()
 * @method static Builder<static>|HolidayCalendar query()
 *
 * @mixin \Eloquent
 */
class HolidayCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'country_code',
        'included_holidays',
        'excluded_holidays',
        'custom_dates',
        'is_default',
    ];

    protected $casts = [
        'included_holidays' => 'array',
        'excluded_holidays' => 'array',
        'custom_dates' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the business that owns the calendar
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Scope: Filter by business
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope: Filter by country code
     */
    public function scopeForCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope: Only default calendars (system-wide)
     */
    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true)->whereNull('business_id');
    }

    /**
     * Get effective holidays for this calendar
     *
     * @return Collection<int, PublicHoliday|array>
     */
    public function getEffectiveHolidays(int $year, ?string $region = null): Collection
    {
        // Get base holidays for the country/year
        $query = PublicHoliday::query()
            ->forRegion($this->country_code, $region)
            ->forYear($year);

        // Apply inclusions/exclusions
        if (! empty($this->included_holidays)) {
            $query->whereIn('id', $this->included_holidays);
        }

        if (! empty($this->excluded_holidays)) {
            $query->whereNotIn('id', $this->excluded_holidays);
        }

        $holidays = $query->orderBy('date')->get();

        // Add custom dates
        if (! empty($this->custom_dates)) {
            $customHolidays = collect($this->custom_dates)
                ->filter(function ($custom) use ($year) {
                    $date = Carbon::parse($custom['date']);

                    return $date->year === $year;
                })
                ->map(function ($custom) {
                    return [
                        'id' => null,
                        'country_code' => $this->country_code,
                        'region_code' => null,
                        'name' => $custom['name'],
                        'local_name' => $custom['name'],
                        'date' => Carbon::parse($custom['date']),
                        'is_national' => false,
                        'is_observed' => true,
                        'type' => 'custom',
                        'surge_multiplier' => $custom['surge_multiplier'] ?? PublicHoliday::DEFAULT_SURGE_MULTIPLIER,
                        'shifts_restricted' => $custom['shifts_restricted'] ?? false,
                        'is_custom' => true,
                    ];
                });

            // Merge and sort by date
            $holidays = $holidays->concat($customHolidays)
                ->sortBy(function ($holiday) {
                    if ($holiday instanceof PublicHoliday) {
                        return $holiday->date->timestamp;
                    }

                    return $holiday['date']->timestamp;
                })
                ->values();
        }

        return $holidays;
    }

    /**
     * Check if a date is a holiday according to this calendar
     */
    public function isHoliday(Carbon $date, ?string $region = null): bool
    {
        // Check public holidays
        $holiday = PublicHoliday::query()
            ->forRegion($this->country_code, $region)
            ->forDate($date)
            ->first();

        if ($holiday) {
            // Check if excluded
            if (! empty($this->excluded_holidays) && in_array($holiday->id, $this->excluded_holidays)) {
                return false;
            }

            // If we have inclusions, check if included
            if (! empty($this->included_holidays)) {
                return in_array($holiday->id, $this->included_holidays);
            }

            return true;
        }

        // Check custom dates
        if (! empty($this->custom_dates)) {
            foreach ($this->custom_dates as $custom) {
                if (Carbon::parse($custom['date'])->isSameDay($date)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get holiday info for a specific date
     *
     * @return PublicHoliday|array<string, mixed>|null
     */
    public function getHolidayInfo(Carbon $date, ?string $region = null): PublicHoliday|array|null
    {
        // Check public holidays first
        $holiday = PublicHoliday::query()
            ->forRegion($this->country_code, $region)
            ->forDate($date)
            ->first();

        if ($holiday) {
            // Check if excluded
            if (! empty($this->excluded_holidays) && in_array($holiday->id, $this->excluded_holidays)) {
                $holiday = null;
            }

            // If we have inclusions, check if included
            if ($holiday && ! empty($this->included_holidays) && ! in_array($holiday->id, $this->included_holidays)) {
                $holiday = null;
            }
        }

        // If no public holiday, check custom dates
        if (! $holiday && ! empty($this->custom_dates)) {
            foreach ($this->custom_dates as $custom) {
                if (Carbon::parse($custom['date'])->isSameDay($date)) {
                    return [
                        'id' => null,
                        'country_code' => $this->country_code,
                        'name' => $custom['name'],
                        'date' => Carbon::parse($custom['date']),
                        'type' => 'custom',
                        'surge_multiplier' => $custom['surge_multiplier'] ?? PublicHoliday::DEFAULT_SURGE_MULTIPLIER,
                        'is_custom' => true,
                    ];
                }
            }
        }

        return $holiday;
    }

    /**
     * Get surge multiplier for a specific date
     */
    public function getSurgeMultiplier(Carbon $date, ?string $region = null): float
    {
        $holidayInfo = $this->getHolidayInfo($date, $region);

        if (! $holidayInfo) {
            return 1.0;
        }

        if ($holidayInfo instanceof PublicHoliday) {
            return (float) $holidayInfo->surge_multiplier;
        }

        return (float) ($holidayInfo['surge_multiplier'] ?? PublicHoliday::DEFAULT_SURGE_MULTIPLIER);
    }

    /**
     * Add a custom date to this calendar
     *
     * @param  array{date: string, name: string, surge_multiplier?: float, shifts_restricted?: bool}  $customDate
     */
    public function addCustomDate(array $customDate): self
    {
        $customDates = $this->custom_dates ?? [];

        // Check for duplicates
        foreach ($customDates as $existing) {
            if ($existing['date'] === $customDate['date'] && $existing['name'] === $customDate['name']) {
                return $this; // Already exists
            }
        }

        $customDates[] = [
            'date' => $customDate['date'],
            'name' => $customDate['name'],
            'surge_multiplier' => $customDate['surge_multiplier'] ?? PublicHoliday::DEFAULT_SURGE_MULTIPLIER,
            'shifts_restricted' => $customDate['shifts_restricted'] ?? false,
        ];

        $this->custom_dates = $customDates;
        $this->save();

        return $this;
    }

    /**
     * Remove a custom date from this calendar
     */
    public function removeCustomDate(string $date, string $name): self
    {
        if (empty($this->custom_dates)) {
            return $this;
        }

        $customDates = collect($this->custom_dates)
            ->reject(function ($custom) use ($date, $name) {
                return $custom['date'] === $date && $custom['name'] === $name;
            })
            ->values()
            ->toArray();

        $this->custom_dates = $customDates ?: null;
        $this->save();

        return $this;
    }

    /**
     * Exclude a public holiday
     */
    public function excludeHoliday(int $holidayId): self
    {
        $excluded = $this->excluded_holidays ?? [];

        if (! in_array($holidayId, $excluded)) {
            $excluded[] = $holidayId;
            $this->excluded_holidays = $excluded;
            $this->save();
        }

        return $this;
    }

    /**
     * Include a public holiday (remove from exclusions)
     */
    public function includeHoliday(int $holidayId): self
    {
        if (! empty($this->excluded_holidays)) {
            $this->excluded_holidays = array_values(array_diff($this->excluded_holidays, [$holidayId]));
            $this->save();
        }

        return $this;
    }

    /**
     * Create a default calendar for a country
     */
    public static function createDefaultForCountry(string $countryCode): self
    {
        $countryName = PublicHoliday::getSupportedCountries()[strtoupper($countryCode)] ?? $countryCode;

        return self::create([
            'business_id' => null,
            'name' => "{$countryName} Default Calendar",
            'country_code' => strtoupper($countryCode),
            'is_default' => true,
        ]);
    }

    /**
     * Get or create a calendar for a business
     */
    public static function getOrCreateForBusiness(User $business, string $countryCode): self
    {
        $calendar = self::query()
            ->forBusiness($business->id)
            ->forCountry($countryCode)
            ->first();

        if (! $calendar) {
            $calendar = self::create([
                'business_id' => $business->id,
                'name' => "{$business->name} Calendar",
                'country_code' => strtoupper($countryCode),
                'is_default' => false,
            ]);
        }

        return $calendar;
    }
}
