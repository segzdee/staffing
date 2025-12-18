<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarExportService;

/**
 * BIZ-012: Integration APIs - Calendar Feed Controller
 *
 * Provides iCal feed endpoint for calendar subscriptions.
 * The feed URL is public but secured by a unique token per user.
 */
class CalendarFeedController extends Controller
{
    public function __construct(
        protected CalendarExportService $calendarExportService
    ) {}

    /**
     * Get calendar feed in iCal format.
     *
     * @param  string  $token  The calendar token (includes .ics extension)
     * @return \Illuminate\Http\Response
     */
    public function show(string $token)
    {
        // Remove .ics extension if present
        $token = str_replace('.ics', '', $token);

        // Find user by calendar token
        $user = $this->calendarExportService->findUserByToken($token);

        if (! $user) {
            return response('Calendar not found', 404)
                ->header('Content-Type', 'text/plain');
        }

        // Generate the iCal feed
        $icalContent = $this->calendarExportService->generateICalFeed($user);

        return response($icalContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="overtimestaff-calendar.ics"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
