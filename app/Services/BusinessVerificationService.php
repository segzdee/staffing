<?php

namespace App\Services;

use App\Models\BusinessDocument;
use App\Models\BusinessProfile;
use App\Models\BusinessVerification;
use App\Models\User;
use App\Models\VerificationRequirement;
use App\Notifications\Business\VerificationInitiatedNotification;
use App\Notifications\Business\DocumentsSubmittedNotification;
use App\Notifications\Business\VerificationCompleteNotification;
use App\Notifications\Business\VerificationFailedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Business Verification Service
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Handles KYB document verification, validation, and workflow management
 */
class BusinessVerificationService
{
    /**
     * Get KYB requirements for a jurisdiction.
     */
    public function getKYBRequirements(
        string $jurisdiction,
        ?string $businessType = null,
        ?string $industry = null
    ): array {
        $requirements = VerificationRequirement::getKybRequirements(
            $jurisdiction,
            $businessType,
            $industry
        );

        return [
            'jurisdiction' => $jurisdiction,
            'jurisdiction_name' => VerificationRequirement::getJurisdictionName($jurisdiction),
            'requirements' => $requirements->map(function ($req) {
                return [
                    'id' => $req->id,
                    'document_type' => $req->document_type,
                    'document_name' => $req->document_name,
                    'description' => $req->description,
                    'is_required' => $req->is_required,
                    'has_auto_validation' => $req->hasValidationApi(),
                    'validity_months' => $req->validity_months,
                ];
            })->values()->toArray(),
            'required_count' => $requirements->where('is_required', true)->count(),
            'total_count' => $requirements->count(),
        ];
    }

    /**
     * Initiate business verification.
     */
    public function initiateVerification(BusinessProfile $profile, string $jurisdiction): BusinessVerification
    {
        // Check for existing pending verification
        $existing = BusinessVerification::where('business_profile_id', $profile->id)
            ->whereIn('status', [
                BusinessVerification::STATUS_PENDING,
                BusinessVerification::STATUS_IN_REVIEW,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        DB::beginTransaction();
        try {
            $verification = BusinessVerification::create([
                'business_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'jurisdiction' => strtoupper($jurisdiction),
                'status' => BusinessVerification::STATUS_PENDING,
                'verification_type' => BusinessVerification::TYPE_KYB,
            ]);

            // Update profile status
            $profile->update([
                'verification_status' => 'in_progress',
            ]);

            // Send notification
            $profile->user->notify(new VerificationInitiatedNotification($verification));

            DB::commit();

            Log::info('Business verification initiated', [
                'verification_id' => $verification->id,
                'business_profile_id' => $profile->id,
                'jurisdiction' => $jurisdiction,
            ]);

            return $verification;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate verification', [
                'error' => $e->getMessage(),
                'business_profile_id' => $profile->id,
            ]);
            throw $e;
        }
    }

    /**
     * Upload and store business document.
     */
    public function uploadDocument(
        BusinessVerification $verification,
        UploadedFile $file,
        string $documentType,
        ?string $documentName = null
    ): BusinessDocument {
        // Get requirement for this document type
        $requirement = VerificationRequirement::active()
            ->kyb()
            ->forJurisdiction($verification->jurisdiction)
            ->where('document_type', $documentType)
            ->first();

        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $secureFilename = Str::uuid() . '.' . $extension;
        $storagePath = "business-documents/{$verification->business_profile_id}/{$secureFilename}";

        // Calculate file hash before upload
        $fileHash = BusinessDocument::calculateFileHash($file->getContent());

        // Store encrypted file
        $path = $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            config('filesystems.verification_disk', 's3')
        );

