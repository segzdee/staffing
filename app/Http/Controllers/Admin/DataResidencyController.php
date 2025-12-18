<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataRegion;
use App\Models\DataTransferLog;
use App\Models\User;
use App\Models\UserDataResidency;
use App\Services\DataResidencyService;
use Illuminate\Http\Request;

/**
 * GLO-010: Data Residency Controller
 *
 * Admin interface for managing data regions, viewing user distribution,
 * initiating data migrations, and reviewing transfer logs.
 */
class DataResidencyController extends Controller
{
    public function __construct(
        protected DataResidencyService $residencyService
    ) {}

    /**
     * Display the data residency dashboard.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        // Get statistics
        $stats = $this->residencyService->getResidencyStatistics();

        // Get recent transfers
        $recentTransfers = DataTransferLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get regions with user counts
        $regions = DataRegion::withCount('userDataResidencies')
            ->orderBy('name')
            ->get();

        return view('admin.data-residency.index', [
            'stats' => $stats,
            'regions' => $regions,
            'recentTransfers' => $recentTransfers,
        ]);
    }

    /**
     * Display all data regions.
     */
    public function regions(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $query = DataRegion::withCount('userDataResidencies');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active');
        }

        $regions = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.data-residency.regions', [
            'regions' => $regions,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show form to create a new region.
     */
    public function createRegion()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $complianceFrameworks = DataRegion::getComplianceFrameworkOptions();

