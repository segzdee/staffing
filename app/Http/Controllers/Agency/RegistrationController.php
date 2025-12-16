<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\RegistrationStepRequest;
use App\Models\AgencyProfile;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Agency Registration Controller
 *
 * AGY-REG-002: Handles 8-step agency registration flow
 *
 * Steps:
 * 1. Business Information (name, registration number, type)
 * 2. Contact Details (address, phone, email)
 * 3. Document Upload (license, insurance, tax ID)
 * 4. Partnership Tier Selection (standard/professional/enterprise)
 * 5. Worker Pool Details (existing workers count, industries)
 * 6. References (2-3 business references)
 * 7. Commercial Terms Review
 * 8. Final Review & Submit
 */
class RegistrationController extends Controller
{
    /**
     * Total number of registration steps.
     */
    protected const TOTAL_STEPS = 8;

    /**
     * Session key for storing registration data.
     */
    protected const SESSION_KEY = 'agency_registration';

    /**
     * Partnership tiers configuration.
     */
    protected array $partnershipTiers = [
        'standard' => [
            'name' => 'Standard',
            'price' => 0,
            'commission' => 15,
            'features' => [
                'Up to 50 workers',
                'Basic shift matching',
                'Email support',
                'Standard payouts (3-5 days)',
                'Basic analytics dashboard',
            ],
            'limits' => [
                'max_workers' => 50,
                'priority_support' => false,
                'api_access' => false,
                'custom_branding' => false,
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'price' => 99,
            'commission' => 12,
            'features' => [
                'Up to 200 workers',
                'Advanced AI matching',
                'Priority email & chat support',
                'Fast payouts (1-2 days)',
                'Advanced analytics & reports',
                'API access',
                'Bulk worker import',
            ],
            'limits' => [
                'max_workers' => 200,
                'priority_support' => true,
                'api_access' => true,
                'custom_branding' => false,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 299,
            'commission' => 8,
            'features' => [
                'Unlimited workers',
                'Premium AI matching with priority',
                'Dedicated account manager',
                'Same-day payouts',
                'Custom analytics & reporting',
                'Full API access',
                'White-label options',
                'Custom integrations',
                'SLA guarantees',
            ],
            'limits' => [
                'max_workers' => null,
                'priority_support' => true,
                'api_access' => true,
                'custom_branding' => true,
            ],
        ],
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Guest middleware for registration, except for authenticated agency completing registration
        $this->middleware('guest')->except(['showStep', 'saveStep', 'previousStep', 'submitApplication']);
    }

    /**
     * Show the agency registration landing page.
     */
    public function index(): View
    {
        return view('agency.registration.index', [
            'tiers' => $this->partnershipTiers,
        ]);
    }

    /**
     * Start a new registration flow.
     */
    public function start(): View
    {
        // Clear any existing registration data
        session()->forget(self::SESSION_KEY);

        // Initialize registration session
        session()->put(self::SESSION_KEY, [
            'current_step' => 1,
            'started_at' => now()->toIso8601String(),
            'data' => [],
        ]);

        return $this->showStep(1);
    }

    /**
     * Show a specific registration step.
     */
    public function showStep(int $step): View|JsonResponse
    {
        // Validate step number
        if ($step < 1 || $step > self::TOTAL_STEPS) {
            abort(404, 'Invalid registration step');
        }

        // Get registration data from session
        $registration = session(self::SESSION_KEY, []);

        // Check if user can access this step (must complete previous steps)
        $currentStep = $registration['current_step'] ?? 1;
        if ($step > $currentStep + 1) {
            return redirect()->route('agency.register.step', ['step' => $currentStep])
                ->with('error', 'Please complete the previous steps first.');
        }

        // Prepare step data
        $stepData = $this->prepareStepData($step, $registration['data'] ?? []);

        return view('agency.registration.step' . $step, [
            'step' => $step,
            'totalSteps' => self::TOTAL_STEPS,
            'stepTitle' => $this->getStepTitle($step),
            'stepDescription' => $this->getStepDescription($step),
            'data' => $registration['data'] ?? [],
            'stepData' => $stepData,
            'progress' => round(($step / self::TOTAL_STEPS) * 100),
        ]);
    }

    /**
     * Save data for a specific step and proceed to next.
     */
    public function saveStep(RegistrationStepRequest $request, int $step): JsonResponse
    {
        // Get registration data from session
        $registration = session(self::SESSION_KEY, [
            'current_step' => 1,
            'started_at' => now()->toIso8601String(),
            'data' => [],
        ]);

        // Merge validated data into registration
        $registration['data'] = array_merge(
            $registration['data'] ?? [],
            $request->validated()
        );

        // Update current step if advancing
        if ($step >= ($registration['current_step'] ?? 1)) {
            $registration['current_step'] = min($step + 1, self::TOTAL_STEPS);
        }

        // Store updated registration
        session()->put(self::SESSION_KEY, $registration);

        // Determine next step or completion
        $nextStep = $step + 1;
        $isComplete = $step >= self::TOTAL_STEPS;

        return response()->json([
            'success' => true,
            'message' => $this->getStepSuccessMessage($step),
            'next_step' => $isComplete ? null : $nextStep,
            'redirect' => $isComplete
                ? route('agency.register.submit')
                : route('agency.register.step', ['step' => $nextStep]),
            'progress' => round(($nextStep / self::TOTAL_STEPS) * 100),
        ]);
    }

    /**
     * Go to previous step.
     */
    public function previousStep(int $step): JsonResponse
    {
        if ($step <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot go back from the first step.',
            ], 400);
        }

        $previousStep = $step - 1;

        return response()->json([
            'success' => true,
            'redirect' => route('agency.register.step', ['step' => $previousStep]),
        ]);
    }

