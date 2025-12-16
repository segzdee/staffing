<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyApplication;
use App\Models\AgencyDocument;
use App\Models\AgencyComplianceCheck;
use App\Models\User;
use App\Notifications\AgencyApplicationStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Admin Agency Application Controller - AGY-REG-003
 *
 * Handles admin review workflow for agency registration applications including:
 * - Listing and filtering applications
 * - Document verification
 * - Compliance check management
 * - Final approval/rejection
 * - Reviewer assignment
 */
class AgencyApplicationController extends Controller
{
    /**
     * Display a listing of agency applications.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = AgencyApplication::with(['user', 'reviewer', 'documents', 'complianceChecks'])
            ->withCount(['documents', 'complianceChecks']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('submitted_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('submitted_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Assigned reviewer filter
        if ($request->filled('reviewer_id')) {
            if ($request->reviewer_id === 'unassigned') {
                $query->whereNull('reviewer_id');
            } else {
                $query->where('reviewer_id', $request->reviewer_id);
            }
        }

        // Search filter (company name, contact email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('agency_name', 'like', "%{$search}%")
                  ->orWhere('trading_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('business_registration_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Country filter
        if ($request->filled('country')) {
            $query->where('registered_country', $request->country);
        }

        // Sort order
        $sortField = $request->get('sort', 'submitted_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['submitted_at', 'agency_name', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('submitted_at', 'desc');
        }

        $applications = $query->paginate(20)->withQueryString();

        // Get statistics
        $statistics = $this->getStatistics();

        // Get reviewers for filter dropdown
        $reviewers = User::where('role', 'admin')
            ->orWhere('role', 'super_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get distinct countries for filter
        $countries = AgencyApplication::whereNotNull('registered_country')
            ->distinct()
            ->pluck('registered_country')
            ->sort()
            ->values();

        // Filter values for form persistence
        $filters = $request->only(['status', 'date_from', 'date_to', 'reviewer_id', 'search', 'country', 'sort', 'dir']);

        return view('admin.agency-applications.index', compact(
            'applications',
            'statistics',
            'reviewers',
            'countries',
            'filters'
        ));
    }

    /**
     * Display the specified application with full details.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $application = AgencyApplication::with([
            'user',
            'reviewer',
            'documents' => function ($q) {
                $q->orderBy('document_type')->orderBy('created_at', 'desc');
            },
            'complianceChecks' => function ($q) {
                $q->orderBy('check_type');
            },
            'commercialAgreement',
        ])->findOrFail($id);

        // Get activity log if available
        $activityLog = [];
        if (method_exists($application, 'activities')) {
            $activityLog = $application->activities()->latest()->take(50)->get();
        }

        // Get available reviewers for assignment
        $reviewers = User::where('role', 'admin')
            ->orWhere('role', 'super_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get required compliance checks for the application's jurisdiction
        $requiredChecks = AgencyComplianceCheck::getRequiredChecksForJurisdiction(
            $application->registered_country ?? 'US'
        );

        // Check which compliance checks are missing
        $existingCheckTypes = $application->complianceChecks->pluck('check_type')->toArray();
        $missingChecks = array_diff($requiredChecks, $existingCheckTypes);

        return view('admin.agency-applications.show', compact(
            'application',
            'activityLog',
            'reviewers',
            'requiredChecks',
            'missingChecks'
        ));
    }

    /**
     * Review and update document statuses.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reviewDocuments(Request $request, $id)
    {
        $request->validate([
            'documents' => 'required|array',
            'documents.*.id' => 'required|exists:agency_documents,id',
            'documents.*.status' => 'required|in:pending,verified,rejected',
            'documents.*.notes' => 'nullable|string|max:1000',
        ]);

        $application = AgencyApplication::findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($request->documents as $docData) {
                $document = AgencyDocument::where('id', $docData['id'])
                    ->where('agency_application_id', $id)
                    ->firstOrFail();

                $oldStatus = $document->status;

                $document->update([
                    'status' => $docData['status'],
                    'reviewer_notes' => $docData['notes'] ?? null,
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);

                // Log the document review
                $this->logActivity($application, 'document_reviewed', [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'old_status' => $oldStatus,
                    'new_status' => $docData['status'],
                    'notes' => $docData['notes'] ?? null,
                ]);
            }

            // Update application reviewer if not set
            if (!$application->reviewer_id) {
                $application->update(['reviewer_id' => auth()->id()]);
            }

            // Check if all documents are verified - auto-advance if so
            if ($application->hasAllDocumentsVerified()) {
                $application->update([
                    'status' => AgencyApplication::STATUS_DOCUMENTS_VERIFIED,
                    'documents_verified_at' => now(),
                ]);

                $this->logActivity($application, 'status_changed', [
                    'old_status' => $application->getOriginal('status'),
                    'new_status' => AgencyApplication::STATUS_DOCUMENTS_VERIFIED,
                    'reason' => 'All documents verified',
                ]);

                // Send notification to applicant
                $this->notifyApplicant($application, 'documents_verified');
            } elseif ($application->rejectedDocuments()->exists()) {
                // If any documents are rejected, update status
                $application->update([
                    'status' => AgencyApplication::STATUS_PENDING_DOCUMENTS,
                ]);

                // Notify applicant about rejected documents
                $this->notifyApplicant($application, 'documents_rejected');
            }

            DB::commit();

            return redirect()
                ->route('admin.agency-applications.show', $id)
                ->with('success', 'Document review saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document review failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to save document review. Please try again.');
        }
    }

    /**
     * Run or review compliance checks.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reviewCompliance(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:run_check,update_status,run_all',
            'check_type' => 'required_if:action,run_check|nullable|string',
            'check_id' => 'required_if:action,update_status|nullable|exists:agency_compliance_checks,id',
            'status' => 'required_if:action,update_status|nullable|in:pending,in_progress,passed,failed,manual_review',
            'notes' => 'nullable|string|max:2000',
            'failure_reason' => 'required_if:status,failed|nullable|string|max:1000',
            'risk_level' => 'nullable|in:low,medium,high,critical',
        ]);

        $application = AgencyApplication::findOrFail($id);

        DB::beginTransaction();
        try {
            switch ($request->action) {
                case 'run_check':
                    $this->runComplianceCheck($application, $request->check_type, $request->notes);
                    break;

                case 'update_status':
                    $this->updateComplianceCheckStatus($request, $application);
                    break;

                case 'run_all':
                    $this->runAllComplianceChecks($application);
                    break;
            }

            // Update application reviewer if not set
            if (!$application->reviewer_id) {
                $application->update(['reviewer_id' => auth()->id()]);
            }

            // Check if all compliance checks passed - advance status if so
            $application->refresh();
            if ($application->hasAllComplianceChecksPassed() &&
                $application->status === AgencyApplication::STATUS_PENDING_COMPLIANCE) {

                $application->approveCompliance('All compliance checks passed');

                $this->logActivity($application, 'status_changed', [
                    'old_status' => AgencyApplication::STATUS_PENDING_COMPLIANCE,
                    'new_status' => AgencyApplication::STATUS_COMPLIANCE_APPROVED,
                    'reason' => 'All compliance checks passed',
                ]);

                // Notify applicant
                $this->notifyApplicant($application, 'compliance_approved');
            }

            DB::commit();

            return redirect()
                ->route('admin.agency-applications.show', $id)
                ->with('success', 'Compliance review updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Compliance review failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to update compliance review. Please try again.');
        }
    }

    /**
     * Approve the application.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveApplication(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $application = AgencyApplication::findOrFail($id);

        // Validate application can be approved
        if ($application->isTerminal()) {
            return redirect()
                ->back()
                ->with('error', 'This application has already been finalized.');
        }

        // Check prerequisites
        $errors = [];
        if (!$application->hasAllDocumentsVerified()) {
            $errors[] = 'Not all documents have been verified.';
        }
        if (!$application->hasAllComplianceChecksPassed()) {
            $errors[] = 'Not all compliance checks have passed.';
        }

        if (!empty($errors)) {
            return redirect()
                ->back()
                ->with('error', 'Cannot approve application: ' . implode(' ', $errors));
        }

        DB::beginTransaction();
        try {
            $oldStatus = $application->status;

            $application->approve(auth()->user(), $request->notes);

            $this->logActivity($application, 'approved', [
                'old_status' => $oldStatus,
                'new_status' => AgencyApplication::STATUS_APPROVED,
                'notes' => $request->notes,
                'approved_by' => auth()->id(),
            ]);

            // Create agency profile for the user
            $this->createAgencyProfile($application);

            // Notify applicant
            $this->notifyApplicant($application, 'approved');

            DB::commit();

            return redirect()
                ->route('admin.agency-applications.show', $id)
                ->with('success', 'Application approved successfully. Agency profile has been created.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application approval failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to approve application. Please try again.');
        }
    }

    /**
     * Reject the application.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectApplication(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:2000',
            'rejection_details' => 'nullable|array',
            'allow_resubmission' => 'boolean',
        ]);

        $application = AgencyApplication::findOrFail($id);

        if ($application->isTerminal()) {
            return redirect()
                ->back()
                ->with('error', 'This application has already been finalized.');
        }

        DB::beginTransaction();
        try {
            $oldStatus = $application->status;

            $application->reject(
                auth()->user(),
                $request->rejection_reason,
                $request->rejection_details
            );

            $this->logActivity($application, 'rejected', [
                'old_status' => $oldStatus,
                'new_status' => AgencyApplication::STATUS_REJECTED,
                'rejection_reason' => $request->rejection_reason,
                'rejection_details' => $request->rejection_details,
                'rejected_by' => auth()->id(),
            ]);

            // Notify applicant
            $this->notifyApplicant($application, 'rejected');

            DB::commit();

            return redirect()
                ->route('admin.agency-applications.show', $id)
                ->with('success', 'Application has been rejected.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application rejection failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to reject application. Please try again.');
        }
    }

    /**
     * Assign a reviewer to the application.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignReviewer(Request $request, $id)
    {
        $request->validate([
            'reviewer_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $application = AgencyApplication::findOrFail($id);

        // Verify the assigned user is an admin
        $reviewer = User::findOrFail($request->reviewer_id);
        if (!in_array($reviewer->role, ['admin', 'super_admin'])) {
            return redirect()
                ->back()
                ->with('error', 'Selected user is not an admin.');
        }

        $oldReviewerId = $application->reviewer_id;

        $application->update([
            'reviewer_id' => $request->reviewer_id,
        ]);

        $this->logActivity($application, 'reviewer_assigned', [
            'old_reviewer_id' => $oldReviewerId,
            'new_reviewer_id' => $request->reviewer_id,
            'assigned_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return redirect()
            ->route('admin.agency-applications.show', $id)
            ->with('success', "Application assigned to {$reviewer->name}.");
    }

    /**
     * Start compliance checks for an application.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startComplianceChecks(Request $request, $id)
    {
        $application = AgencyApplication::findOrFail($id);

        if (!$application->isDocumentsVerified()) {
            return redirect()
                ->back()
                ->with('error', 'Documents must be verified before starting compliance checks.');
        }

        DB::beginTransaction();
        try {
            $application->startComplianceChecks();

            $this->logActivity($application, 'compliance_started', [
                'started_by' => auth()->id(),
            ]);

            // Initialize required compliance checks
            $requiredChecks = AgencyComplianceCheck::getRequiredChecksForJurisdiction(
                $application->registered_country ?? 'US'
            );

            foreach ($requiredChecks as $checkType) {
                // Check if this check type already exists
                $existing = $application->complianceChecks()
                    ->where('check_type', $checkType)
                    ->first();

                if (!$existing) {
                    AgencyComplianceCheck::create([
                        'agency_application_id' => $application->id,
                        'check_type' => $checkType,
                        'name' => AgencyComplianceCheck::getCheckTypeOptions()[$checkType] ?? $checkType,
                        'status' => AgencyComplianceCheck::STATUS_PENDING,
                        'is_required' => true,
                        'can_override' => !in_array($checkType, [
                            AgencyComplianceCheck::TYPE_AML_CHECK,
                            AgencyComplianceCheck::TYPE_SANCTIONS_SCREENING,
                        ]),
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.agency-applications.show', $id)
                ->with('success', 'Compliance checks initialized. Review and update each check.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start compliance checks', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to start compliance checks. Please try again.');
        }
    }

    /**
     * Add an internal note to the application.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $application = AgencyApplication::findOrFail($id);

        $existingNotes = $application->reviewer_notes ?? '';
        $timestamp = now()->format('Y-m-d H:i');
        $userName = auth()->user()->name;

        $newNote = "[{$timestamp}] {$userName}: {$request->note}";

        $application->update([
            'reviewer_notes' => $existingNotes
                ? $existingNotes . "\n\n" . $newNote
                : $newNote,
        ]);

        $this->logActivity($application, 'note_added', [
            'note' => $request->note,
            'added_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.agency-applications.show', $id)
            ->with('success', 'Note added successfully.');
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Get dashboard statistics.
     *
     * @return array
     */
    private function getStatistics(): array
    {
        return [
            'total' => AgencyApplication::count(),
            'pending' => AgencyApplication::pending()->count(),
            'submitted' => AgencyApplication::where('status', AgencyApplication::STATUS_SUBMITTED)->count(),
            'documents_pending' => AgencyApplication::where('status', AgencyApplication::STATUS_PENDING_DOCUMENTS)->count(),
            'compliance_pending' => AgencyApplication::where('status', AgencyApplication::STATUS_PENDING_COMPLIANCE)->count(),
            'approved_this_month' => AgencyApplication::where('status', AgencyApplication::STATUS_APPROVED)
                ->where('approved_at', '>=', now()->startOfMonth())
                ->count(),
            'rejected_this_month' => AgencyApplication::where('status', AgencyApplication::STATUS_REJECTED)
                ->where('rejected_at', '>=', now()->startOfMonth())
                ->count(),
            'unassigned' => AgencyApplication::pending()->whereNull('reviewer_id')->count(),
        ];
    }