        DB::beginTransaction();
        try {
            $document = BusinessDocument::create([
                'business_verification_id' => $verification->id,
                'business_profile_id' => $verification->business_profile_id,
                'requirement_id' => $requirement?->id,
                'document_type' => $documentType,
                'document_name' => $documentName ?? $requirement?->document_name ?? ucwords(str_replace('_', ' ', $documentType)),
                'file_hash' => $fileHash,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'storage_provider' => config('filesystems.verification_disk', 's3'),
                'status' => BusinessDocument::STATUS_PENDING,
            ]);

            // Set encrypted path
            $document->setFilePath($path);
            $document->save();

            // Log access
            $document->recordAccess('upload', auth()->user());

            DB::commit();

            Log::info('Business document uploaded', [
                'document_id' => $document->id,
                'verification_id' => $verification->id,
                'document_type' => $documentType,
            ]);

            // Queue OCR processing if applicable
            if ($this->shouldProcessOCR($document)) {
                dispatch(new \App\Jobs\ProcessDocumentOCR($document));
            }

            return $document;

        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up uploaded file
            Storage::disk(config('filesystems.verification_disk', 's3'))->delete($path);
            throw $e;
        }
    }

    /**
     * Submit documents for verification.
     */
    public function submitDocuments(BusinessVerification $verification): BusinessVerification
    {
        $missingDocs = $verification->getMissingDocuments();

        if ($missingDocs->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Missing required documents: ' . $missingDocs->pluck('document_name')->implode(', ')
            );
        }

        $verification->submit();

        // Send notification
        $verification->user->notify(new DocumentsSubmittedNotification($verification));

        // Attempt auto-verification
        $this->attemptAutoVerification($verification);

        return $verification->fresh();
    }

    /**
     * Attempt automatic verification using external APIs.
     */
    public function attemptAutoVerification(BusinessVerification $verification): array
    {
        $results = [];
        $allPassed = true;

        foreach ($verification->documents as $document) {
            $requirement = $document->requirement;

            if (!$requirement || !$requirement->hasValidationApi()) {
                continue;
            }

            try {
                $result = $this->verifyDocumentExternal($document, $requirement);
                $results[$document->document_type] = $result;

                if (!$result['valid']) {
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                Log::warning('External verification failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
                $results[$document->document_type] = [
                    'valid' => false,
                    'error' => 'External verification unavailable',
                ];
                $allPassed = false;
            }
        }

        // Record results
        $verification->recordAutoVerification($results, $allPassed);

        // If all passed, complete verification
        if ($allPassed && !empty($results)) {
            $this->completeVerification($verification, null, 'Auto-verified');
        } else {
            // Route to manual review
            $verification->routeToManualReview(
                'Auto-verification incomplete or failed',
                $allPassed ? 0 : 1
            );
        }

        return $results;
    }

    /**
     * Verify document against external API.
     */
    protected function verifyDocumentExternal(BusinessDocument $document, VerificationRequirement $requirement): array
    {
        $api = $requirement->validation_api;

        // Route to appropriate verification handler
        return match ($api) {
            'irs_ein' => $this->verifyEIN($document),
            'companies_house' => $this->verifyCompaniesHouse($document),
            'vies_vat' => $this->verifyVIES($document),
            'abn_lookup' => $this->verifyABN($document),
            'acra' => $this->verifyACRA($document),
            default => ['valid' => false, 'error' => 'Unknown validation API'],
        };
    }

    /**
     * Verify US EIN with IRS.
     * Note: Real implementation would use IRS e-Services or third-party API
     */
    protected function verifyEIN(BusinessDocument $document): array
    {
        $extractedData = $document->extracted_data ?? [];
        $ein = $extractedData['ein'] ?? null;

        if (!$ein) {
            return ['valid' => false, 'error' => 'EIN not extracted from document'];
        }

        // Validate EIN format
        if (!preg_match('/^\d{2}-?\d{7}$/', $ein)) {
            return ['valid' => false, 'error' => 'Invalid EIN format'];
        }

        // In production, call actual IRS API or third-party service
        // For now, simulate successful validation
        return [
            'valid' => true,
            'verified_ein' => $ein,
            'verification_date' => now()->toIso8601String(),
        ];
    }

    /**
     * Verify UK company with Companies House API.
     */
    protected function verifyCompaniesHouse(BusinessDocument $document): array
    {
        $extractedData = $document->extracted_data ?? [];
        $companyNumber = $extractedData['company_number'] ?? null;

        if (!$companyNumber) {
            return ['valid' => false, 'error' => 'Company number not extracted'];
        }

        $apiKey = config('services.companies_house.api_key');

        if (!$apiKey) {
            return ['valid' => false, 'error' => 'Companies House API not configured'];
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->get("https://api.company-information.service.gov.uk/company/{$companyNumber}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => $data['company_status'] === 'active',
                    'company_name' => $data['company_name'] ?? null,
                    'company_status' => $data['company_status'] ?? null,
                    'incorporation_date' => $data['date_of_creation'] ?? null,
                    'registered_address' => $data['registered_office_address'] ?? null,
                ];
            }

            return ['valid' => false, 'error' => 'Company not found'];

        } catch (\Exception $e) {
            Log::error('Companies House API error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'API request failed'];
        }
    }

    /**
     * Verify EU VAT with VIES.
     */
    protected function verifyVIES(BusinessDocument $document): array
    {
        $extractedData = $document->extracted_data ?? [];
        $vatNumber = $extractedData['vat_number'] ?? null;

        if (!$vatNumber) {
            return ['valid' => false, 'error' => 'VAT number not extracted'];
        }

        // Extract country code from VAT number
        $countryCode = substr($vatNumber, 0, 2);
        $number = substr($vatNumber, 2);

        try {
            $response = Http::post('https://ec.europa.eu/taxation_customs/vies/rest-api/ms/' . $countryCode . '/vat/' . $number);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => $data['isValid'] ?? false,
                    'company_name' => $data['name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'vat_number' => $vatNumber,
                ];
            }

            return ['valid' => false, 'error' => 'VAT validation failed'];

        } catch (\Exception $e) {
            Log::error('VIES API error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'API request failed'];
        }
    }

    /**
     * Verify Australian ABN.
     */
    protected function verifyABN(BusinessDocument $document): array
    {
        $extractedData = $document->extracted_data ?? [];
        $abn = $extractedData['abn'] ?? null;

        if (!$abn) {
            return ['valid' => false, 'error' => 'ABN not extracted'];
        }

        // Remove spaces
        $abn = preg_replace('/\s+/', '', $abn);

        // Validate ABN format (11 digits)
        if (!preg_match('/^\d{11}$/', $abn)) {
            return ['valid' => false, 'error' => 'Invalid ABN format'];
        }

        $guid = config('services.abn_lookup.guid');

        if (!$guid) {
            return ['valid' => false, 'error' => 'ABN Lookup API not configured'];
        }

        try {
            $response = Http::get('https://abr.business.gov.au/json/AbnDetails.aspx', [
                'abn' => $abn,
                'callback' => 'callback',
                'guid' => $guid,
            ]);

            if ($response->successful()) {
                // Parse JSONP response
                $body = $response->body();
                $json = preg_replace('/^callback\((.*)\)$/', '$1', $body);
                $data = json_decode($json, true);

                if ($data && isset($data['Abn'])) {
                    return [
                        'valid' => $data['AbnStatus'] === 'Active',
                        'abn' => $data['Abn'],
                        'entity_name' => $data['EntityName'] ?? null,
                        'entity_type' => $data['EntityType'] ?? null,
                        'status' => $data['AbnStatus'] ?? null,
                        'gst_registered' => $data['Gst'] ?? null,
                    ];
                }
            }

            return ['valid' => false, 'error' => 'ABN not found'];

        } catch (\Exception $e) {
            Log::error('ABN Lookup API error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'API request failed'];
        }
    }

    /**
     * Verify Singapore company with ACRA.
     */
    protected function verifyACRA(BusinessDocument $document): array
    {
        $extractedData = $document->extracted_data ?? [];
        $uen = $extractedData['uen'] ?? null;

        if (!$uen) {
            return ['valid' => false, 'error' => 'UEN not extracted'];
        }

        // ACRA API requires business registration - placeholder for real implementation
        // In production, integrate with ACRA API or data.gov.sg

        // Validate UEN format
        if (!preg_match('/^[0-9]{8}[A-Z]$|^[0-9]{9}[A-Z]$|^[A-Z][0-9]{2}[A-Z]{2}[0-9]{4}[A-Z]$/', $uen)) {
            return ['valid' => false, 'error' => 'Invalid UEN format'];
        }

        return [
            'valid' => true, // Placeholder - real implementation would verify
            'uen' => $uen,
            'note' => 'Format validated, full verification pending',
        ];
    }

    /**
     * Extract document data using OCR.
     */
    public function extractDocumentData(BusinessDocument $document): array
    {
        // Mark as processing
        $document->markProcessing();

        try {
            // Get file contents
            $path = $document->getFilePath();
            $contents = Storage::disk($document->storage_provider)->get($path);

            // Use configured OCR service
            $ocrService = config('services.ocr.provider', 'textract');

            $extractedData = match ($ocrService) {
                'textract' => $this->extractWithTextract($contents, $document->mime_type),
                'google_vision' => $this->extractWithGoogleVision($contents),
                'azure' => $this->extractWithAzure($contents),
                default => throw new \Exception('Unknown OCR provider'),
            };

            // Calculate confidence
            $confidence = $extractedData['confidence'] ?? 80.0;

            // Record extracted data
            $document->recordExtractedData($extractedData['data'] ?? [], $confidence);

            return $extractedData;

        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->update(['status' => BusinessDocument::STATUS_PENDING]);

            throw $e;
        }
    }

    /**
     * Extract text with AWS Textract.
     */
    protected function extractWithTextract(string $contents, string $mimeType): array
    {
        // In production, use AWS Textract SDK
        // This is a placeholder implementation

        return [
            'data' => [],
            'confidence' => 0,
            'raw_text' => '',
            'error' => 'Textract integration pending',
        ];
    }

    /**
     * Extract text with Google Cloud Vision.
     */
    protected function extractWithGoogleVision(string $contents): array
    {
        // Placeholder for Google Cloud Vision integration
        return [
            'data' => [],
            'confidence' => 0,
            'error' => 'Google Vision integration pending',
        ];
    }

    /**
     * Extract text with Azure Cognitive Services.
     */
    protected function extractWithAzure(string $contents): array
    {
        // Placeholder for Azure integration
        return [
            'data' => [],
            'confidence' => 0,
            'error' => 'Azure integration pending',
        ];
    }

    /**
     * Validate document data completeness.
     */
    public function validateDocumentCompleteness(BusinessDocument $document): array
    {
        $requirement = $document->requirement;
        $extractedData = $document->extracted_data ?? [];
        $validationRules = $requirement?->getValidationRules() ?? [];

        $errors = [];
        $warnings = [];

        // Check required fields based on document type
        $requiredFields = $this->getRequiredFieldsForDocumentType($document->document_type);

        foreach ($requiredFields as $field => $label) {
            if (empty($extractedData[$field])) {
                $errors[] = "{$label} not found in document";
            }
        }

        // Run custom validation rules
        foreach ($validationRules as $rule) {
            $result = $this->runValidationRule($rule, $extractedData);
            if (!$result['passed']) {
                if ($result['severity'] === 'error') {
                    $errors[] = $result['message'];
                } else {
                    $warnings[] = $result['message'];
                }
            }
        }

        $valid = empty($errors);

        $document->recordValidation([
            'errors' => $errors,
            'warnings' => $warnings,
        ], $valid);

        return [
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get required fields for document type.
     */
    protected function getRequiredFieldsForDocumentType(string $type): array
    {
        $fieldMappings = [
            'ein_letter' => ['ein' => 'EIN', 'business_name' => 'Business Name'],
            'business_registration' => ['registration_number' => 'Registration Number', 'business_name' => 'Business Name'],
            'vat_certificate' => ['vat_number' => 'VAT Number', 'business_name' => 'Business Name'],
            'companies_house' => ['company_number' => 'Company Number', 'company_name' => 'Company Name'],
            'abn_certificate' => ['abn' => 'ABN', 'entity_name' => 'Entity Name'],
            'gst_certificate' => ['gst_number' => 'GST Number'],
            'acra_profile' => ['uen' => 'UEN', 'company_name' => 'Company Name'],
            'trade_license' => ['license_number' => 'License Number', 'business_name' => 'Business Name'],
            'good_standing' => ['certificate_date' => 'Certificate Date', 'state' => 'State'],
        ];

        return $fieldMappings[$type] ?? [];
    }

    /**
     * Run a single validation rule.
     */
    protected function runValidationRule(array $rule, array $data): array
    {
        $field = $rule['field'] ?? null;
        $type = $rule['type'] ?? 'required';
        $value = $data[$field] ?? null;

        return match ($type) {
            'required' => [
                'passed' => !empty($value),
                'message' => ($rule['message'] ?? "{$field} is required"),
                'severity' => 'error',
            ],
            'regex' => [
                'passed' => !$value || preg_match($rule['pattern'], $value),
                'message' => ($rule['message'] ?? "{$field} format is invalid"),
                'severity' => $rule['severity'] ?? 'error',
            ],
            'date_not_expired' => [
                'passed' => !$value || now()->lt($value),
                'message' => ($rule['message'] ?? "Document has expired"),
                'severity' => 'error',
            ],
            default => ['passed' => true, 'message' => '', 'severity' => 'info'],
        };
    }

    /**
     * Complete verification.
     */
    public function completeVerification(
        BusinessVerification $verification,
        ?User $reviewer = null,
        ?string $notes = null
    ): BusinessVerification {
        DB::beginTransaction();
        try {
            // Set reviewer if not auto-verified
            if ($reviewer) {
                $verification->reviewer_id = $reviewer->id;
            }

            // Approve verification
            $verification->approve($notes);

            // Verify all documents
            foreach ($verification->pendingDocuments as $document) {
                $document->update(['status' => BusinessDocument::STATUS_VERIFIED]);
            }

            // Update business profile
            $verification->businessProfile->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verification_status' => 'verified',
                'can_post_shifts' => true,
            ]);

            // Send notification
            $verification->user->notify(new VerificationCompleteNotification($verification));

            DB::commit();

            Log::info('Business verification completed', [
                'verification_id' => $verification->id,
                'reviewer_id' => $reviewer?->id,
            ]);

            return $verification->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject verification.
     */
    public function rejectVerification(
        BusinessVerification $verification,
        User $reviewer,
        string $reason,
        ?array $details = null,
        ?string $notes = null
    ): BusinessVerification {
        DB::beginTransaction();
        try {
            $verification->reviewer_id = $reviewer->id;
            $verification->reject($reason, $details, $notes);

            // Send notification
            $verification->user->notify(new VerificationFailedNotification($verification, $reason));

            DB::commit();

            Log::info('Business verification rejected', [
                'verification_id' => $verification->id,
                'reviewer_id' => $reviewer->id,
                'reason' => $reason,
            ]);

            return $verification->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Route verification to manual review queue.
     */
    public function routeToReviewQueue(
        BusinessVerification $verification,
        string $reason,
        int $priority = 0
    ): BusinessVerification {
        $verification->routeToManualReview($reason, $priority);

        Log::info('Verification routed to review queue', [
            'verification_id' => $verification->id,
            'reason' => $reason,
            'priority' => $priority,
        ]);

        return $verification;
    }

    /**
     * Get verification status summary.
     */
    public function getVerificationStatus(BusinessVerification $verification): array
    {
        $requirements = $this->getKYBRequirements(
            $verification->jurisdiction,
            $verification->businessProfile->business_type ?? null,
            $verification->businessProfile->industry ?? null
        );

        $documents = $verification->documents;
        $documentStatuses = [];

        foreach ($requirements['requirements'] as $req) {
            $doc = $documents->firstWhere('document_type', $req['document_type']);
            $documentStatuses[$req['document_type']] = [
                'requirement' => $req,
                'document' => $doc ? [
                    'id' => $doc->id,
                    'status' => $doc->status,
                    'status_label' => $doc->getStatusLabel(),
                    'uploaded_at' => $doc->created_at->toIso8601String(),
                    'expiry_date' => $doc->expiry_date?->toDateString(),
                ] : null,
                'submitted' => $doc !== null,
                'verified' => $doc?->isVerified() ?? false,
            ];
        }

        return [
            'verification_id' => $verification->id,
            'status' => $verification->status,
            'status_label' => $verification->getStatusLabel(),
            'status_color' => $verification->getStatusColor(),
            'jurisdiction' => $verification->jurisdiction,
            'jurisdiction_name' => VerificationRequirement::getJurisdictionName($verification->jurisdiction),
            'completion_percentage' => $verification->getDocumentCompletionPercentage(),
            'documents' => $documentStatuses,
            'missing_documents' => $verification->getMissingDocuments()->pluck('document_name')->toArray(),
            'has_all_required' => $verification->hasAllRequiredDocuments(),
            'submitted_at' => $verification->submitted_at?->toIso8601String(),
            'reviewed_at' => $verification->reviewed_at?->toIso8601String(),
            'valid_until' => $verification->valid_until?->toDateString(),
            'days_until_expiry' => $verification->getDaysUntilExpiry(),
            'auto_verified' => $verification->auto_verified,
            'requires_manual_review' => $verification->requires_manual_review,
            'rejection_reason' => $verification->rejection_reason,
        ];
    }

    /**
     * Check if document should be processed with OCR.
     */
    protected function shouldProcessOCR(BusinessDocument $document): bool
    {
        $ocrTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/tiff'];
        return in_array($document->mime_type, $ocrTypes);
    }

    /**
     * Handle expired verification.
     */
    public function handleExpiredVerification(BusinessVerification $verification): void
    {
        $verification->markExpired();

        Log::info('Business verification expired', [
            'verification_id' => $verification->id,
            'business_profile_id' => $verification->business_profile_id,
        ]);
    }

    /**
     * Resubmit rejected document.
     */
    public function resubmitDocument(
        BusinessVerification $verification,
        BusinessDocument $oldDocument,
        UploadedFile $file
    ): BusinessDocument {
        // Soft delete old document
        $oldDocument->delete();

        // Upload new document
        return $this->uploadDocument(
            $verification,
            $file,
            $oldDocument->document_type,
            $oldDocument->document_name
        );
    }
}
