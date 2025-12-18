<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyProfile;
use App\Models\AgencyTier;
use App\Models\AgencyTierHistory;
use App\Models\User;
use App\Services\AgencyTierService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * AgencyTierController
 *
 * Admin controller for managing the agency tier system.
 *
 * TASK: AGY-001 Agency Tier System
 *
 * Features:
 * - View and manage tier definitions
 * - View tier distribution across agencies
 * - Manual tier adjustments
 * - View tier history
 */
class AgencyTierController extends Controller
{
    public function __construct(protected AgencyTierService $tierService) {}

    /**
     * Display the agency tier management dashboard.
     */
    public function index()
    {
        $tiers = AgencyTier::withCount('agencyProfiles')
            ->orderBy('level')
            ->get();

        $distribution = $this->tierService->getTierDistribution();
        $totalAgencies = AgencyProfile::count();
        $agenciesWithTier = AgencyProfile::whereNotNull('agency_tier_id')->count();
        $agenciesWithoutTier = $totalAgencies - $agenciesWithTier;

        // Recent tier changes
        $recentChanges = AgencyTierHistory::with(['agency', 'fromTier', 'toTier', 'processedByUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.agency-tiers.index', compact(
            'tiers',
            'distribution',
            'totalAgencies',
            'agenciesWithTier',
            'agenciesWithoutTier',
            'recentChanges'
        ));
    }

    /**
     * Show the form for creating a new tier.
     */
    public function create()
    {
        return view('admin.agency-tiers.create');
    }

    /**
     * Store a newly created tier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:agency_tiers,slug'],
            'level' => ['required', 'integer', 'min:1', 'max:10', 'unique:agency_tiers,level'],
            'min_monthly_revenue' => ['required', 'numeric', 'min:0'],
            'min_active_workers' => ['required', 'integer', 'min:0'],
            'min_fill_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'priority_booking_hours' => ['required', 'integer', 'min:0'],
            'dedicated_support' => ['boolean'],
            'custom_branding' => ['boolean'],
            'api_access' => ['boolean'],
            'additional_benefits' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['dedicated_support'] = $request->boolean('dedicated_support');
        $validated['custom_branding'] = $request->boolean('custom_branding');
        $validated['api_access'] = $request->boolean('api_access');
        $validated['is_active'] = $request->boolean('is_active', true);

        $tier = AgencyTier::create($validated);

        return redirect()
            ->route('admin.agency-tiers.index')
            ->with('success', "Tier '{$tier->name}' created successfully.");
    }

    /**
     * Display the specified tier.
     */
    public function show(AgencyTier $agencyTier)
    {
        $agencyTier->loadCount('agencyProfiles');

        $agencies = AgencyProfile::where('agency_tier_id', $agencyTier->id)
            ->with(['user', 'tier'])
            ->paginate(20);

        $tierHistory = AgencyTierHistory::where('to_tier_id', $agencyTier->id)
            ->orWhere('from_tier_id', $agencyTier->id)
            ->with(['agency', 'fromTier', 'toTier'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.agency-tiers.show', compact('agencyTier', 'agencies', 'tierHistory'));
    }

    /**
     * Show the form for editing the specified tier.
     */
    public function edit(AgencyTier $agencyTier)
    {
        return view('admin.agency-tiers.edit', compact('agencyTier'));
    }

    /**
     * Update the specified tier.
     */
    public function update(Request $request, AgencyTier $agencyTier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('agency_tiers')->ignore($agencyTier->id)],
            'level' => ['required', 'integer', 'min:1', 'max:10', Rule::unique('agency_tiers')->ignore($agencyTier->id)],
            'min_monthly_revenue' => ['required', 'numeric', 'min:0'],
            'min_active_workers' => ['required', 'integer', 'min:0'],
            'min_fill_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'priority_booking_hours' => ['required', 'integer', 'min:0'],
            'dedicated_support' => ['boolean'],
            'custom_branding' => ['boolean'],
            'api_access' => ['boolean'],
            'additional_benefits' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['dedicated_support'] = $request->boolean('dedicated_support');
        $validated['custom_branding'] = $request->boolean('custom_branding');
        $validated['api_access'] = $request->boolean('api_access');
        $validated['is_active'] = $request->boolean('is_active', true);

        $agencyTier->update($validated);

        return redirect()
            ->route('admin.agency-tiers.show', $agencyTier)
            ->with('success', "Tier '{$agencyTier->name}' updated successfully.");
    }

    /**
     * Remove the specified tier.
     */
    public function destroy(AgencyTier $agencyTier)
    {
        // Check if any agencies are using this tier
        if ($agencyTier->agencyProfiles()->exists()) {
            return back()->with('error', 'Cannot delete tier that has agencies assigned to it.');
        }

        $tierName = $agencyTier->name;
        $agencyTier->delete();

        return redirect()
            ->route('admin.agency-tiers.index')
            ->with('success', "Tier '{$tierName}' deleted successfully.");
    }

    /**
     * View all agencies with tier information.
     */
    public function agencies(Request $request)
    {
        $query = AgencyProfile::with(['user', 'tier'])
            ->withCount(['agencyWorkers as active_workers_count' => function ($q) {
                $q->where('status', 'active');
            }]);

        // Filter by tier
        if ($request->filled('tier')) {
            if ($request->tier === 'none') {
                $query->whereNull('agency_tier_id');
            } else {
                $query->where('agency_tier_id', $request->tier);
            }
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('agency_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $agencies = $query->orderBy('agency_name')->paginate(25);
        $tiers = AgencyTier::orderBy('level')->get();

        return view('admin.agency-tiers.agencies', compact('agencies', 'tiers'));
    }

    /**
     * Show manual tier adjustment form.
     */
    public function adjustForm(AgencyProfile $agencyProfile)
    {
        $agencyProfile->load(['user', 'tier', 'tierHistory.fromTier', 'tierHistory.toTier']);
        $tiers = AgencyTier::active()->orderBy('level')->get();
        $metrics = $this->tierService->calculateAgencyMetrics($agencyProfile->user);
        $eligibleTier = $this->tierService->determineEligibleTier($agencyProfile->user);

        return view('admin.agency-tiers.adjust', compact(
            'agencyProfile',
            'tiers',
            'metrics',
            'eligibleTier'
        ));
    }

    /**
     * Process manual tier adjustment.
     */
    public function adjust(Request $request, AgencyProfile $agencyProfile)
    {
        $validated = $request->validate([
            'tier_id' => ['required', 'exists:agency_tiers,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $newTier = AgencyTier::findOrFail($validated['tier_id']);

        $this->tierService->manualTierAdjustment(
            $agencyProfile->user,
            $newTier,
            auth()->id(),
            $validated['reason']
        );

        return redirect()
            ->route('admin.agency-tiers.agencies')
            ->with('success', "Agency '{$agencyProfile->agency_name}' tier adjusted to {$newTier->name}.");
    }

    /**
     * View tier history.
     */
    public function history(Request $request)
    {
        $query = AgencyTierHistory::with(['agency', 'fromTier', 'toTier', 'processedByUser']);

        // Filter by change type
        if ($request->filled('type')) {
            $query->where('change_type', $request->type);
        }

        // Filter by tier
        if ($request->filled('tier')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_tier_id', $request->tier)
                    ->orWhere('to_tier_id', $request->tier);
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->orderBy('created_at', 'desc')->paginate(50);
        $tiers = AgencyTier::orderBy('level')->get();

        return view('admin.agency-tiers.history', compact('history', 'tiers'));
    }

    /**
     * Run tier review for all agencies.
     */
    public function runReview(Request $request)
    {
        $dryRun = $request->boolean('dry_run');

        if ($dryRun) {
            // Return preview of what would happen
            $agencies = User::where('user_type', 'agency')
                ->whereHas('agencyProfile')
                ->with('agencyProfile.tier')
                ->get();

            $preview = $agencies->map(function ($agency) {
                $currentTier = $agency->agencyProfile?->tier;
                $eligibleTier = $this->tierService->determineEligibleTier($agency);
                $metrics = $this->tierService->calculateAgencyMetrics($agency);

                $action = 'no_change';
                if (! $currentTier && $eligibleTier) {
                    $action = 'initial';
                } elseif ($eligibleTier && $currentTier) {
                    if ($eligibleTier->level > $currentTier->level) {
                        $action = 'upgrade';
                    } elseif ($eligibleTier->level < $currentTier->level) {
                        $action = 'downgrade';
                    }
                }

                return [
                    'agency_id' => $agency->id,
                    'agency_name' => $agency->agencyProfile?->agency_name ?? $agency->name,
                    'current_tier' => $currentTier?->name,
                    'eligible_tier' => $eligibleTier?->name,
                    'action' => $action,
                    'metrics' => $metrics,
                ];
            });

            return response()->json([
                'preview' => $preview,
                'summary' => [
                    'total' => $preview->count(),
                    'upgrades' => $preview->where('action', 'upgrade')->count(),
                    'downgrades' => $preview->where('action', 'downgrade')->count(),
                    'initial' => $preview->where('action', 'initial')->count(),
                    'no_change' => $preview->where('action', 'no_change')->count(),
                ],
            ]);
        }

        // Execute actual review
        $result = $this->tierService->processMonthlyTierReview();

        return redirect()
            ->route('admin.agency-tiers.index')
            ->with('success', sprintf(
                'Tier review completed: %d reviewed, %d upgrades, %d downgrades, %d no change.',
                $result['total_reviewed'],
                $result['upgrades'],
                $result['downgrades'],
                $result['no_change']
            ));
    }

    /**
     * Assign initial tiers to agencies without one.
     */
    public function assignInitialTiers()
    {
        $agencies = User::where('user_type', 'agency')
            ->whereHas('agencyProfile', function ($q) {
                $q->whereNull('agency_tier_id');
            })
            ->get();

        $assigned = 0;

        foreach ($agencies as $agency) {
            $history = $this->tierService->assignInitialTier($agency);
            if ($history) {
                $assigned++;
            }
        }

        return redirect()
            ->route('admin.agency-tiers.index')
            ->with('success', "Initial tiers assigned to {$assigned} agencies.");
    }
}
