<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerAvailabilitySchedule;
use App\Models\WorkerAvailabilityOverride;
use App\Models\WorkerBlackoutDate;
use App\Models\WorkerPreference;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Availability Service
 * STAFF-REG-009: Worker Availability Setup
 *
 * Manages worker availability, schedules, overrides, and preferences.
 */
class AvailabilityService
{
    /**
     * Days of the week.
     */
    public const DAYS = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
    ];

    /**
     * Set or update worker's weekly schedule.
     */
    public function setWeeklySchedule(User $worker, array $schedule): array
    {
        DB::beginTransaction();

        try {
            $savedSchedules = [];

            foreach ($schedule as $day => $slots) {
                // Validate day
                if (!in_array(strtolower($day), self::DAYS)) {
                    continue;
                }

                // Delete existing schedules for this day
                WorkerAvailabilitySchedule::where('worker_id', $worker->id)
                    ->where('day_of_week', strtolower($day))
                    ->delete();

                // Create new slots
                foreach ($slots as $slot) {
                    if (!$slot['is_available'] ?? false) {
                        continue;
                    }

                    $savedSchedule = WorkerAvailabilitySchedule::create([
                        'worker_id' => $worker->id,
                        'day_of_week' => strtolower($day),
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'is_available' => true,
                        'preferred_shift_types' => $slot['preferred_shift_types'] ?? null,
                        'recurrence' => $slot['recurrence'] ?? 'weekly',
                        'effective_from' => $slot['effective_from'] ?? null,
                        'effective_until' => $slot['effective_until'] ?? null,
                    ]);

                    $savedSchedules[] = $savedSchedule;
                }
            }

            // Update worker profile to indicate availability is set
            if ($worker->workerProfile) {
                $worker->workerProfile->update([
                    'availability_schedule' => $this->formatScheduleForProfile($savedSchedules),
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'schedules' => $savedSchedules,
                'message' => 'Weekly schedule updated successfully.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get worker's complete availability.
     */
    public function getWorkerAvailability(User $worker): array
    {
        $schedules = WorkerAvailabilitySchedule::where('worker_id', $worker->id)
            ->active()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $overrides = WorkerAvailabilityOverride::where('user_id', $worker->id)
            ->active()
            ->orderBy('date')
            ->get();

        $blackouts = WorkerBlackoutDate::where('worker_id', $worker->id)
            ->active()
            ->orderBy('start_date')
            ->get();

        $preferences = WorkerPreference::where('user_id', $worker->id)->first();

        return [
            'weekly_schedule' => $this->groupSchedulesByDay($schedules),
            'overrides' => $overrides,
            'blackouts' => $blackouts,
            'preferences' => $preferences ?? WorkerPreference::getDefaults(),
        ];
    }

    /**
     * Add a date override.
     */
    public function addDateOverride(User $worker, array $data): array
    {
        try {
            $override = WorkerAvailabilityOverride::create([
                'user_id' => $worker->id,
                'date' => $data['date'],
                'type' => $data['type'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_one_time' => $data['is_one_time'] ?? true,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'priority' => $data['priority'] ?? 1,
            ]);

            return [
                'success' => true,
                'override' => $override,
                'message' => 'Date override added successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add a blackout date.
     */
    public function addBlackoutDate(User $worker, array $data): array
    {
        try {
            $blackout = WorkerBlackoutDate::create([
                'worker_id' => $worker->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'type' => $data['type'] ?? 'personal',
            ]);

            return [
                'success' => true,
                'blackout' => $blackout,
                'message' => 'Blackout period added successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update worker preferences.
     */
    public function updatePreferences(User $worker, array $data): array
    {
        try {
            $preferences = WorkerPreference::updateOrCreate(
                ['user_id' => $worker->id],
                [
                    'max_hours_per_week' => $data['max_hours_per_week'] ?? 40,
                    'max_shifts_per_day' => $data['max_shifts_per_day'] ?? 1,
                    'min_hours_per_shift' => $data['min_hours_per_shift'] ?? 2.00,
                    'max_travel_distance' => $data['max_travel_distance'] ?? 25,
                    'distance_unit' => $data['distance_unit'] ?? 'km',
                    'preferred_shift_types' => $data['preferred_shift_types'] ?? null,
                    'min_hourly_rate' => $data['min_hourly_rate'] ?? null,
                    'preferred_currency' => $data['preferred_currency'] ?? 'USD',
                    'preferred_industries' => $data['preferred_industries'] ?? null,
                    'preferred_roles' => $data['preferred_roles'] ?? null,
                    'excluded_businesses' => $data['excluded_businesses'] ?? null,
                    'notify_new_shifts' => $data['notify_new_shifts'] ?? true,
                    'notify_matching_shifts' => $data['notify_matching_shifts'] ?? true,
                    'notify_urgent_shifts' => $data['notify_urgent_shifts'] ?? true,
                    'advance_notice_hours' => $data['advance_notice_hours'] ?? 24,
                    'auto_accept_invitations' => $data['auto_accept_invitations'] ?? false,
                    'auto_accept_recurring' => $data['auto_accept_recurring'] ?? false,
                ]
            );

            return [
                'success' => true,
                'preferences' => $preferences,
                'message' => 'Preferences updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available time slots for a worker within a date range.
     */
    public function getAvailableSlots(User $worker, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $period = CarbonPeriod::create($start, $end);

        $availability = $this->getWorkerAvailability($worker);
        $slots = [];

        foreach ($period as $date) {
            $daySlots = $this->getSlotsForDate($worker, $date, $availability);
            if (!empty($daySlots)) {
                $slots[$date->toDateString()] = $daySlots;
            }
        }

        return $slots;
    }

    /**
     * Check if worker is available at a specific time.
     */
    public function checkAvailability(User $worker, Carbon $dateTime): array
    {
        $date = $dateTime->copy()->startOfDay();
        $time = $dateTime->format('H:i');
        $dayOfWeek = strtolower($date->format('l'));

        // Check blackout dates first
        $blackout = WorkerBlackoutDate::where('worker_id', $worker->id)
            ->forDateRange($date, $date)
            ->first();

        if ($blackout) {
            return [
                'available' => false,
                'reason' => 'blackout_period',
                'details' => $blackout->reason ?? 'Unavailable during this period',
            ];
        }

        // Check date overrides
        $override = WorkerAvailabilityOverride::getForUserAndDate($worker->id, $date);

        if ($override) {
            if ($override->type === 'unavailable') {
                return [
                    'available' => false,
                    'reason' => 'date_override_unavailable',
                    'details' => $override->reason ?? 'Unavailable on this date',
                ];
            }

            if ($override->isAvailableAt($time)) {
                return [
                    'available' => true,
                    'reason' => 'date_override_available',
                    'override' => $override,
                ];
            }
        }

        // Check regular schedule
        $schedule = WorkerAvailabilitySchedule::where('worker_id', $worker->id)
            ->forDay($dayOfWeek)
            ->active()
            ->get();

        foreach ($schedule as $slot) {
            if ($slot->isAvailableAt($dayOfWeek, $time)) {
                return [
                    'available' => true,
                    'reason' => 'regular_schedule',
                    'schedule' => $slot,
                ];
            }
        }

        return [
            'available' => false,
            'reason' => 'no_availability_set',
            'details' => 'No availability configured for this time',
        ];
    }

    /**
     * Check if a shift matches worker's preferences.
     */
    public function matchesPreferences(User $worker, array $shiftData): array
    {
        $preferences = WorkerPreference::where('user_id', $worker->id)->first();

        if (!$preferences) {
            return [
                'matches' => true,
                'checks' => [],
                'message' => 'No preferences set - accepting all shifts',
            ];
        }

        $checks = [];
        $allPass = true;

        // Check hourly rate
        if (isset($shiftData['hourly_rate'])) {
            $ratePass = $preferences->meetsMinimumRate($shiftData['hourly_rate']);
            $checks['hourly_rate'] = [
                'passed' => $ratePass,
                'required' => $preferences->min_hourly_rate,
                'actual' => $shiftData['hourly_rate'],
            ];
            if (!$ratePass) $allPass = false;
        }

        // Check duration
        if (isset($shiftData['duration_hours'])) {
            $durationPass = $preferences->meetsDurationRequirement($shiftData['duration_hours']);
            $checks['duration'] = [
                'passed' => $durationPass,
                'required' => $preferences->min_hours_per_shift,
                'actual' => $shiftData['duration_hours'],
            ];
            if (!$durationPass) $allPass = false;
        }

        // Check distance
        if (isset($shiftData['distance'])) {
            $distancePass = $preferences->isWithinDistance($shiftData['distance'], $shiftData['distance_unit'] ?? 'km');
            $checks['distance'] = [
                'passed' => $distancePass,
                'max' => $preferences->max_travel_distance,
                'actual' => $shiftData['distance'],
            ];
            if (!$distancePass) $allPass = false;
        }

        // Check shift type
        if (isset($shiftData['start_time'])) {
            $typePass = $preferences->matchesShiftTime($shiftData['start_time']);
            $checks['shift_type'] = [
                'passed' => $typePass,
                'preferred' => $preferences->preferred_shift_types,
                'actual_time' => $shiftData['start_time'],
            ];
            if (!$typePass) $allPass = false;
        }

        // Check excluded businesses
        if (isset($shiftData['business_id'])) {
            $notExcluded = !$preferences->isBusinessExcluded($shiftData['business_id']);
            $checks['business_not_excluded'] = [
                'passed' => $notExcluded,
                'business_id' => $shiftData['business_id'],
            ];
            if (!$notExcluded) $allPass = false;
        }

        // Check advance notice
        if (isset($shiftData['start_datetime'])) {
            $noticePass = $preferences->hasEnoughAdvanceNotice(new \DateTime($shiftData['start_datetime']));
            $checks['advance_notice'] = [
                'passed' => $noticePass,
                'required_hours' => $preferences->advance_notice_hours,
                'shift_start' => $shiftData['start_datetime'],
            ];
            if (!$noticePass) $allPass = false;
        }

        return [
            'matches' => $allPass,
            'checks' => $checks,
            'message' => $allPass ? 'Shift matches all preferences' : 'Shift does not match some preferences',
        ];
    }

    /**
     * Get slots for a specific date.
     */
    protected function getSlotsForDate(User $worker, Carbon $date, array $availability): array
    {
        // Check blackouts
        foreach ($availability['blackouts'] as $blackout) {
            if ($blackout->includesDate($date)) {
                return []; // No availability on blackout dates
            }
        }

        // Check overrides
        foreach ($availability['overrides'] as $override) {
            if ($override->appliesToDate($date)) {
                if ($override->type === 'unavailable') {
                    return [];
                }

                if ($override->start_time && $override->end_time) {
                    return [[
                        'start_time' => $override->start_time->format('H:i'),
                        'end_time' => $override->end_time->format('H:i'),
                        'type' => 'override',
                    ]];
                }
            }
        }

        // Use regular schedule
        $dayOfWeek = strtolower($date->format('l'));
        $daySchedule = $availability['weekly_schedule'][$dayOfWeek] ?? [];

        return array_map(function ($slot) {
            return [
                'start_time' => $slot->start_time->format('H:i'),
                'end_time' => $slot->end_time->format('H:i'),
                'type' => 'schedule',
            ];
        }, $daySchedule);
    }

    /**
     * Group schedules by day.
     */
    protected function groupSchedulesByDay(Collection $schedules): array
    {
        $grouped = [];
        foreach (self::DAYS as $day) {
            $grouped[$day] = [];
        }

        foreach ($schedules as $schedule) {
            $grouped[$schedule->day_of_week][] = $schedule;
        }

        return $grouped;
    }

    /**
     * Format schedule for profile storage.
     */
    protected function formatScheduleForProfile(array $schedules): array
    {
        $formatted = [];

        foreach ($schedules as $schedule) {
            $day = $schedule->day_of_week;
            if (!isset($formatted[$day])) {
                $formatted[$day] = [
                    'available' => true,
                    'slots' => [],
                ];
            }

            $formatted[$day]['slots'][] = [
                'start' => $schedule->start_time->format('H:i'),
                'end' => $schedule->end_time->format('H:i'),
            ];
        }

        return $formatted;
    }

    /**
     * Delete a blackout date.
     */
    public function deleteBlackoutDate(User $worker, int $blackoutId): array
    {
        $blackout = WorkerBlackoutDate::where('worker_id', $worker->id)
            ->where('id', $blackoutId)
            ->first();

        if (!$blackout) {
            return [
                'success' => false,
                'error' => 'Blackout date not found.',
            ];
        }

        $blackout->delete();

        return [
            'success' => true,
            'message' => 'Blackout date removed successfully.',
        ];
    }

    /**
     * Delete a date override.
     */
    public function deleteOverride(User $worker, int $overrideId): array
    {
        $override = WorkerAvailabilityOverride::where('user_id', $worker->id)
            ->where('id', $overrideId)
            ->first();

        if (!$override) {
            return [
                'success' => false,
                'error' => 'Override not found.',
            ];
        }

        $override->delete();

        return [
            'success' => true,
            'message' => 'Date override removed successfully.',
        ];
    }
}
