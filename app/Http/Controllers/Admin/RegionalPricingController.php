<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceAdjustment;
use App\Models\RegionalPricing;
use App\Services\RegionalPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * GLO-009: Regional Pricing System - Admin Controller
 *
 * Handles all admin operations for managing regional pricing configurations.
 */
class RegionalPricingController extends Controller
{
    public function __construct(
        protected RegionalPricingService $pricingService
    ) {}

    /**
     * Display the regional pricing dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        // Get filter parameters
        $countryCode = $request->get('country');
        $currency = $request->get('currency');
        $status = $request->get('status');

        // Build query
        $query = RegionalPricing::query()
            ->with('activeAdjustments')
            ->orderBy('country_name')
            ->orderBy('region_name');

        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        if ($currency) {
            $query->where('currency_code', $currency);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $regions = $query->paginate(20);

        // Get filter options
        $countries = RegionalPricing::select('country_code', 'country_name')
            ->distinct()
            ->orderBy('country_name')
            ->get();

        $currencies = RegionalPricing::select('currency_code')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code');

        // Get analytics
        $analytics = $this->pricingService->getRegionalAnalytics();

        return view('admin.regional-pricing.index', [
            'regions' => $regions,
            'countries' => $countries,
            'currencies' => $currencies,
            'analytics' => $analytics,
            'filters' => [
                'country' => $countryCode,
                'currency' => $currency,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new regional pricing.
     */
    public function create(): View
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        return view('admin.regional-pricing.create', [
            'tierAdjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
        ]);
    }

    /**
     * Store a newly created regional pricing.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'country_code' => 'required|string|size:2|uppercase',
            'region_code' => 'nullable|string|max:10',
            'currency_code' => 'required|string|size:3|uppercase',
            'ppp_factor' => 'required|numeric|min:0.01|max:10',
            'min_hourly_rate' => 'required|numeric|min:0',
            'max_hourly_rate' => 'required|numeric|gt:min_hourly_rate',
            'platform_fee_rate' => 'required|numeric|min:0|max:50',
            'worker_fee_rate' => 'required|numeric|min:0|max:50',
            'country_name' => 'nullable|string|max:100',
            'region_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate
        $exists = RegionalPricing::where('country_code', strtoupper($validated['country_code']))
            ->where('region_code', $validated['region_code'] ?? null)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['country_code' => 'Regional pricing already exists for this location.']);
        }

        try {
            $regional = $this->pricingService->upsertRegionalPricing($validated);

            Log::channel('admin')->info('Regional pricing created', [
                'admin_id' => $user->id,
                'regional_id' => $regional->id,
                'country' => $regional->country_code,
            ]);

            return redirect()->route('admin.regional-pricing.index')
                ->with('success', "Regional pricing for {$regional->display_name} created successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to create regional pricing', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create regional pricing: '.$e->getMessage());
        }
    }

    /**
     * Display the specified regional pricing.
     */
    public function show(RegionalPricing $regionalPricing): View
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        $regionalPricing->load(['priceAdjustments' => function ($query) {
            $query->orderBy('adjustment_type')->orderBy('valid_from', 'desc');
        }]);

        return view('admin.regional-pricing.show', [
            'regional' => $regionalPricing,
        ]);
    }

    /**
     * Show the form for editing the specified regional pricing.
     */
    public function edit(RegionalPricing $regionalPricing): View
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        return view('admin.regional-pricing.edit', [
            'regional' => $regionalPricing,
            'tierAdjustments' => $regionalPricing->tier_adjustments ?? RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
        ]);
    }

    /**
     * Update the specified regional pricing.
     */
    public function update(Request $request, RegionalPricing $regionalPricing): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'currency_code' => 'required|string|size:3|uppercase',
            'ppp_factor' => 'required|numeric|min:0.01|max:10',
            'min_hourly_rate' => 'required|numeric|min:0',
            'max_hourly_rate' => 'required|numeric|gt:min_hourly_rate',
            'platform_fee_rate' => 'required|numeric|min:0|max:50',
            'worker_fee_rate' => 'required|numeric|min:0|max:50',
            'country_name' => 'nullable|string|max:100',
            'region_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'tier_adjustments' => 'nullable|array',
        ]);

        try {
            $regionalPricing->update($validated);
            $this->pricingService->clearCache();

            Log::channel('admin')->info('Regional pricing updated', [
                'admin_id' => $user->id,
                'regional_id' => $regionalPricing->id,
                'country' => $regionalPricing->country_code,
            ]);

            return redirect()->route('admin.regional-pricing.index')
                ->with('success', "Regional pricing for {$regionalPricing->display_name} updated successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to update regional pricing', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update regional pricing: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified regional pricing.
     */
    public function destroy(RegionalPricing $regionalPricing): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        try {
            $displayName = $regionalPricing->display_name;
            $this->pricingService->deleteRegionalPricing($regionalPricing->id);

            Log::channel('admin')->info('Regional pricing deleted', [
                'admin_id' => $user->id,
                'country' => $regionalPricing->country_code,
            ]);

            return redirect()->route('admin.regional-pricing.index')
                ->with('success', "Regional pricing for {$displayName} deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete regional pricing', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to delete regional pricing: '.$e->getMessage());
        }
    }

    /**
     * Toggle active status for a regional pricing.
     */
    public function toggleStatus(RegionalPricing $regionalPricing): JsonResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $regionalPricing->update(['is_active' => ! $regionalPricing->is_active]);
            $this->pricingService->clearCache();

            return response()->json([
                'success' => true,
                'is_active' => $regionalPricing->is_active,
                'message' => $regionalPricing->is_active ? 'Activated' : 'Deactivated',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update PPP factor for a specific region.
     */
    public function updatePPP(Request $request, RegionalPricing $regionalPricing): JsonResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validated = $request->validate([
            'ppp_factor' => 'required|numeric|min:0.01|max:10',
        ]);

        try {
            $regionalPricing->update(['ppp_factor' => $validated['ppp_factor']]);
            $this->pricingService->clearCache();

            return response()->json([
                'success' => true,
                'ppp_factor' => $regionalPricing->ppp_factor,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync PPP rates from World Bank.
     */
    public function syncPPP(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $results = $this->pricingService->syncPPPRates();

        Log::channel('admin')->info('PPP rates sync triggered', [
            'admin_id' => $user->id,
            'results' => $results,
        ]);

        if (! empty($results['errors'])) {
            return redirect()->back()
                ->with('warning', "PPP sync completed with errors. Updated: {$results['updated']}, Errors: ".count($results['errors']));
        }

        return redirect()->back()
            ->with('success', "PPP rates synced successfully. Updated: {$results['updated']} regions.");
    }

    /**
     * Display pricing analytics.
     */
    public function analytics(): View
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        $analytics = $this->pricingService->getRegionalAnalytics();

        // Additional detailed analytics
        $regionsByContinent = RegionalPricing::active()->get()
            ->groupBy(function ($region) {
                return $this->getContinent($region->country_code);
            })
            ->map(fn ($group) => $group->count());

        $feeDistribution = RegionalPricing::active()
            ->selectRaw('platform_fee_rate, COUNT(*) as count')
            ->groupBy('platform_fee_rate')
            ->orderBy('platform_fee_rate')
            ->get();

        return view('admin.regional-pricing.analytics', [
            'analytics' => $analytics,
            'regionsByContinent' => $regionsByContinent,
            'feeDistribution' => $feeDistribution,
        ]);
    }

    /**
     * Export regional pricing data.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $data = $this->pricingService->exportRegionalPricing();

        $filename = 'regional_pricing_'.now()->format('Y-m-d_His').'.json';

        Log::channel('admin')->info('Regional pricing exported', [
            'admin_id' => $user->id,
            'count' => count($data),
        ]);

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import regional pricing data.
     */
    public function import(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $request->validate([
            'import_file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('import_file')->path());
            $data = json_decode($content, true);

            if (! is_array($data)) {
                throw new \InvalidArgumentException('Invalid JSON format');
            }

            $results = $this->pricingService->bulkImport($data);

            Log::channel('admin')->info('Regional pricing imported', [
                'admin_id' => $user->id,
                'results' => $results,
            ]);

            if (! empty($results['errors'])) {
                return redirect()->back()
                    ->with('warning', "Import completed with errors. Created: {$results['created']}, Updated: {$results['updated']}, Errors: ".count($results['errors']));
            }

            return redirect()->route('admin.regional-pricing.index')
                ->with('success', "Import successful. Created: {$results['created']}, Updated: {$results['updated']}");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Manage price adjustments for a region.
     */
    public function adjustments(RegionalPricing $regionalPricing): View
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        $adjustments = $regionalPricing->priceAdjustments()
            ->orderBy('adjustment_type')
            ->orderBy('valid_from', 'desc')
            ->paginate(20);

        return view('admin.regional-pricing.adjustments', [
            'regional' => $regionalPricing,
            'adjustments' => $adjustments,
            'adjustmentTypes' => PriceAdjustment::getTypeOptions(),
        ]);
    }

    /**
     * Store a new price adjustment.
     */
    public function storeAdjustment(Request $request, RegionalPricing $regionalPricing): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'adjustment_type' => 'required|string|in:'.implode(',', array_keys(PriceAdjustment::ADJUSTMENT_TYPES)),
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'multiplier' => 'required|numeric|min:0.01|max:10',
            'fixed_adjustment' => 'required|numeric',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        try {
            $adjustment = $this->pricingService->createPriceAdjustment(array_merge(
                $validated,
                [
                    'regional_pricing_id' => $regionalPricing->id,
                    'created_by' => $user->id,
                ]
            ));

            Log::channel('admin')->info('Price adjustment created', [
                'admin_id' => $user->id,
                'adjustment_id' => $adjustment->id,
                'regional_id' => $regionalPricing->id,
            ]);

            return redirect()->route('admin.regional-pricing.adjustments', $regionalPricing)
                ->with('success', 'Price adjustment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create adjustment: '.$e->getMessage());
        }
    }

    /**
     * Update a price adjustment.
     */
    public function updateAdjustment(Request $request, PriceAdjustment $priceAdjustment): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'multiplier' => 'required|numeric|min:0.01|max:10',
            'fixed_adjustment' => 'required|numeric',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        try {
            $this->pricingService->updatePriceAdjustment($priceAdjustment->id, $validated);

            Log::channel('admin')->info('Price adjustment updated', [
                'admin_id' => $user->id,
                'adjustment_id' => $priceAdjustment->id,
            ]);

            return redirect()->route('admin.regional-pricing.adjustments', $priceAdjustment->regional_pricing_id)
                ->with('success', 'Price adjustment updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update adjustment: '.$e->getMessage());
        }
    }

    /**
     * Delete a price adjustment.
     */
    public function destroyAdjustment(PriceAdjustment $priceAdjustment): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        try {
            $regionalId = $priceAdjustment->regional_pricing_id;
            $this->pricingService->deletePriceAdjustment($priceAdjustment->id);

            Log::channel('admin')->info('Price adjustment deleted', [
                'admin_id' => $user->id,
                'adjustment_id' => $priceAdjustment->id,
            ]);

            return redirect()->route('admin.regional-pricing.adjustments', $regionalId)
                ->with('success', 'Price adjustment deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete adjustment: '.$e->getMessage());
        }
    }

    /**
     * Calculate and preview pricing for a specific scenario.
     */
    public function previewPricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'region_code' => 'nullable|string|max:10',
            'hourly_rate' => 'required|numeric|min:0',
            'hours' => 'required|numeric|min:0.5',
            'business_tier' => 'nullable|string',
            'worker_tier' => 'nullable|string',
        ]);

        $fees = $this->pricingService->calculateShiftFees(
            $validated['hourly_rate'],
            $validated['hours'],
            $validated['country_code'],
            $validated['region_code'] ?? null,
            $validated['business_tier'] ?? null,
            $validated['worker_tier'] ?? null
        );

        return response()->json($fees);
    }

    /**
     * Get continent for a country code.
     */
    protected function getContinent(string $countryCode): string
    {
        $continents = [
            'North America' => ['US', 'CA', 'MX'],
            'Europe' => ['GB', 'DE', 'FR', 'NL', 'ES', 'IT', 'IE', 'PL', 'SE', 'NO', 'DK', 'FI', 'AT', 'CH', 'BE'],
            'Oceania' => ['AU', 'NZ'],
            'Asia' => ['IN', 'PH', 'SG', 'JP', 'KR', 'CN'],
            'Middle East' => ['AE', 'SA'],
            'Africa' => ['NG', 'ZA', 'KE', 'GH', 'EG', 'TZ', 'UG'],
            'South America' => ['BR', 'AR', 'CL', 'CO', 'PE'],
        ];

        foreach ($continents as $continent => $countries) {
            if (in_array($countryCode, $countries)) {
                return $continent;
            }
        }

        return 'Other';
    }
}
