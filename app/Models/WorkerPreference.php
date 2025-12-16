<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Worker Preferences Model
 * STAFF-REG-009: Worker Availability Setup
 *
 * Stores worker preferences for shifts, travel, rates, and notifications.
 */
class WorkerPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'max_hours_per_week',
        'max_shifts_per_day',
        'min_hours_per_shift',
        'max_travel_distance',
        'distance_unit',
        'preferred_shift_types',
        'min_hourly_rate',
        'preferred_currency',
        'preferred_industries',
        'preferred_roles',
        'excluded_businesses',
        'notify_new_shifts',
        'notify_matching_shifts',
        'notify_urgent_shifts',
        'advance_notice_hours',
        'auto_accept_invitations',
        'auto_accept_recurring',
    ];

    protected $casts = [
        'preferred_shift_types' => 'array',
        'preferred_industries' => 'array',
        'preferred_roles' => 'array',
        'excluded_businesses' => 'array',
        'min_hourly_rate' => 'decimal:2',
        'min_hours_per_shift' => 'decimal:2',
        'notify_new_shifts' => 'boolean',
        'notify_matching_shifts' => 'boolean',
        'notify_urgent_shifts' => 'boolean',
        'auto_accept_invitations' => 'boolean',
        'auto_accept_recurring' => 'boolean',
    ];

    /**
     * Default shift types.
     */
    public const SHIFT_TYPES = [
        'morning' => ['label' => 'Morning', 'start' => '06:00', 'end' => '12:00'],
        'afternoon' => ['label' => 'Afternoon', 'start' => '12:00', 'end' => '18:00'],
        'evening' => ['label' => 'Evening', 'start' => '18:00', 'end' => '22:00'],
        'overnight' => ['label' => 'Overnight', 'start' => '22:00', 'end' => '06:00'],
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if worker prefers a specific shift type.
     */
    public function prefersShiftType(string $type): bool
    {
        if (empty($this->preferred_shift_types)) {
            return true; // If no preferences set, accept all types
        }

        return in_array($type, $this->preferred_shift_types);
    }

    /**
     * Check if shift time matches worker's preferred shift types.
     */
    public function matchesShiftTime(string $startTime): bool
    {
        if (empty($this->preferred_shift_types)) {
            return true;
        }

        $hour = (int) date('H', strtotime($startTime));

        foreach ($this->preferred_shift_types as $type) {
            $typeConfig = self::SHIFT_TYPES[$type] ?? null;
            if (!$typeConfig) {
                continue;
            }

            $startHour = (int) date('H', strtotime($typeConfig['start']));
            $endHour = (int) date('H', strtotime($typeConfig['end']));

            // Handle overnight shifts
            if ($type === 'overnight') {
                if ($hour >= $startHour || $hour < $endHour) {
                    return true;
                }
            } else {
                if ($hour >= $startHour && $hour < $endHour) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a business is excluded.
     */
    public function isBusinessExcluded(int $businessId): bool
    {
        if (empty($this->excluded_businesses)) {
            return false;
        }

        return in_array($businessId, $this->excluded_businesses);
    }

    /**
     * Check if worker is within distance limits.
     */
    public function isWithinDistance(float $distance, string $unit = 'km'): bool
    {
        if (!$this->max_travel_distance) {
            return true;
        }

        // Convert if units don't match
        $convertedDistance = $distance;
        if ($unit !== $this->distance_unit) {
            if ($unit === 'km' && $this->distance_unit === 'miles') {
                $convertedDistance = $distance * 0.621371;
            } elseif ($unit === 'miles' && $this->distance_unit === 'km') {
                $convertedDistance = $distance * 1.60934;
            }
        }

        return $convertedDistance <= $this->max_travel_distance;
    }

    /**
     * Check if hourly rate meets minimum.
     */
    public function meetsMinimumRate(float $rate): bool
    {
        if (!$this->min_hourly_rate) {
            return true;
        }

        return $rate >= $this->min_hourly_rate;
    }

    /**
     * Check if shift duration meets minimum hours.
     */
    public function meetsDurationRequirement(float $hours): bool
    {
        if (!$this->min_hours_per_shift) {
            return true;
        }

        return $hours >= $this->min_hours_per_shift;
    }

    /**
     * Check if worker has availability for more shifts today.
     */
    public function canTakeMoreShiftsToday(int $currentShiftsToday): bool
    {
        if (!$this->max_shifts_per_day) {
            return true;
        }

        return $currentShiftsToday < $this->max_shifts_per_day;
    }

    /**
     * Check if advance notice is sufficient.
     */
    public function hasEnoughAdvanceNotice(\DateTime $shiftStart): bool
    {
        if (!$this->advance_notice_hours) {
            return true;
        }

        $now = new \DateTime();
        $hoursUntilShift = ($shiftStart->getTimestamp() - $now->getTimestamp()) / 3600;

        return $hoursUntilShift >= $this->advance_notice_hours;
    }

    /**
     * Get default preferences for new workers.
     */
    public static function getDefaults(): array
    {
        return [
            'max_hours_per_week' => 40,
            'max_shifts_per_day' => 1,
            'min_hours_per_shift' => 2.00,
            'max_travel_distance' => 25,
            'distance_unit' => 'km',
            'preferred_shift_types' => ['morning', 'afternoon', 'evening'],
            'notify_new_shifts' => true,
            'notify_matching_shifts' => true,
            'notify_urgent_shifts' => true,
            'advance_notice_hours' => 24,
            'auto_accept_invitations' => false,
            'auto_accept_recurring' => false,
        ];
    }
}
