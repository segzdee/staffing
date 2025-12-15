<?php

namespace App\Services;

use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\Shift;
use App\Models\UrgentShiftRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * UrgentFillService
 *
 * Handles urgent shift fill detection and routing to qualified agencies.
 *
 * TASK: AGY-004 Urgent Fill Routing
 *
 * Features:
 * - Detect urgent fills (<4 hours until shift OR <80% fill rate)
 * - Query qualifying agencies (skills, radius, fill rate history)
 * - Priority notifications to agencies (30-minute response SLA)
 * - Agency response tracking
 * - Escalation if no response
 */
class UrgentFillService
{
    /**
     * Default search radius for agencies (in miles).
     */
    const DEFAULT_RADIUS = 50;

    /**
     * Minimum agency fill rate to be eligible for urgent requests.
     */
    const MIN_AGENCY_FILL_RATE = 75.00;

    /**
     * SLA response time in minutes.
     */
    const SLA_RESPONSE_MINUTES = 30;

    /**
     * Detect and create urgent shift requests.
     *
     * Scans all open shifts and identifies those needing urgent agency support.
     *
     * @return array Summary of detected urgent shifts
     */
    public function detectUrgentShifts()
    {
        $urgentShifts = [];

        // Get all open shifts
        $shifts = Shift::where('status', 'open')
            ->where('allow_agencies', true)
            ->where('shift_date', '>=', now()->toDateString())
            ->whereColumn('filled_workers', '<', 'required_workers')
            ->get();

        foreach ($shifts as $shift) {
            $isUrgent = false;
            $urgencyReason = null;

            // Check time constraint (<4 hours until shift)
            $hoursUntilShift = now()->diffInHours($shift->start_time, false);
            if ($hoursUntilShift >= 0 && $hoursUntilShift < 4) {
                $isUrgent = true;
                $urgencyReason = 'time_constraint';
            }

            // Check fill rate (<80%)
            $fillPercentage = ($shift->filled_workers / $shift->required_workers) * 100;
            if ($fillPercentage < 80 && $hoursUntilShift < 12) {
                $isUrgent = true;
                $urgencyReason = $urgencyReason ?? 'low_fill_rate';
            }

            if ($isUrgent) {
                // Check if already tracked
                $existing = UrgentShiftRequest::where('shift_id', $shift->id)
                    ->whereIn('status', ['pending', 'routed'])
                    ->first();

                if (!$existing) {
                    $request = $this->createUrgentRequest($shift, $urgencyReason, $fillPercentage, $hoursUntilShift);
                    $urgentShifts[] = $request;
                }
            }
        }

        Log::info("Urgent shift detection complete", [
            'total_scanned' => $shifts->count(),
            'urgent_detected' => count($urgentShifts),
        ]);

        return $urgentShifts;
    }

    /**
     * Create an urgent shift request record.
     *
     * @param Shift $shift
     * @param string $urgencyReason
     * @param float $fillPercentage
     * @param int $hoursUntilShift
     * @return UrgentShiftRequest
     */
    protected function createUrgentRequest(Shift $shift, $urgencyReason, $fillPercentage, $hoursUntilShift)
    {
        return UrgentShiftRequest::create([
            'shift_id' => $shift->id,
            'business_id' => $shift->business_id,
            'urgency_reason' => $urgencyReason,
            'fill_percentage' => $fillPercentage,
            'hours_until_shift' => $hoursUntilShift,
            'shift_start_time' => $shift->start_time,
            'detected_at' => now(),
            'status' => 'pending',
        ]);
    }

