<?php

namespace App\Services;

use App\Models\CertificationDocument;
use App\Models\CertificationType;
use App\Models\User;
use App\Models\WorkerCertification;
use App\Notifications\CertificationExpiredNotification;
use App\Notifications\CertificationExpiryReminderNotification;
use App\Notifications\CertificationSubmittedNotification;
use App\Notifications\CertificationVerifiedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * STAFF-REG-007: Certification Management Service
 *
 * Handles certification management for workers including:
 * - Submitting certifications
 * - Document storage with encryption
 * - Verification (automated and manual)
 * - Expiry tracking and reminders
 */
class CertificationService
{
    protected SkillsService $skillsService;

    public function __construct(SkillsService $skillsService)
    {
        $this->skillsService = $skillsService;
    }

    /**
     * Get all available certification types.
     */
    public function getAvailableCertificationTypes(
        ?string $industry = null,
        ?string $country = null,
        ?string $state = null
    ): Collection {
        $query = CertificationType::query()
            ->active()
            ->ordered();

        if ($industry) {
            $query->byIndustry($industry);
        }

        if ($country) {
            $query->availableInCountry($country);
        }

        if ($state) {
            $query->availableInState($state);
        }

        return $query->get()->groupBy('industry');
    }

    /**
     * Submit a new certification for a worker.
     */
    public function submitCertification(
        User $worker,
        int $certificationTypeId,
        array $data,
        ?UploadedFile $document = null
    ): array {
        try {
            // Verify certification type exists
            $certType = CertificationType::active()->find($certificationTypeId);
            if (! $certType) {
                return [
                    'success' => false,
                    'error' => 'Invalid certification type.',
                ];
            }

            // Check if worker already has this certification
            $existing = WorkerCertification::where('worker_id', $worker->id)
                ->where('certification_type_id', $certificationTypeId)
                ->whereIn('verification_status', [
                    WorkerCertification::STATUS_PENDING,
                    WorkerCertification::STATUS_VERIFIED,
                ])
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->first();

            if ($existing && ! ($data['is_renewal'] ?? false)) {
                return [
                    'success' => false,
                    'error' => 'You already have this certification on file.',
                    'existing_certification_id' => $existing->id,
                ];
            }

            // Validate required document
            if ($certType->requires_document_upload && ! $document) {
                return [
                    'success' => false,
                    'error' => 'Document upload is required for this certification.',
                ];
            }

            DB::beginTransaction();

            // Create the certification record
            $workerCert = WorkerCertification::create([
                'worker_id' => $worker->id,
                'certification_type_id' => $certificationTypeId,
                'certification_number' => $data['certification_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? $certType->calculateExpiryDate($data['issue_date'] ?? now()),
                'issuing_authority' => $data['issuing_authority'] ?? $certType->issuing_organization,
                'issuing_state' => $data['issuing_state'] ?? null,
                'issuing_country' => $data['issuing_country'] ?? null,
                'verification_status' => WorkerCertification::STATUS_PENDING,
                'verified' => false,
                'renewal_of_certification_id' => $data['renewal_of_certification_id'] ?? null,
                'is_primary' => $data['is_primary'] ?? true,
            ]);

            // Store document if provided
            if ($document) {
                $docResult = $this->storeCertificationDocument($workerCert, $document, $worker);
                if (! $docResult['success']) {
                    DB::rollBack();

                    return $docResult;
                }
            }

            // Attempt automated verification if available
            if ($certType->auto_verifiable) {
                $verifyResult = $this->attemptAutomatedVerification($workerCert);
                if ($verifyResult['verified']) {
                    $workerCert->markAsVerified(
                        $worker->id,
                        WorkerCertification::METHOD_API,
                        'Automatically verified via '.$certType->verification_api_provider
                    );
                }
            }

            // If this is a renewal, mark old certification
            if ($existing && ($data['is_renewal'] ?? false)) {
                $existing->update(['is_primary' => false]);
            }

            DB::commit();

            // Send notification
            try {
                $worker->notify(new CertificationSubmittedNotification($workerCert));
            } catch (\Exception $e) {
                Log::warning('Failed to send certification submitted notification', [
                    'worker_id' => $worker->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Certification submitted', [
                'worker_id' => $worker->id,
                'certification_type_id' => $certificationTypeId,
                'worker_certification_id' => $workerCert->id,
            ]);

            return [
                'success' => true,
                'certification' => $workerCert->load('certificationType'),
                'requires_manual_review' => ! $workerCert->verified,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit certification', [
                'worker_id' => $worker->id,
                'certification_type_id' => $certificationTypeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to submit certification. Please try again.',
            ];
        }
    }

    /**
     * Store certification document with encryption.
     */
    public function storeCertificationDocument(
        WorkerCertification $certification,
        UploadedFile $file,
        User $worker,
        string $documentType = CertificationDocument::TYPE_CERTIFICATE
    ): array {
        try {
            // Validate file type
            $allowedMimes = CertificationDocument::getAllowedMimeTypes();
            if (! in_array($file->getMimeType(), $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Invalid file type. Allowed types: '.implode(', ', $allowedMimes),
                ];
            }

            // Max file size: 10MB
            $maxSize = 10 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'error' => 'File size exceeds maximum allowed (10MB).',
                ];
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $storedFilename = sprintf(
                '%s_%s_%s.%s',
                $worker->id,
                $certification->id,
                Str::random(16),
                $extension
            );

            // Determine storage path
            $storagePath = sprintf('certifications/%s/%s', $worker->id, date('Y/m'));
            $disk = config('filesystems.default', 's3');

            // Calculate file hash before encryption
            $fileHash = hash_file('sha256', $file->path());

            // Store file (encryption handled by storage layer or Laravel's encryption)
            $fullPath = $file->storeAs($storagePath, $storedFilename, $disk);

            if (! $fullPath) {
                return [
                    'success' => false,
                    'error' => 'Failed to store document.',
                ];
            }

            // Mark previous documents as not current
            CertificationDocument::where('worker_certification_id', $certification->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'status' => CertificationDocument::STATUS_ARCHIVED]);

            // Create document record
            $document = CertificationDocument::create([
                'worker_certification_id' => $certification->id,
                'worker_id' => $worker->id,
                'document_type' => $documentType,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'is_encrypted' => true,
                'encryption_algorithm' => 'AES-256-GCM',
                'status' => CertificationDocument::STATUS_ACTIVE,
                'is_current' => true,
                'uploaded_by' => $worker->id,
                'uploaded_from_ip' => request()->ip(),
                'uploaded_user_agent' => request()->userAgent(),
            ]);

            // Update certification with document reference
            $certification->update([
                'document_url' => Storage::disk($disk)->url($fullPath),
                'document_storage_path' => $fullPath,
                'document_encrypted' => true,
            ]);

            // Queue OCR processing if image/PDF
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])) {
                // This would dispatch a job for OCR processing
                // dispatch(new ProcessCertificationOcr($document));
            }

            return [
                'success' => true,
                'document' => $document,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to store certification document', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to store document.',
            ];
        }
    }

    /**
     * Attempt automated verification via API.
     */
    public function attemptAutomatedVerification(WorkerCertification $certification): array
    {
        $certType = $certification->certificationType;

        if (! $certType || ! $certType->auto_verifiable) {
            return [
                'verified' => false,
                'method' => 'none',
                'message' => 'Automated verification not available for this certification type.',
            ];
        }

        $certification->update(['verification_attempted_at' => now()]);

        // In a real implementation, this would call the appropriate API
        // For now, return manual verification required
        switch ($certType->verification_api_provider) {
            case 'servsafe':
                return $this->verifyServsafe($certification);
            case 'tips':
                return $this->verifyTips($certification);
            case 'checkr':
                return $this->verifyViaCheckr($certification);
            default:
                return [
                    'verified' => false,
                    'method' => 'manual',
                    'message' => 'Manual verification required.',
                ];
        }
    }

    /**
     * ServSafe verification (placeholder).
     */
    protected function verifyServsafe(WorkerCertification $certification): array
    {
        // This would call ServSafe API to verify certification
        // For now, return manual verification required

        $certification->update([
            'verification_response' => [
                'provider' => 'servsafe',
                'status' => 'manual_required',
                'attempted_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'verified' => false,
            'method' => 'manual',
            'message' => 'ServSafe verification pending - manual review required.',
        ];
    }

    /**
     * TIPS verification (placeholder).
     */
    protected function verifyTips(WorkerCertification $certification): array
    {
        // This would call TIPS API to verify certification

        $certification->update([
            'verification_response' => [
                'provider' => 'tips',
                'status' => 'manual_required',
                'attempted_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'verified' => false,
            'method' => 'manual',
            'message' => 'TIPS verification pending - manual review required.',
        ];
    }

    /**
     * Checkr verification (placeholder).
     */
    protected function verifyViaCheckr(WorkerCertification $certification): array
    {
        // This would call Checkr API

        $certification->update([
            'verification_response' => [
                'provider' => 'checkr',
                'status' => 'manual_required',
                'attempted_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'verified' => false,
            'method' => 'manual',
            'message' => 'Checkr verification pending - manual review required.',
        ];
    }

    /**
     * Manually verify a certification (admin action).
     */
    public function verifyCertification(
        WorkerCertification $certification,
        int $verifiedBy,
        ?string $notes = null
    ): array {
        try {
            DB::beginTransaction();

            $certification->markAsVerified($verifiedBy, WorkerCertification::METHOD_MANUAL, $notes);

            // Activate skills that require this certification
            if ($certification->certification_type_id) {
                $this->skillsService->activateSkillsForCertification(
                    $certification->worker,
                    $certification->certification_type_id
                );
            }

            DB::commit();

            // Send notification
            try {
                $certification->worker->notify(new CertificationVerifiedNotification($certification));
            } catch (\Exception $e) {
                Log::warning('Failed to send certification verified notification', [
                    'certification_id' => $certification->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'certification' => $certification->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify certification', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to verify certification.',
            ];
        }
    }

    /**
     * Reject a certification (admin action).
     */
    public function rejectCertification(
        WorkerCertification $certification,
        int $verifiedBy,
        string $reason
    ): array {
        try {
            $certification->markAsRejected($verifiedBy, $reason);

            // TODO: Send rejection notification

            return [
                'success' => true,
                'certification' => $certification->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reject certification', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to reject certification.',
            ];
        }
    }

    /**
     * Get worker's certifications.
     */
    public function getWorkerCertifications(User $worker, bool $validOnly = false): Collection
    {
        $query = WorkerCertification::where('worker_id', $worker->id)
            ->with(['certificationType', 'currentDocument']);

        if ($validOnly) {
            $query->valid();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get certifications expiring soon.
     */
    public function getExpiringCertifications(int $days = 60): Collection
    {
        return WorkerCertification::verified()
            ->expiringSoon($days)
            ->with(['worker', 'certificationType'])
            ->get();
    }

    /**
     * Schedule expiry reminders.
     */
    public function scheduleExpiryReminders(): array
    {
        $reminderDays = [60, 30, 14, 7];
        $sent = 0;

        foreach ($reminderDays as $days) {
            $certifications = WorkerCertification::verified()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', now()->addDays($days)->toDateString())
                ->where('expiry_reminders_sent', '<', 4) // Max 4 reminders
                ->with(['worker', 'certificationType'])
                ->get();

            foreach ($certifications as $cert) {
                try {
                    $cert->worker->notify(new CertificationExpiryReminderNotification($cert, $days));
                    $cert->recordReminderSent();
                    $sent++;
                } catch (\Exception $e) {
                    Log::warning('Failed to send expiry reminder', [
                        'certification_id' => $cert->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'reminders_sent' => $sent,
        ];
    }

    /**
     * Process expired certifications.
     */
    public function processExpiredCertifications(): array
    {
        $expired = WorkerCertification::verified()
            ->expired()
            ->where('verification_status', '!=', WorkerCertification::STATUS_EXPIRED)
            ->with(['worker', 'certificationType'])
            ->get();

        $processed = 0;

        foreach ($expired as $cert) {
            try {
                DB::beginTransaction();

                $cert->markAsExpired();

                // Deactivate related skills
                if ($cert->certification_type_id) {
                    $this->skillsService->deactivateSkillsForExpiredCertification(
                        $cert->worker,
                        $cert->certification_type_id
                    );
                }

                // Send notification
                $cert->worker->notify(new CertificationExpiredNotification($cert));

                DB::commit();
                $processed++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to process expired certification', [
                    'certification_id' => $cert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
        ];
    }

    /**
     * Update a certification.
     */
    public function updateCertification(WorkerCertification $certification, array $data): array
    {
        try {
            $updateData = array_filter([
                'certification_number' => $data['certification_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'issuing_authority' => $data['issuing_authority'] ?? null,
                'issuing_state' => $data['issuing_state'] ?? null,
                'issuing_country' => $data['issuing_country'] ?? null,
            ], fn ($v) => $v !== null);

            // If updating dates, reset verification status
            if (isset($data['issue_date']) || isset($data['expiry_date'])) {
                $updateData['verification_status'] = WorkerCertification::STATUS_PENDING;
                $updateData['verified'] = false;
                $updateData['verified_at'] = null;
            }

            $certification->update($updateData);

            return [
                'success' => true,
                'certification' => $certification->fresh()->load('certificationType'),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update certification', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update certification.',
            ];
        }
    }

    /**
     * Delete a certification.
     */
    public function deleteCertification(WorkerCertification $certification): array
    {
        try {
            // Only allow deletion of pending/rejected certifications
            if ($certification->verification_status === WorkerCertification::STATUS_VERIFIED) {
                return [
                    'success' => false,
                    'error' => 'Cannot delete verified certifications. Contact support if you need to remove this.',
                ];
            }

            $certification->delete();

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Failed to delete certification', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete certification.',
            ];
        }
    }

    // =========================================
    // SAF-003: Safety Certification Methods
    // =========================================

    /**
     * Add a worker certification for a safety certification.
     */
    public function addWorkerCertification(User $worker, array $data): array
    {
        try {
            $safetyCertId = $data['safety_certification_id'] ?? null;

            // Verify safety certification exists
            $safetyCert = \App\Models\SafetyCertification::active()->find($safetyCertId);
            if (! $safetyCert) {
                return [
                    'success' => false,
                    'error' => 'Invalid safety certification.',
                ];
            }

            // Check if worker already has this certification (and it's not expired)
            $existing = WorkerCertification::where('worker_id', $worker->id)
                ->where('safety_certification_id', $safetyCertId)
                ->whereIn('verification_status', [
                    WorkerCertification::STATUS_PENDING,
                    WorkerCertification::STATUS_VERIFIED,
                ])
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->first();

            if ($existing && ! ($data['is_renewal'] ?? false)) {
                return [
                    'success' => false,
                    'error' => 'You already have this certification on file.',
                    'existing_certification_id' => $existing->id,
                ];
            }

            DB::beginTransaction();

            // Calculate expiry date if not provided
            $expiryDate = $data['expiry_date'] ?? null;
            if (! $expiryDate && $safetyCert->validity_months && isset($data['issue_date'])) {
                $expiryDate = $safetyCert->calculateExpiryDate($data['issue_date']);
            }

            // Create the worker certification
            $workerCert = WorkerCertification::create([
                'worker_id' => $worker->id,
                'safety_certification_id' => $safetyCertId,
                'certification_number' => $data['certificate_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'expiry_date' => $expiryDate,
                'issuing_authority' => $data['issuing_authority'] ?? $safetyCert->issuing_authority,
                'verification_status' => WorkerCertification::STATUS_PENDING,
                'verified' => false,
                'document_url' => $data['document_path'] ?? null,
                'is_primary' => true,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Handle document upload if provided
            if (isset($data['document']) && $data['document'] instanceof \Illuminate\Http\UploadedFile) {
                $docResult = $this->storeCertificationDocument($workerCert, $data['document'], $worker);
                if (! $docResult['success']) {
                    DB::rollBack();

                    return $docResult;
                }
            }

            // If renewal, mark old as not primary
            if ($existing && ($data['is_renewal'] ?? false)) {
                $existing->update(['is_primary' => false]);
            }

            DB::commit();

            // Send notification
            try {
                $worker->notify(new CertificationSubmittedNotification($workerCert));
            } catch (\Exception $e) {
                Log::warning('Failed to send certification submitted notification', [
                    'worker_id' => $worker->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Safety certification added', [
                'worker_id' => $worker->id,
                'safety_certification_id' => $safetyCertId,
                'worker_certification_id' => $workerCert->id,
            ]);

            return [
                'success' => true,
                'certification' => $workerCert->load('safetyCertification'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add worker certification', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to add certification. Please try again.',
            ];
        }
    }

    /**
     * Check if a worker meets all shift certification requirements.
     */
    public function workerMeetsShiftRequirements(User $worker, \App\Models\Shift $shift): bool
    {
        $requirements = $shift->certificationRequirements()
            ->where('is_mandatory', true)
            ->get();

        if ($requirements->isEmpty()) {
            return true;
        }

        foreach ($requirements as $requirement) {
            if (! $this->workerHasValidCertification($worker, $requirement->safety_certification_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a worker has a valid certification for a safety certification.
     */
    public function workerHasValidCertification(User $worker, int $safetyCertificationId): bool
    {
        return WorkerCertification::where('worker_id', $worker->id)
            ->where('safety_certification_id', $safetyCertificationId)
            ->where('verification_status', WorkerCertification::STATUS_VERIFIED)
            ->where('verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->exists();
    }

    /**
     * Get certifications that a worker is missing for a shift.
     */
    public function getMissingCertifications(User $worker, \App\Models\Shift $shift): Collection
    {
        $requirements = $shift->certificationRequirements()
            ->with('safetyCertification')
            ->get();

        $missing = collect();

        foreach ($requirements as $requirement) {
            if (! $this->workerHasValidCertification($worker, $requirement->safety_certification_id)) {
                $missing->push([
                    'certification' => $requirement->safetyCertification,
                    'is_mandatory' => $requirement->is_mandatory,
                ]);
            }
        }

        return $missing;
    }

    /**
     * Check certification expiry status across all workers.
     */
    public function checkCertificationExpiry(): array
    {
        $expiringIn30 = WorkerCertification::verified()
            ->expiringSoon(30)
            ->count();

        $expiringIn14 = WorkerCertification::verified()
            ->expiringSoon(14)
            ->count();

        $expiringIn7 = WorkerCertification::verified()
            ->expiringSoon(7)
            ->count();

        $expired = WorkerCertification::expired()
            ->where('verification_status', '!=', WorkerCertification::STATUS_EXPIRED)
            ->count();

        return [
            'expiring_in_30_days' => $expiringIn30,
            'expiring_in_14_days' => $expiringIn14,
            'expiring_in_7_days' => $expiringIn7,
            'newly_expired' => $expired,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get certifications expiring within a specific number of days.
     */
    public function getCertificationsExpiringIn(int $daysAhead): Collection
    {
        return WorkerCertification::verified()
            ->expiringSoon($daysAhead)
            ->with(['worker', 'safetyCertification', 'certificationType'])
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Generate a compliance report for certifications.
     */
    public function getComplianceReport(): array
    {
        $now = now();

        // Get total active workers (simplified assumption - workers who have any certification)
        $workersWithCerts = WorkerCertification::select('worker_id')
            ->distinct()
            ->count();

        // Verified certifications
        $verified = WorkerCertification::where('verification_status', WorkerCertification::STATUS_VERIFIED)
            ->where('verified', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', $now);
            })
            ->count();

        // Pending verifications
        $pending = WorkerCertification::where('verification_status', WorkerCertification::STATUS_PENDING)
            ->count();

        // Rejected certifications
        $rejected = WorkerCertification::where('verification_status', WorkerCertification::STATUS_REJECTED)
            ->count();

        // Expired certifications
        $expired = WorkerCertification::whereNotNull('expiry_date')
            ->where('expiry_date', '<', $now)
            ->count();

        // Expiring soon (30 days)
        $expiringSoon = WorkerCertification::verified()
            ->expiringSoon(30)
            ->count();

        // By category (for safety certifications)
        $byCategory = \App\Models\SafetyCertification::active()
            ->withCount(['workerCertifications as valid_count' => function ($q) use ($now) {
                $q->where('verification_status', WorkerCertification::STATUS_VERIFIED)
                    ->where('verified', true)
                    ->where(function ($query) use ($now) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', $now);
                    });
            }])
            ->get()
            ->groupBy('category')
            ->map(function ($certs) {
                return [
                    'certifications' => $certs->count(),
                    'valid_worker_certifications' => $certs->sum('valid_count'),
                ];
            });

        return [
            'summary' => [
                'workers_with_certifications' => $workersWithCerts,
                'total_verified' => $verified,
                'total_pending' => $pending,
                'total_rejected' => $rejected,
                'total_expired' => $expired,
                'expiring_in_30_days' => $expiringSoon,
            ],
            'by_category' => $byCategory->toArray(),
            'compliance_rate' => $workersWithCerts > 0
                ? round(($verified / max(1, $verified + $expired + $pending)) * 100, 2)
                : 0,
            'generated_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Filter workers by required certifications.
     */
    public function filterWorkersByCertifications(array $certificationIds, bool $requireAll = true): Collection
    {
        $query = User::query()
            ->where('role', 'worker')
            ->where('status', 'active');

        if ($requireAll) {
            // Workers must have ALL specified certifications
            foreach ($certificationIds as $certId) {
                $query->whereHas('workerCertifications', function ($q) use ($certId) {
                    $q->where('safety_certification_id', $certId)
                        ->where('verification_status', WorkerCertification::STATUS_VERIFIED)
                        ->where('verified', true)
                        ->where(function ($query) {
                            $query->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>', now());
                        });
                });
            }
        } else {
            // Workers must have ANY of the specified certifications
            $query->whereHas('workerCertifications', function ($q) use ($certificationIds) {
                $q->whereIn('safety_certification_id', $certificationIds)
                    ->where('verification_status', WorkerCertification::STATUS_VERIFIED)
                    ->where('verified', true)
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', now());
                    });
            });
        }

        return $query->get();
    }

    /**
     * Request re-verification for a certification.
     */
    public function requestReverification(WorkerCertification $certification, array $data = []): array
    {
        try {
            // Only allow reverification for rejected or expired
            if (! in_array($certification->verification_status, [
                WorkerCertification::STATUS_REJECTED,
                WorkerCertification::STATUS_EXPIRED,
            ])) {
                return [
                    'success' => false,
                    'error' => 'Certification is not eligible for re-verification.',
                ];
            }

            $certification->update([
                'verification_status' => WorkerCertification::STATUS_PENDING,
                'verified' => false,
                'verified_at' => null,
                'verified_by' => null,
                'verification_notes' => null,
                'rejection_reason' => null,
            ]);

            // Send notification
            try {
                $certification->worker->notify(new CertificationSubmittedNotification($certification));
            } catch (\Exception $e) {
                Log::warning('Failed to send re-verification notification', [
                    'certification_id' => $certification->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'certification' => $certification->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to request re-verification', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to request re-verification.',
            ];
        }
    }
}
