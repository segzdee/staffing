<?php

namespace App\Http\Controllers;

use App\Http\Requests\Privacy\ConsentUpdateRequest;
use App\Http\Requests\Privacy\DataSubjectRequestRequest;
use App\Models\ConsentRecord;
use App\Models\DataSubjectRequest;
use App\Services\DataExportService;
use App\Services\PrivacyComplianceService;
use Illuminate\Http\Request;

/**
 * GLO-005: GDPR/CCPA Compliance - Privacy Controller
 *
 * Handles user-facing privacy operations:
 * - Data subject requests (access, erasure, portability)
 * - Consent management
 * - Data export downloads
 */
class PrivacyController extends Controller
{
    public function __construct(
        protected PrivacyComplianceService $privacyService,
        protected DataExportService $exportService
    ) {
        $this->middleware('auth')->except([
            'showRequestForm',
            'submitRequest',
            'verifyRequest',
            'downloadExport',
        ]);
    }

    /**
     * Display the privacy settings page.
     */
    public function index()
    {
        $user = auth()->user();

        // Get user's consent records
        $consents = ConsentRecord::where('user_id', $user->id)
            ->get()
            ->keyBy('consent_type');

        // Get consent types with current status
        $consentTypes = ConsentRecord::getTypes();
        foreach ($consentTypes as $type => &$info) {
            $consent = $consents->get($type);
            $info['consented'] = $consent?->isActive() ?? false;
            $info['updated_at'] = $consent?->updated_at?->diffForHumans();
        }

        // Get user's pending data requests
        $pendingRequests = DataSubjectRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get completed requests
        $completedRequests = DataSubjectRequest::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'rejected'])
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        return view('settings.privacy', compact(
            'consentTypes',
            'pendingRequests',
            'completedRequests'
        ));
    }

    /**
     * Update user's consent preferences.
     */
    public function updateConsents(ConsentUpdateRequest $request)
    {
        $user = auth()->user();
        $consents = $request->validated()['consents'] ?? [];

        $this->privacyService->updateConsents(
            $user,
            $consents,
            ConsentRecord::SOURCE_SETTINGS
        );

        return redirect()->back()->with('success', 'Your privacy preferences have been updated.');
    }

    /**
     * Show the data request form (public).
     */
    public function showRequestForm(Request $request)
    {
        $types = DataSubjectRequest::getTypes();
        $selectedType = $request->query('type', 'access');

        return view('privacy.request-form', compact('types', 'selectedType'));
    }

    /**
     * Submit a data subject request (public).
     */
    public function submitRequest(DataSubjectRequestRequest $request)
    {
        $validated = $request->validated();

        $dsRequest = match ($validated['type']) {
            'access' => $this->privacyService->createAccessRequest($validated['email']),
            'erasure' => $this->privacyService->createErasureRequest($validated['email']),
            'portability' => $this->privacyService->createPortabilityRequest($validated['email']),
            'rectification' => $this->privacyService->createRectificationRequest(
                $validated['email'],
                $validated['description'] ?? ''
            ),
            'restriction' => $this->privacyService->createRestrictionRequest(
                $validated['email'],
                $validated['description'] ?? ''
            ),
            'objection' => $this->privacyService->createObjectionRequest(
                $validated['email'],
                $validated['description'] ?? ''
            ),
            default => $this->privacyService->createAccessRequest($validated['email']),
        };

        return redirect()->route('privacy.request-submitted', [
            'number' => $dsRequest->request_number,
        ]);
    }

    /**
     * Show the request submitted confirmation page.
     */
    public function showRequestSubmitted(Request $request)
    {
        $requestNumber = $request->query('number');

        return view('privacy.request-submitted', compact('requestNumber'));
    }

    /**
     * Verify a data subject request via email link.
     */
    public function verifyRequest(Request $request, string $requestNumber, string $token)
    {
        $dsRequest = DataSubjectRequest::where('request_number', $requestNumber)->first();

        if (! $dsRequest) {
            return view('privacy.verification-failed', [
                'message' => 'Request not found.',
            ]);
        }

        if ($dsRequest->isVerified()) {
            return view('privacy.verification-already-done', [
                'request' => $dsRequest,
            ]);
        }

        if ($this->privacyService->verifyRequest($dsRequest, $token)) {
            return view('privacy.verification-success', [
                'request' => $dsRequest,
            ]);
        }

        return view('privacy.verification-failed', [
            'message' => 'Invalid or expired verification link.',
        ]);
    }

    /**
     * Download a data export (requires valid token).
     */
    public function downloadExport(Request $request, string $requestNumber, string $token)
    {
        $dsRequest = DataSubjectRequest::where('request_number', $requestNumber)->first();

        if (! $dsRequest) {
            abort(404, 'Request not found.');
        }

        if ($dsRequest->verification_token !== $token) {
            abort(403, 'Invalid token.');
        }

        if (! $dsRequest->export_file_path) {
            abort(404, 'Export file not found.');
        }

        $path = $this->exportService->getExportFile($dsRequest->export_file_path);

        if (! $path || ! file_exists($path)) {
            abort(404, 'Export file not found.');
        }

        return response()->download($path, basename($dsRequest->export_file_path));
    }

    /**
     * Request data export (authenticated users).
     */
    public function requestExport(Request $request)
    {
        $user = auth()->user();

        // Check for existing pending request
        $existingRequest = DataSubjectRequest::where('user_id', $user->id)
            ->where('type', 'access')
            ->whereNotIn('status', ['completed', 'rejected'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('warning', 'You already have a pending data access request.');
        }

        $dsRequest = $this->privacyService->createAccessRequest($user->email);

        return redirect()->back()->with('success', 'Your data access request has been submitted. Please check your email to verify the request.');
    }

    /**
     * Request account deletion (authenticated users).
     */
    public function requestDeletion(Request $request)
    {
        $user = auth()->user();

        // Check for existing pending request
        $existingRequest = DataSubjectRequest::where('user_id', $user->id)
            ->where('type', 'erasure')
            ->whereNotIn('status', ['completed', 'rejected'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('warning', 'You already have a pending account deletion request.');
        }

        $dsRequest = $this->privacyService->createErasureRequest($user->email);

        return redirect()->back()->with('success', 'Your account deletion request has been submitted. Please check your email to verify the request.');
    }

    /**
     * Request data portability (authenticated users).
     */
    public function requestPortability(Request $request)
    {
        $user = auth()->user();

        // Check for existing pending request
        $existingRequest = DataSubjectRequest::where('user_id', $user->id)
            ->where('type', 'portability')
            ->whereNotIn('status', ['completed', 'rejected'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('warning', 'You already have a pending data portability request.');
        }

        $dsRequest = $this->privacyService->createPortabilityRequest($user->email);

        return redirect()->back()->with('success', 'Your data portability request has been submitted. Please check your email to verify the request.');
    }

    /**
     * View user's data request history.
     */
    public function viewRequests()
    {
        $user = auth()->user();

        $requests = DataSubjectRequest::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('settings.privacy-requests', compact('requests'));
    }

    /**
     * Cancel a pending data request.
     */
    public function cancelRequest(Request $request, DataSubjectRequest $dsRequest)
    {
        $user = auth()->user();

        // Verify ownership
        if ($dsRequest->user_id !== $user->id && $dsRequest->email !== $user->email) {
            abort(403, 'You are not authorized to cancel this request.');
        }

        // Can only cancel pending/verifying requests
        if (! in_array($dsRequest->status, ['pending', 'verifying'])) {
            return redirect()->back()->with('error', 'This request cannot be cancelled.');
        }

        $dsRequest->reject('Cancelled by user.');

        return redirect()->back()->with('success', 'Your request has been cancelled.');
    }

    /**
     * Record cookie consent (for cookie banner AJAX).
     */
    public function recordCookieConsent(Request $request)
    {
        $validated = $request->validate([
            'consents' => 'required|array',
            'consents.*' => 'boolean',
        ]);

        $user = auth()->user();
        $sessionId = session()->getId();

        foreach ($validated['consents'] as $type => $consented) {
            if ($user) {
                ConsentRecord::recordForUser(
                    $user->id,
                    $type,
                    (bool) $consented,
                    ConsentRecord::SOURCE_COOKIE_BANNER
                );
            } else {
                ConsentRecord::recordForSession(
                    $sessionId,
                    $type,
                    (bool) $consented,
                    ConsentRecord::SOURCE_COOKIE_BANNER
                );
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get current cookie consent status.
     */
    public function getCookieConsent(Request $request)
    {
        $user = auth()->user();
        $sessionId = session()->getId();

        $consents = [];
        $types = ConsentRecord::getTypes();

        foreach ($types as $type => $info) {
            if ($user) {
                $consents[$type] = ConsentRecord::hasUserConsent($user->id, $type);
            } else {
                $consents[$type] = ConsentRecord::hasSessionConsent($sessionId, $type);
            }
        }

        return response()->json([
            'consents' => $consents,
            'types' => $types,
        ]);
    }
}
