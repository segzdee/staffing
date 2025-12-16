<?php

namespace App\Services;

use App\Models\AgencyProfile;
use App\Models\User;
use App\Models\AgencyWorker;
use App\Models\ShiftAssignment;
use App\Notifications\AgencyGoLiveReady;
use App\Notifications\AgencyActivated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;

/**
 * AgencyGoLiveService
 *
 * Manages the agency go-live checklist and activation process.
 *
 * Go-Live Requirements:
 * - Profile 100% complete
 * - Stripe Connect configured
 * - At least 5 workers onboarded
 * - All documents verified
 * - Commercial agreement signed
 * - Test shift completed
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 */
class AgencyGoLiveService
{
    protected AgencyComplianceService $complianceService;

    /**
     * Go-live checklist item keys
     */
    public const CHECKLIST_ITEMS = [
        'profile_complete',
        'stripe_configured',
        'workers_onboarded',
        'documents_verified',
        'agreement_signed',
        'test_shift_completed',
    ];

    /**
     * Minimum workers required for go-live
     */
    public const MIN_WORKERS_REQUIRED = 5;

    public function __construct(AgencyComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Generate the complete go-live checklist for an agency.
     *
     * @param int $agencyId
     * @return array
     */
    public function getChecklist(int $agencyId): array
    {
        $agency = AgencyProfile::where('user_id', $agencyId)->first();

        if (!$agency) {
            return [
                'success' => false,
                'error' => 'Agency profile not found.',
                'checklist' => [],
            ];
        }

        $checklist = [];

        // 1. Profile Completeness
        $checklist['profile_complete'] = $this->checkProfileComplete($agency);

        // 2. Stripe Connect Configuration
        $checklist['stripe_configured'] = $this->checkStripeConfigured($agency);

        // 3. Workers Onboarded
        $checklist['workers_onboarded'] = $this->checkWorkersOnboarded($agency);

        // 4. Documents Verified
        $checklist['documents_verified'] = $this->checkDocumentsVerified($agency);

        // 5. Commercial Agreement Signed
        $checklist['agreement_signed'] = $this->checkAgreementSigned($agency);

        // 6. Test Shift Completed
        $checklist['test_shift_completed'] = $this->checkTestShiftCompleted($agency);

        // Calculate overall progress
        $completedCount = collect($checklist)->filter(fn($item) => $item['completed'])->count();
        $totalCount = count($checklist);
        $progressPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        // Get compliance score
        $complianceResult = $this->complianceService->calculateComplianceScore($agency);

        return [
            'success' => true,
            'agency' => [
                'id' => $agency->id,
                'user_id' => $agency->user_id,
                'name' => $agency->agency_name,
                'status' => $agency->verification_status ?? 'pending',
                'is_live' => $agency->is_live ?? false,
                'activated_at' => $agency->activated_at,
            ],
            'checklist' => $checklist,
            'progress' => [
                'completed' => $completedCount,
                'total' => $totalCount,
                'percentage' => $progressPercentage,
            ],
            'compliance' => [
                'score' => $complianceResult['score'],
                'grade' => $complianceResult['grade'],
                'is_compliant' => $complianceResult['is_compliant'],
            ],
            'is_ready' => $completedCount === $totalCount && $complianceResult['is_go_live_ready'],
            'blocking_items' => $this->getBlockingItems($checklist),
            'next_steps' => $this->getRecommendedNextSteps($checklist),
        ];
    }

    /**
     * Check if profile is 100% complete.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkProfileComplete(AgencyProfile $agency): array
    {
        $requiredFields = [
            'agency_name',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'description',
        ];

        $completedFields = [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!empty($agency->$field)) {
                $completedFields[] = $field;
            } else {
                $missingFields[] = $field;
            }
        }

        $isComplete = empty($missingFields);
        $percentage = count($requiredFields) > 0
            ? round((count($completedFields) / count($requiredFields)) * 100)
            : 0;

        return [
            'key' => 'profile_complete',
            'title' => 'Complete Agency Profile',
            'description' => 'Fill in all required profile information.',
            'completed' => $isComplete,
            'percentage' => $percentage,
            'details' => [
                'completed_fields' => count($completedFields),
                'total_fields' => count($requiredFields),
                'missing_fields' => $missingFields,
            ],
            'action_url' => route('agency.profile.edit'),
            'action_label' => $isComplete ? 'View Profile' : 'Complete Profile',
            'priority' => 1,
        ];
    }

    /**
     * Check if Stripe Connect is configured.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkStripeConfigured(AgencyProfile $agency): array
    {
        $hasStripeAccount = !empty($agency->stripe_connect_account_id);
        $isOnboardingComplete = $agency->stripe_onboarding_complete ?? false;
        $canReceivePayouts = $agency->canReceivePayouts();

        $status = 'not_started';
        if ($canReceivePayouts) {
            $status = 'active';
        } elseif ($isOnboardingComplete) {
            $status = 'pending_verification';
        } elseif ($hasStripeAccount) {
            $status = 'incomplete';
        }

        return [
            'key' => 'stripe_configured',
            'title' => 'Set Up Payment Processing',
            'description' => 'Connect your Stripe account to receive commission payments.',
            'completed' => $canReceivePayouts,
            'status' => $status,
            'details' => [
                'has_account' => $hasStripeAccount,
                'onboarding_complete' => $isOnboardingComplete,
                'can_receive_payouts' => $canReceivePayouts,
                'stripe_status' => $agency->stripe_status ?? 'not_started',
            ],
            'action_url' => route('agency.stripe.onboarding'),
            'action_label' => $canReceivePayouts ? 'View Status' : ($hasStripeAccount ? 'Continue Setup' : 'Connect Stripe'),
            'priority' => 2,
        ];
    }

    /**
     * Check if minimum workers are onboarded.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkWorkersOnboarded(AgencyProfile $agency): array
    {
        $activeWorkers = AgencyWorker::where('agency_id', $agency->user_id)
            ->where('status', 'active')
            ->count();

        $pendingWorkers = AgencyWorker::where('agency_id', $agency->user_id)
            ->where('status', 'pending')
            ->count();

        $isComplete = $activeWorkers >= self::MIN_WORKERS_REQUIRED;
        $percentage = min(100, round(($activeWorkers / self::MIN_WORKERS_REQUIRED) * 100));

        return [
            'key' => 'workers_onboarded',
            'title' => 'Onboard Workers',
            'description' => 'Add at least ' . self::MIN_WORKERS_REQUIRED . ' active workers to your agency.',
            'completed' => $isComplete,
            'percentage' => $percentage,
            'details' => [
                'active_workers' => $activeWorkers,
                'pending_workers' => $pendingWorkers,
                'required_workers' => self::MIN_WORKERS_REQUIRED,
                'remaining' => max(0, self::MIN_WORKERS_REQUIRED - $activeWorkers),
            ],
            'action_url' => route('agency.workers.index'),
            'action_label' => $isComplete ? 'Manage Workers' : 'Add Workers',
            'priority' => 3,
        ];
    }

    /**
     * Check if all required documents are verified.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkDocumentsVerified(AgencyProfile $agency): array
    {
        $documentResult = $this->complianceService->checkDocumentsComplete($agency);
        $licenseResult = $this->complianceService->checkBusinessLicense($agency);
        $insuranceResult = $this->complianceService->checkInsuranceCoverage($agency);

        $allVerified = $documentResult['verified'] &&
                       $licenseResult['verified'] &&
                       $insuranceResult['verified'];

        $documentsStatus = [];
        $documentsStatus['business_documents'] = $documentResult['verified'];
        $documentsStatus['business_license'] = $licenseResult['verified'];
        $documentsStatus['insurance'] = $insuranceResult['verified'];

        $verifiedCount = count(array_filter($documentsStatus));
        $totalCount = count($documentsStatus);

        return [
            'key' => 'documents_verified',
            'title' => 'Verify Documents',
            'description' => 'Upload and verify all required business documents.',
            'completed' => $allVerified,
            'percentage' => $totalCount > 0 ? round(($verifiedCount / $totalCount) * 100) : 0,
            'details' => [
                'documents_status' => $documentsStatus,
                'verified_count' => $verifiedCount,
                'total_count' => $totalCount,
                'license_status' => $licenseResult['status'],
                'insurance_status' => $insuranceResult['status'],
            ],
            'action_url' => route('agency.profile.edit'),
            'action_label' => $allVerified ? 'View Documents' : 'Upload Documents',
            'priority' => 4,
        ];
    }

    /**
     * Check if commercial agreement is signed.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkAgreementSigned(AgencyProfile $agency): array
    {
        $isSigned = $agency->agreement_signed ?? false;
        $signedAt = $agency->agreement_signed_at;

        return [
            'key' => 'agreement_signed',
            'title' => 'Sign Commercial Agreement',
            'description' => 'Review and sign the agency partnership agreement.',
            'completed' => $isSigned,
            'details' => [
                'signed' => $isSigned,
                'signed_at' => $signedAt,
                'agreement_version' => $agency->agreement_version ?? '1.0',
            ],
            'action_url' => route('agency.go-live.agreement'),
            'action_label' => $isSigned ? 'View Agreement' : 'Sign Agreement',
            'priority' => 5,
        ];
    }

    /**
     * Check if test shift has been completed.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function checkTestShiftCompleted(AgencyProfile $agency): array
    {
        // Check for any completed shift assignment by agency workers
        $workerIds = AgencyWorker::where('agency_id', $agency->user_id)
            ->pluck('worker_id')
            ->toArray();

        $completedShift = ShiftAssignment::whereIn('worker_id', $workerIds)
            ->where('status', 'completed')
            ->first();

        $hasCompletedShift = !is_null($completedShift);

        // Alternative: Check if agency has marked test shift as complete
        $testShiftMarked = $agency->test_shift_completed ?? false;

        $isComplete = $hasCompletedShift || $testShiftMarked;

        return [
            'key' => 'test_shift_completed',
            'title' => 'Complete Test Shift',
            'description' => 'Have one of your workers complete a test shift successfully.',
            'completed' => $isComplete,
            'details' => [
                'has_completed_shift' => $hasCompletedShift,
                'test_shift_marked' => $testShiftMarked,
                'completed_shift_id' => $completedShift?->id,
                'completed_at' => $completedShift?->checked_out_at,
            ],
            'action_url' => route('agency.shifts.browse'),
            'action_label' => $isComplete ? 'View Shifts' : 'Browse Shifts',
            'priority' => 6,
        ];
    }

    /**
     * Check if agency is ready for go-live.
     *
     * @param int $agencyId
     * @return array
     */
    public function isReadyForGoLive(int $agencyId): array
    {
        $checklistResult = $this->getChecklist($agencyId);

        if (!$checklistResult['success']) {
            return [
                'ready' => false,
                'error' => $checklistResult['error'],
            ];
        }

        $isReady = $checklistResult['is_ready'];
        $blockingItems = $checklistResult['blocking_items'];

        return [
            'ready' => $isReady,
            'progress' => $checklistResult['progress'],
            'compliance_score' => $checklistResult['compliance']['score'],
            'blocking_items' => $blockingItems,
            'message' => $isReady
                ? 'Your agency is ready to go live!'
                : 'Please complete all checklist items before going live.',
        ];
    }

    /**
     * Activate agency (transition to live status).
     *
     * @param int $agencyId
     * @param int|null $approvedBy Admin user ID who approved
     * @return array
     */
    public function activateAgency(int $agencyId, ?int $approvedBy = null): array
    {
        $readinessCheck = $this->isReadyForGoLive($agencyId);

        if (!$readinessCheck['ready']) {
            return [
                'success' => false,
                'error' => 'Agency is not ready for activation.',
                'blocking_items' => $readinessCheck['blocking_items'],
            ];
        }

        try {
            DB::beginTransaction();

            $agency = AgencyProfile::where('user_id', $agencyId)->first();

            // Update agency status
            $agency->update([
                'is_live' => true,
                'verification_status' => 'approved',
                'activated_at' => now(),
                'activated_by' => $approvedBy,
            ]);

            // Update user status
            $user = User::find($agencyId);
            if ($user) {
                $user->update([
                    'status' => 'active',
                    'onboarding_completed' => true,
                ]);
            }

            // Log activation event
            Log::info('Agency activated', [
                'agency_id' => $agency->id,
                'user_id' => $agencyId,
                'approved_by' => $approvedBy,
            ]);

            // Send congratulations notification
            if ($user) {
                try {
                    $user->notify(new AgencyActivated($agency));
                } catch (\Exception $e) {
                    Log::warning('Failed to send activation notification', [
                        'agency_id' => $agency->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Agency activated successfully. Welcome to OvertimeStaff!',
                'agency' => [
                    'id' => $agency->id,
                    'name' => $agency->agency_name,
                    'is_live' => true,
                    'activated_at' => $agency->activated_at,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Agency activation failed', [
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to activate agency. Please try again.',
            ];
        }
    }

    /**
     * Request go-live review (for agencies that believe they are ready).
     *
     * @param int $agencyId
     * @return array
     */
    public function requestGoLive(int $agencyId): array
    {
        $checklistResult = $this->getChecklist($agencyId);

        if (!$checklistResult['success']) {
            return [
                'success' => false,
                'error' => $checklistResult['error'],
            ];
        }

        if (!$checklistResult['is_ready']) {
            return [
                'success' => false,
                'error' => 'Please complete all checklist items before requesting go-live.',
                'blocking_items' => $checklistResult['blocking_items'],
            ];
        }

        try {
            $agency = AgencyProfile::where('user_id', $agencyId)->first();

            // Update status to pending review
            $agency->update([
                'verification_status' => 'pending_review',
                'go_live_requested_at' => now(),
            ]);

            // Notify admins
            $this->notifyAdminsOfGoLiveRequest($agency);

            return [
                'success' => true,
                'message' => 'Go-live request submitted successfully. Our team will review and activate your account within 24-48 hours.',
            ];

        } catch (\Exception $e) {
            Log::error('Go-live request failed', [
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to submit go-live request. Please try again.',
            ];
        }
    }

    /**
     * Mark a specific checklist item as verified (admin action).
     *
     * @param int $agencyId
     * @param string $item
     * @param int $verifiedBy
     * @return array
     */
    public function verifyChecklistItem(int $agencyId, string $item, int $verifiedBy): array
    {
        if (!in_array($item, self::CHECKLIST_ITEMS)) {
            return [
                'success' => false,
                'error' => 'Invalid checklist item.',
            ];
        }

        $agency = AgencyProfile::where('user_id', $agencyId)->first();

        if (!$agency) {
            return [
                'success' => false,
                'error' => 'Agency not found.',
            ];
        }

        try {
            // Store verification in manual_verifications JSON field
            $manualVerifications = $agency->manual_verifications ?? [];
            $manualVerifications[$item] = [
                'verified' => true,
                'verified_at' => now()->toIso8601String(),
                'verified_by' => $verifiedBy,
            ];

            $agency->update([
                'manual_verifications' => $manualVerifications,
            ]);

            return [
                'success' => true,
                'message' => "Checklist item '{$item}' verified successfully.",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to verify checklist item.',
            ];
        }
    }

    /**
     * Get blocking items that prevent go-live.
     *
     * @param array $checklist
     * @return array
     */
    protected function getBlockingItems(array $checklist): array
    {
        $blocking = [];

        foreach ($checklist as $key => $item) {
            if (!$item['completed']) {
                $blocking[] = [
                    'key' => $key,
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'action_url' => $item['action_url'],
                    'action_label' => $item['action_label'],
                    'priority' => $item['priority'],
                ];
            }
        }

        // Sort by priority
        usort($blocking, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $blocking;
    }

    /**
     * Get recommended next steps based on checklist status.
     *
     * @param array $checklist
     * @return array
     */
    protected function getRecommendedNextSteps(array $checklist): array
    {
        $steps = [];

        // Find first incomplete item
        foreach ($checklist as $key => $item) {
            if (!$item['completed']) {
                $steps[] = [
                    'step' => count($steps) + 1,
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'action_url' => $item['action_url'],
                    'action_label' => $item['action_label'],
                ];

                // Return only first 3 steps
                if (count($steps) >= 3) {
                    break;
                }
            }
        }

        return $steps;
    }

    /**
     * Notify admins of go-live request.
     *
     * @param AgencyProfile $agency
     * @return void
     */
    protected function notifyAdminsOfGoLiveRequest(AgencyProfile $agency): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new AgencyGoLiveReady($agency));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of go-live request', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if agency needs attention (for admin dashboard).
     *
     * @param AgencyProfile $agency
     * @return array
     */
    public function getAgencyAttentionStatus(AgencyProfile $agency): array
    {
        $warnings = [];

        // Check for expiring documents
        $complianceResult = $this->complianceService->calculateComplianceScore($agency);

        if (!empty($complianceResult['expires_soon'])) {
            foreach ($complianceResult['expires_soon'] as $expiring) {
                $warnings[] = [
                    'type' => 'expiring_document',
                    'message' => ucfirst(str_replace('_', ' ', $expiring['type'])) . " expires in {$expiring['days_remaining']} days.",
                    'severity' => $expiring['days_remaining'] <= 7 ? 'high' : 'medium',
                ];
            }
        }

        // Check compliance score
        if ($complianceResult['score'] < AgencyComplianceService::SCORE_ACCEPTABLE) {
            $warnings[] = [
                'type' => 'low_compliance',
                'message' => "Compliance score ({$complianceResult['score']}%) is below acceptable threshold.",
                'severity' => 'high',
            ];
        }

        // Check pending go-live request
        if ($agency->verification_status === 'pending_review') {
            $daysPending = Carbon::parse($agency->go_live_requested_at)->diffInDays(now());
            if ($daysPending > 2) {
                $warnings[] = [
                    'type' => 'pending_review',
                    'message' => "Go-live request pending for {$daysPending} days.",
                    'severity' => 'medium',
                ];
            }
        }

        return [
            'needs_attention' => !empty($warnings),
            'warnings' => $warnings,
            'compliance_score' => $complianceResult['score'],
            'is_live' => $agency->is_live ?? false,
        ];
    }
}
