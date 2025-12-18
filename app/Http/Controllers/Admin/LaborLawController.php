<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplianceViolation;
use App\Models\LaborLawRule;
use App\Models\WorkerExemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GLO-003: Labor Law Compliance - Admin Controller
 *
 * Manages labor law rules, compliance violations, and worker exemptions.
 */
class LaborLawController extends Controller
{
    // ==================== LABOR LAW RULES ====================

    /**
     * Display all labor law rules.
     */
    public function index(Request $request)
    {
        $query = LaborLawRule::query();

        // Filter by jurisdiction
        if ($request->filled('jurisdiction')) {
            $query->forJurisdiction($request->jurisdiction);
        }

        // Filter by rule type
        if ($request->filled('rule_type')) {
            $query->ofType($request->rule_type);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->orderBy('jurisdiction')
            ->orderBy('rule_type')
            ->paginate(25);

        $jurisdictions = LaborLawRule::distinct()->pluck('jurisdiction');
        $ruleTypes = [
            LaborLawRule::TYPE_WORKING_TIME => 'Working Time',
            LaborLawRule::TYPE_REST_PERIOD => 'Rest Period',
            LaborLawRule::TYPE_BREAK => 'Break',
            LaborLawRule::TYPE_OVERTIME => 'Overtime',
            LaborLawRule::TYPE_AGE_RESTRICTION => 'Age Restriction',
            LaborLawRule::TYPE_WAGE => 'Wage',
            LaborLawRule::TYPE_NIGHT_WORK => 'Night Work',
        ];

        return view('admin.labor-law.index', compact('rules', 'jurisdictions', 'ruleTypes'));
    }

    /**
     * Show form to create a new rule.
     */
    public function create()
    {
        $ruleTypes = [
            LaborLawRule::TYPE_WORKING_TIME => 'Working Time',
            LaborLawRule::TYPE_REST_PERIOD => 'Rest Period',
            LaborLawRule::TYPE_BREAK => 'Break',
            LaborLawRule::TYPE_OVERTIME => 'Overtime',
            LaborLawRule::TYPE_AGE_RESTRICTION => 'Age Restriction',
            LaborLawRule::TYPE_WAGE => 'Wage',
            LaborLawRule::TYPE_NIGHT_WORK => 'Night Work',
        ];

        $enforcementLevels = [
            LaborLawRule::ENFORCEMENT_HARD_BLOCK => 'Hard Block (Prevent Action)',
            LaborLawRule::ENFORCEMENT_SOFT_WARNING => 'Soft Warning (Allow with Warning)',
            LaborLawRule::ENFORCEMENT_LOG_ONLY => 'Log Only (Silent Tracking)',
        ];

        return view('admin.labor-law.create', compact('ruleTypes', 'enforcementLevels'));
    }

    /**
     * Store a new labor law rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jurisdiction' => 'required|string|max:50',
            'rule_code' => 'required|string|max:100|unique:labor_law_rules,rule_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|string|in:working_time,rest_period,break,overtime,age_restriction,wage,night_work',
            'parameters' => 'required|array',
            'enforcement' => 'required|string|in:hard_block,soft_warning,log_only',
            'is_active' => 'boolean',
            'allows_opt_out' => 'boolean',
            'opt_out_requirements' => 'nullable|string',
            'legal_reference' => 'nullable|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $rule = LaborLawRule::create($validated);

        Log::info('Labor law rule created', [
            'rule_id' => $rule->id,
            'rule_code' => $rule->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.labor-law.show', $rule)
            ->with('success', 'Labor law rule created successfully.');
    }

    /**
     * Display a specific rule.
     */
    public function show(LaborLawRule $laborLaw)
    {
        $laborLaw->load(['violations' => function ($query) {
            $query->recent(30)->orderBy('created_at', 'desc');
        }, 'exemptions' => function ($query) {
            $query->active();
        }]);

        $violationStats = [
            'total' => $laborLaw->violations()->count(),
            'recent' => $laborLaw->violations()->recent(30)->count(),
            'blocked' => $laborLaw->violations()->blocking()->count(),
            'resolved' => $laborLaw->violations()->resolved()->count(),
        ];

        $exemptionStats = [
            'total' => $laborLaw->exemptions()->count(),
            'active' => $laborLaw->exemptions()->active()->count(),
            'pending' => $laborLaw->exemptions()->pending()->count(),
        ];

        return view('admin.labor-law.show', compact('laborLaw', 'violationStats', 'exemptionStats'));
    }

    /**
     * Show form to edit a rule.
     */
    public function edit(LaborLawRule $laborLaw)
    {
        $ruleTypes = [
            LaborLawRule::TYPE_WORKING_TIME => 'Working Time',
            LaborLawRule::TYPE_REST_PERIOD => 'Rest Period',
            LaborLawRule::TYPE_BREAK => 'Break',
            LaborLawRule::TYPE_OVERTIME => 'Overtime',
            LaborLawRule::TYPE_AGE_RESTRICTION => 'Age Restriction',
            LaborLawRule::TYPE_WAGE => 'Wage',
            LaborLawRule::TYPE_NIGHT_WORK => 'Night Work',
        ];

        $enforcementLevels = [
            LaborLawRule::ENFORCEMENT_HARD_BLOCK => 'Hard Block (Prevent Action)',
            LaborLawRule::ENFORCEMENT_SOFT_WARNING => 'Soft Warning (Allow with Warning)',
            LaborLawRule::ENFORCEMENT_LOG_ONLY => 'Log Only (Silent Tracking)',
        ];

        return view('admin.labor-law.edit', compact('laborLaw', 'ruleTypes', 'enforcementLevels'));
    }

    /**
     * Update a labor law rule.
     */
    public function update(Request $request, LaborLawRule $laborLaw)
    {
        $validated = $request->validate([
            'jurisdiction' => 'required|string|max:50',
            'rule_code' => 'required|string|max:100|unique:labor_law_rules,rule_code,'.$laborLaw->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|string|in:working_time,rest_period,break,overtime,age_restriction,wage,night_work',
            'parameters' => 'required|array',
            'enforcement' => 'required|string|in:hard_block,soft_warning,log_only',
            'is_active' => 'boolean',
            'allows_opt_out' => 'boolean',
            'opt_out_requirements' => 'nullable|string',
            'legal_reference' => 'nullable|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $laborLaw->update($validated);

        Log::info('Labor law rule updated', [
            'rule_id' => $laborLaw->id,
            'rule_code' => $laborLaw->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.labor-law.show', $laborLaw)
            ->with('success', 'Labor law rule updated successfully.');
    }

    /**
     * Toggle rule active status.
     */
    public function toggleActive(LaborLawRule $laborLaw)
    {
        $laborLaw->update(['is_active' => ! $laborLaw->is_active]);

        $status = $laborLaw->is_active ? 'activated' : 'deactivated';

        Log::info("Labor law rule {$status}", [
            'rule_id' => $laborLaw->id,
            'rule_code' => $laborLaw->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', "Rule {$status} successfully.");
    }

    /**
     * Delete a rule (soft delete consideration).
     */
    public function destroy(LaborLawRule $laborLaw)
    {
        // Check for existing violations
        if ($laborLaw->violations()->exists()) {
            return back()->with('error', 'Cannot delete rule with existing violations. Deactivate instead.');
        }

        Log::info('Labor law rule deleted', [
            'rule_id' => $laborLaw->id,
            'rule_code' => $laborLaw->rule_code,
            'admin_id' => auth()->id(),
        ]);

        $laborLaw->delete();

        return redirect()
            ->route('admin.labor-law.index')
            ->with('success', 'Labor law rule deleted successfully.');
    }

    // ==================== VIOLATIONS ====================

    /**
     * Display all compliance violations.
     */
    public function violations(Request $request)
    {
        $query = ComplianceViolation::with(['user', 'shift', 'laborLawRule']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by rule
        if ($request->filled('rule_id')) {
            $query->where('labor_law_rule_id', $request->rule_id);
        }

        // Filter by blocked
        if ($request->boolean('blocked_only')) {
            $query->blocking();
        }

        $violations = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [
            ComplianceViolation::STATUS_DETECTED => 'Detected',
            ComplianceViolation::STATUS_ACKNOWLEDGED => 'Acknowledged',
            ComplianceViolation::STATUS_RESOLVED => 'Resolved',
            ComplianceViolation::STATUS_EXEMPTED => 'Exempted',
            ComplianceViolation::STATUS_APPEALED => 'Appealed',
        ];

        $severities = [
            ComplianceViolation::SEVERITY_INFO => 'Info',
            ComplianceViolation::SEVERITY_WARNING => 'Warning',
            ComplianceViolation::SEVERITY_VIOLATION => 'Violation',
            ComplianceViolation::SEVERITY_CRITICAL => 'Critical',
        ];

        $rules = LaborLawRule::orderBy('name')->pluck('name', 'id');

        return view('admin.labor-law.violations', compact('violations', 'statuses', 'severities', 'rules'));
    }

    /**
     * Show a specific violation.
     */
    public function showViolation(ComplianceViolation $violation)
    {
        $violation->load(['user.workerProfile', 'shift', 'laborLawRule', 'resolver']);

        return view('admin.labor-law.violation-show', compact('violation'));
    }

    /**
     * Resolve a violation.
     */
    public function resolveViolation(Request $request, ComplianceViolation $violation)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $violation->resolve(auth()->id(), $validated['resolution_notes']);

        Log::info('Compliance violation resolved', [
            'violation_id' => $violation->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Violation resolved successfully.');
    }

    // ==================== EXEMPTIONS ====================

    /**
     * Display all worker exemptions.
     */
    public function exemptions(Request $request)
    {
        $query = WorkerExemption::with(['user', 'laborLawRule', 'approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by rule
        if ($request->filled('rule_id')) {
            $query->where('labor_law_rule_id', $request->rule_id);
        }

        // Show expiring soon
        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon(30);
        }

        $exemptions = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [
            WorkerExemption::STATUS_PENDING => 'Pending',
            WorkerExemption::STATUS_APPROVED => 'Approved',
            WorkerExemption::STATUS_REJECTED => 'Rejected',
            WorkerExemption::STATUS_EXPIRED => 'Expired',
            WorkerExemption::STATUS_REVOKED => 'Revoked',
        ];

        $rules = LaborLawRule::optOutable()->orderBy('name')->pluck('name', 'id');

        return view('admin.labor-law.exemptions', compact('exemptions', 'statuses', 'rules'));
    }

    /**
     * Show a specific exemption.
     */
    public function showExemption(WorkerExemption $exemption)
    {
        $exemption->load(['user.workerProfile', 'laborLawRule', 'approver', 'rejecter']);

        return view('admin.labor-law.exemption-show', compact('exemption'));
    }

    /**
     * Approve an exemption.
     */
    public function approveExemption(WorkerExemption $exemption)
    {
        if ($exemption->status !== WorkerExemption::STATUS_PENDING) {
            return back()->with('error', 'Only pending exemptions can be approved.');
        }

        $exemption->approve(auth()->id());

        Log::info('Worker exemption approved', [
            'exemption_id' => $exemption->id,
            'worker_id' => $exemption->user_id,
            'rule_code' => $exemption->laborLawRule->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Exemption approved successfully.');
    }

    /**
     * Reject an exemption.
     */
    public function rejectExemption(Request $request, WorkerExemption $exemption)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($exemption->status !== WorkerExemption::STATUS_PENDING) {
            return back()->with('error', 'Only pending exemptions can be rejected.');
        }

        $exemption->reject(auth()->id(), $validated['rejection_reason']);

        Log::info('Worker exemption rejected', [
            'exemption_id' => $exemption->id,
            'worker_id' => $exemption->user_id,
            'rule_code' => $exemption->laborLawRule->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Exemption rejected.');
    }

    /**
     * Revoke an active exemption.
     */
    public function revokeExemption(Request $request, WorkerExemption $exemption)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($exemption->status !== WorkerExemption::STATUS_APPROVED) {
            return back()->with('error', 'Only approved exemptions can be revoked.');
        }

        $exemption->revoke(auth()->id(), $validated['reason'] ?? null);

        Log::info('Worker exemption revoked', [
            'exemption_id' => $exemption->id,
            'worker_id' => $exemption->user_id,
            'rule_code' => $exemption->laborLawRule->rule_code,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Exemption revoked.');
    }

    // ==================== REPORTS ====================

    /**
     * Display compliance dashboard/report.
     */
    public function dashboard()
    {
        $stats = [
            'rules' => [
                'total' => LaborLawRule::count(),
                'active' => LaborLawRule::active()->count(),
                'blocking' => LaborLawRule::hardBlocking()->count(),
            ],
            'violations' => [
                'total' => ComplianceViolation::count(),
                'this_month' => ComplianceViolation::where('created_at', '>=', now()->startOfMonth())->count(),
                'unresolved' => ComplianceViolation::unresolved()->count(),
                'critical' => ComplianceViolation::critical()->unresolved()->count(),
            ],
            'exemptions' => [
                'total' => WorkerExemption::count(),
                'active' => WorkerExemption::active()->count(),
                'pending' => WorkerExemption::pending()->count(),
                'expiring_soon' => WorkerExemption::expiringSoon(30)->count(),
            ],
        ];

        // Recent violations
        $recentViolations = ComplianceViolation::with(['user', 'laborLawRule'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Pending exemptions
        $pendingExemptions = WorkerExemption::with(['user', 'laborLawRule'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Violations by rule type
        $violationsByType = ComplianceViolation::selectRaw('
                labor_law_rules.rule_type,
                COUNT(compliance_violations.id) as count
            ')
            ->join('labor_law_rules', 'compliance_violations.labor_law_rule_id', '=', 'labor_law_rules.id')
            ->groupBy('labor_law_rules.rule_type')
            ->pluck('count', 'rule_type');

        return view('admin.labor-law.dashboard', compact('stats', 'recentViolations', 'pendingExemptions', 'violationsByType'));
    }
}
