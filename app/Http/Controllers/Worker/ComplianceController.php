<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\LaborLawRule;
use App\Models\WorkerExemption;
use App\Services\ComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GLO-003: Labor Law Compliance - Worker Controller
 *
 * Handles worker-facing compliance features including opt-outs and reports.
 */
class ComplianceController extends Controller
{
    public function __construct(
        protected ComplianceService $complianceService
    ) {}

    /**
     * Show worker's compliance dashboard.
     */
    public function index()
    {
        $worker = auth()->user();
        $report = $this->complianceService->getComplianceReport($worker);

        // Get available opt-out rules
        $optOutRules = LaborLawRule::active()
            ->optOutable()
            ->currentlyEffective()
            ->get();

        // Get worker's current exemptions
        $exemptions = $this->complianceService->getWorkerExemptions($worker);

        return view('worker.compliance.index', compact('report', 'optOutRules', 'exemptions'));
    }

    /**
     * Show opt-out form for a specific rule.
     */
    public function showOptOutForm(LaborLawRule $rule)
    {
        if (! $rule->allows_opt_out) {
            return back()->with('error', 'This rule does not allow opt-out.');
        }

        if (! $rule->is_active || ! $rule->isCurrentlyEffective()) {
            return back()->with('error', 'This rule is not currently active.');
        }

        $worker = auth()->user();

        // Check if already has an exemption
        $existingExemption = WorkerExemption::forWorker($worker->id)
            ->where('labor_law_rule_id', $rule->id)
            ->whereIn('status', [WorkerExemption::STATUS_PENDING, WorkerExemption::STATUS_APPROVED])
            ->first();

        return view('worker.compliance.opt-out', compact('rule', 'existingExemption'));
    }

    /**
     * Submit opt-out request.
     */
    public function submitOptOut(Request $request, LaborLawRule $rule)
    {
        if (! $rule->allows_opt_out) {
            return back()->with('error', 'This rule does not allow opt-out.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'acknowledge_consequences' => 'required|accepted',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'valid_until' => 'nullable|date|after:today',
        ]);

        $worker = auth()->user();

        // Handle document upload
        $documentUrl = null;
        $documentType = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $documentUrl = $file->store('exemption-documents', 'public');
            $documentType = $file->getClientOriginalExtension();
        }

        try {
            $exemption = $this->complianceService->recordOptOut($worker, $rule->rule_code, [
                'reason' => $validated['reason'],
                'document_url' => $documentUrl,
                'document_type' => $documentType,
                'valid_until' => $validated['valid_until'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::info('Worker submitted opt-out request', [
                'worker_id' => $worker->id,
                'rule_code' => $rule->rule_code,
                'exemption_id' => $exemption->id,
            ]);

            $message = $exemption->status === WorkerExemption::STATUS_APPROVED
                ? 'Your opt-out has been approved and is now active.'
                : 'Your opt-out request has been submitted and is pending approval.';

            return redirect()
                ->route('worker.compliance.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to submit opt-out request', [
                'worker_id' => $worker->id,
                'rule_code' => $rule->rule_code,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to submit opt-out request. Please try again.');
        }
    }

    /**
     * Withdraw/cancel an opt-out request.
     */
    public function withdrawOptOut(WorkerExemption $exemption)
    {
        $worker = auth()->user();

        // Ensure this belongs to the current worker
        if ($exemption->user_id !== $worker->id) {
            abort(403);
        }

        // Can only withdraw pending or approved exemptions
        if (! in_array($exemption->status, [WorkerExemption::STATUS_PENDING, WorkerExemption::STATUS_APPROVED])) {
            return back()->with('error', 'This exemption cannot be withdrawn.');
        }

        $exemption->update([
            'status' => WorkerExemption::STATUS_REVOKED,
            'rejection_reason' => 'Withdrawn by worker.',
        ]);

        Log::info('Worker withdrew opt-out', [
            'worker_id' => $worker->id,
            'exemption_id' => $exemption->id,
            'rule_code' => $exemption->laborLawRule->rule_code,
        ]);

        return back()->with('success', 'Your opt-out has been withdrawn.');
    }

    /**
     * Show worker's violation history.
     */
    public function violations()
    {
        $worker = auth()->user();

        $violations = $worker->complianceViolations()
            ->with('laborLawRule', 'shift')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('worker.compliance.violations', compact('violations'));
    }

    /**
     * Acknowledge a violation.
     */
    public function acknowledgeViolation(Request $request, $violationId)
    {
        $worker = auth()->user();

        $violation = $worker->complianceViolations()->findOrFail($violationId);

        if ($violation->status !== 'detected') {
            return back()->with('error', 'This violation has already been acknowledged.');
        }

        $violation->acknowledge();

        Log::info('Worker acknowledged violation', [
            'worker_id' => $worker->id,
            'violation_id' => $violation->id,
        ]);

        return back()->with('success', 'Violation acknowledged.');
    }

    /**
     * Show weekly hours summary.
     */
    public function weeklyHours(Request $request)
    {
        $worker = auth()->user();
        $weekStart = $request->filled('week')
            ? \Carbon\Carbon::parse($request->week)->startOfWeek()
            : now()->startOfWeek();

        $weeklyHours = $this->complianceService->getWorkerWeeklyHours($worker, $weekStart);
        $weeklyLimit = ComplianceService::DEFAULT_WEEKLY_HOURS_LIMIT;

        // Check if worker has opted out of weekly limit
        $hasOptedOut = $this->complianceService->hasOptedOut($worker, 'WTD_WEEKLY_MAX');

        // Get shifts for this week
        $shifts = $worker->shiftAssignments()
            ->with('shift')
            ->whereHas('shift', function ($query) use ($weekStart) {
                $query->whereBetween('shift_date', [
                    $weekStart,
                    $weekStart->copy()->endOfWeek(),
                ]);
            })
            ->get();

        return view('worker.compliance.weekly-hours', compact(
            'weeklyHours',
            'weeklyLimit',
            'hasOptedOut',
            'shifts',
            'weekStart'
        ));
    }
}
