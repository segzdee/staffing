<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandMetric;
use App\Models\SurgeEvent;
use App\Services\DemandTrackingService;
use App\Services\SurgeEventService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * SL-008: Admin Surge Pricing Management Controller
 *
 * Handles admin operations for managing surge events, viewing demand metrics,
 * and configuring surge pricing parameters.
 */
class SurgeController extends Controller
{
    public function __construct(
        protected SurgeEventService $surgeEventService,
        protected DemandTrackingService $demandTrackingService
    ) {}

    /**
     * Display the surge pricing dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return view('admin.unauthorized');
        }

        // Get current and upcoming surge events
        $currentEvents = SurgeEvent::query()
            ->active()
            ->current()
            ->orderByDesc('surge_multiplier')
            ->get();

        $upcomingEvents = SurgeEvent::query()
            ->active()
            ->upcoming(14)
            ->get();

        // Get event statistics
        $eventStats = $this->surgeEventService->getEventStatistics();

        // Get demand heatmap for last 7 days
        $demandHeatmap = DemandMetric::getDemandHeatmap(7);

        // Get regional demand analysis
        $regions = DemandMetric::query()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region');

        $regionalDemand = [];
        foreach ($regions as $region) {
            $regionalDemand[$region] = DemandMetric::getDemandTrend($region, 7);
        }

        // Get skill shortage analysis
        $skillAnalysis = DemandMetric::getSkillDemandAnalysis(null, 7);

        return view('admin.surge.index', [
            'currentEvents' => $currentEvents,
            'upcomingEvents' => $upcomingEvents,
            'eventStats' => $eventStats,
            'demandHeatmap' => $demandHeatmap,
            'regionalDemand' => $regionalDemand,
            'skillAnalysis' => $skillAnalysis,
            'eventTypes' => SurgeEvent::getEventTypes(),
        ]);
    }

    /**
     * Display the surge events management page.
     */
    public function events(Request $request)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return view('admin.unauthorized');
        }

        $query = SurgeEvent::query()
            ->with('creator')
            ->orderByDesc('start_date');

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active()->current();
            } elseif ($request->status === 'upcoming') {
                $query->active()->upcoming();
            } elseif ($request->status === 'ended') {
                $query->where('end_date', '<', now()->toDateString());
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $events = $query->paginate(20);

        // Get unique regions for filter dropdown
        $regions = SurgeEvent::query()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region');

        return view('admin.surge.events', [
            'events' => $events,
            'eventTypes' => SurgeEvent::getEventTypes(),
            'regions' => $regions,
            'filters' => $request->only(['status', 'event_type', 'region', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new surge event.
     */
    public function create()
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return view('admin.unauthorized');
        }

        return view('admin.surge.create', [
            'eventTypes' => SurgeEvent::getEventTypes(),
        ]);
    }

    /**
     * Store a newly created surge event.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'region' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'surge_multiplier' => 'required|numeric|min:1|max:5',
            'event_type' => ['required', Rule::in(array_keys(SurgeEvent::getEventTypes()))],
            'expected_demand_increase' => 'nullable|integer|min:0|max:200',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = $user->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $event = $this->surgeEventService->createEvent($validated);

        Log::channel('admin')->info('Surge event created', [
            'admin_id' => $user->id,
            'event_id' => $event->id,
            'event_name' => $event->name,
        ]);

        return redirect()
            ->route('admin.surge.events')
            ->with('success', "Surge event '{$event->name}' created successfully.");
    }

    /**
     * Show the form for editing a surge event.
     */
    public function edit(SurgeEvent $event)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return view('admin.unauthorized');
        }

        return view('admin.surge.edit', [
            'event' => $event,
            'eventTypes' => SurgeEvent::getEventTypes(),
        ]);
    }

    /**
     * Update the specified surge event.
     */
    public function update(Request $request, SurgeEvent $event)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'region' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'surge_multiplier' => 'required|numeric|min:1|max:5',
            'event_type' => ['required', Rule::in(array_keys(SurgeEvent::getEventTypes()))],
            'expected_demand_increase' => 'nullable|integer|min:0|max:200',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->surgeEventService->updateEvent($event, $validated);

        Log::channel('admin')->info('Surge event updated', [
            'admin_id' => $user->id,
            'event_id' => $event->id,
            'event_name' => $event->name,
        ]);

        return redirect()
            ->route('admin.surge.events')
            ->with('success', "Surge event '{$event->name}' updated successfully.");
    }

    /**
     * Delete the specified surge event.
     */
    public function destroy(SurgeEvent $event)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $eventName = $event->name;
        $event->delete();

        Log::channel('admin')->info('Surge event deleted', [
            'admin_id' => $user->id,
            'event_name' => $eventName,
        ]);

        return redirect()
            ->route('admin.surge.events')
            ->with('success', "Surge event '{$eventName}' deleted successfully.");
    }

    /**
     * Toggle the active status of a surge event.
     */
    public function toggleActive(SurgeEvent $event): JsonResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $event->update(['is_active' => ! $event->is_active]);

        Log::channel('admin')->info('Surge event status toggled', [
            'admin_id' => $user->id,
            'event_id' => $event->id,
            'is_active' => $event->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $event->is_active,
            'message' => $event->is_active ? 'Event activated' : 'Event deactivated',
        ]);
    }

    /**
     * Display demand metrics dashboard.
     */
    public function demand(Request $request)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return view('admin.unauthorized');
        }

        $region = $request->get('region');
        $days = (int) $request->get('days', 14);

        // Get demand trend
        $demandTrend = DemandMetric::getDemandTrend($region, $days);

        // Get demand heatmap
        $heatmap = $this->demandTrackingService->getDemandHeatmap($region ?? '', $days);

        // Get skill analysis
        $skillAnalysis = DemandMetric::getSkillDemandAnalysis($region, $days);

        // Get surge forecast
        $forecast = $this->demandTrackingService->getSurgeForecast($region, 7);

        // Get available regions
        $regions = DemandMetric::query()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region');

        return view('admin.surge.demand', [
            'demandTrend' => $demandTrend,
            'heatmap' => $heatmap,
            'skillAnalysis' => $skillAnalysis,
            'forecast' => $forecast,
            'regions' => $regions,
            'selectedRegion' => $region,
            'days' => $days,
        ]);
    }

    /**
     * Import events from external APIs.
     */
    public function importEvents(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $region = $request->get('region');

        try {
            $imported = $this->surgeEventService->importEventsFromAPI($region);

            Log::channel('admin')->info('Surge events imported from API', [
                'admin_id' => $user->id,
                'region' => $region,
                'imported_count' => $imported,
            ]);

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'message' => "Successfully imported {$imported} events",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to import surge events', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to import events: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recalculate demand metrics for a date range.
     */
    public function recalculateMetrics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_surge_pricing')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $daysProcessed = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $this->demandTrackingService->calculateDailyMetrics($currentDate);
            $currentDate->addDay();
            $daysProcessed++;
        }

        Log::channel('admin')->info('Demand metrics recalculated', [
            'admin_id' => $user->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_processed' => $daysProcessed,
        ]);

        return response()->json([
            'success' => true,
            'days_processed' => $daysProcessed,
            'message' => "Recalculated metrics for {$daysProcessed} days",
        ]);
    }

    /**
     * Get surge preview for a specific date and region (API endpoint).
     */
    public function getSurgePreview(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'region' => 'nullable|string',
            'skill' => 'nullable|string',
            'base_rate' => 'required|numeric|min:0',
            'is_night_shift' => 'boolean',
            'is_weekend' => 'boolean',
            'is_urgent' => 'boolean',
        ]);

        $pricingService = app(\App\Services\ShiftPricingService::class);

        $preview = $pricingService->getSurgePreview(
            Carbon::parse($request->date),
            $request->region,
            $request->skill,
            (float) $request->base_rate,
            $request->boolean('is_night_shift'),
            $request->boolean('is_weekend'),
            $request->boolean('is_urgent')
        );

        return response()->json($preview);
    }

    /**
     * Get demand metrics data for charts (API endpoint).
     */
    public function getDemandData(Request $request): JsonResponse
    {
        $request->validate([
            'region' => 'nullable|string',
            'days' => 'integer|min:1|max:90',
        ]);

        $region = $request->get('region');
        $days = (int) $request->get('days', 14);

        $trend = DemandMetric::getDemandTrend($region, $days);
        $heatmap = DemandMetric::getDemandHeatmap($days);

        return response()->json([
            'trend' => $trend,
            'heatmap' => $heatmap,
        ]);
    }

    /**
     * Get active events for calendar view (API endpoint).
     */
    public function getEventsCalendar(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'region' => 'nullable|string',
        ]);

        $query = SurgeEvent::query()
            ->where('start_date', '<=', $request->end)
            ->where('end_date', '>=', $request->start);

        if ($request->filled('region')) {
            $query->where(function ($q) use ($request) {
                $q->where('region', $request->region)
                    ->orWhereNull('region');
            });
        }

        $events = $query->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->start_date->toDateString(),
                'end' => $event->end_date->addDay()->toDateString(), // FullCalendar end is exclusive
                'color' => $this->getEventColor($event),
                'extendedProps' => [
                    'type' => $event->event_type,
                    'region' => $event->region,
                    'multiplier' => $event->surge_multiplier,
                    'is_active' => $event->is_active,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Get color for event based on type.
     */
    protected function getEventColor(SurgeEvent $event): string
    {
        if (! $event->is_active) {
            return '#9CA3AF'; // Gray for inactive
        }

        return match ($event->event_type) {
            SurgeEvent::TYPE_CONCERT => '#8B5CF6',    // Purple
            SurgeEvent::TYPE_SPORTS => '#10B981',     // Green
            SurgeEvent::TYPE_CONFERENCE => '#3B82F6', // Blue
            SurgeEvent::TYPE_FESTIVAL => '#F59E0B',   // Orange
            SurgeEvent::TYPE_HOLIDAY => '#EF4444',    // Red
            SurgeEvent::TYPE_WEATHER => '#6366F1',    // Indigo
            default => '#6B7280',                      // Gray
        };
    }
}
