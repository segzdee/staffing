<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * SAF-003: SafetyCertification Model
 *
 * Represents a type of safety certification that can be required for shifts
 * or held by workers. Examples include Food Handler Certificate, First Aid/CPR,
 * TIPS Alcohol Certification, etc.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $category
 * @property string|null $issuing_authority
 * @property int|null $validity_months
 * @property bool $requires_renewal
 * @property array|null $applicable_industries
 * @property array|null $applicable_positions
 * @property bool $is_mandatory
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SafetyCertification extends Model
{
    use HasFactory;

    /**
     * Category constants
     */
    public const CATEGORY_FOOD_SAFETY = 'food_safety';

    public const CATEGORY_HEALTH = 'health';

    public const CATEGORY_SECURITY = 'security';

    public const CATEGORY_INDUSTRY_SPECIFIC = 'industry_specific';

    public const CATEGORY_GENERAL = 'general';

    /**
     * Category labels for display
     */
    public const CATEGORY_LABELS = [
        self::CATEGORY_FOOD_SAFETY => 'Food Safety',
        self::CATEGORY_HEALTH => 'Health & Medical',
        self::CATEGORY_SECURITY => 'Security',
        self::CATEGORY_INDUSTRY_SPECIFIC => 'Industry Specific',
        self::CATEGORY_GENERAL => 'General',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'issuing_authority',
        'validity_months',
        'requires_renewal',
        'applicable_industries',
        'applicable_positions',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'validity_months' => 'integer',
        'requires_renewal' => 'boolean',
        'applicable_industries' => 'array',
        'applicable_positions' => 'array',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method to auto-generate slug.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SafetyCertification $certification) {
            if (empty($certification->slug)) {
                $certification->slug = Str::slug($certification->name);
            }
        });
    }

    /**
     * Get all worker certifications of this type.
     */
    public function workerCertifications(): HasMany
    {
        return $this->hasMany(WorkerCertification::class, 'safety_certification_id');
    }

    /**
     * Get all shifts that require this certification.
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_certification_requirements')
            ->withPivot('is_mandatory')
            ->withTimestamps();
    }

    /**
     * Get shift certification requirements.
     */
    public function shiftRequirements(): HasMany
    {
        return $this->hasMany(ShiftCertificationRequirement::class);
    }

    /**
     * Get workers who have this certification (verified and valid).
     */
    public function certifiedWorkers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'worker_certifications', 'safety_certification_id', 'worker_id')
            ->wherePivot('verification_status', WorkerCertification::STATUS_VERIFIED)
            ->wherePivot('verified', true)
            ->where(function ($query) {
                $query->whereNull('worker_certifications.expiry_date')
                    ->orWhere('worker_certifications.expiry_date', '>', now());
            })
            ->withPivot('certification_number', 'issue_date', 'expiry_date', 'verification_status')
            ->withTimestamps();
    }

    /**
     * Scope: Active certifications only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Mandatory certifications only.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by applicable industry.
     */
    public function scopeForIndustry($query, string $industry)
    {
        return $query->where(function ($q) use ($industry) {
            $q->whereNull('applicable_industries')
                ->orWhereJsonContains('applicable_industries', $industry);
        });
    }

    /**
     * Scope: Filter by applicable position.
     */
    public function scopeForPosition($query, string $position)
    {
        return $query->where(function ($q) use ($position) {
            $q->whereNull('applicable_positions')
                ->orWhereJsonContains('applicable_positions', $position);
        });
    }

    /**
     * Check if this certification is applicable to a specific industry.
     */
    public function isApplicableToIndustry(?string $industry): bool
    {
        if (empty($this->applicable_industries)) {
            return true; // No restriction means applicable to all
        }

        return in_array($industry, $this->applicable_industries, true);
    }

    /**
     * Check if this certification is applicable to a specific position.
     */
    public function isApplicableToPosition(?string $position): bool
    {
        if (empty($this->applicable_positions)) {
            return true; // No restriction means applicable to all
        }

        return in_array($position, $this->applicable_positions, true);
    }

    /**
     * Get the category label for display.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    /**
     * Get validity period formatted for display.
     */
    public function getValidityDisplayAttribute(): string
    {
        if (! $this->validity_months) {
            return 'No expiration';
        }

        if ($this->validity_months >= 12) {
            $years = floor($this->validity_months / 12);
            $months = $this->validity_months % 12;

            if ($months === 0) {
                return $years === 1 ? '1 year' : "{$years} years";
            }

            return "{$years} year(s), {$months} month(s)";
        }

        return $this->validity_months === 1 ? '1 month' : "{$this->validity_months} months";
    }

    /**
     * Calculate expiry date from issue date.
     */
    public function calculateExpiryDate($issueDate): ?\Carbon\Carbon
    {
        if (! $this->validity_months) {
            return null;
        }

        return \Carbon\Carbon::parse($issueDate)->addMonths($this->validity_months);
    }

    /**
     * Get count of workers with valid certification.
     */
    public function getValidCertificationsCount(): int
    {
        return $this->workerCertifications()
            ->where('verification_status', WorkerCertification::STATUS_VERIFIED)
            ->where('verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->count();
    }

    /**
     * Get count of pending verification requests.
     */
    public function getPendingVerificationsCount(): int
    {
        return $this->workerCertifications()
            ->where('verification_status', WorkerCertification::STATUS_PENDING)
            ->count();
    }

    /**
     * Get all category options for forms.
     */
    public static function getCategoryOptions(): array
    {
        return self::CATEGORY_LABELS;
    }
}
