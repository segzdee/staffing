<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\LiveMarketService;
use App\Services\DemoShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveMarketController extends Controller
{
    protected $liveMarketService;
    protected $demoShiftService;

    public function __construct(
        LiveMarketService $liveMarketService,
        DemoShiftService $demoShiftService
    ) {
        $this->liveMarketService = $liveMarketService;
        $this->demoShiftService = $demoShiftService;
    }

    /**
     * Get market shifts (API endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $worker = Auth::check() && Auth::user()->role === 'worker' ? Auth::user() : null;

        $filters = $request->only([
            'industry',
            'role_type',
            'city',
            'min_rate',
            'instant_claim',
            'surge_only',
        ]);

        $limit = $request->input('limit', 20);

        try {
            $shifts = $this->liveMarketService->getMarketShifts($worker, $filters, $limit);
            $statistics = $this->liveMarketService->getStatistics();

            return response()->json([
                'success' => true,
                'shifts' => $shifts->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'is_demo' => $shift->is_demo ?? false,
                        'title' => $shift->title,
                        'role_type' => $shift->role_type,
                        'industry' => $shift->industry,
                        'business_name' => $shift->demo_business_name ?? $shift->business?->name ?? 'Business',
                        'location_city' => $shift->location_city,
                        'location_state' => $shift->location_state,
                        'shift_date' => $shift->shift_date?->format('Y-m-d'),
                        'start_time' => is_string($shift->start_time) ? $shift->start_time : $shift->start_time?->format('H:i'),
                        'end_time' => is_string($shift->end_time) ? $shift->end_time : $shift->end_time?->format('H:i'),
                        'duration_hours' => $shift->duration_hours,
                        'base_rate' => $shift->base_rate,
                        'final_rate' => $shift->final_rate,
                        'effective_rate' => $shift->effective_rate ?? $shift->final_rate,
                        'surge_multiplier' => $shift->surge_multiplier,
                        'required_workers' => $shift->required_workers,
                        'filled_workers' => $shift->filled_workers,
                        'spots_remaining' => $shift->spots_remaining ?? ($shift->required_workers - $shift->filled_workers),
                        'fill_percentage' => $shift->fill_percentage ?? round(($shift->filled_workers / max(1, $shift->required_workers)) * 100),
                        'instant_claim_enabled' => $shift->instant_claim_enabled ?? false,
                        'market_posted_at' => $shift->market_posted_at?->diffForHumans(),
                        'market_views' => $shift->market_views ?? 0,
                        'market_applications' => $shift->market_applications ?? 0,
                        'match_score' => $shift->match_score ?? null,
                        'match_reasons' => $shift->match_reasons ?? [],
                        'is_new' => $shift->market_posted_at?->gt(now()->subHours(2)) ?? false,
                        'is_urgent' => ($shift->surge_multiplier ?? 1.0) > 1.3,
                    ];
                }),
                'statistics' => $statistics,
                'has_demo_shifts' => $shifts->contains('is_demo', true),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load market shifts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply to a shift.
     *
     * @param Request $request
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(Request $request, Shift $shift)
    {
        if (!Auth::check() || Auth::user()->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can apply to shifts',
            ], 403);
        }

        $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $application = $this->liveMarketService->applyToShift(
                $shift,
                Auth::user(),
                $request->input('message')
            );

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application' => $application,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Instant claim a shift.
     *
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function instantClaim(Shift $shift)
    {
        if (!Auth::check() || Auth::user()->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can claim shifts',
            ], 403);
        }

        try {
            $assignment = $this->liveMarketService->instantClaim($shift, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Shift claimed successfully!',
                'assignment' => $assignment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Agency assigns a worker to their client's shift.
     *
     * @param Request $request
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function agencyAssign(Request $request, Shift $shift)
    {
        if (!Auth::check() || Auth::user()->role !== 'agency') {
            return response()->json([
                'success' => false,
                'message' => 'Only agencies can assign workers',
            ], 403);
        }

        $request->validate([
            'worker_id' => 'required|exists:users,id',
        ]);

        try {
            $worker = \App\Models\User::findOrFail($request->input('worker_id'));

            $assignment = $this->liveMarketService->agencyAssign($shift, Auth::user(), $worker);

            return response()->json([
                'success' => true,
                'message' => 'Worker assigned successfully',
                'assignment' => $assignment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Simulate demo activity for real-time feed.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulateActivity()
    {
        try {
            $activities = $this->demoShiftService->simulateActivity();

            return response()->json([
                'success' => true,
                'activities' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate activity',
            ], 500);
        }
    }
}
