<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\FeatureFlagLog;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;

/**
 * Feature Flag Controller
 *
 * ADM-007: Feature Flags System
 * Admin interface for managing feature flags.
 */
class FeatureFlagController extends Controller
{
    public function __construct(
        protected FeatureFlagService $featureFlagService
    ) {}

    /**
     * Display all feature flags.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return view('admin.unauthorized');
        }

        $query = FeatureFlag::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if ($status === 'enabled') {
                $query->where('is_enabled', true);
            } elseif ($status === 'disabled') {
                $query->where('is_enabled', false);
            } elseif ($status === 'active') {
                $query->active();
            } elseif ($status === 'scheduled') {
                $query->where('is_enabled', true)
                    ->where('starts_at', '>', now());
            } elseif ($status === 'expired') {
                $query->where('is_enabled', true)
                    ->where('ends_at', '<', now());
            }
        }

        $featureFlags = $query->orderBy('name')->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => FeatureFlag::count(),
            'enabled' => FeatureFlag::where('is_enabled', true)->count(),
            'disabled' => FeatureFlag::where('is_enabled', false)->count(),
            'active' => FeatureFlag::active()->count(),
            'rolling_out' => FeatureFlag::where('is_enabled', true)
                ->where('rollout_percentage', '>', 0)
                ->where('rollout_percentage', '<', 100)
                ->count(),
        ];

        // Get recent activity
        $recentActivity = FeatureFlagLog::with(['featureFlag', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.feature-flags.index', [
            'featureFlags' => $featureFlags,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new feature flag.
     */
    public function create()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return view('admin.unauthorized');
        }

        return view('admin.feature-flags.create');
    }

    /**
     * Store a newly created feature flag.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return redirect()->route('admin.feature-flags.index')
                ->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                'unique:feature_flags,key',
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_enabled' => 'boolean',
            'rollout_percentage' => 'integer|min:0|max:100',
            'enabled_for_users' => 'nullable|string',
            'enabled_for_roles' => 'nullable|array',
            'enabled_for_roles.*' => 'string',
            'enabled_for_tiers' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ], [
            'key.regex' => 'The key must start with a letter and contain only lowercase letters, numbers, and underscores.',
        ]);

        // Parse user IDs from comma-separated string
        $enabledForUsers = null;
        if (! empty($validated['enabled_for_users'])) {
            $enabledForUsers = array_map('intval', array_filter(explode(',', $validated['enabled_for_users'])));
        }

        // Parse tiers from comma-separated string
        $enabledForTiers = null;
        if (! empty($validated['enabled_for_tiers'])) {
            $enabledForTiers = array_filter(array_map('trim', explode(',', $validated['enabled_for_tiers'])));
        }

        $featureFlag = $this->featureFlagService->create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? false,
            'rollout_percentage' => $validated['rollout_percentage'] ?? 0,
            'enabled_for_users' => $enabledForUsers,
            'enabled_for_roles' => $validated['enabled_for_roles'] ?? null,
            'enabled_for_tiers' => $enabledForTiers,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()->route('admin.feature-flags.index')
            ->with('success', "Feature flag '{$featureFlag->name}' created successfully.");
    }

    /**
     * Display a specific feature flag.
     */
    public function show(FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return view('admin.unauthorized');
        }

        $history = $featureFlag->logs()
            ->with('user:id,name,email')
            ->paginate(20);

        return view('admin.feature-flags.show', [
            'featureFlag' => $featureFlag,
            'history' => $history,
        ]);
    }

    /**
     * Show the form for editing a feature flag.
     */
    public function edit(FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return view('admin.unauthorized');
        }

        return view('admin.feature-flags.edit', [
            'featureFlag' => $featureFlag,
        ]);
    }

    /**
     * Update a feature flag.
     */
    public function update(Request $request, FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return redirect()->route('admin.feature-flags.index')
                ->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_enabled' => 'boolean',
            'rollout_percentage' => 'integer|min:0|max:100',
            'enabled_for_users' => 'nullable|string',
            'enabled_for_roles' => 'nullable|array',
            'enabled_for_roles.*' => 'string',
            'enabled_for_tiers' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        // Parse user IDs
        $enabledForUsers = null;
        if (! empty($validated['enabled_for_users'])) {
            $enabledForUsers = array_map('intval', array_filter(explode(',', $validated['enabled_for_users'])));
        }

        // Parse tiers
        $enabledForTiers = null;
        if (! empty($validated['enabled_for_tiers'])) {
            $enabledForTiers = array_filter(array_map('trim', explode(',', $validated['enabled_for_tiers'])));
        }

        $this->featureFlagService->update($featureFlag->key, [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? false,
            'rollout_percentage' => $validated['rollout_percentage'] ?? 0,
            'enabled_for_users' => $enabledForUsers,
            'enabled_for_roles' => $validated['enabled_for_roles'] ?? null,
            'enabled_for_tiers' => $enabledForTiers,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()->route('admin.feature-flags.index')
            ->with('success', "Feature flag '{$featureFlag->name}' updated successfully.");
    }

    /**
     * Remove a feature flag.
     */
    public function destroy(FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return redirect()->route('admin.feature-flags.index')
                ->with('error', 'Unauthorized');
        }

        $name = $featureFlag->name;
        $this->featureFlagService->delete($featureFlag->key);

        return redirect()->route('admin.feature-flags.index')
            ->with('success', "Feature flag '{$name}' deleted successfully.");
    }

    /**
     * Toggle a feature flag on/off.
     */
    public function toggle(FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($featureFlag->is_enabled) {
            $this->featureFlagService->disable($featureFlag->key);
            $status = 'disabled';
        } else {
            $this->featureFlagService->enable($featureFlag->key);
            $status = 'enabled';
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'is_enabled' => ! $featureFlag->is_enabled,
            ]);
        }

        return redirect()->back()
            ->with('success', "Feature flag '{$featureFlag->name}' {$status} successfully.");
    }

    /**
     * Update rollout percentage via AJAX.
     */
    public function updateRollout(Request $request, FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rollout_percentage' => 'required|integer|min:0|max:100',
        ]);

        $this->featureFlagService->setRolloutPercentage(
            $featureFlag->key,
            $validated['rollout_percentage']
        );

        return response()->json([
            'success' => true,
            'rollout_percentage' => $validated['rollout_percentage'],
        ]);
    }

    /**
     * Get flag history via AJAX.
     */
    public function history(FeatureFlag $featureFlag)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $history = $this->featureFlagService->getFlagHistory($featureFlag->key, 20);

        $formattedHistory = $history->map(function ($log) {
            return [
                'action' => $log->action,
                'action_description' => $log->action_description,
                'action_color' => $log->action_color,
                'changed_by' => $log->user ? $log->user->name : 'System',
                'change_summary' => $log->getChangeSummary(),
                'created_at' => $log->created_at->format('M j, Y g:i A'),
                'relative_time' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'history' => $formattedHistory,
        ]);
    }

    /**
     * Batch toggle multiple feature flags.
     */
    public function batchToggle(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:feature_flags,id',
            'action' => 'required|in:enable,disable',
        ]);

        $flags = FeatureFlag::whereIn('id', $validated['ids'])->pluck('key')->toArray();

        $count = $validated['action'] === 'enable'
            ? $this->featureFlagService->batchEnable($flags)
            : $this->featureFlagService->batchDisable($flags);

        return response()->json([
            'success' => true,
            'count' => $count,
            'action' => $validated['action'],
        ]);
    }

    /**
     * Clear all feature flag caches.
     */
    public function clearCache()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('feature_flags')) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $this->featureFlagService->clearAllCaches();

        return redirect()->back()
            ->with('success', 'Feature flag cache cleared successfully.');
    }
}
