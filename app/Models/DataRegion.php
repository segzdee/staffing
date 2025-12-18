<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GLO-010: Data Residency System - DataRegion Model
 *
 * Represents a geographic data storage region with associated compliance frameworks.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property array $countries
 * @property string $primary_storage
 * @property string|null $backup_storage
 * @property array $compliance_frameworks
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DataRegion extends Model
{
    use HasFactory;

    /**
     * Region code constants.
     */
    public const REGION_EU = 'eu';

    public const REGION_UK = 'uk';

    public const REGION_US = 'us';

    public const REGION_APAC = 'apac';

    public const REGION_LATAM = 'latam';

    public const REGION_MEA = 'mea';

    /**
     * Compliance framework constants.
     */
    public const FRAMEWORK_GDPR = 'GDPR';

    public const FRAMEWORK_UK_GDPR = 'UK-GDPR';

    public const FRAMEWORK_CCPA = 'CCPA';

    public const FRAMEWORK_APP = 'APP';

    public const FRAMEWORK_PDPA = 'PDPA';

    public const FRAMEWORK_LGPD = 'LGPD';

    public const FRAMEWORK_POPIA = 'POPIA';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'countries',
        'primary_storage',
        'backup_storage',
        'compliance_frameworks',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'compliance_frameworks' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all user data residency records for this region.
     */
    public function userDataResidencies(): HasMany
    {
        return $this->hasMany(UserDataResidency::class);
    }

    /**
     * Scope: Only active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a country belongs to this region.
     */
    public function hasCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), $this->countries ?? [], true);
    }

    /**
     * Check if a compliance framework applies to this region.
     */
    public function hasComplianceFramework(string $framework): bool
    {
        return in_array($framework, $this->compliance_frameworks ?? [], true);
    }

    /**
     * Get the storage disk name for this region.
     */
    public function getStorageDisk(): string
    {
        return config("data_residency.storage_disks.{$this->code}", $this->primary_storage);
    }

    /**
     * Get the backup storage disk name for this region.
     */
    public function getBackupStorageDisk(): ?string
    {
        return $this->backup_storage;
    }

    /**
     * Get the count of users in this region.
     */
    public function getUserCountAttribute(): int
    {
        return $this->userDataResidencies()->count();
    }

    /**
     * Find a region by country code.
     */
    public static function findByCountry(string $countryCode): ?self
    {
        $countryCode = strtoupper($countryCode);

        return static::active()
            ->get()
            ->first(function ($region) use ($countryCode) {
                return $region->hasCountry($countryCode);
            });
    }

    /**
     * Get the default region.
     */
    public static function getDefault(): ?self
    {
        $defaultCode = config('data_residency.default_region', 'us');

        return static::where('code', $defaultCode)->first();
    }

    /**
     * Get all compliance frameworks as options array.
     */
    public static function getComplianceFrameworkOptions(): array
    {
        return [
            self::FRAMEWORK_GDPR => 'GDPR (EU General Data Protection Regulation)',
            self::FRAMEWORK_UK_GDPR => 'UK GDPR (UK General Data Protection Regulation)',
            self::FRAMEWORK_CCPA => 'CCPA (California Consumer Privacy Act)',
            self::FRAMEWORK_APP => 'APP (Australian Privacy Principles)',
            self::FRAMEWORK_PDPA => 'PDPA (Personal Data Protection Act - Singapore)',
            self::FRAMEWORK_LGPD => 'LGPD (Brazil General Data Protection Law)',
            self::FRAMEWORK_POPIA => 'POPIA (Protection of Personal Information Act - South Africa)',
        ];
    }
}
