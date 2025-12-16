<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use App\Http\Requests\Worker\SetWeeklyScheduleRequest;
use App\Http\Requests\Worker\AddDateOverrideRequest;
use App\Http\Requests\Worker\UpdatePreferencesRequest;
use App\Http\Requests\Worker\AddBlackoutDateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Availability Controller
 * STAFF-REG-009: Worker Availability Setup
 *
 * Handles worker availability, schedules, overrides, and preferences.
 */
class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->middleware(['auth', 'worker']);
        $this->availabilityService = $availabilityService;
    }

    /**
     * Show availability management page (web route).
     *
     * GET /worker/availability
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $worker = Auth::user();
        $availability = $this->availabilityService->getWorkerAvailability($worker);

        return view('worker.availability.index', [
            'availability' => $availability,
            'shiftTypes' => \App\Models\WorkerPreference::SHIFT_TYPES ?? [],
            'days' => \App\Services\AvailabilityService::DAYS ?? [],
        ]);
    }

    /**
     * Get worker's complete availability (API route).
     *
     * @return JsonResponse
     */
    public function getAvailability(): JsonResponse
    {
        $worker = Auth::user();
        $availability = $this->availabilityService->getWorkerAvailability($worker);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Set or update weekly schedule.
     *
     * @param SetWeeklyScheduleRequest $request
     * @return JsonResponse
     */
    public function setWeeklySchedule(SetWeeklyScheduleRequest $request): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->setWeeklySchedule($worker, $request->validated()['schedule']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'schedules' => $result['schedules'],
        ]);
    }

    /**
     * Add a date override.
     *
     * @param AddDateOverrideRequest $request
     * @return JsonResponse
     */
    public function addDateOverride(AddDateOverrideRequest $request): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->addDateOverride($worker, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'override' => $result['override'],
        ], 201);
    }

    /**
     * Update worker preferences.
     *
     * @param UpdatePreferencesRequest $request
     * @return JsonResponse
     */
    public function setPreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->updatePreferences($worker, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'preferences' => $result['preferences'],
        ]);
    }

    /**
     * Add a blackout date.
     *
     * @param AddBlackoutDateRequest $request
     * @return JsonResponse
     */
    public function addBlackoutDate(AddBlackoutDateRequest $request): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->addBlackoutDate($worker, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'blackout' => $result['blackout'],
        ], 201);
    }

    /**
     * Delete a blackout date.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteBlackoutDate(int $id): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->deleteBlackoutDate($worker, $id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Delete a date override.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteOverride(int $id): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->availabilityService->deleteOverride($worker, $id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Get available time slots for a date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $worker = Auth::user();
        $slots = $this->availabilityService->getAvailableSlots(
            $worker,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    /**
     * Check availability for a specific datetime.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => 'required|date',
        ]);

        $worker = Auth::user();
        $result = $this->availabilityService->checkAvailability(
            $worker,
            \Carbon\Carbon::parse($request->datetime)
        );

        return response()->json([
            'success' => true,
            'availability' => $result,
        ]);
    }

}