        return view('admin.data-residency.create-region', [
            'complianceFrameworks' => $complianceFrameworks,
        ]);
    }

    /**
     * Store a new data region.
     */
    public function storeRegion(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return redirect()->route('admin.data-residency.regions')
                ->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:data_regions,code',
            'name' => 'required|string|max:255',
            'countries' => 'required|string',
            'primary_storage' => 'required|string|max:255',
            'backup_storage' => 'nullable|string|max:255',
            'compliance_frameworks' => 'required|array|min:1',
            'compliance_frameworks.*' => 'string',
            'is_active' => 'boolean',
        ]);

        // Parse countries from comma-separated string
        $countries = array_map(
            fn ($c) => strtoupper(trim($c)),
            explode(',', $validated['countries'])
        );

        DataRegion::create([
            'code' => strtolower($validated['code']),
            'name' => $validated['name'],
            'countries' => $countries,
            'primary_storage' => $validated['primary_storage'],
            'backup_storage' => $validated['backup_storage'] ?? null,
            'compliance_frameworks' => $validated['compliance_frameworks'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.data-residency.regions')
            ->with('success', "Region '{$validated['name']}' created successfully.");
    }

    /**
     * Show a specific region.
     */
    public function showRegion(DataRegion $region)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        // Get users in this region with pagination
        $users = UserDataResidency::with('user:id,name,email,role')
            ->where('data_region_id', $region->id)
            ->paginate(20);

        // Get transfer stats for this region
        $transferStats = [
            'outgoing' => DataTransferLog::fromRegion($region->code)->count(),
            'incoming' => DataTransferLog::toRegion($region->code)->count(),
            'pending' => DataTransferLog::where(function ($q) use ($region) {
                $q->where('from_region', $region->code)
                    ->orWhere('to_region', $region->code);
            })->pending()->count(),
        ];

        return view('admin.data-residency.show-region', [
            'region' => $region,
            'users' => $users,
            'transferStats' => $transferStats,
        ]);
    }

    /**
     * Show form to edit a region.
     */
    public function editRegion(DataRegion $region)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $complianceFrameworks = DataRegion::getComplianceFrameworkOptions();

        return view('admin.data-residency.edit-region', [
            'region' => $region,
            'complianceFrameworks' => $complianceFrameworks,
        ]);
    }

    /**
     * Update a data region.
     */
    public function updateRegion(Request $request, DataRegion $region)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return redirect()->route('admin.data-residency.regions')
                ->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'required|string',
            'primary_storage' => 'required|string|max:255',
            'backup_storage' => 'nullable|string|max:255',
            'compliance_frameworks' => 'required|array|min:1',
            'compliance_frameworks.*' => 'string',
            'is_active' => 'boolean',
        ]);

        // Parse countries
        $countries = array_map(
            fn ($c) => strtoupper(trim($c)),
            explode(',', $validated['countries'])
        );

        $region->update([
            'name' => $validated['name'],
            'countries' => $countries,
            'primary_storage' => $validated['primary_storage'],
            'backup_storage' => $validated['backup_storage'] ?? null,
            'compliance_frameworks' => $validated['compliance_frameworks'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.data-residency.regions')
            ->with('success', "Region '{$region->name}' updated successfully.");
    }

    /**
     * Toggle region active status.
     */
    public function toggleRegion(DataRegion $region)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $region->update(['is_active' => ! $region->is_active]);

        $status = $region->is_active ? 'activated' : 'deactivated';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $region->is_active,
                'message' => "Region {$status} successfully.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Region '{$region->name}' {$status} successfully.");
    }

    /**
     * Display user distribution across regions.
     */
    public function userDistribution(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $query = UserDataResidency::with(['user:id,name,email,role', 'dataRegion:id,code,name']);

        // Filter by region
        if ($regionId = $request->input('region')) {
            $query->where('data_region_id', $regionId);
        }

        // Filter by country
        if ($country = $request->input('country')) {
            $query->where('detected_country', strtoupper($country));
        }

        // Filter by consent status
        if ($consent = $request->input('consent')) {
            if ($consent === 'given') {
                $query->whereNotNull('consent_given_at');
            } else {
                $query->whereNull('consent_given_at');
            }
        }

        // Search by user
        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $residencies = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $regions = DataRegion::orderBy('name')->get(['id', 'code', 'name']);

        // Get country distribution
        $countryDistribution = UserDataResidency::selectRaw('detected_country, COUNT(*) as count')
            ->groupBy('detected_country')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return view('admin.data-residency.user-distribution', [
            'residencies' => $residencies,
            'regions' => $regions,
            'countryDistribution' => $countryDistribution,
            'search' => $search,
            'selectedRegion' => $regionId,
            'selectedCountry' => $country,
            'selectedConsent' => $consent,
        ]);
    }

    /**
     * Display transfer logs.
     */
    public function transferLogs(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $query = DataTransferLog::with('user:id,name,email');

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('transfer_type', $type);
        }

        // Filter by from region
        if ($fromRegion = $request->input('from_region')) {
            $query->where('from_region', $fromRegion);
        }

        // Filter by to region
        if ($toRegion = $request->input('to_region')) {
            $query->where('to_region', $toRegion);
        }

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('created_at', '<=', $endDate.' 23:59:59');
        }

        // Search by user
        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $transfers = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $regions = DataRegion::orderBy('name')->get(['id', 'code', 'name']);

        // Get transfer statistics
        $transferStats = [
            'total' => DataTransferLog::count(),
            'pending' => DataTransferLog::pending()->count(),
            'in_progress' => DataTransferLog::inProgress()->count(),
            'completed' => DataTransferLog::completed()->count(),
            'failed' => DataTransferLog::failed()->count(),
        ];

        return view('admin.data-residency.transfer-logs', [
            'transfers' => $transfers,
            'regions' => $regions,
            'transferStats' => $transferStats,
            'statusOptions' => DataTransferLog::getStatusOptions(),
            'typeOptions' => DataTransferLog::getTransferTypeOptions(),
            'search' => $search,
            'selectedStatus' => $status,
            'selectedType' => $type,
            'selectedFromRegion' => $fromRegion,
            'selectedToRegion' => $toRegion,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Show a specific transfer log.
     */
    public function showTransfer(DataTransferLog $transfer)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return view('admin.unauthorized');
        }

        $transfer->load('user');

        return view('admin.data-residency.show-transfer', [
            'transfer' => $transfer,
        ]);
    }

    /**
     * Initiate data migration for a user.
     */
    public function initiateMigration(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'target_region_id' => 'required|exists:data_regions,id',
            'legal_basis' => 'nullable|string',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $targetRegion = DataRegion::findOrFail($validated['target_region_id']);

        try {
            $transfer = $this->residencyService->migrateUserData(
                $targetUser,
                $targetRegion,
                $validated['legal_basis'] ?? DataTransferLog::LEGAL_BASIS_CONSENT
            );

            return response()->json([
                'success' => true,
                'transfer_id' => $transfer->id,
                'message' => 'Migration initiated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get user residency report.
     */
    public function userReport(User $targetUser)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report = $this->residencyService->generateDataResidencyReport($targetUser);

        if (request()->wantsJson()) {
            return response()->json($report);
        }

        return view('admin.data-residency.user-report', [
            'report' => $report,
            'targetUser' => $targetUser,
        ]);
    }

    /**
     * Batch assign regions to users without residency.
     */
    public function batchAssign()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->residencyService->batchAssignRegions();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        }

        return redirect()->back()
            ->with('success', "Batch assignment complete. {$result['assigned']} users assigned.");
    }

    /**
     * Export transfer logs to CSV.
     */
    public function exportTransferLogs(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('data_residency')) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $query = DataTransferLog::with('user:id,name,email');

        // Apply filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($startDate = $request->input('start_date')) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('created_at', '<=', $endDate.' 23:59:59');
        }

        $transfers = $query->orderBy('created_at', 'desc')->get();

        $csv = "ID,User,Email,From Region,To Region,Type,Status,Legal Basis,Created At,Completed At\n";

        foreach ($transfers as $transfer) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $transfer->id,
                '"'.($transfer->user->name ?? 'N/A').'"',
                '"'.($transfer->user->email ?? 'N/A').'"',
                $transfer->from_region,
                $transfer->to_region,
                $transfer->transfer_type,
                $transfer->status,
                '"'.($transfer->legal_basis ?? '').'"',
                $transfer->created_at->toIso8601String(),
                $transfer->completed_at?->toIso8601String() ?? ''
            );
        }

        $filename = 'data_transfer_logs_'.now()->format('Y-m-d_His').'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
