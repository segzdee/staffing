<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * BIZ-012: Integration APIs - Calendar Export Service
 *
 * Generates iCal (ICS) feeds for workers and businesses to subscribe
 * to their shifts from external calendar applications.
 */
class CalendarExportService
{
    /**
     * Cache duration for calendar feeds (in minutes).
     */
    protected int $cacheDuration = 5;

    /**
     * Generate iCal feed for a user.
     */
    public function generateICalFeed(User $user): string
    {
        $cacheKey = "ical_feed_{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($user) {
            if ($user->isWorker()) {
                return $this->generateWorkerFeed($user);
            }

            if ($user->isBusiness()) {
                return $this->generateBusinessFeed($user);
            }

            return $this->generateEmptyFeed();
        });
    }

    /**
     * Get the calendar feed URL for a user.
     */
    public function getCalendarUrl(User $user): string
    {
        // Generate or retrieve a calendar token for the user
        $token = $this->getOrCreateCalendarToken($user);

        return url("/api/calendar/{$token}.ics");
    }

    /**
     * Get or create a calendar token for a user.
     */
    public function getOrCreateCalendarToken(User $user): string
    {
        $cacheKey = "calendar_token_{$user->id}";

        return Cache::rememberForever($cacheKey, function () use ($user) {
            // Generate a unique token
            return Str::random(32).$user->id;
        });
    }

    /**
     * Find user by calendar token.
     */
    public function findUserByToken(string $token): ?User
    {
        // Extract user ID from token (last part after the random string)
        // Token format: {32_random_chars}{user_id}
        if (strlen($token) < 33) {
            return null;
        }

        $userId = substr($token, 32);
        if (! is_numeric($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        if (! $user) {
            return null;
        }

        // Verify the token matches
        $expectedToken = Cache::get("calendar_token_{$user->id}");

        if ($expectedToken !== $token) {
            return null;
        }

        return $user;
    }

    /**
     * Regenerate calendar token (for security).
     */
    public function regenerateCalendarToken(User $user): string
    {
        $cacheKey = "calendar_token_{$user->id}";
        Cache::forget($cacheKey);

        // Also invalidate the feed cache
        Cache::forget("ical_feed_{$user->id}");

        return $this->getOrCreateCalendarToken($user);
    }

    /**
     * Generate iCal feed for a worker.
     */
    protected function generateWorkerFeed(User $worker): string
    {
        // Get all upcoming assigned shifts for the worker
        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->whereHas('shift', function ($query) {
                $query->where('shift_date', '>=', now()->subDays(7)->toDateString())
                    ->whereIn('status', ['open', 'confirmed', 'in_progress', 'completed']);
            })
            ->with('shift.business')
            ->get();

        $events = [];

        foreach ($assignments as $assignment) {
            $shift = $assignment->shift;
            if (! $shift) {
                continue;
            }

            $events[] = $this->createShiftEvent($shift, $assignment, $worker);
        }

        return $this->buildICalFeed(
            "OvertimeStaff - {$worker->name}'s Shifts",
            'Your scheduled shifts from OvertimeStaff',
            $events
        );
    }

    /**
     * Generate iCal feed for a business.
     */
    protected function generateBusinessFeed(User $business): string
    {
        // Get all shifts for the business
        $shifts = Shift::where('business_id', $business->id)
            ->where('shift_date', '>=', now()->subDays(7)->toDateString())
            ->whereIn('status', ['draft', 'open', 'confirmed', 'in_progress', 'completed'])
            ->with('assignments.worker')
            ->get();

        $events = [];

        foreach ($shifts as $shift) {
            $events[] = $this->createBusinessShiftEvent($shift);
        }

        $businessName = $business->businessProfile?->company_name ?? $business->name;

        return $this->buildICalFeed(
            "OvertimeStaff - {$businessName} Shifts",
            'All scheduled shifts for your business',
            $events
        );
    }

    /**
     * Generate an empty calendar feed.
     */
    protected function generateEmptyFeed(): string
    {
        return $this->buildICalFeed(
            'OvertimeStaff Calendar',
            'No shifts available',
            []
        );
    }

    /**
     * Create a shift event for worker calendar.
     */
    protected function createShiftEvent(Shift $shift, ShiftAssignment $assignment, User $worker): array
    {
        $startDateTime = $this->parseShiftDateTime($shift->shift_date, $shift->start_time);
        $endDateTime = $this->parseShiftDateTime($shift->shift_date, $shift->end_time);

        // Handle overnight shifts
        if ($endDateTime <= $startDateTime) {
            $endDateTime->addDay();
        }

        $businessName = $shift->business?->businessProfile?->company_name ?? $shift->business?->name ?? 'Unknown Business';

        $description = $this->buildWorkerEventDescription($shift, $assignment);

        return [
            'uid' => "shift-{$shift->id}-assignment-{$assignment->id}@overtimestaff.com",
            'summary' => "{$shift->title} @ {$businessName}",
            'description' => $description,
            'location' => $this->formatLocation($shift),
            'start' => $startDateTime,
            'end' => $endDateTime,
            'status' => $this->mapShiftStatusToIcal($assignment->status),
            'categories' => ['Work', 'Shift'],
            'url' => url("/worker/shifts/{$shift->id}"),
        ];
    }

    /**
     * Create a shift event for business calendar.
     */
    protected function createBusinessShiftEvent(Shift $shift): array
    {
        $startDateTime = $this->parseShiftDateTime($shift->shift_date, $shift->start_time);
        $endDateTime = $this->parseShiftDateTime($shift->shift_date, $shift->end_time);

        // Handle overnight shifts
        if ($endDateTime <= $startDateTime) {
            $endDateTime->addDay();
        }

        $description = $this->buildBusinessEventDescription($shift);

        return [
            'uid' => "shift-{$shift->id}@overtimestaff.com",
            'summary' => "{$shift->title} ({$shift->filled_workers}/{$shift->required_workers} filled)",
            'description' => $description,
            'location' => $this->formatLocation($shift),
            'start' => $startDateTime,
            'end' => $endDateTime,
            'status' => $this->mapShiftStatusToIcal($shift->status),
            'categories' => ['Work', 'Shift'],
            'url' => url("/business/shifts/{$shift->id}"),
        ];
    }

    /**
     * Build event description for worker view.
     */
    protected function buildWorkerEventDescription(Shift $shift, ShiftAssignment $assignment): string
    {
        $lines = [];

        $businessName = $shift->business?->businessProfile?->company_name ?? $shift->business?->name ?? 'Unknown';
        $lines[] = "Business: {$businessName}";
        $lines[] = 'Status: '.ucfirst($assignment->status);

        if ($shift->description) {
            $lines[] = '';
            $lines[] = 'Description:';
            $lines[] = $shift->description;
        }

        if ($shift->dress_code) {
            $lines[] = '';
            $lines[] = "Dress Code: {$shift->dress_code}";
        }

        if ($shift->special_instructions) {
            $lines[] = '';
            $lines[] = 'Special Instructions:';
            $lines[] = $shift->special_instructions;
        }

        if ($shift->parking_info) {
            $lines[] = '';
            $lines[] = "Parking: {$shift->parking_info}";
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'Powered by OvertimeStaff';

        return implode('\\n', $lines);
    }

    /**
     * Build event description for business view.
     */
    protected function buildBusinessEventDescription(Shift $shift): string
    {
        $lines = [];

        $lines[] = "Workers: {$shift->filled_workers}/{$shift->required_workers}";
        $lines[] = 'Status: '.ucfirst($shift->status);

        if ($shift->assignments && $shift->assignments->count() > 0) {
            $lines[] = '';
            $lines[] = 'Assigned Workers:';
            foreach ($shift->assignments as $assignment) {
                $workerName = $assignment->worker?->name ?? 'Unknown';
                $status = ucfirst($assignment->status);
                $lines[] = "- {$workerName} ({$status})";
            }
        }

        if ($shift->description) {
            $lines[] = '';
            $lines[] = 'Description:';
            $lines[] = $shift->description;
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'Powered by OvertimeStaff';

        return implode('\\n', $lines);
    }

    /**
     * Format location for calendar event.
     */
    protected function formatLocation(Shift $shift): string
    {
        $parts = array_filter([
            $shift->location_address,
            $shift->location_city,
            $shift->location_state,
            $shift->location_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Parse shift date and time into Carbon instance.
     */
    protected function parseShiftDateTime($date, $time): Carbon
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : $date;
        $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : $time;

        return Carbon::parse("{$dateStr} {$timeStr}");
    }

    /**
     * Map shift/assignment status to iCal status.
     */
    protected function mapShiftStatusToIcal(string $status): string
    {
        return match ($status) {
            'draft', 'pending' => 'TENTATIVE',
            'open', 'assigned', 'confirmed' => 'CONFIRMED',
            'cancelled' => 'CANCELLED',
            default => 'CONFIRMED',
        };
    }

    /**
     * Build the complete iCal feed.
     */
    protected function buildICalFeed(string $calendarName, string $description, array $events): string
    {
        $ical = [];

        // Calendar header
        $ical[] = 'BEGIN:VCALENDAR';
        $ical[] = 'VERSION:2.0';
        $ical[] = 'PRODID:-//OvertimeStaff//Calendar//EN';
        $ical[] = 'CALSCALE:GREGORIAN';
        $ical[] = 'METHOD:PUBLISH';
        $ical[] = 'X-WR-CALNAME:'.$this->escapeIcalText($calendarName);
        $ical[] = 'X-WR-CALDESC:'.$this->escapeIcalText($description);
        $ical[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT5M';
        $ical[] = 'X-PUBLISHED-TTL:PT5M';

        // Add timezone
        $ical[] = 'BEGIN:VTIMEZONE';
        $ical[] = 'TZID:'.config('app.timezone', 'UTC');
        $ical[] = 'BEGIN:STANDARD';
        $ical[] = 'DTSTART:19700101T000000';
        $ical[] = 'TZOFFSETFROM:+0000';
        $ical[] = 'TZOFFSETTO:+0000';
        $ical[] = 'END:STANDARD';
        $ical[] = 'END:VTIMEZONE';

        // Add events
        foreach ($events as $event) {
            $ical[] = $this->buildEvent($event);
        }

        // Calendar footer
        $ical[] = 'END:VCALENDAR';

        return implode("\r\n", $ical);
    }

    /**
     * Build a single event entry.
     */
    protected function buildEvent(array $event): string
    {
        $lines = [];

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:'.$event['uid'];
        $lines[] = 'DTSTAMP:'.$this->formatIcalDate(now());
        $lines[] = 'DTSTART:'.$this->formatIcalDate($event['start']);
        $lines[] = 'DTEND:'.$this->formatIcalDate($event['end']);
        $lines[] = 'SUMMARY:'.$this->escapeIcalText($event['summary']);

        if (! empty($event['description'])) {
            $lines[] = 'DESCRIPTION:'.$this->escapeIcalText($event['description']);
        }

        if (! empty($event['location'])) {
            $lines[] = 'LOCATION:'.$this->escapeIcalText($event['location']);
        }

        if (! empty($event['status'])) {
            $lines[] = 'STATUS:'.$event['status'];
        }

        if (! empty($event['categories'])) {
            $lines[] = 'CATEGORIES:'.implode(',', $event['categories']);
        }

        if (! empty($event['url'])) {
            $lines[] = 'URL:'.$event['url'];
        }

        // Add alarm for upcoming shifts (1 hour before)
        $lines[] = 'BEGIN:VALARM';
        $lines[] = 'TRIGGER:-PT1H';
        $lines[] = 'ACTION:DISPLAY';
        $lines[] = 'DESCRIPTION:Shift starting in 1 hour';
        $lines[] = 'END:VALARM';

        // Add another alarm (15 minutes before)
        $lines[] = 'BEGIN:VALARM';
        $lines[] = 'TRIGGER:-PT15M';
        $lines[] = 'ACTION:DISPLAY';
        $lines[] = 'DESCRIPTION:Shift starting in 15 minutes';
        $lines[] = 'END:VALARM';

        $lines[] = 'END:VEVENT';

        return implode("\r\n", $lines);
    }

    /**
     * Format a date for iCal format.
     */
    protected function formatIcalDate(Carbon $date): string
    {
        return $date->format('Ymd\THis\Z');
    }

    /**
     * Escape text for iCal format.
     */
    protected function escapeIcalText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("\r\n", '\\n', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);

        return $text;
    }

    /**
     * Invalidate calendar cache for a user.
     */
    public function invalidateCache(User $user): void
    {
        Cache::forget("ical_feed_{$user->id}");
    }

    /**
     * Invalidate cache for all users related to a shift.
     */
    public function invalidateCacheForShift(Shift $shift): void
    {
        // Invalidate business cache
        $this->invalidateCache($shift->business);

        // Invalidate worker caches
        foreach ($shift->assignments as $assignment) {
            if ($assignment->worker) {
                $this->invalidateCache($assignment->worker);
            }
        }
    }
}