    /**
     * Route urgent shifts to qualifying agencies.
     *
     * @param UrgentShiftRequest|null $specificRequest Route specific request or all pending
     * @return array Summary of routing results
     */
    public function routeToAgencies($specificRequest = null)
    {
        $requests = $specificRequest
            ? collect([$specificRequest])
            : UrgentShiftRequest::where('status', 'pending')->with('shift')->get();

        $summary = [
            'total_requests' => $requests->count(),
            'routed' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($requests as $request) {
            $result = $this->routeSingleRequest($request);
            $summary['details'][] = $result;

            if ($result['success']) {
                $summary['routed']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * Route a single urgent request to qualifying agencies.
     *
     * @param UrgentShiftRequest $request
     * @return array Result details
     */
    protected function routeSingleRequest(UrgentShiftRequest $request)
    {
        $shift = $request->shift;

        if (!$shift) {
            return [
                'success' => false,
                'request_id' => $request->id,
                'error' => 'Shift not found',
            ];
        }

        // Find qualifying agencies
        $agencies = $this->findQualifyingAgencies($shift);

        if ($agencies->isEmpty()) {
            Log::warning("No qualifying agencies found for urgent shift", [
                'shift_id' => $shift->id,
                'request_id' => $request->id,
            ]);

            return [
                'success' => false,
                'request_id' => $request->id,
                'shift_id' => $shift->id,
                'error' => 'No qualifying agencies',
            ];
        }

        // Notify agencies
        $notifiedAgencyIds = [];
        foreach ($agencies as $agency) {
            try {
                // Send notification (will be implemented in Notification class)
                // Notification::send($agency, new \App\Notifications\UrgentShiftNotification($request, $shift));
                $notifiedAgencyIds[] = $agency->id;
            } catch (\Exception $e) {
                Log::error("Failed to notify agency", [
                    'agency_id' => $agency->id,
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update request status
        $request->markAsRouted($notifiedAgencyIds);

        Log::info("Urgent shift routed to agencies", [
            'request_id' => $request->id,
            'shift_id' => $shift->id,
            'agencies_notified' => count($notifiedAgencyIds),
        ]);

        return [
            'success' => true,
            'request_id' => $request->id,
            'shift_id' => $shift->id,
            'agencies_notified' => count($notifiedAgencyIds),
            'agency_ids' => $notifiedAgencyIds,
        ];
    }

    /**
     * Find agencies qualified to handle an urgent shift.
     *
     * Criteria:
     * - Urgent fill enabled
     * - Within geographic radius
     * - Has workers with required skills
     * - Fill rate above threshold (75%)
     *
     * @param Shift $shift
     * @return \Illuminate\Support\Collection
     */
    protected function findQualifyingAgencies(Shift $shift)
    {
        $requiredSkills = $shift->required_skills ?? [];

        // Base query for agencies with urgent fill enabled
        $agencies = User::whereHas('agencyProfile', function ($query) {
                $query->where('urgent_fill_enabled', true)
                    ->where('fill_rate', '>=', self::MIN_AGENCY_FILL_RATE);
            })
            ->with('agencyProfile')
            ->get();

        // Filter by geographic proximity (if shift has location)
        if ($shift->location_lat && $shift->location_lng) {
            $agencies = $agencies->filter(function ($agency) use ($shift) {
                return $this->isWithinRadius(
                    $shift->location_lat,
                    $shift->location_lng,
                    $agency->agencyProfile->location_lat ?? null,
                    $agency->agencyProfile->location_lng ?? null,
                    self::DEFAULT_RADIUS
                );
            });
        }

        // Filter by skill availability (if shift requires specific skills)
        if (!empty($requiredSkills)) {
            $agencies = $agencies->filter(function ($agency) use ($requiredSkills) {
                return $this->hasWorkersWithSkills($agency->id, $requiredSkills);
            });
        }

        // Sort by fill rate (best performers first)
        $agencies = $agencies->sortByDesc(function ($agency) {
            return $agency->agencyProfile->fill_rate;
        });

        return $agencies;
    }

    /**
     * Check if coordinates are within radius.
     *
     * @param float $lat1
     * @param float $lng1
     * @param float|null $lat2
     * @param float|null $lng2
     * @param float $radius In miles
     * @return bool
     */
    protected function isWithinRadius($lat1, $lng1, $lat2, $lng2, $radius)
    {
        if (!$lat2 || !$lng2) {
            return false;
        }

        // Haversine formula
        $earthRadius = 3959; // miles

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }

    /**
     * Check if agency has workers with required skills.
     *
     * @param int $agencyId
     * @param array $requiredSkills
     * @return bool
     */
    protected function hasWorkersWithSkills($agencyId, $requiredSkills)
    {
        // Get active workers for this agency
        $workerIds = AgencyWorker::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->pluck('worker_id');

        if ($workerIds->isEmpty()) {
            return false;
        }

        // Check if any worker has all required skills
        foreach ($workerIds as $workerId) {
            $workerSkills = DB::table('worker_skills')
                ->where('worker_id', $workerId)
                ->whereIn('skill_id', $requiredSkills)
                ->count();

            if ($workerSkills >= count($requiredSkills)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check SLA compliance for all active requests.
     *
     * @return array Summary of SLA checks
     */
    public function checkSLACompliance()
    {
        $requests = UrgentShiftRequest::active()->get();

        $summary = [
            'total_checked' => $requests->count(),
            'breached' => 0,
            'approaching_breach' => 0,
        ];

        foreach ($requests as $request) {
            $request->checkSLA();

            if ($request->sla_breached) {
                $summary['breached']++;
            } elseif ($request->sla_deadline && $request->sla_deadline->diffInMinutes(now()) <= 5) {
                $summary['approaching_breach']++;
            }
        }

        return $summary;
    }

    /**
     * Record agency acceptance of urgent shift.
     *
     * @param int $requestId
     * @param int $agencyId
     * @return bool Success status
     */
    public function recordAgencyAcceptance($requestId, $agencyId)
    {
        $request = UrgentShiftRequest::find($requestId);

        if (!$request) {
            return false;
        }

        $request->markAsAccepted($agencyId);

        Log::info("Agency accepted urgent shift", [
            'request_id' => $requestId,
            'agency_id' => $agencyId,
            'response_time_minutes' => $request->response_time_minutes,
        ]);

        return true;
    }
}
