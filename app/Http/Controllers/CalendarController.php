<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\WorkerAvailabilitySchedule;
use App\Models\WorkerBlackoutDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show calendar view - routes to appropriate calendar based on user type
     */
    public function index(Request $request)
    {
        if (Auth::user()->isWorker()) {
            return $this->workerCalendar($request);
        } elseif (Auth::user()->isBusiness() || Auth::user()->isAgency()) {
            return $this->businessCalendar($request);
        }

        abort(403, 'Calendar access denied.');
    }

    /**
     * Show calendar view for worker
     */
    public function workerCalendar(Request $request)
    {
        if (!Auth::user()->isWorker()) {
            abort(403, 'Only workers can access the calendar.');
        }

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        // Get worker's assignments for the month
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $assignments = ShiftAssignment::with('shift')
            ->where('worker_id', Auth::id())
            ->whereHas('shift', function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('shift_date', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        // Get availability schedules
        $availabilitySchedules = WorkerAvailabilitySchedule::where('worker_id', Auth::id())
            ->active()
            ->get();

        // Get blackout dates
        $blackoutDates = WorkerBlackoutDate::where('worker_id', Auth::id())
            ->forDateRange($startOfMonth, $endOfMonth)
            ->get();

        // Get available shifts in the area (for discovery)
        $workerProfile = Auth::user()->workerProfile;
        $nearbyShifts = collect([]);

        if ($workerProfile && $workerProfile->location_lat && $workerProfile->location_lng) {
            $nearbyShifts = Shift::open()
                ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
                ->nearby($workerProfile->location_lat, $workerProfile->location_lng, $workerProfile->preferred_radius ?? 25)
                ->limit(50)
                ->get();
        }

        return view('calendar.worker', compact(
            'month',
            'year',
            'assignments',
            'availabilitySchedules',
            'blackoutDates',
            'nearbyShifts'
        ));
    }

    /**
     * Show calendar view for business
     */
    public function businessCalendar(Request $request)
    {
        if (!Auth::user()->isBusiness() && !Auth::user()->isAgency()) {
            abort(403, 'Only businesses can access this calendar.');
        }

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        // Get business shifts for the month
        $shifts = Shift::where('business_id', Auth::id())
            ->with(['assignments.worker', 'applications'])
            ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
            ->get();

        return view('calendar.business', compact('month', 'year', 'shifts'));
    }

    /**
     * Get calendar data as JSON (for AJAX requests)
     */
    public function getCalendarData(Request $request)
    {
        $start = Carbon::parse($request->get('start'));
        $end = Carbon::parse($request->get('end'));

        if (Auth::user()->isWorker()) {
            return $this->getWorkerCalendarData($start, $end);
        } elseif (Auth::user()->isBusiness() || Auth::user()->isAgency()) {
            return $this->getBusinessCalendarData($start, $end);
        }

        return response()->json([]);
    }

    /**
     * Get worker calendar data
     */
    protected function getWorkerCalendarData($start, $end)
    {
        $events = [];

        // Assigned shifts
        $assignments = ShiftAssignment::with('shift')
            ->where('worker_id', Auth::id())
            ->whereHas('shift', function($q) use ($start, $end) {
                $q->whereBetween('shift_date', [$start, $end]);
            })
            ->get();

        foreach ($assignments as $assignment) {
            $shift = $assignment->shift;
            $events[] = [
                'id' => 'assignment-' . $assignment->id,
                'title' => $shift->title,
                'start' => $shift->shift_date . ' ' . $shift->start_time,
                'end' => $shift->shift_date . ' ' . $shift->end_time,
                'type' => 'assignment',
                'status' => $assignment->status,
                'backgroundColor' => $this->getAssignmentColor($assignment->status),
                'borderColor' => $this->getAssignmentColor($assignment->status),
                'url' => route('worker.assignments'),
            ];
        }

        // Blackout dates
        $blackoutDates = WorkerBlackoutDate::where('worker_id', Auth::id())
            ->forDateRange($start, $end)
            ->get();

        foreach ($blackoutDates as $blackout) {
            $events[] = [
                'id' => 'blackout-' . $blackout->id,
                'title' => 'Unavailable: ' . ($blackout->reason ?? $blackout->type),
                'start' => $blackout->start_date->toDateString(),
                'end' => $blackout->end_date->addDay()->toDateString(), // FullCalendar uses exclusive end dates
                'type' => 'blackout',
                'display' => 'background',
                'backgroundColor' => '#ff0000',
                'opacity' => 0.3,
            ];
        }

        return response()->json($events);
    }

    /**
     * Get business calendar data
     */
    protected function getBusinessCalendarData($start, $end)
    {
        $events = [];

        $shifts = Shift::where('business_id', Auth::id())
            ->whereBetween('shift_date', [$start, $end])
            ->with('assignments')
            ->get();

        foreach ($shifts as $shift) {
            $events[] = [
                'id' => 'shift-' . $shift->id,
                'title' => $shift->title . ' (' . $shift->filled_workers . '/' . $shift->required_workers . ')',
                'start' => $shift->shift_date . ' ' . $shift->start_time,
                'end' => $shift->shift_date . ' ' . $shift->end_time,
                'type' => 'shift',
                'status' => $shift->status,
                'backgroundColor' => $this->getShiftColor($shift->status, $shift->isFull()),
                'borderColor' => $this->getShiftColor($shift->status, $shift->isFull()),
                'url' => route('shift.show', $shift->id),
            ];
        }

        return response()->json($events);
    }

    /**
     * Get color for assignment based on status
     */
    protected function getAssignmentColor($status)
    {
        return match($status) {
            'assigned' => '#3b82f6', // Blue
            'checked_in' => '#10b981', // Green
            'completed' => '#6b7280', // Gray
            'cancelled' => '#ef4444', // Red
            'no_show' => '#dc2626', // Dark red
            default => '#9ca3af', // Light gray
        };
    }

    /**
     * Get color for shift based on status
     */
    protected function getShiftColor($status, $isFull)
    {
        if ($isFull) {
            return '#10b981'; // Green - fully staffed
        }

        return match($status) {
            'open' => '#f59e0b', // Orange - needs workers
            'assigned' => '#3b82f6', // Blue - has workers
            'in_progress' => '#8b5cf6', // Purple - ongoing
            'completed' => '#6b7280', // Gray - done
            'cancelled' => '#ef4444', // Red - cancelled
            default => '#9ca3af', // Light gray
        };
    }

    /**
     * Update worker availability schedule
     */
    public function updateAvailability(Request $request)
    {
        if (!Auth::user()->isWorker()) {
            abort(403, 'Only workers can update availability.');
        }

        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.is_available' => 'required|boolean',
            'schedules.*.preferred_shift_types' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Delete existing schedules
        WorkerAvailabilitySchedule::where('worker_id', Auth::id())->delete();

        // Create new schedules
        foreach ($request->schedules as $schedule) {
            WorkerAvailabilitySchedule::create([
                'worker_id' => Auth::id(),
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_available' => $schedule['is_available'],
                'preferred_shift_types' => $schedule['preferred_shift_types'] ?? [],
                'recurrence' => 'weekly',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully'
        ]);
    }

    /**
     * Add blackout date (alias for route)
     */
    public function storeBlackout(Request $request)
    {
        return $this->addBlackoutDate($request);
    }

    /**
     * Add blackout date
     */
    public function addBlackoutDate(Request $request)
    {
        if (!Auth::user()->isWorker()) {
            abort(403, 'Only workers can add blackout dates.');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:vacation,personal,medical,other',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for overlapping assignments
        $hasConflict = ShiftAssignment::where('worker_id', Auth::id())
            ->whereIn('status', ['assigned', 'checked_in'])
            ->whereHas('shift', function($q) use ($request) {
                $q->whereBetween('shift_date', [$request->start_date, $request->end_date]);
            })
            ->exists();

        if ($hasConflict) {
            return redirect()->back()
                ->with('warning', 'You have assigned shifts during this period. Please cancel them first or adjust your blackout dates.')
                ->withInput();
        }

        WorkerBlackoutDate::create([
            'worker_id' => Auth::id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'type' => $request->type,
            'notes' => $request->notes,
        ]);

        return redirect()->back()
            ->with('success', 'Blackout dates added successfully');
    }

    /**
     * Remove blackout date (alias for route)
     */
    public function deleteBlackout($id)
    {
        return $this->removeBlackoutDate($id);
    }

    /**
     * Remove blackout date
     */
    public function removeBlackoutDate($id)
    {
        $blackout = WorkerBlackoutDate::findOrFail($id);

        if ($blackout->worker_id !== Auth::id()) {
            abort(403, 'You can only remove your own blackout dates.');
        }

        $blackout->delete();

        return redirect()->back()
            ->with('success', 'Blackout date removed successfully');
    }

    /**
     * Check for shift conflicts
     */
    public function checkConflicts(Request $request)
    {
        if (!Auth::user()->isWorker()) {
            return response()->json(['conflicts' => []]);
        }

        $shiftDate = $request->get('shift_date');
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');

        $conflicts = [];

        // Check for existing assignments
        $existingAssignment = ShiftAssignment::where('worker_id', Auth::id())
            ->whereIn('status', ['assigned', 'checked_in'])
            ->whereHas('shift', function($q) use ($shiftDate, $startTime, $endTime) {
                $q->where('shift_date', $shiftDate)
                  ->where(function($query) use ($startTime, $endTime) {
                      $query->whereBetween('start_time', [$startTime, $endTime])
                            ->orWhereBetween('end_time', [$startTime, $endTime])
                            ->orWhere(function($q2) use ($startTime, $endTime) {
                                $q2->where('start_time', '<=', $startTime)
                                   ->where('end_time', '>=', $endTime);
                            });
                  });
            })
            ->with('shift')
            ->first();

        if ($existingAssignment) {
            $conflicts[] = [
                'type' => 'assignment',
                'message' => 'You have another shift assigned during this time',
                'shift' => $existingAssignment->shift->title,
            ];
        }

        // Check for blackout dates
        $blackout = WorkerBlackoutDate::where('worker_id', Auth::id())
            ->forDateRange($shiftDate, $shiftDate)
            ->first();

        if ($blackout) {
            $conflicts[] = [
                'type' => 'blackout',
                'message' => 'This date falls within your blackout period',
                'reason' => $blackout->reason ?? $blackout->type,
            ];
        }

        return response()->json([
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ]);
    }
}