    /**
     * Run a specific compliance check.
     *
     * @param AgencyApplication $application
     * @param string $checkType
     * @param string|null $notes
     * @return void
     */
    private function runComplianceCheck(AgencyApplication $application, string $checkType, ?string $notes = null): void
    {
        // Check if this check type already exists
        $check = $application->complianceChecks()
            ->where('check_type', $checkType)
            ->first();

        if (!$check) {
            $check = AgencyComplianceCheck::create([
                'agency_application_id' => $application->id,
                'check_type' => $checkType,
                'name' => AgencyComplianceCheck::getCheckTypeOptions()[$checkType] ?? $checkType,
                'status' => AgencyComplianceCheck::STATUS_IN_PROGRESS,
                'initiated_at' => now(),
                'performed_by' => auth()->id(),
                'is_required' => true,
                'notes' => $notes,
            ]);
        } else {
            $check->start();
            if ($notes) {
                $check->update(['notes' => $notes]);
            }
        }

        $this->logActivity($application, 'compliance_check_started', [
            'check_type' => $checkType,
            'check_id' => $check->id,
            'initiated_by' => auth()->id(),
        ]);
    }

    /**
     * Update a compliance check status.
     *
     * @param Request $request
     * @param AgencyApplication $application
     * @return void
     */
    private function updateComplianceCheckStatus(Request $request, AgencyApplication $application): void
    {
        $check = AgencyComplianceCheck::where('id', $request->check_id)
            ->where('agency_application_id', $application->id)
            ->firstOrFail();

        $oldStatus = $check->status;

        switch ($request->status) {
            case 'passed':
                $check->markPassed(
                    auth()->user(),
                    null,
                    null,
                    $request->notes
                );
                break;

            case 'failed':
                $check->markFailed(
                    auth()->user(),
                    $request->failure_reason ?? 'Check failed',
                    null,
                    null,
                    $request->risk_level ?? 'high',
                    $request->notes
                );
                break;

            case 'manual_review':
                $check->routeToManualReview($request->notes);
                break;

            default:
                $check->update([
                    'status' => $request->status,
                    'notes' => $request->notes,
                ]);
        }

        $this->logActivity($application, 'compliance_check_updated', [
            'check_id' => $check->id,
            'check_type' => $check->check_type,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Run all pending compliance checks.
     *
     * @param AgencyApplication $application
     * @return void
     */
    private function runAllComplianceChecks(AgencyApplication $application): void
    {
        $requiredChecks = AgencyComplianceCheck::getRequiredChecksForJurisdiction(
            $application->registered_country ?? 'US'
        );

        foreach ($requiredChecks as $checkType) {
            $existing = $application->complianceChecks()
                ->where('check_type', $checkType)
                ->first();

            if (!$existing) {
                AgencyComplianceCheck::create([
                    'agency_application_id' => $application->id,
                    'check_type' => $checkType,
                    'name' => AgencyComplianceCheck::getCheckTypeOptions()[$checkType] ?? $checkType,
                    'status' => AgencyComplianceCheck::STATUS_IN_PROGRESS,
                    'initiated_at' => now(),
                    'performed_by' => auth()->id(),
                    'is_required' => true,
                ]);
            } elseif ($existing->isPending()) {
                $existing->start();
            }
        }

        $this->logActivity($application, 'all_compliance_checks_started', [
            'check_types' => $requiredChecks,
            'initiated_by' => auth()->id(),
        ]);
    }

    /**
     * Create agency profile after approval.
     *
     * @param AgencyApplication $application
     * @return void
     */
    private function createAgencyProfile(AgencyApplication $application): void
    {
        // Check if profile already exists
        $existingProfile = \App\Models\AgencyProfile::where('user_id', $application->user_id)->first();

        if ($existingProfile) {
            // Update existing profile
            $existingProfile->update([
                'agency_name' => $application->agency_name,
                'license_number' => $application->license_number,
                'license_verified' => true,
                'business_model' => $application->business_model ?? 'staffing_agency',
                'commission_rate' => $application->proposed_commission_rate ?? 10.00,
            ]);
        } else {
            // Create new profile
            \App\Models\AgencyProfile::create([
                'user_id' => $application->user_id,
                'agency_name' => $application->agency_name,
                'license_number' => $application->license_number,
                'license_verified' => true,
                'business_model' => $application->business_model ?? 'staffing_agency',
                'commission_rate' => $application->proposed_commission_rate ?? 10.00,
            ]);
        }

        // Update user role
        $application->user->update(['role' => 'agency']);
    }

    /**
     * Log activity for the application.
     *
     * @param AgencyApplication $application
     * @param string $action
     * @param array $properties
     * @return void
     */
    private function logActivity(AgencyApplication $application, string $action, array $properties = []): void
    {
        // If using spatie/laravel-activitylog
        if (function_exists('activity')) {
            activity()
                ->performedOn($application)
                ->causedBy(auth()->user())
                ->withProperties($properties)
                ->log($action);
        }

        // Also log to Laravel log for audit trail
        Log::info("Agency Application Activity: {$action}", [
            'application_id' => $application->id,
            'user_id' => auth()->id(),
            'properties' => $properties,
        ]);
    }

    /**
     * Send notification to applicant about status change.
     *
     * @param AgencyApplication $application
     * @param string $notificationType
     * @return void
     */
    private function notifyApplicant(AgencyApplication $application, string $notificationType): void
    {
        try {
            $application->user->notify(new AgencyApplicationStatusChanged($application, $notificationType));
        } catch (\Exception $e) {
            Log::error('Failed to send applicant notification', [
                'application_id' => $application->id,
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
