<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * STAFF-REG-006: Background Check Consent Model
 *
 * Stores consent information for background checks (FCRA compliance).
 *
 * @property int $id
 * @property int $background_check_id
 * @property int $user_id
 * @property string $consent_type
 * @property bool $consented
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property string|null $signature_data_encrypted
 * @property string|null $signature_type
 * @property string|null $signatory_name
 * @property string|null $document_version
 * @property string|null $document_hash
 * @property string|null $consent_ip
 * @property string|null $consent_user_agent
 * @property string|null $consent_device_fingerprint
 * @property string|null $consent_location
 * @property string|null $full_disclosure_text
 * @property bool $separate_document_provided
 * @property bool $is_withdrawn
 * @property \Illuminate\Support\Carbon|null $withdrawn_at
 * @property string|null $withdrawal_reason
 * @property array|null $audit_log
 */
class BackgroundCheckConsent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'background_check_id',
        'user_id',
        'consent_type',
        'consented',
        'consented_at',
        'signature_data_encrypted',
        'signature_type',
        'signatory_name',
        'document_version',
        'document_hash',
        'consent_ip',
        'consent_user_agent',
        'consent_device_fingerprint',
        'consent_location',
        'full_disclosure_text',
        'separate_document_provided',
        'is_withdrawn',
        'withdrawn_at',
        'withdrawal_reason',
        'audit_log',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'consented' => 'boolean',
        'consented_at' => 'datetime',
        'separate_document_provided' => 'boolean',
        'is_withdrawn' => 'boolean',
        'withdrawn_at' => 'datetime',
        'audit_log' => 'array',
    ];

    /**
     * Consent type constants.
     */
    public const TYPE_FCRA_DISCLOSURE = 'fcra_disclosure';
    public const TYPE_FCRA_AUTHORIZATION = 'fcra_authorization';
    public const TYPE_DBS_CONSENT = 'dbs_consent';
    public const TYPE_GENERAL_CONSENT = 'general_consent';
    public const TYPE_DATA_PROCESSING = 'data_processing';

    /**
     * Signature type constants.
     */
    public const SIGNATURE_TYPED = 'typed';
    public const SIGNATURE_DRAWN = 'drawn';
    public const SIGNATURE_CHECKBOX = 'checkbox';

    /**
     * Consent types by jurisdiction.
     */
    public const REQUIRED_CONSENTS = [
        'US' => [
            self::TYPE_FCRA_DISCLOSURE,
            self::TYPE_FCRA_AUTHORIZATION,
        ],
        'UK' => [
            self::TYPE_DBS_CONSENT,
            self::TYPE_DATA_PROCESSING,
        ],
        'EU' => [
            self::TYPE_GENERAL_CONSENT,
            self::TYPE_DATA_PROCESSING,
        ],
        'AU' => [
            self::TYPE_GENERAL_CONSENT,
        ],
        'DEFAULT' => [
            self::TYPE_GENERAL_CONSENT,
        ],
    ];

    /**
     * Get the background check this consent belongs to.
     */
    public function backgroundCheck()
    {
        return $this->belongsTo(BackgroundCheck::class);
    }

    /**
     * Get the user who gave consent.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for valid (not withdrawn) consents.
     */
    public function scopeValid($query)
    {
        return $query->where('consented', true)
            ->where('is_withdrawn', false);
    }

    /**
     * Scope by consent type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    // ========== Encrypted Field Accessors ==========

    /**
     * Get decrypted signature data.
     */
    public function getSignatureDataAttribute(): ?string
    {
        if (!$this->signature_data_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->signature_data_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted signature data.
     */
    public function setSignatureDataAttribute(?string $value): void
    {
        $this->attributes['signature_data_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    // ========== Helper Methods ==========

    /**
     * Check if consent is valid (given and not withdrawn).
     */
    public function isValid(): bool
    {
        return $this->consented && !$this->is_withdrawn;
    }

    /**
     * Record consent.
     */
    public function recordConsent(
        string $signatureType,
        ?string $signatureData = null,
        ?string $signatoryName = null,
        ?string $disclosureText = null
    ): void {
        $this->update([
            'consented' => true,
            'consented_at' => now(),
            'signature_type' => $signatureType,
            'signatory_name' => $signatoryName,
            'full_disclosure_text' => $disclosureText,
            'consent_ip' => request()->ip(),
            'consent_user_agent' => request()->userAgent(),
        ]);

        if ($signatureData) {
            $this->signature_data = $signatureData;
            $this->save();
        }

        $this->addAuditLog('consent_recorded', [
            'signature_type' => $signatureType,
            'signatory_name' => $signatoryName,
        ]);
    }

    /**
     * Withdraw consent.
     */
    public function withdraw(string $reason): void
    {
        $this->update([
            'is_withdrawn' => true,
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason,
        ]);

        $this->addAuditLog('consent_withdrawn', [
            'reason' => $reason,
        ]);
    }

    /**
     * Add an entry to the audit log.
     */
    public function addAuditLog(string $action, array $details = []): void
    {
        $log = $this->audit_log ?? [];

        $log[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->update(['audit_log' => $log]);
    }

    /**
     * Get consent type display name.
     */
    public function getConsentTypeNameAttribute(): string
    {
        $names = [
            self::TYPE_FCRA_DISCLOSURE => 'FCRA Disclosure',
            self::TYPE_FCRA_AUTHORIZATION => 'FCRA Authorization',
            self::TYPE_DBS_CONSENT => 'DBS Consent',
            self::TYPE_GENERAL_CONSENT => 'General Consent',
            self::TYPE_DATA_PROCESSING => 'Data Processing Consent',
        ];

        return $names[$this->consent_type] ?? ucwords(str_replace('_', ' ', $this->consent_type));
    }

    /**
     * Verify document integrity using stored hash.
     */
    public function verifyDocumentIntegrity(string $documentContents): bool
    {
        return $this->document_hash && hash('sha256', $documentContents) === $this->document_hash;
    }

    /**
     * Get FCRA disclosure text for US checks.
     */
    public static function getFCRADisclosureText(): string
    {
        return config('background_check.fcra_disclosure_text',
            "DISCLOSURE REGARDING BACKGROUND INVESTIGATION\n\n" .
            "The Company may obtain information about you from a third party consumer reporting agency " .
            "for employment purposes. Thus, you may be the subject of a \"consumer report\" and/or an " .
            "\"investigative consumer report\" which may include information about your character, general " .
            "reputation, personal characteristics, and/or mode of living, and which can involve personal " .
            "interviews with sources such as your neighbors, friends, or associates. These reports may " .
            "contain information regarding your credit history, criminal history, social security " .
            "verification, motor vehicle records (\"driving records\"), verification of your education " .
            "or employment history, or other background checks.\n\n" .
            "You have the right, upon written request made within a reasonable time, to request whether " .
            "a consumer report has been run about you, disclosure of the nature and scope of any " .
            "investigative consumer report, and to request a copy of your report."
        );
    }

    /**
     * Get FCRA authorization text for US checks.
     */
    public static function getFCRAAuthorizationText(): string
    {
        return config('background_check.fcra_authorization_text',
            "AUTHORIZATION TO OBTAIN CONSUMER REPORT\n\n" .
            "I acknowledge receipt of the separate DISCLOSURE REGARDING BACKGROUND INVESTIGATION and " .
            "A SUMMARY OF YOUR RIGHTS UNDER THE FAIR CREDIT REPORTING ACT and certify that I have read " .
            "and understand both of those documents. I hereby authorize the obtaining of \"consumer reports\" " .
            "and/or \"investigative consumer reports\" by the Company at any time after receipt of this " .
            "authorization and throughout my employment, if applicable. To this end, I hereby authorize, " .
            "without reservation, any law enforcement agency, administrator, state or federal agency, " .
            "institution, school or university (public or private), information service bureau, employer, " .
            "or insurance company to furnish any and all background information requested by the consumer " .
            "reporting agency.\n\n" .
            "I understand that the information obtained by this authorization will be used solely for the " .
            "purpose of evaluating my eligibility for employment or continued employment."
        );
    }
}
