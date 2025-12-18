<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-007: Tax Reporting - Tax Report Model
 *
 * @property int $id
 * @property int $user_id
 * @property int $tax_year
 * @property string $report_type
 * @property float $total_earnings
 * @property float $total_fees
 * @property float $total_taxes_withheld
 * @property int $total_shifts
 * @property array|null $monthly_breakdown
 * @property array|null $jurisdiction_breakdown
 * @property string|null $document_url
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class TaxReport extends Model
{
    use HasFactory;

    // Report types
    public const TYPE_1099_NEC = '1099_nec';

    public const TYPE_1099_K = '1099_k';

    public const TYPE_P60 = 'p60';

    public const TYPE_PAYMENT_SUMMARY = 'payment_summary';

    public const TYPE_ANNUAL_STATEMENT = 'annual_statement';

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    // US 1099-NEC threshold
    public const US_1099_THRESHOLD = 600.00;

    protected $fillable = [
        'user_id',
        'tax_year',
        'report_type',
        'total_earnings',
        'total_fees',
        'total_taxes_withheld',
        'total_shifts',
        'monthly_breakdown',
        'jurisdiction_breakdown',
        'document_url',
        'status',
        'generated_at',
        'sent_at',
    ];

    protected $casts = [
        'tax_year' => 'integer',
        'total_earnings' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'total_taxes_withheld' => 'decimal:2',
        'total_shifts' => 'integer',
        'monthly_breakdown' => 'array',
        'jurisdiction_breakdown' => 'array',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * User who owns this tax report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for a specific tax year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    /**
     * Scope for a specific report type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope for draft reports.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for generated reports.
     */
    public function scopeGenerated($query)
    {
        return $query->whereIn('status', [self::STATUS_GENERATED, self::STATUS_SENT, self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * Scope for reports that need to be sent.
     */
    public function scopePendingSend($query)
    {
        return $query->where('status', self::STATUS_GENERATED);
    }

    /**
     * Check if the report is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the report has been generated.
     */
    public function isGenerated(): bool
    {
        return in_array($this->status, [self::STATUS_GENERATED, self::STATUS_SENT, self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * Check if the report has been sent to the user.
     */
    public function isSent(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * Check if user meets 1099-NEC threshold.
     */
    public function meets1099Threshold(): bool
    {
        return $this->total_earnings >= self::US_1099_THRESHOLD;
    }

    /**
     * Get the net earnings (total earnings - fees - taxes withheld).
     */
    public function getNetEarningsAttribute(): float
    {
        return $this->total_earnings - $this->total_fees - $this->total_taxes_withheld;
    }

    /**
     * Get human-readable report type name.
     */
    public function getReportTypeNameAttribute(): string
    {
        return match ($this->report_type) {
            self::TYPE_1099_NEC => 'Form 1099-NEC',
            self::TYPE_1099_K => 'Form 1099-K',
            self::TYPE_P60 => 'P60 End of Year Certificate',
            self::TYPE_PAYMENT_SUMMARY => 'Payment Summary',
            self::TYPE_ANNUAL_STATEMENT => 'Annual Statement',
            default => ucfirst(str_replace('_', ' ', $this->report_type)),
        };
    }

    /**
     * Get human-readable status name.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_SENT => 'Sent',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            default => ucfirst($this->status),
        };
    }

    /**
     * Mark report as generated.
     */
    public function markAsGenerated(?string $documentUrl = null): self
    {
        $this->update([
            'status' => self::STATUS_GENERATED,
            'generated_at' => now(),
            'document_url' => $documentUrl ?? $this->document_url,
        ]);

        return $this;
    }

    /**
     * Mark report as sent.
     */
    public function markAsSent(): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark report as acknowledged by user.
     */
    public function markAsAcknowledged(): self
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
        ]);

        return $this;
    }

    /**
     * Get available report types for a country.
     */
    public static function getReportTypesForCountry(string $countryCode): array
    {
        return match (strtoupper($countryCode)) {
            'US' => [self::TYPE_1099_NEC, self::TYPE_1099_K, self::TYPE_ANNUAL_STATEMENT],
            'GB' => [self::TYPE_P60, self::TYPE_ANNUAL_STATEMENT],
            'AU' => [self::TYPE_PAYMENT_SUMMARY, self::TYPE_ANNUAL_STATEMENT],
            default => [self::TYPE_ANNUAL_STATEMENT],
        };
    }

    /**
     * Get the primary report type for a country.
     */
    public static function getPrimaryReportTypeForCountry(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'US' => self::TYPE_1099_NEC,
            'GB' => self::TYPE_P60,
            'AU' => self::TYPE_PAYMENT_SUMMARY,
            default => self::TYPE_ANNUAL_STATEMENT,
        };
    }
}
