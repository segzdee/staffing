<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Agency Registration Application Model
 * AGY-REG: Agency Registration & Onboarding System
 *
 * Tracks the complete agency registration workflow including:
 * - Initial application submission
 * - Document verification
 * - Compliance checks
 * - Commercial agreement signing
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $agency_name
 * @property string|null $trading_name
 * @property string|null $business_registration_number
 * @property string|null $tax_id
 * @property string|null $license_number
 * @property string $business_type
 * @property string|null $incorporation_state
 * @property string|null $incorporation_country
 * @property \Illuminate\Support\Carbon|null $incorporation_date
 * @property string|null $registered_address
 * @property string|null $registered_city
 * @property string|null $registered_state
 * @property string|null $registered_postal_code
 * @property string|null $registered_country
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $website
 * @property string|null $description
 * @property array|null $specializations
 * @property array|null $service_areas
 * @property int|null $estimated_worker_count
 * @property string|null $business_model
 * @property numeric|null $proposed_commission_rate
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $documents_verified_at
 * @property \Illuminate\Support\Carbon|null $compliance_approved_at
 * @property \Illuminate\Support\Carbon|null $agreement_signed_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property int|null $reviewer_id
 * @property string|null $reviewer_notes
 * @property string|null $rejection_reason
 * @property array|null $rejection_details
 * @property int $submission_attempts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $reviewer
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AgencyDocument[] $documents
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AgencyComplianceCheck[] $complianceChecks
 * @property-read \App\Models\AgencyCommercialAgreement|null $commercialAgreement
 */
