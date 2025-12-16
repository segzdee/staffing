<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\InitiateBackgroundCheckRequest;
use App\Http\Requests\Worker\SubmitConsentRequest;
use App\Http\Requests\Worker\RespondToAdjudicationRequest;
use App\Models\AdjudicationCase;
use App\Models\BackgroundCheck;
use App\Models\BackgroundCheckConsent;
use App\Services\BackgroundCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * STAFF-REG-006: Worker Background Check Controller
 *
 * Handles background check initiation, consent submission,
 * and status checking for workers.
 */
class BackgroundCheckController extends Controller
{
    protected BackgroundCheckService $bgCheckService;

    public function __construct(BackgroundCheckService $bgCheckService)
    {
        $this->middleware(['auth', 'worker']);
        $this->bgCheckService = $bgCheckService;
    }

    /**
     * Get background check requirements for a jurisdiction.
     *
     * GET /api/worker/background-check/requirements/{jurisdiction}
     */
    public function getRequirements(string $jurisdiction): JsonResponse
    {
        try {
            $requirements = $this->bgCheckService->getCheckRequirements(strtoupper($jurisdiction));

            return response()->json([
                'success' => true,
                'data' => $requirements,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available check types for a jurisdiction.
     *
     * GET /api/worker/background-check/types/{jurisdiction}
     */
    public function getCheckTypes(string $jurisdiction): JsonResponse
    {
        $types = $this->bgCheckService->getSupportedCheckTypes(strtoupper($jurisdiction));

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Initiate a background check.
     *
     * POST /api/worker/background-check/initiate
     */
    public function initiateBackgroundCheck(InitiateBackgroundCheckRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        try {
            $check = $this->bgCheckService->initiateCheck(
                $user,
                strtoupper($validated['jurisdiction']),
                $validated['check_type'],
                $validated['billed_to'] ?? null
            );

            // Get consent requirements
            $consentStatus = $this->getConsentDetails($check);

            return response()->json([
                'success' => true,
                'message' => 'Background check initiated',
                'data' => [
                    'check_id' => $check->id,
                    'status' => $check->status,
                    'status_name' => $check->status_name,
                    'jurisdiction' => $check->jurisdiction,
                    'check_type' => $check->check_type,
                    'check_type_name' => $check->check_type_name,
                    'provider' => $check->provider,
                    'components' => $check->check_components,
                    'cost' => $check->cost,
                    'currency' => $check->cost_currency,
                    'consent_requirements' => $consentStatus,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Background check initiation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate background check',
            ], 500);
        }
    }

    /**
     * Get consent form content.
     *
     * GET /api/worker/background-check/{id}/consent-forms
     */
    public function getConsentForms(int $id): JsonResponse
    {
        $user = Auth::user();

        $check = BackgroundCheck::where('id', $id)
            ->where('user_id', $user->id)
            ->with('consents')
            ->firstOrFail();

        $forms = $check->consents->map(function ($consent) {
            return [
                'id' => $consent->id,
                'consent_type' => $consent->consent_type,
                'consent_type_name' => $consent->consent_type_name,
                'consented' => $consent->consented,
                'consented_at' => $consent->consented_at?->toDateTimeString(),
                'disclosure_text' => $this->getDisclosureText($consent->consent_type),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'check_id' => $check->id,
                'check_type' => $check->check_type_name,
                'forms' => $forms,
            ],
        ]);
    }

    /**
     * Submit consent for a background check.
     *
     * POST /api/worker/background-check/consent
     */
    public function submitConsent(SubmitConsentRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $check = BackgroundCheck::where('id', $validated['check_id'])
            ->where('user_id', $user->id)
            ->where('status', BackgroundCheck::STATUS_PENDING_CONSENT)
            ->firstOrFail();

        try {
            $consent = $this->bgCheckService->recordConsent(
                $check,
                $validated['consent_type'],
                $validated['signature_type'],
                $validated['signature_data'] ?? null,
                $validated['signatory_name'] ?? null
            );

            // Refresh check to get updated status
            $check->refresh();

            // Check if all consents are received
            $allConsentsReceived = $check->status === BackgroundCheck::STATUS_CONSENT_RECEIVED;

            return response()->json([
                'success' => true,
                'message' => 'Consent recorded successfully',
                'data' => [
                    'consent_id' => $consent->id,
                    'consent_type' => $consent->consent_type_name,
                    'check_status' => $check->status,
                    'all_consents_received' => $allConsentsReceived,
                    'next_step' => $allConsentsReceived
                        ? 'Ready to submit for processing'
                        : 'Additional consent required',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Consent submission failed', [
                'check_id' => $check->id,
                'consent_type' => $validated['consent_type'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record consent',
            ], 500);
        }
    }

    /**
     * Submit check to provider after all consents received.
     *
     * POST /api/worker/background-check/{id}/submit
     */
    public function submitToProvider(int $id): JsonResponse
    {
        $user = Auth::user();

        $check = BackgroundCheck::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', BackgroundCheck::STATUS_CONSENT_RECEIVED)
            ->firstOrFail();

        try {
            $result = $this->bgCheckService->submitToProvider($check);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'Background check submitted for processing'
                    : ($result['error'] ?? 'Submission failed'),
                'data' => [
                    'check_id' => $check->id,
                    'status' => $check->fresh()->status,
                    'invitation_url' => $result['invitation_url'] ?? null,
                    'simulated' => $result['simulated'] ?? false,
                ],
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Background check submission failed', [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit background check',
            ], 500);
        }
    }

    /**
     * Get background check status for current user.
     *
     * GET /api/worker/background-check/status
     */
    public function getCheckStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        $jurisdiction = $request->query('jurisdiction');

        $status = $this->bgCheckService->getCheckStatus(
            $user,
            $jurisdiction ? strtoupper($jurisdiction) : null
        );

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get details of a specific background check.
     *
     * GET /api/worker/background-check/{id}
     */
    public function getCheck(int $id): JsonResponse
    {
        $user = Auth::user();

        $check = BackgroundCheck::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['consents', 'adjudicationCases'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $check->id,
                'jurisdiction' => $check->jurisdiction,
                'provider' => $check->provider,
                'check_type' => $check->check_type,
                'check_type_name' => $check->check_type_name,
                'components' => $check->check_components,
                'status' => $check->status,
                'status_name' => $check->status_name,
                'result' => $check->result,
                'adjudication_status' => $check->adjudication_status,
                'submitted_at' => $check->submitted_at?->toDateTimeString(),
                'completed_at' => $check->completed_at?->toDateTimeString(),
                'expires_at' => $check->expires_at?->toDateTimeString(),
                'is_expired' => $check->isExpired(),
                'consent_status' => $this->getConsentDetails($check),
                'adjudication_cases' => $check->adjudicationCases->map(fn($case) => [
                    'id' => $case->id,
                    'case_number' => $case->case_number,
                    'case_type' => $case->case_type_name,
                    'status' => $case->status_name,
                    'requires_response' => $case->status === AdjudicationCase::STATUS_PENDING_WORKER_RESPONSE,
                    'waiting_period_ends_at' => $case->waiting_period_ends_at?->toDateString(),
                ]),
            ],
        ]);
    }

    /**
     * Get pending adjudication cases requiring worker response.
     *
     * GET /api/worker/background-check/adjudication-cases
     */
    public function getAdjudicationCases(): JsonResponse
    {
        $user = Auth::user();

        $cases = AdjudicationCase::where('user_id', $user->id)
            ->whereIn('status', [
                AdjudicationCase::STATUS_PENDING_WORKER_RESPONSE,
                AdjudicationCase::STATUS_PRE_ADVERSE_ACTION,
                AdjudicationCase::STATUS_WAITING_PERIOD,
            ])
            ->with('backgroundCheck')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cases->map(fn($case) => [
                'id' => $case->id,
                'case_number' => $case->case_number,
                'case_type' => $case->case_type_name,
                'status' => $case->status,
                'status_name' => $case->status_name,
                'severity' => $case->severity,
                'requires_response' => $case->status === AdjudicationCase::STATUS_PENDING_WORKER_RESPONSE,
                'waiting_period_ends_at' => $case->waiting_period_ends_at?->toDateString(),
                'background_check' => [
                    'id' => $case->backgroundCheck->id,
                    'check_type' => $case->backgroundCheck->check_type_name,
                    'jurisdiction' => $case->backgroundCheck->jurisdiction,
                ],
                'created_at' => $case->created_at->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Respond to an adjudication case.
     *
     * POST /api/worker/background-check/adjudication/{id}/respond
     */
    public function respondToAdjudication(int $id, RespondToAdjudicationRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $case = AdjudicationCase::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', AdjudicationCase::STATUS_PENDING_WORKER_RESPONSE)
            ->firstOrFail();

        try {
            // Handle document uploads if provided
            $documentRefs = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    // Store documents securely
                    $path = $file->store(
                        "adjudication-documents/{$user->id}/{$case->id}",
                        config('background_check.storage_disk', 's3')
                    );
                    $documentRefs[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toIso8601String(),
                    ];
                }
            }

            $case->recordWorkerResponse(
                $validated['response'],
                !empty($documentRefs) ? $documentRefs : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Response submitted successfully',
                'data' => [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'status' => $case->status_name,
                    'documents_uploaded' => count($documentRefs),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Adjudication response failed', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit response',
            ], 500);
        }
    }

    /**
     * Check if user has valid background check for jurisdiction.
     *
     * GET /api/worker/background-check/valid/{jurisdiction}
     */
    public function hasValidCheck(string $jurisdiction, Request $request): JsonResponse
    {
        $user = Auth::user();
        $jurisdiction = strtoupper($jurisdiction);
        $checkType = $request->query('check_type');

        $isValid = $this->bgCheckService->hasValidCheck($user, $jurisdiction, $checkType);

        return response()->json([
            'success' => true,
            'data' => [
                'jurisdiction' => $jurisdiction,
                'check_type' => $checkType,
                'has_valid_check' => $isValid,
            ],
        ]);
    }

    /**
     * Get consent details for a check.
     */
    protected function getConsentDetails(BackgroundCheck $check): array
    {
        $consents = $check->consents;

        return [
            'required' => $consents->count(),
            'received' => $consents->where('consented', true)->count(),
            'complete' => $consents->every(fn($c) => $c->consented),
            'pending' => $consents->where('consented', false)->map(fn($c) => [
                'id' => $c->id,
                'type' => $c->consent_type,
                'type_name' => $c->consent_type_name,
            ])->values()->toArray(),
        ];
    }

    /**
     * Get disclosure text for consent type.
     */
    protected function getDisclosureText(string $consentType): ?string
    {
        return match ($consentType) {
            BackgroundCheckConsent::TYPE_FCRA_DISCLOSURE => BackgroundCheckConsent::getFCRADisclosureText(),
            BackgroundCheckConsent::TYPE_FCRA_AUTHORIZATION => BackgroundCheckConsent::getFCRAAuthorizationText(),
            BackgroundCheckConsent::TYPE_DBS_CONSENT => config('background_check.dbs_consent_text'),
            BackgroundCheckConsent::TYPE_DATA_PROCESSING => config('background_check.data_processing_text'),
            default => config('background_check.general_consent_text'),
        };
    }
}
