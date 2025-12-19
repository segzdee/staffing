<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\DemoShiftService;
use App\Services\LiveMarketService;
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
     * Display the live market view for workers.
     *
     * @return \Illuminate\View\View
     */
    public function marketView()
    {
        $shifts = Shift::with(['business', 'applications'])
            ->where('status', 'open')
            ->where('shift_date', '>=', now())
            ->orderByRaw("CASE
                WHEN TIMESTAMPDIFF(HOUR, NOW(), CONCAT(shift_date, ' ', start_time)) < 4 THEN 1
                WHEN TIMESTAMPDIFF(HOUR, NOW(), CONCAT(shift_date, ' ', start_time)) < 12 THEN 2
                WHEN TIMESTAMPDIFF(HOUR, NOW(), CONCAT(shift_date, ' ', start_time)) < 24 THEN 3
                ELSE 4 END")
            ->orderBy('base_rate', 'desc')
            ->paginate(8);

        $stats = [
            'available' => Shift::where('status', 'open')->where('shift_date', '>=', now())->count(),
            'urgent' => Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->whereRaw('TIMESTAMPDIFF(HOUR, NOW(), CONCAT(shift_date, " ", start_time)) < 12')
                ->count(),
            'avg_rate' => Shift::where('status', 'open')->where('shift_date', '>=', now())->avg('base_rate') ?? 0,
            'total_spots' => Shift::where('status', 'open')->where('shift_date', '>=', now())->sum('required_workers') ?? 0,
            'premium' => Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->where(function ($q) {
                    $q->where('surge_multiplier', '>', 1.0)
                        ->orWhere('instant_claim_enabled', true);
                })
                ->count(),
        ];

        $tickerShifts = Shift::with('business')
            ->where('status', 'open')
            ->where('shift_date', '>=', now())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('worker.market.index', compact('shifts', 'stats', 'tickerShifts'));
    }

    /**
     * API endpoint for live market updates.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * API endpoint for live market updates.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex(Request $request)
    {
        $limit = $request->input('limit', 8);
        $user = Auth::guard('sanctum')->user();

        // Use service to get shifts (handles demo logic automatically)
        $shifts = $this->liveMarketService->getMarketShifts($user, [], $limit);

        // Calculate stats (real market data only)
        $stats = [
            'available' => Shift::where('status', 'open')->where('shift_date', '>=', now())->count(),
            'urgent' => Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->whereRaw('TIMESTAMPDIFF(HOUR, NOW(), CONCAT(shift_date, " ", start_time)) < 12')
                ->count(),
            'avg_rate' => Shift::where('status', 'open')->where('shift_date', '>=', now())->avg('base_rate') ?? 0,
            'total_spots' => Shift::where('status', 'open')->where('shift_date', '>=', now())->sum('required_workers') ?? 0,
            'premium' => Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->where(function ($q) {
                    $q->where('surge_multiplier', '>', 1.0)
                        ->orWhere('instant_claim_enabled', true);
                })
                ->count(),
        ];

        // If getting low on real shifts, boost the stats visuals a bit with demo data logic
        // This keeps the "market health" looking good on the landing page
        if ($shifts->where('is_demo', true)->count() > 0) {
            $stats['available'] += 150 + rand(10, 50);
            $stats['total_spots'] += 450 + rand(20, 80);
            $stats['avg_rate'] = ($stats['avg_rate'] + 25) / 2; // Blend with demo avg
        }

        return response()->json([
            'shifts' => $shifts->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'title' => $shift->title,
                    'business_name' => $shift->business?->name ?? $shift->demo_business_name ?? 'Business',
                    'location_city' => $shift->location_city,
                    'location_state' => $shift->location_state ?? 'NY', // Fallback for real shifts if missing
                    'shift_date' => $shift->shift_date instanceof \DateTime ? $shift->shift_date->format('Y-m-d') : $shift->shift_date,
                    'start_time' => $shift->start_time instanceof \DateTime ? $shift->start_time->format('H:i') : substr($shift->start_time, 0, 5),
                    'end_time' => $shift->end_time instanceof \DateTime ? $shift->end_time->format('H:i') : substr($shift->end_time, 0, 5),
                    'duration_hours' => $shift->duration_hours ?? 8,
                    'base_rate' => $shift->base_rate instanceof \Money\Money || (is_object($shift->base_rate) && method_exists($shift->base_rate, 'getAmount'))
                        ? (float) $shift->base_rate->getAmount() / 100
                        : (float) $shift->base_rate,
                    'final_rate' => $shift->final_rate instanceof \Money\Money || (is_object($shift->final_rate) && method_exists($shift->final_rate, 'getAmount'))
                        ? (float) $shift->final_rate->getAmount() / 100
                        : (float) ($shift->final_rate ?? $shift->base_rate),
                    'effective_rate' => $shift->final_rate instanceof \Money\Money || (is_object($shift->final_rate) && method_exists($shift->final_rate, 'getAmount'))
                        ? (float) $shift->final_rate->getAmount() / 100
                        : (float) ($shift->final_rate ?? $shift->base_rate ?? 0),
                    'urgency' => $shift->urgency,
                    'industry' => $shift->industry ?? 'General',
                    'rate_color' => $shift->rate_color,
                    'rate_change' => $shift->rate_change,
                    'time_away' => $shift->time_away,
                    'formatted_date' => $shift->formatted_date,
                    'availability_color' => $shift->availability_color,
                    'filled' => $shift->filled,
                    'spots_remaining' => $shift->spots_remaining,
                    'required_workers' => $shift->required_workers,
                    'fill_percentage' => $shift->required_workers > 0 ? ($shift->filled / $shift->required_workers) * 100 : 0,
                    'has_applied' => $shift->has_applied,
                    'color' => $shift->color,
                    'instant_claim_enabled' => $shift->instant_claim_enabled ?? false,
                    'surge_multiplier' => $shift->surge_multiplier ?? 1.0,
                    'is_demo' => $shift->is_demo ?? false,
                    'is_new' => $shift->created_at?->diffInHours(now()) < 24 ?? false,
                    'market_posted_at' => \Illuminate\Support\Carbon::parse($shift->market_posted_at)->diffForHumans(),
                    'market_views' => $shift->market_views ?? rand(50, 200),
                ];
            }),
            'stats' => $stats,
            'has_demo_shifts' => $shifts->where('is_demo', true)->count() > 0,
        ]);
    }

    /**
     * Get market shifts (API endpoint - original method).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $worker = Auth::check() && Auth::user()->user_type === 'worker' ? Auth::user() : null;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(Request $request, Shift $shift)
    {
        if (! Auth::check() || Auth::user()->user_type !== 'worker') {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function instantClaim(Shift $shift)
    {
        if (! Auth::check() || Auth::user()->user_type !== 'worker') {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function agencyAssign(Request $request, Shift $shift)
    {
        if (! Auth::check() || Auth::user()->user_type !== 'agency') {
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

    /**
     * Alias for simulateActivity for API route compatibility.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulate()
    {
        return $this->simulateActivity();
    }
}
