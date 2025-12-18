<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceFingerprint;
use App\Models\FraudRule;
use App\Models\FraudSignal;
use App\Models\User;
use App\Models\UserRiskScore;
use App\Services\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FIN-015: Admin Fraud Controller
 *
 * Manages fraud detection system including:
 * - Fraud signals dashboard
 * - Risk score management
 * - Fraud rules configuration
 * - Device fingerprint management
 * - User blocking/unblocking
 */
class FraudController extends Controller
{
    /**
     * The fraud detection service.
     */
    protected FraudDetectionService $fraudService;

    /**
     * Create a new controller instance.
     */
    public function __construct(FraudDetectionService $fraudService)
    {
        $this->fraudService = $fraudService;
    }

    /**
     * Display fraud detection dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        // Get statistics
        $stats = $this->fraudService->getStatistics();

        // Recent signals
        $recentSignals = FraudSignal::with('user')
            ->unresolved()
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // High risk users
        $highRiskUsers = UserRiskScore::with('user')
            ->highRisk()
            ->orderBy('risk_score', 'desc')
            ->limit(10)
            ->get();

        // Active rules count
        $activeRulesCount = FraudRule::active()->count();

        return view('admin.fraud.index', compact(
            'stats',
            'recentSignals',
            'highRiskUsers',
            'activeRulesCount'
        ));
    }

    /**
     * Display list of fraud signals.
     *
     * @return \Illuminate\View\View
     */
    public function signals(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $query = FraudSignal::with('user');

        // Filters
        if ($request->filled('type')) {
            $query->ofType($request->get('type'));
        }

        if ($request->filled('code')) {
            $query->withCode($request->get('code'));
        }

        if ($request->filled('status')) {
            if ($request->get('status') === 'resolved') {
                $query->resolved();
            } else {
                $query->unresolved();
            }
        }

        if ($request->filled('severity')) {
            $severity = $request->get('severity');
            if ($severity === 'high') {
                $query->highSeverity();
            } elseif ($severity === 'critical') {
                $query->where('severity', '>=', 9);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $signals = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $signalTypes = FraudSignal::getSignalTypes();
        $signalCodes = FraudSignal::getSignalCodes();

        return view('admin.fraud.signals', compact('signals', 'signalTypes', 'signalCodes'));
    }

    /**
     * Resolve a fraud signal.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolveSignal(Request $request, $id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $signal = FraudSignal::findOrFail($id);
        $signal->resolve($request->input('resolution_notes'));

        Log::info('Fraud signal resolved', [
            'signal_id' => $signal->id,
            'admin_id' => $admin->id,
            'user_id' => $signal->user_id,
        ]);

        return redirect()->back()->with('success', 'Signal resolved successfully.');
    }

    /**
     * Bulk resolve fraud signals.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkResolveSignals(Request $request)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'signal_ids' => 'required|array|min:1',
            'signal_ids.*' => 'integer|exists:fraud_signals,id',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $notes = $request->input('resolution_notes', 'Bulk resolved by admin');
        $count = 0;

        foreach ($request->input('signal_ids') as $signalId) {
            $signal = FraudSignal::find($signalId);
            if ($signal && ! $signal->is_resolved) {
                $signal->resolve($notes);
                $count++;
            }
        }

        Log::info('Fraud signals bulk resolved', [
            'count' => $count,
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', "{$count} signal(s) resolved successfully.");
    }

    /**
     * Display risk scores list.
     *
     * @return \Illuminate\View\View
     */
    public function riskScores(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $query = UserRiskScore::with('user');

        // Filters
        if ($request->filled('level')) {
            $query->where('risk_level', $request->get('level'));
        }

        if ($request->filled('min_score')) {
            $query->minScore((int) $request->get('min_score'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $scores = $query->orderBy('risk_score', 'desc')
            ->paginate(25)
            ->withQueryString();

        $riskLevels = UserRiskScore::getRiskLevels();

        return view('admin.fraud.risk-scores', compact('scores', 'riskLevels'));
    }

    /**
     * Recalculate risk score for a user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recalculateRiskScore($userId)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $user = User::findOrFail($userId);
        $riskScore = $this->fraudService->analyzeUser($user);

        Log::info('User risk score recalculated', [
            'user_id' => $userId,
            'admin_id' => $admin->id,
            'new_score' => $riskScore->risk_score,
            'new_level' => $riskScore->risk_level,
        ]);

        return redirect()->back()->with('success', "Risk score recalculated: {$riskScore->risk_score} ({$riskScore->risk_level})");
    }

    /**
     * Display fraud rules list.
     *
     * @return \Illuminate\View\View
     */
    public function rules(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $query = FraudRule::query();

        if ($request->filled('category')) {
            $query->ofCategory($request->get('category'));
        }

        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $rules = $query->orderBy('severity', 'desc')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $categories = FraudRule::getCategories();
        $actions = FraudRule::getActions();

        return view('admin.fraud.rules', compact('rules', 'categories', 'actions'));
    }

    /**
     * Show create rule form.
     *
     * @return \Illuminate\View\View
     */
    public function createRule()
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $categories = FraudRule::getCategories();
        $actions = FraudRule::getActions();

        return view('admin.fraud.rules.create', compact('categories', 'actions'));
    }

    /**
     * Store a new fraud rule.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRule(Request $request)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fraud_rules,code|alpha_dash',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:velocity,device,location,behavior,identity,payment',
            'condition_field' => 'required|string|max:100',
            'condition_operator' => 'required|in:>,>=,<,<=,=,!=',
            'condition_value' => 'required|numeric',
            'condition_period' => 'required|string|max:20',
            'severity' => 'required|integer|min:1|max:10',
            'action' => 'required|in:flag,block,review,notify',
            'is_active' => 'boolean',
        ]);

        $rule = FraudRule::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'],
            'category' => $validated['category'],
            'conditions' => [
                'field' => $validated['condition_field'],
                'operator' => $validated['condition_operator'],
                'value' => $validated['condition_value'],
                'period' => $validated['condition_period'],
            ],
            'severity' => $validated['severity'],
            'action' => $validated['action'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Clear rules cache
        $this->fraudService->clearRulesCache();

        Log::info('Fraud rule created', [
            'rule_id' => $rule->id,
            'admin_id' => $admin->id,
        ]);

        return redirect()->route('admin.fraud.rules')
            ->with('success', "Rule '{$rule->name}' created successfully.");
    }

    /**
     * Show edit rule form.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function editRule($id)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $rule = FraudRule::findOrFail($id);
        $categories = FraudRule::getCategories();
        $actions = FraudRule::getActions();

        return view('admin.fraud.rules.edit', compact('rule', 'categories', 'actions'));
    }

    /**
     * Update a fraud rule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRule(Request $request, $id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $rule = FraudRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:velocity,device,location,behavior,identity,payment',
            'condition_field' => 'required|string|max:100',
            'condition_operator' => 'required|in:>,>=,<,<=,=,!=',
            'condition_value' => 'required|numeric',
            'condition_period' => 'required|string|max:20',
            'severity' => 'required|integer|min:1|max:10',
            'action' => 'required|in:flag,block,review,notify',
            'is_active' => 'boolean',
        ]);

        $rule->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'conditions' => [
                'field' => $validated['condition_field'],
                'operator' => $validated['condition_operator'],
                'value' => $validated['condition_value'],
                'period' => $validated['condition_period'],
            ],
            'severity' => $validated['severity'],
            'action' => $validated['action'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Clear rules cache
        $this->fraudService->clearRulesCache();

        Log::info('Fraud rule updated', [
            'rule_id' => $rule->id,
            'admin_id' => $admin->id,
        ]);

        return redirect()->route('admin.fraud.rules')
            ->with('success', "Rule '{$rule->name}' updated successfully.");
    }

    /**
     * Toggle rule active status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleRule($id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $rule = FraudRule::findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        // Clear rules cache
        $this->fraudService->clearRulesCache();

        $status = $rule->is_active ? 'activated' : 'deactivated';

        Log::info("Fraud rule {$status}", [
            'rule_id' => $rule->id,
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', "Rule '{$rule->name}' {$status}.");
    }

    /**
     * Delete a fraud rule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteRule($id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $rule = FraudRule::findOrFail($id);
        $ruleName = $rule->name;
        $rule->delete();

        // Clear rules cache
        $this->fraudService->clearRulesCache();

        Log::info('Fraud rule deleted', [
            'rule_name' => $ruleName,
            'admin_id' => $admin->id,
        ]);

        return redirect()->route('admin.fraud.rules')
            ->with('success', "Rule '{$ruleName}' deleted successfully.");
    }

    /**
     * Seed default fraud rules.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function seedDefaultRules()
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $defaultRules = FraudRule::getDefaultRules();
        $created = 0;

        foreach ($defaultRules as $ruleData) {
            $exists = FraudRule::where('code', $ruleData['code'])->exists();
            if (! $exists) {
                FraudRule::create($ruleData);
                $created++;
            }
        }

        // Clear rules cache
        $this->fraudService->clearRulesCache();

        Log::info('Default fraud rules seeded', [
            'created' => $created,
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', "{$created} default rule(s) created.");
    }

    /**
     * Display device fingerprints list.
     *
     * @return \Illuminate\View\View
     */
    public function devices(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('fraud_management')) {
            return view('admin.unauthorized');
        }

        $query = DeviceFingerprint::with('user');

        if ($request->filled('status')) {
            if ($request->get('status') === 'blocked') {
                $query->blocked();
            } elseif ($request->get('status') === 'trusted') {
                $query->trusted();
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('fingerprint_hash', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $devices = $query->orderBy('last_seen_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fraud.devices', compact('devices'));
    }

    /**
     * Block a device fingerprint.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function blockDevice($id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $device = DeviceFingerprint::findOrFail($id);
        $device->markBlocked();

        Log::warning('Device fingerprint blocked', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'fingerprint_hash' => substr($device->fingerprint_hash, 0, 16).'...',
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', 'Device blocked successfully.');
    }

    /**
     * Unblock a device fingerprint.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unblockDevice($id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $device = DeviceFingerprint::findOrFail($id);
        $device->unblock();

        Log::info('Device fingerprint unblocked', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', 'Device unblocked successfully.');
    }

    /**
     * Trust a device fingerprint.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trustDevice($id)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $device = DeviceFingerprint::findOrFail($id);
        $device->markTrusted();

        Log::info('Device fingerprint marked as trusted', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'admin_id' => $admin->id,
        ]);

        return redirect()->back()->with('success', 'Device marked as trusted.');
    }

    /**
     * Block user for fraud.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function blockUser(Request $request, $userId)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Cannot block admin users.');
        }

        $this->fraudService->blockUser($user, $request->input('reason'));

        Log::alert('User blocked for fraud by admin', [
            'user_id' => $userId,
            'admin_id' => $admin->id,
            'reason' => $request->input('reason'),
        ]);

        return redirect()->back()->with('success', "User {$user->email} has been blocked for fraud.");
    }

    /**
     * Get user fraud details (AJAX).
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userDetails($userId)
    {
        $admin = auth()->user();

        // Permission check
        if (! $admin->is_dev_account && ! $admin->hasPermission('fraud_management')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);

        $riskScore = UserRiskScore::where('user_id', $userId)->first();
        $signals = FraudSignal::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $devices = DeviceFingerprint::where('user_id', $userId)
            ->orderBy('last_seen_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'risk_score' => $riskScore ? [
                'score' => $riskScore->risk_score,
                'level' => $riskScore->risk_level,
                'last_calculated' => $riskScore->last_calculated_at?->toIso8601String(),
            ] : null,
            'signals' => $signals->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->signal_type,
                'code' => $s->signal_code,
                'severity' => $s->severity,
                'is_resolved' => $s->is_resolved,
                'created_at' => $s->created_at->toIso8601String(),
            ]),
            'devices' => $devices->map(fn ($d) => [
                'id' => $d->id,
                'browser' => $d->browser,
                'os' => $d->os,
                'is_trusted' => $d->is_trusted,
                'is_blocked' => $d->is_blocked,
                'last_seen' => $d->last_seen_at->toIso8601String(),
            ]),
        ]);
    }
}
