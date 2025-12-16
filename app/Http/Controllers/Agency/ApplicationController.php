<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Agency Application Controller
 *
 * AGY-REG-002: Handles agency application management after submission
 *
 * Features:
 * - View application status
 * - View application details
 * - Upload additional documents
 * - E-signature submission for agreements
 */
class ApplicationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('agency')->except(['trackStatus']);
    }

    /**
     * Show the application status dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            return redirect()->route('agency.register.start')
                ->with('error', 'No agency application found. Please start the registration process.');
        }

        // Get application status details
        $status = $this->getApplicationStatus($agencyProfile);

        // Get application timeline
        $timeline = $this->getApplicationTimeline($agencyProfile);

        // Get any pending requirements
        $pendingRequirements = $this->getPendingRequirements($agencyProfile);

        return view('agency.application.index', [
            'agencyProfile' => $agencyProfile,
            'status' => $status,
            'timeline' => $timeline,
            'pendingRequirements' => $pendingRequirements,
        ]);
    }

    /**
     * Show application details.
     */
    public function show(int $id): View|JsonResponse
    {
        $user = Auth::user();

        // Ensure user can only view their own application
        $agencyProfile = AgencyProfile::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Parse stored application metadata
        $applicationData = $this->parseApplicationData($agencyProfile);

        // Get uploaded documents
        $documents = $this->getUploadedDocuments($user);

        // Get verification history
        $verificationHistory = $this->getVerificationHistory($agencyProfile);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'application' => [
                    'id' => $agencyProfile->id,
                    'agency_name' => $agencyProfile->agency_name,
                    'status' => $agencyProfile->verification_status,
                    'created_at' => $agencyProfile->created_at,
                    'data' => $applicationData,
                    'documents' => $documents,
                ],
            ]);
        }

        return view('agency.application.show', [
            'agencyProfile' => $agencyProfile,
            'applicationData' => $applicationData,
            'documents' => $documents,
            'verificationHistory' => $verificationHistory,
        ]);
    }

    /**
     * Track application status (public, using tracking ID).
     */
    public function trackStatus(Request $request): View|JsonResponse
    {
        $request->validate([
            'tracking_id' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        // Find application by tracking ID (agency profile ID) and email
        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->agencyProfile) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found. Please check your tracking ID and email.',
                ], 404);
            }

            return view('agency.application.track', [
                'error' => 'Application not found. Please check your tracking ID and email.',
            ]);
        }

        // Verify tracking ID matches
        if ($user->agencyProfile->id != $request->tracking_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid tracking ID.',
                ], 404);
            }

            return view('agency.application.track', [
                'error' => 'Invalid tracking ID.',
            ]);
        }

        $status = $this->getApplicationStatus($user->agencyProfile);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        }

        return view('agency.application.track-result', [
            'agencyProfile' => $user->agencyProfile,
            'status' => $status,
        ]);
    }

    /**
     * Handle document upload for existing application.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No agency application found.',
            ], 404);
        }

        try {
            $file = $request->file('document');
            $documentType = $request->input('document_type');

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Store document
            $path = $file->storeAs(
                'agency-documents/' . $user->id . '/' . $documentType,
                $filename,
                'local'
            );

            // Log the upload
            Log::info('Agency document uploaded', [
                'user_id' => $user->id,
                'agency_id' => $agencyProfile->id,
                'document_type' => $documentType,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);

            // Update verification notes with new document info
            $verificationNotes = json_decode($agencyProfile->verification_notes ?? '{}', true);
            $verificationNotes['additional_documents'] = $verificationNotes['additional_documents'] ?? [];
            $verificationNotes['additional_documents'][] = [
                'type' => $documentType,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'description' => $request->input('description'),
                'uploaded_at' => now()->toIso8601String(),
            ];
            $agencyProfile->update([
                'verification_notes' => json_encode($verificationNotes),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'document' => [
                    'type' => $documentType,
                    'name' => $file->getClientOriginalName(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'uploaded_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Agency document upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete an uploaded document.
     */
    public function deleteDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document_path' => 'required|string',
        ]);

        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No agency application found.',
            ], 404);
        }

        $documentPath = $request->input('document_path');

        // Ensure the document belongs to this user
        if (!str_starts_with($documentPath, 'agency-documents/' . $user->id . '/')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            // Delete file
            if (Storage::disk('local')->exists($documentPath)) {
                Storage::disk('local')->delete($documentPath);
            }

            // Update verification notes
            $verificationNotes = json_decode($agencyProfile->verification_notes ?? '{}', true);
            if (isset($verificationNotes['additional_documents'])) {
                $verificationNotes['additional_documents'] = array_filter(
                    $verificationNotes['additional_documents'],
                    fn($doc) => ($doc['path'] ?? '') !== $documentPath
                );
                $agencyProfile->update([
                    'verification_notes' => json_encode($verificationNotes),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Agency document deletion failed', [
                'user_id' => $user->id,
                'document_path' => $documentPath,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document.',
            ], 500);
        }
    }

    /**
     * Show the agreement signing page.
     */
    public function showAgreement(): View
    {
        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            return redirect()->route('agency.register.start')
                ->with('error', 'No agency application found.');
        }

        // Get agreement content based on selected tier
        $tierData = $this->getTierData($agencyProfile->business_model ?? 'standard');

        return view('agency.application.agreement', [
            'agencyProfile' => $agencyProfile,
            'agreement' => $this->getAgreementContent($agencyProfile),
            'tierData' => $tierData,
        ]);
    }

    /**
     * Handle e-signature submission.
     */
    public function signAgreement(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'title' => 'required|string|max:100',
            'signature' => 'required|string', // Base64 encoded signature image
            'agree_terms' => 'required|accepted',
            'agree_privacy' => 'required|accepted',
            'agree_commercial' => 'required|accepted',
        ]);

        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No agency application found.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Store signature image
            $signaturePath = $this->storeSignature(
                $user->id,
                $request->input('signature')
            );

            // Create e-signature record
            $signatureData = [
                'signed_by' => $request->input('full_name'),
                'signer_title' => $request->input('title'),
                'signer_email' => $user->email,
                'signature_path' => $signaturePath,
                'signed_at' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'agreements_accepted' => [
                    'terms_of_service' => true,
                    'privacy_policy' => true,
                    'commercial_terms' => true,
                ],
                'agreement_version' => '1.0',
            ];

            // Update verification notes with signature
            $verificationNotes = json_decode($agencyProfile->verification_notes ?? '{}', true);
            $verificationNotes['e_signature'] = $signatureData;
            $verificationNotes['agreement_signed_at'] = now()->toIso8601String();

            // Update agency profile
            $agencyProfile->update([
                'verification_notes' => json_encode($verificationNotes),
                'verification_status' => 'pending_review', // Move to review queue
            ]);

            // Log the signature event
            Log::info('Agency agreement signed', [
                'user_id' => $user->id,
                'agency_id' => $agencyProfile->id,
                'signed_by' => $request->input('full_name'),
                'ip_address' => $request->ip(),
            ]);

            // Notify admins that agreement is signed and ready for review
            $this->notifyAdminsAgreementSigned($user, $agencyProfile);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Agreement signed successfully. Your application is now pending review.',
                'redirect' => route('agency.application.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Agreement signing failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process your signature. Please try again.',
            ], 500);
        }
    }

    /**
     * Download a copy of the signed agreement.
     */
    public function downloadAgreement(): \Symfony\Component\HttpFoundation\Response
    {
        $user = Auth::user();
        $agencyProfile = $user->agencyProfile;

        if (!$agencyProfile) {
            abort(404, 'No agency application found.');
        }

        $verificationNotes = json_decode($agencyProfile->verification_notes ?? '{}', true);

        if (empty($verificationNotes['e_signature'])) {
            abort(404, 'No signed agreement found.');
        }

        // Generate PDF of the agreement (would need a PDF library)
        // For now, return JSON of agreement data
        return response()->json([
            'agency_name' => $agencyProfile->agency_name,
            'agreement' => $this->getAgreementContent($agencyProfile),
            'signature' => $verificationNotes['e_signature'],
        ]);
    }

    /**
     * Get application status details.
     */
    protected function getApplicationStatus(AgencyProfile $profile): array
    {
        $status = $profile->verification_status ?? 'pending';

        $statusDetails = [
            'pending' => [
                'label' => 'Application Submitted',
                'description' => 'Your application has been received and is awaiting review.',
                'color' => 'yellow',
                'icon' => 'clock',
                'next_steps' => [
                    'Ensure all required documents are uploaded',
                    'Sign the partnership agreement if you haven\'t already',
                    'Our team will review your application within 2-3 business days',
                ],
            ],
            'pending_review' => [
                'label' => 'Under Review',
                'description' => 'Your application is being reviewed by our team.',
                'color' => 'blue',
                'icon' => 'search',
                'next_steps' => [
                    'Our team is reviewing your submitted documents',
                    'You may be contacted if additional information is needed',
                    'Expected completion within 1-2 business days',
                ],
            ],
            'pending_documents' => [
                'label' => 'Additional Documents Required',
                'description' => 'Please upload the requested additional documents.',
                'color' => 'orange',
                'icon' => 'document',
                'next_steps' => [
                    'Review the requested documents below',
                    'Upload all required documents',
                    'Your application will continue processing once documents are received',
                ],
            ],
            'verified' => [
                'label' => 'Approved',
                'description' => 'Congratulations! Your agency application has been approved.',
                'color' => 'green',
                'icon' => 'check-circle',
                'next_steps' => [
                    'Complete your agency profile setup',
                    'Add your workers to the platform',
                    'Start browsing available shifts',
                ],
            ],
            'rejected' => [
                'label' => 'Not Approved',
                'description' => 'Unfortunately, your application was not approved at this time.',
                'color' => 'red',
                'icon' => 'x-circle',
                'next_steps' => [
                    'Review the rejection reason provided',
                    'Contact support if you have questions',
                    'You may reapply after addressing the concerns',
                ],
            ],
        ];

        return array_merge(
            $statusDetails[$status] ?? $statusDetails['pending'],
            [
                'status_code' => $status,
                'submitted_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ]
        );
    }

    /**
     * Get application timeline.
     */
    protected function getApplicationTimeline(AgencyProfile $profile): array
    {
        $timeline = [];
        $verificationNotes = json_decode($profile->verification_notes ?? '{}', true);

        // Application submitted
        $timeline[] = [
            'event' => 'Application Submitted',
            'description' => 'Your agency registration was submitted.',
            'timestamp' => $profile->created_at,
            'completed' => true,
        ];

        // Documents uploaded
        if (!empty($verificationNotes['additional_documents'])) {
            $lastDoc = end($verificationNotes['additional_documents']);
            $timeline[] = [
                'event' => 'Documents Uploaded',
                'description' => 'Additional documents were uploaded.',
                'timestamp' => $lastDoc['uploaded_at'] ?? null,
                'completed' => true,
            ];
        }

        // Agreement signed
        if (!empty($verificationNotes['e_signature'])) {
            $timeline[] = [
                'event' => 'Agreement Signed',
                'description' => 'Partnership agreement was signed electronically.',
                'timestamp' => $verificationNotes['agreement_signed_at'] ?? null,
                'completed' => true,
            ];
        }

        // Under review
        if ($profile->verification_status === 'pending_review') {
            $timeline[] = [
                'event' => 'Under Review',
                'description' => 'Application is being reviewed by our team.',
                'timestamp' => null,
                'completed' => false,
                'current' => true,
            ];
        }

        // Verified/Approved
        if ($profile->verification_status === 'verified') {
            $timeline[] = [
                'event' => 'Application Approved',
                'description' => 'Your agency has been verified and approved.',
                'timestamp' => $profile->verified_at,
                'completed' => true,
            ];
        }

        // Rejected
        if ($profile->verification_status === 'rejected') {
            $timeline[] = [
                'event' => 'Application Not Approved',
                'description' => 'Your application was not approved.',
                'timestamp' => $profile->updated_at,
                'completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Get pending requirements.
     */
    protected function getPendingRequirements(AgencyProfile $profile): array
    {
        $requirements = [];
        $verificationNotes = json_decode($profile->verification_notes ?? '{}', true);

        // Check if agreement is signed
        if (empty($verificationNotes['e_signature'])) {
            $requirements[] = [
                'type' => 'agreement',
                'title' => 'Sign Partnership Agreement',
                'description' => 'Please review and sign the partnership agreement to continue.',
                'action' => route('agency.application.agreement'),
                'action_label' => 'Sign Agreement',
                'priority' => 'high',
            ];
        }

        // Check for requested documents
        if ($profile->verification_status === 'pending_documents') {
            $requestedDocs = $verificationNotes['requested_documents'] ?? [];
            foreach ($requestedDocs as $doc) {
                $requirements[] = [
                    'type' => 'document',
                    'title' => 'Upload ' . ($doc['name'] ?? 'Document'),
                    'description' => $doc['reason'] ?? 'Additional documentation required.',
                    'action' => route('agency.application.index') . '#documents',
                    'action_label' => 'Upload Document',
                    'priority' => 'high',
                ];
            }
        }

        return $requirements;
    }

    /**
     * Parse application data from profile.
     */
    protected function parseApplicationData(AgencyProfile $profile): array
    {
        $verificationNotes = json_decode($profile->verification_notes ?? '{}', true);

        return [
            'business_info' => [
                'agency_name' => $profile->agency_name,
                'registration_number' => $verificationNotes['registration_number'] ?? $profile->business_registration_number,
                'agency_type' => $verificationNotes['agency_type'] ?? null,
                'years_in_business' => $verificationNotes['years_in_business'] ?? null,
            ],
            'contact_info' => [
                'address' => $profile->address,
                'city' => $profile->city,
                'state' => $profile->state,
                'postal_code' => $profile->zip_code,
                'country' => $profile->country,
                'phone' => $profile->phone,
                'website' => $profile->website,
            ],
            'partnership' => [
                'tier' => $verificationNotes['partnership_tier'] ?? $profile->business_model ?? 'standard',
                'commission_rate' => $profile->commission_rate,
            ],
            'worker_pool' => [
                'industries' => $verificationNotes['industries'] ?? json_decode($profile->specializations ?? '[]', true),
                'worker_count_range' => $verificationNotes['worker_count_range'] ?? null,
                'total_workers' => $profile->total_workers,
            ],
            'references' => $verificationNotes['references'] ?? [],
            'submitted_at' => $verificationNotes['submitted_at'] ?? $profile->created_at->toIso8601String(),
        ];
    }

    /**
     * Get uploaded documents for agency.
     */
    protected function getUploadedDocuments(User $user): array
    {
        $documents = [];
        $basePath = 'agency-documents/' . $user->id;

        // Standard document types
        $documentTypes = [
            'business_license' => 'Business License',
            'insurance_certificate' => 'Insurance Certificate',
            'tax_id' => 'Tax ID Document',
        ];

        foreach ($documentTypes as $type => $name) {
            $typePath = $basePath . '/' . $type;

            if (Storage::disk('local')->exists($typePath)) {
                $files = Storage::disk('local')->files($typePath);

                if (!empty($files)) {
                    $documents[] = [
                        'type' => $type,
                        'name' => $name,
                        'path' => $files[0],
                        'uploaded' => true,
                    ];
                }
            } else {
                $documents[] = [
                    'type' => $type,
                    'name' => $name,
                    'uploaded' => false,
                ];
            }
        }

        // Get additional documents from verification notes
        $agencyProfile = $user->agencyProfile;
        if ($agencyProfile) {
            $verificationNotes = json_decode($agencyProfile->verification_notes ?? '{}', true);
            $additionalDocs = $verificationNotes['additional_documents'] ?? [];

            foreach ($additionalDocs as $doc) {
                $documents[] = [
                    'type' => $doc['type'] ?? 'other',
                    'name' => $doc['original_name'] ?? 'Document',
                    'path' => $doc['path'] ?? null,
                    'description' => $doc['description'] ?? null,
                    'uploaded' => true,
                    'uploaded_at' => $doc['uploaded_at'] ?? null,
                ];
            }
        }

        return $documents;
    }

    /**
     * Get verification history.
     */
    protected function getVerificationHistory(AgencyProfile $profile): array
    {
        // This would typically come from a dedicated verification_logs table
        // For now, return a basic history based on status changes
        $history = [];

        $history[] = [
            'action' => 'Application Submitted',
            'performed_by' => 'System',
            'timestamp' => $profile->created_at,
            'notes' => 'Agency registration application submitted.',
        ];

        if ($profile->verification_status === 'verified') {
            $history[] = [
                'action' => 'Application Approved',
                'performed_by' => 'Admin',
                'timestamp' => $profile->verified_at ?? $profile->updated_at,
                'notes' => 'Agency application approved.',
            ];
        }

        if ($profile->verification_status === 'rejected') {
            $history[] = [
                'action' => 'Application Rejected',
                'performed_by' => 'Admin',
                'timestamp' => $profile->updated_at,
                'notes' => 'Agency application was not approved.',
            ];
        }

        return array_reverse($history);
    }

    /**
     * Get tier data.
     */
    protected function getTierData(string $tier): array
    {
        $tiers = [
            'standard' => [
                'name' => 'Standard',
                'price' => 0,
                'commission' => 15,
            ],
            'professional' => [
                'name' => 'Professional',
                'price' => 99,
                'commission' => 12,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 299,
                'commission' => 8,
            ],
        ];

        return $tiers[$tier] ?? $tiers['standard'];
    }

    /**
     * Get agreement content.
     */
    protected function getAgreementContent(AgencyProfile $profile): array
    {
        return [
            'title' => 'OvertimeStaff Agency Partnership Agreement',
            'version' => '1.0',
            'effective_date' => now()->format('F j, Y'),
            'sections' => [
                [
                    'title' => '1. Partnership Overview',
                    'content' => 'This Agreement is entered into between OvertimeStaff ("Platform") and ' .
                        $profile->agency_name . ' ("Agency") for the purpose of establishing a staffing partnership.',
                ],
                [
                    'title' => '2. Agency Obligations',
                    'content' => 'The Agency agrees to: (a) maintain accurate records of all workers; ' .
                        '(b) ensure workers meet platform requirements; (c) comply with all applicable laws; ' .
                        '(d) maintain required insurance coverage.',
                ],
                [
                    'title' => '3. Platform Services',
                    'content' => 'The Platform will provide: (a) access to shift marketplace; ' .
                        '(b) worker management tools; (c) payment processing; (d) analytics and reporting.',
                ],
                [
                    'title' => '4. Commission and Payment',
                    'content' => 'Commission rate: ' . $profile->commission_rate . '%. ' .
                        'Payments will be processed weekly via the selected payout method.',
                ],
                [
                    'title' => '5. Term and Termination',
                    'content' => 'This agreement is effective upon signing and continues until terminated ' .
                        'by either party with 30 days written notice.',
                ],
                [
                    'title' => '6. Confidentiality',
                    'content' => 'Both parties agree to maintain confidentiality of proprietary information ' .
                        'and business data shared under this agreement.',
                ],
                [
                    'title' => '7. Liability',
                    'content' => 'Each party shall be responsible for their own acts and omissions. ' .
                        'The Platform\'s liability is limited to the fees paid in the preceding 12 months.',
                ],
            ],
        ];
    }

    /**
     * Store signature image.
     */
    protected function storeSignature(int $userId, string $signatureBase64): string
    {
        // Remove data URI prefix if present
        $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureBase64);
        $signatureData = base64_decode($signatureData);

        // Generate filename
        $filename = 'signature_' . now()->timestamp . '.png';
        $path = 'agency-documents/' . $userId . '/signatures/' . $filename;

        // Store the signature
        Storage::disk('local')->put($path, $signatureData);

        return $path;
    }

    /**
     * Notify admins that agreement is signed.
     */
    protected function notifyAdminsAgreementSigned(User $user, AgencyProfile $profile): void
    {
        $admins = User::where('user_type', 'admin')->get();

        foreach ($admins as $admin) {
            Log::info('Agency agreement signed notification', [
                'admin_id' => $admin->id,
                'agency_user_id' => $user->id,
                'agency_name' => $profile->agency_name,
            ]);
        }
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
