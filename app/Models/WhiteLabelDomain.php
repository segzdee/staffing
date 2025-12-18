<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WhiteLabelDomain Model
 *
 * AGY-006: White-Label Solution - Domain Verification
 * Tracks DNS verification for custom domains used in white-label portals.
 *
 * @property int $id
 * @property int $white_label_config_id
 * @property string $domain
 * @property string $verification_token
 * @property string $verification_method
 * @property bool $is_verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $last_check_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WhiteLabelConfig $config
 */
class WhiteLabelDomain extends Model
{
    use HasFactory;

    /**
     * Verification methods.
     */
    public const METHOD_DNS_TXT = 'dns_txt';

    public const METHOD_DNS_CNAME = 'dns_cname';

    public const METHOD_FILE = 'file';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'white_label_config_id',
        'domain',
        'verification_token',
        'verification_method',
        'is_verified',
        'verified_at',
        'last_check_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_check_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_method' => self::METHOD_DNS_TXT,
        'is_verified' => false,
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the white-label config this domain belongs to.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelConfig::class, 'white_label_config_id');
    }

    /**
     * Get the agency through the config.
     */
    public function agency(): BelongsTo
    {
        return $this->config->agency();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter verified domains.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to filter pending verification domains.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope to filter by verification method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('verification_method', $method);
    }

    /**
     * Scope to find domains needing re-verification (not checked in 24 hours).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsRecheck($query)
    {
        $ttl = config('whitelabel.verification_ttl', 86400);

        return $query->where('is_verified', false)
            ->where(function ($q) use ($ttl) {
                $q->whereNull('last_check_at')
                    ->orWhere('last_check_at', '<', now()->subSeconds($ttl));
            });
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the DNS record name for TXT verification.
     */
    public function getDnsTxtRecordNameAttribute(): string
    {
        return "_overtimestaff-verify.{$this->domain}";
    }

    /**
     * Get the expected DNS TXT record value.
     */
    public function getDnsTxtRecordValueAttribute(): string
    {
        return "overtimestaff-verification={$this->verification_token}";
    }

    /**
     * Get the CNAME target for DNS CNAME verification.
     */
    public function getDnsCnameTargetAttribute(): string
    {
        return 'verify.overtimestaff.com';
    }

    /**
     * Get the DNS CNAME record name.
     */
    public function getDnsCnameRecordNameAttribute(): string
    {
        return "{$this->verification_token}.{$this->domain}";
    }

    /**
     * Get the file verification path.
     */
    public function getFileVerificationPathAttribute(): string
    {
        return '/.well-known/overtimestaff-verification.txt';
    }

    /**
     * Get the expected file verification content.
     */
    public function getFileVerificationContentAttribute(): string
    {
        return "overtimestaff-verification={$this->verification_token}";
    }

    /**
     * Get verification instructions based on method.
     *
     * @return array<string, string>
     */
    public function getVerificationInstructionsAttribute(): array
    {
        return match ($this->verification_method) {
            self::METHOD_DNS_TXT => [
                'type' => 'DNS TXT Record',
                'record_name' => $this->dns_txt_record_name,
                'record_type' => 'TXT',
                'record_value' => $this->dns_txt_record_value,
                'instructions' => "Add a TXT record to your domain's DNS settings with the following values:",
            ],
            self::METHOD_DNS_CNAME => [
                'type' => 'DNS CNAME Record',
                'record_name' => $this->dns_cname_record_name,
                'record_type' => 'CNAME',
                'record_value' => $this->dns_cname_target,
                'instructions' => "Add a CNAME record to your domain's DNS settings with the following values:",
            ],
            self::METHOD_FILE => [
                'type' => 'File Verification',
                'file_path' => $this->file_verification_path,
                'file_content' => $this->file_verification_content,
                'instructions' => 'Create a file at the specified path with the verification content:',
            ],
            default => [
                'type' => 'Unknown',
                'instructions' => 'Please contact support for verification instructions.',
            ],
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_verified) {
            return 'verified';
        }

        if ($this->last_check_at && $this->last_check_at->gt(now()->subMinutes(5))) {
            return 'checking';
        }

        return 'pending';
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'verified' => 'Verified',
            'checking' => 'Verification in Progress',
            'pending' => 'Pending Verification',
            default => 'Unknown',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'verified' => 'bg-green-100 text-green-800',
            'checking' => 'bg-yellow-100 text-yellow-800',
            'pending' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Mark domain as verified.
     */
    public function markVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'last_check_at' => now(),
        ]);

        // Also update the parent config
        $this->config->update([
            'custom_domain' => $this->domain,
            'custom_domain_verified' => true,
        ]);
    }

    /**
     * Mark verification check as performed.
     */
    public function markChecked(): void
    {
        $this->update(['last_check_at' => now()]);
    }

    /**
     * Reset verification (for re-verification).
     */
    public function resetVerification(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    /**
     * Check if verification can be retried (rate limiting).
     */
    public function canRetryVerification(): bool
    {
        if (! $this->last_check_at) {
            return true;
        }

        // Allow retry after 30 seconds
        return $this->last_check_at->lt(now()->subSeconds(30));
    }

    /**
     * Get time until next retry allowed.
     */
    public function getSecondsUntilRetry(): int
    {
        if ($this->canRetryVerification()) {
            return 0;
        }

        return $this->last_check_at->addSeconds(30)->diffInSeconds(now());
    }

    /**
     * Check if domain is expired (not verified within TTL).
     */
    public function isExpired(): bool
    {
        if ($this->is_verified) {
            return false;
        }

        $ttl = config('whitelabel.verification_ttl', 86400);

        return $this->created_at->lt(now()->subSeconds($ttl * 7)); // 7 days to complete verification
    }

    /**
     * Generate a new verification token.
     */
    public function regenerateToken(): void
    {
        $this->update([
            'verification_token' => bin2hex(random_bytes(16)),
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