class AgencyApplication extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        // Business Information
        'agency_name',
        'trading_name',
        'business_registration_number',
        'tax_id',
        'license_number',
        'business_type',
        'incorporation_state',
        'incorporation_country',
        'incorporation_date',
        // Address
        'registered_address',
        'registered_city',
        'registered_state',
        'registered_postal_code',
        'registered_country',
        // Contact
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'description',
        // Operations
        'specializations',
        'service_areas',
        'estimated_worker_count',
        'business_model',
        'proposed_commission_rate',
        // Workflow timestamps
        'submitted_at',
        'documents_verified_at',
        'compliance_approved_at',
        'agreement_signed_at',
        'approved_at',
        'rejected_at',
        // Review
        'reviewer_id',
        'reviewer_notes',
        'rejection_reason',
        'rejection_details',
        'submission_attempts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'incorporation_date' => 'date',
        'submitted_at' => 'datetime',
        'documents_verified_at' => 'datetime',
        'compliance_approved_at' => 'datetime',
        'agreement_signed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'specializations' => 'array',
        'service_areas' => 'array',
        'rejection_details' => 'array',
        'proposed_commission_rate' => 'decimal:2',
        'estimated_worker_count' => 'integer',
        'submission_attempts' => 'integer',
    ];

    // ==================== STATUS CONSTANTS ====================

    /**
     * Application status: Draft (not yet submitted)
     */
    const STATUS_DRAFT = 'draft';

    /**
     * Application status: Submitted and awaiting document review
     */
    const STATUS_SUBMITTED = 'submitted';

    /**
     * Application status: Pending documents (additional docs needed)
     */
    const STATUS_PENDING_DOCUMENTS = 'pending_documents';

    /**
     * Application status: Documents verified, awaiting compliance
     */
    const STATUS_DOCUMENTS_VERIFIED = 'documents_verified';

    /**
     * Application status: Pending compliance checks
     */
    const STATUS_PENDING_COMPLIANCE = 'pending_compliance';

    /**
     * Application status: Compliance approved, awaiting agreement
     */
    const STATUS_COMPLIANCE_APPROVED = 'compliance_approved';

    /**
     * Application status: Pending agreement signature
     */
    const STATUS_PENDING_AGREEMENT = 'pending_agreement';

    /**
     * Application status: Fully approved and active
     */
    const STATUS_APPROVED = 'approved';

    /**
     * Application status: Rejected
     */
    const STATUS_REJECTED = 'rejected';

    /**
     * Application status: Withdrawn by applicant
     */
    const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_PENDING_DOCUMENTS,
        self::STATUS_DOCUMENTS_VERIFIED,
        self::STATUS_PENDING_COMPLIANCE,
        self::STATUS_COMPLIANCE_APPROVED,
        self::STATUS_PENDING_AGREEMENT,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who submitted this application.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer (admin) who processed this application.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get all documents for this application.
     */
    public function documents()
    {
        return $this->hasMany(AgencyDocument::class, 'agency_application_id');
    }

    /**
     * Get all compliance checks for this application.
     */
    public function complianceChecks()
    {
        return $this->hasMany(AgencyComplianceCheck::class, 'agency_application_id');
    }

    /**
     * Get the commercial agreement for this application.
     */
    public function commercialAgreement()
    {
        return $this->hasOne(AgencyCommercialAgreement::class, 'agency_application_id');
    }

    /**
     * Get pending documents.
     */
    public function pendingDocuments()
    {
        return $this->documents()->where('status', 'pending');
    }

    /**
     * Get verified documents.
     */
    public function verifiedDocuments()
    {
        return $this->documents()->where('status', 'verified');
    }

    /**
     * Get rejected documents.
     */
    public function rejectedDocuments()
    {
        return $this->documents()->where('status', 'rejected');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending applications (awaiting review).
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_DOCUMENTS,
            self::STATUS_DOCUMENTS_VERIFIED,
            self::STATUS_PENDING_COMPLIANCE,
            self::STATUS_COMPLIANCE_APPROVED,
            self::STATUS_PENDING_AGREEMENT,
        ]);
    }

    /**
     * Scope to approved applications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to rejected applications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to draft applications.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to submitted applications awaiting document review.
     */
    public function scopeAwaitingDocumentReview($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_DOCUMENTS,
        ]);
    }

    /**
     * Scope to applications awaiting compliance review.
     */
    public function scopeAwaitingComplianceReview($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DOCUMENTS_VERIFIED,
            self::STATUS_PENDING_COMPLIANCE,
        ]);
    }

    /**
     * Scope to applications awaiting agreement signature.
     */
    public function scopeAwaitingAgreement($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLIANCE_APPROVED,
            self::STATUS_PENDING_AGREEMENT,
        ]);
    }

    /**
     * Scope by jurisdiction/country.
     */
    public function scopeForCountry($query, string $country)
    {
        return $query->where('registered_country', $country);
    }

    // ==================== STATUS HELPER METHODS ====================

    /**
     * Check if application is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if application has been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Check if application is pending documents.
     */
    public function isPendingDocuments(): bool
    {
        return $this->status === self::STATUS_PENDING_DOCUMENTS;
    }

    /**
     * Check if documents are verified.
     */
    public function isDocumentsVerified(): bool
    {
        return in_array($this->status, [
            self::STATUS_DOCUMENTS_VERIFIED,
            self::STATUS_PENDING_COMPLIANCE,
            self::STATUS_COMPLIANCE_APPROVED,
            self::STATUS_PENDING_AGREEMENT,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * Check if application is pending compliance.
     */
    public function isPendingCompliance(): bool
    {
        return $this->status === self::STATUS_PENDING_COMPLIANCE;
    }

    /**
     * Check if compliance is approved.
     */
    public function isComplianceApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLIANCE_APPROVED,
            self::STATUS_PENDING_AGREEMENT,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * Check if application is pending agreement signature.
     */
    public function isPendingAgreement(): bool
    {
        return $this->status === self::STATUS_PENDING_AGREEMENT;
    }

    /**
     * Check if application is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if application is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if application is withdrawn.
     */
    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    /**
     * Check if application is in a terminal state (approved, rejected, or withdrawn).
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ]);
    }

    /**
     * Check if application can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_DOCUMENTS,
        ]);
    }

    // ==================== TRANSITION METHODS ====================

    /**
     * Submit the application for review.
     */
    public function submit(): self
    {
        if ($this->status !== self::STATUS_DRAFT && $this->status !== self::STATUS_PENDING_DOCUMENTS) {
            throw new \InvalidArgumentException('Application can only be submitted from draft or pending documents status.');
        }

        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submission_attempts' => $this->submission_attempts + 1,
        ]);

        return $this;
    }

    /**
     * Request additional documents from applicant.
     */
    public function requestDocuments(array $requiredDocuments, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_PENDING_DOCUMENTS,
            'reviewer_notes' => $notes,
            'rejection_details' => array_merge(
                $this->rejection_details ?? [],
                ['required_documents' => $requiredDocuments]
            ),
        ]);

        return $this;
    }

    /**
     * Approve documents and move to compliance check phase.
     */
    public function approveDocuments(User $reviewer, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_DOCUMENTS_VERIFIED,
            'documents_verified_at' => now(),
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Reject documents (allows resubmission).
     */
    public function rejectDocuments(User $reviewer, string $reason, ?array $details = null): self
    {
        $this->update([
            'status' => self::STATUS_PENDING_DOCUMENTS,
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $reason,
            'rejection_details' => $details,
        ]);

        return $this;
    }

    /**
     * Start compliance checks.
     */
    public function startComplianceChecks(): self
    {
        if ($this->status !== self::STATUS_DOCUMENTS_VERIFIED) {
            throw new \InvalidArgumentException('Documents must be verified before starting compliance checks.');
        }

        $this->update([
            'status' => self::STATUS_PENDING_COMPLIANCE,
        ]);

        return $this;
    }

    /**
     * Approve compliance checks.
     */
    public function approveCompliance(?string $notes = null): self
    {
        if ($this->status !== self::STATUS_PENDING_COMPLIANCE) {
            throw new \InvalidArgumentException('Application must be in pending compliance status.');
        }

        $this->update([
            'status' => self::STATUS_COMPLIANCE_APPROVED,
            'compliance_approved_at' => now(),
            'reviewer_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Fail compliance checks.
     */
    public function failCompliance(string $reason, ?array $details = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'rejection_details' => $details,
        ]);

        return $this;
    }

    /**
     * Send commercial agreement for signature.
     */
    public function sendAgreement(): self
    {
        if ($this->status !== self::STATUS_COMPLIANCE_APPROVED) {
            throw new \InvalidArgumentException('Compliance must be approved before sending agreement.');
        }

        $this->update([
            'status' => self::STATUS_PENDING_AGREEMENT,
        ]);

        return $this;
    }

    /**
     * Mark agreement as signed.
     */
    public function markAgreementSigned(): self
    {
        if ($this->status !== self::STATUS_PENDING_AGREEMENT) {
            throw new \InvalidArgumentException('Application must be pending agreement.');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'agreement_signed_at' => now(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Final approval of the application.
     */
    public function approve(User $reviewer, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Reject the application.
     */
    public function reject(User $reviewer, string $reason, ?array $details = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'reviewer_id' => $reviewer->id,
            'rejection_reason' => $reason,
            'rejection_details' => $details,
        ]);

        return $this;
    }

    /**
     * Withdraw the application.
     */
    public function withdraw(): self
    {
        if ($this->isTerminal()) {
            throw new \InvalidArgumentException('Cannot withdraw an application that is already approved, rejected, or withdrawn.');
        }

        $this->update([
            'status' => self::STATUS_WITHDRAWN,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PENDING_DOCUMENTS => 'Pending Documents',
            self::STATUS_DOCUMENTS_VERIFIED => 'Documents Verified',
            self::STATUS_PENDING_COMPLIANCE => 'Compliance Review',
            self::STATUS_COMPLIANCE_APPROVED => 'Compliance Approved',
            self::STATUS_PENDING_AGREEMENT => 'Pending Agreement',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SUBMITTED => 'blue',
            self::STATUS_PENDING_DOCUMENTS => 'orange',
            self::STATUS_DOCUMENTS_VERIFIED => 'teal',
            self::STATUS_PENDING_COMPLIANCE => 'purple',
            self::STATUS_COMPLIANCE_APPROVED => 'indigo',
            self::STATUS_PENDING_AGREEMENT => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_WITHDRAWN => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get the current workflow step (1-5).
     */
    public function getCurrentStep(): int
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 1,
            self::STATUS_SUBMITTED, self::STATUS_PENDING_DOCUMENTS => 2,
            self::STATUS_DOCUMENTS_VERIFIED, self::STATUS_PENDING_COMPLIANCE => 3,
            self::STATUS_COMPLIANCE_APPROVED, self::STATUS_PENDING_AGREEMENT => 4,
            self::STATUS_APPROVED => 5,
            default => 0,
        };
    }

    /**
     * Get document completion percentage.
     */
    public function getDocumentCompletionPercentage(): int
    {
        $total = $this->documents()->count();
        if ($total === 0) {
            return 0;
        }

        $verified = $this->verifiedDocuments()->count();
        return min(100, (int) round(($verified / $total) * 100));
    }

    /**
     * Get compliance checks completion percentage.
     */
    public function getComplianceCompletionPercentage(): int
    {
        $total = $this->complianceChecks()->count();
        if ($total === 0) {
            return 0;
        }

        $passed = $this->complianceChecks()->where('status', 'passed')->count();
        return min(100, (int) round(($passed / $total) * 100));
    }

    /**
     * Check if all required documents are verified.
     */
    public function hasAllDocumentsVerified(): bool
    {
        $total = $this->documents()->count();
        if ($total === 0) {
            return false;
        }

        $verified = $this->verifiedDocuments()->count();
        $rejected = $this->rejectedDocuments()->count();

        return $verified > 0 && $rejected === 0 && ($verified === $total);
    }

    /**
     * Check if all compliance checks passed.
     */
    public function hasAllComplianceChecksPassed(): bool
    {
        $total = $this->complianceChecks()->count();
        if ($total === 0) {
            return false;
        }

        $failed = $this->complianceChecks()->where('status', 'failed')->count();
        if ($failed > 0) {
            return false;
        }

        $passed = $this->complianceChecks()->where('status', 'passed')->count();
        return $passed === $total;
    }

    /**
     * Check if commercial agreement is signed.
     */
    public function hasSignedAgreement(): bool
    {
        return $this->commercialAgreement && $this->commercialAgreement->isFullyExecuted();
    }
}