    /**
     * Handle document upload during registration.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_type' => 'required|in:business_license,insurance_certificate,tax_id,other',
        ]);

        try {
            $file = $request->file('document');
            $documentType = $request->input('document_type');

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Store temporarily (will be moved to permanent storage on final submission)
            $path = $file->storeAs(
                'temp/agency-registration/' . session()->getId(),
                $filename,
                'local'
            );

            // Update session with document info
            $registration = session(self::SESSION_KEY, ['data' => []]);
            $documents = $registration['data']['documents'] ?? [];
            $documents[$documentType] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toIso8601String(),
            ];
            $registration['data']['documents'] = $documents;
            session()->put(self::SESSION_KEY, $registration);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'document' => [
                    'type' => $documentType,
                    'name' => $file->getClientOriginalName(),
                    'size' => $this->formatFileSize($file->getSize()),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Agency registration document upload failed', [
                'error' => $e->getMessage(),
                'session_id' => session()->getId(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove an uploaded document.
     */
    public function removeDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|in:business_license,insurance_certificate,tax_id,other',
        ]);

        $documentType = $request->input('document_type');
        $registration = session(self::SESSION_KEY, ['data' => []]);
        $documents = $registration['data']['documents'] ?? [];

        if (isset($documents[$documentType])) {
            // Delete the file
            Storage::disk('local')->delete($documents[$documentType]['path']);

            // Remove from session
            unset($documents[$documentType]);
            $registration['data']['documents'] = $documents;
            session()->put(self::SESSION_KEY, $registration);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document removed successfully.',
        ]);
    }

    /**
     * Show the final review page before submission.
     */
    public function review(): View
    {
        $registration = session(self::SESSION_KEY);

        if (!$registration || empty($registration['data'])) {
            return redirect()->route('agency.register.start')
                ->with('error', 'Please complete the registration steps first.');
        }

        // Validate all required steps are complete
        $requiredSteps = $this->validateAllStepsComplete($registration['data']);
        if (!$requiredSteps['complete']) {
            return redirect()->route('agency.register.step', ['step' => $requiredSteps['incomplete_step']])
                ->with('error', 'Please complete all required steps before reviewing.');
        }

        return view('agency.registration.review', [
            'data' => $registration['data'],
            'tiers' => $this->partnershipTiers,
            'selectedTier' => $this->partnershipTiers[$registration['data']['partnership_tier'] ?? 'standard'] ?? null,
        ]);
    }

    /**
     * Submit the complete agency application.
     */
    public function submitApplication(Request $request): JsonResponse
    {
        $registration = session(self::SESSION_KEY);

        if (!$registration || empty($registration['data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Registration data not found. Please start over.',
                'redirect' => route('agency.register.start'),
            ], 400);
        }

        // Validate all steps complete
        $validation = $this->validateAllStepsComplete($registration['data']);
        if (!$validation['complete']) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete all required steps.',
                'redirect' => route('agency.register.step', ['step' => $validation['incomplete_step']]),
            ], 400);
        }

        $data = $registration['data'];

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['contact_email'],
                'password' => Hash::make($data['password']),
                'user_type' => 'agency',
                'role' => 'user',
                'status' => 'pending', // Pending until application approved
                'phone' => $data['contact_phone'] ?? null,
                'email_verified_at' => null, // Requires verification
            ]);

            // Create agency profile
            $agencyProfile = AgencyProfile::create([
                'user_id' => $user->id,
                'agency_name' => $data['business_name'],
                'business_registration_number' => $data['registration_number'] ?? null,
                'phone' => $data['contact_phone'] ?? null,
                'website' => $data['website'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'description' => $data['business_description'] ?? null,
                'specializations' => json_encode($data['industries'] ?? []),
                'license_number' => $data['license_number'] ?? null,
                'license_verified' => false,
                'verification_status' => 'pending',
                'business_model' => $data['partnership_tier'] ?? 'standard',
                'commission_rate' => $this->partnershipTiers[$data['partnership_tier'] ?? 'standard']['commission'],
                'total_workers' => $data['existing_workers_count'] ?? 0,
                'onboarding_step' => 1,
                'onboarding_completed' => false,
            ]);

            // Store agency application data (for admin review)
            $this->storeApplicationData($user, $agencyProfile, $data);

            // Move temporary documents to permanent storage
            $this->moveDocumentsToPermanentStorage($user, $data['documents'] ?? []);

            // Send verification email
            $user->sendEmailVerificationNotification();

            // Notify admins of new application
            $this->notifyAdminsOfNewApplication($user, $agencyProfile);

            DB::commit();

            // Clear registration session
            session()->forget(self::SESSION_KEY);

            return response()->json([
                'success' => true,
                'message' => 'Your agency application has been submitted successfully!',
                'redirect' => route('agency.register.confirmation', ['id' => $agencyProfile->id]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Agency registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => array_diff_key($data, ['password' => '']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your application. Please try again.',
            ], 500);
        }
    }

    /**
     * Show application confirmation page.
     */
    public function confirmation(int $id): View
    {
        return view('agency.registration.confirmation', [
            'applicationId' => $id,
        ]);
    }

    /**
     * Get step title.
     */
    protected function getStepTitle(int $step): string
    {
        return match ($step) {
            1 => 'Business Information',
            2 => 'Contact Details',
            3 => 'Document Upload',
            4 => 'Partnership Tier',
            5 => 'Worker Pool Details',
            6 => 'Business References',
            7 => 'Commercial Terms',
            8 => 'Review & Submit',
            default => 'Registration',
        };
    }

    /**
     * Get step description.
     */
    protected function getStepDescription(int $step): string
    {
        return match ($step) {
            1 => 'Tell us about your staffing agency',
            2 => 'How can we reach you?',
            3 => 'Upload required business documents',
            4 => 'Choose the plan that fits your needs',
            5 => 'Tell us about your worker pool',
            6 => 'Provide business references for verification',
            7 => 'Review and accept our commercial terms',
            8 => 'Review your application and submit',
            default => '',
        };
    }

    /**
     * Get success message for completing a step.
     */
    protected function getStepSuccessMessage(int $step): string
    {
        return match ($step) {
            1 => 'Business information saved.',
            2 => 'Contact details saved.',
            3 => 'Documents uploaded successfully.',
            4 => 'Partnership tier selected.',
            5 => 'Worker pool details saved.',
            6 => 'References added successfully.',
            7 => 'Terms accepted.',
            8 => 'Application submitted!',
            default => 'Step completed.',
        };
    }

    /**
     * Prepare data specific to a step.
     */
    protected function prepareStepData(int $step, array $data): array
    {
        return match ($step) {
            1 => [
                'agency_types' => [
                    'staffing_agency' => 'General Staffing Agency',
                    'temp_agency' => 'Temporary Staffing Agency',
                    'recruitment_firm' => 'Recruitment Firm',
                    'healthcare_staffing' => 'Healthcare Staffing',
                    'hospitality_staffing' => 'Hospitality Staffing',
                    'industrial_staffing' => 'Industrial Staffing',
                    'it_staffing' => 'IT/Tech Staffing',
                    'other' => 'Other',
                ],
            ],
            2 => [
                'countries' => $this->getCountries(),
            ],
            3 => [
                'required_documents' => [
                    'business_license' => [
                        'name' => 'Business License',
                        'description' => 'Valid business operating license',
                        'required' => true,
                    ],
                    'insurance_certificate' => [
                        'name' => 'Insurance Certificate',
                        'description' => 'Liability insurance certificate',
                        'required' => true,
                    ],
                    'tax_id' => [
                        'name' => 'Tax ID Document',
                        'description' => 'EIN or Tax ID verification',
                        'required' => true,
                    ],
                ],
                'uploaded_documents' => $data['documents'] ?? [],
            ],
            4 => [
                'tiers' => $this->partnershipTiers,
                'selected_tier' => $data['partnership_tier'] ?? null,
            ],
            5 => [
                'industries' => $this->getIndustries(),
                'worker_count_ranges' => [
                    '1-10' => '1-10 workers',
                    '11-50' => '11-50 workers',
                    '51-100' => '51-100 workers',
                    '101-500' => '101-500 workers',
                    '500+' => '500+ workers',
                ],
            ],
            6 => [
                'existing_references' => $data['references'] ?? [],
                'min_references' => 2,
                'max_references' => 3,
            ],
            7 => [
                'terms' => $this->getCommercialTerms(),
                'selected_tier' => $this->partnershipTiers[$data['partnership_tier'] ?? 'standard'] ?? null,
            ],
            8 => [
                'all_data' => $data,
                'selected_tier' => $this->partnershipTiers[$data['partnership_tier'] ?? 'standard'] ?? null,
            ],
            default => [],
        };
    }

    /**
     * Validate all steps are complete.
     */
    protected function validateAllStepsComplete(array $data): array
    {
        // Step 1: Business Information
        if (empty($data['business_name']) || empty($data['agency_type'])) {
            return ['complete' => false, 'incomplete_step' => 1];
        }

        // Step 2: Contact Details
        if (empty($data['contact_name']) || empty($data['contact_email']) || empty($data['password'])) {
            return ['complete' => false, 'incomplete_step' => 2];
        }

        // Step 3: Documents (at least business license required)
        $documents = $data['documents'] ?? [];
        if (empty($documents['business_license'])) {
            return ['complete' => false, 'incomplete_step' => 3];
        }

        // Step 4: Partnership tier
        if (empty($data['partnership_tier'])) {
            return ['complete' => false, 'incomplete_step' => 4];
        }

        // Step 5: Worker pool
        if (empty($data['existing_workers_count'])) {
            return ['complete' => false, 'incomplete_step' => 5];
        }

        // Step 6: References (minimum 2)
        $references = $data['references'] ?? [];
        if (count($references) < 2) {
            return ['complete' => false, 'incomplete_step' => 6];
        }

        // Step 7: Terms accepted
        if (empty($data['terms_accepted']) || !$data['terms_accepted']) {
            return ['complete' => false, 'incomplete_step' => 7];
        }

        return ['complete' => true];
    }

    /**
     * Store application data for admin review.
     */
    protected function storeApplicationData(User $user, AgencyProfile $profile, array $data): void
    {
        // Store in a dedicated table or as JSON in the profile
        // For now, storing as JSON metadata in the profile
        $applicationMeta = [
            'registration_number' => $data['registration_number'] ?? null,
            'agency_type' => $data['agency_type'] ?? null,
            'years_in_business' => $data['years_in_business'] ?? null,
            'references' => $data['references'] ?? [],
            'industries' => $data['industries'] ?? [],
            'worker_count_range' => $data['worker_count_range'] ?? null,
            'partnership_tier' => $data['partnership_tier'] ?? 'standard',
            'submitted_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Could be stored in a separate agency_applications table if needed
        // For now, update the profile with onboarding metadata
        $profile->update([
            'verification_notes' => json_encode($applicationMeta),
        ]);
    }

    /**
     * Move temporary documents to permanent storage.
     */
    protected function moveDocumentsToPermanentStorage(User $user, array $documents): void
    {
        foreach ($documents as $type => $document) {
            if (!empty($document['path']) && Storage::disk('local')->exists($document['path'])) {
                $newPath = 'agency-documents/' . $user->id . '/' . $type . '/' . basename($document['path']);

                Storage::disk('local')->move($document['path'], $newPath);

                // Store document reference (could use a documents table)
                // For now, logging the move
                Log::info('Agency document stored', [
                    'user_id' => $user->id,
                    'document_type' => $type,
                    'path' => $newPath,
                ]);
            }
        }

        // Clean up temp directory
        $tempDir = 'temp/agency-registration/' . session()->getId();
        if (Storage::disk('local')->exists($tempDir)) {
            Storage::disk('local')->deleteDirectory($tempDir);
        }
    }

    /**
     * Notify admins of new agency application.
     */
    protected function notifyAdminsOfNewApplication(User $user, AgencyProfile $profile): void
    {
        // Get admin users
        $admins = User::where('user_type', 'admin')->get();

        foreach ($admins as $admin) {
            // Using Laravel's notification system (would need notification class)
            // $admin->notify(new NewAgencyApplicationNotification($user, $profile));

            // For now, log the notification
            Log::info('New agency application notification', [
                'admin_id' => $admin->id,
                'agency_user_id' => $user->id,
                'agency_name' => $profile->agency_name,
            ]);
        }
    }

    /**
     * Get list of countries.
     */
    protected function getCountries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'SG' => 'Singapore',
            'AE' => 'United Arab Emirates',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'KE' => 'Kenya',
            'IN' => 'India',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'OTHER' => 'Other',
        ];
    }

    /**
     * Get list of industries.
     */
    protected function getIndustries(): array
    {
        // Try to get from database first
        try {
            $industries = Industry::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();

            if (!empty($industries)) {
                return $industries;
            }
        } catch (\Exception $e) {
            // Database table might not exist, use defaults
        }

        return [
            'hospitality' => 'Hospitality',
            'healthcare' => 'Healthcare',
            'retail' => 'Retail',
            'warehouse' => 'Warehouse & Logistics',
            'manufacturing' => 'Manufacturing',
            'construction' => 'Construction',
            'events' => 'Events & Entertainment',
            'office' => 'Office & Administrative',
            'it' => 'IT & Technology',
            'security' => 'Security',
            'cleaning' => 'Cleaning & Facilities',
            'transportation' => 'Transportation',
            'food_service' => 'Food Service',
            'other' => 'Other',
        ];
    }

    /**
     * Get commercial terms content.
     */
    protected function getCommercialTerms(): array
    {
        return [
            'sections' => [
                [
                    'title' => 'Payment Terms',
                    'content' => 'OvertimeStaff processes payments to agencies on a weekly basis. Standard payouts are processed within 3-5 business days. Professional and Enterprise tiers receive expedited payouts.',
                ],
                [
                    'title' => 'Commission Structure',
                    'content' => 'Commission rates vary by partnership tier. Standard: 15%, Professional: 12%, Enterprise: 8%. Commission is calculated on the total shift value and deducted before payout.',
                ],
                [
                    'title' => 'Worker Management',
                    'content' => 'Agencies are responsible for ensuring their workers meet all platform requirements, including background checks, certifications, and compliance with local labor laws.',
                ],
                [
                    'title' => 'Service Level Agreement',
                    'content' => 'Agencies must maintain a minimum 90% shift completion rate and 4.0+ average worker rating to remain in good standing on the platform.',
                ],
                [
                    'title' => 'Termination',
                    'content' => 'Either party may terminate the agreement with 30 days written notice. Outstanding payments will be processed within 14 business days of termination.',
                ],
            ],
            'last_updated' => '2024-01-01',
        ];
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
