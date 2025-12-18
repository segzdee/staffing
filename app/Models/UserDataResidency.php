<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-010: Data Residency System - UserDataResidency Model
 *
 * Tracks which data region a user's data is stored in, along with consent information.
 *
 * @property int $id
 * @property int $user_id
 * @property int $data_region_id
 * @property string $detected_country
 * @property bool $user_selected
 * @property \Illuminate\Support\Carbon|null $consent_given_at
 * @property array|null $data_locations
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read DataRegion $dataRegion
 */
class UserDataResidency extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_data_residency';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'data_region_id',
        'detected_country',
        'user_selected',
        'consent_given_at',
        'data_locations',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'user_selected' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_selected' => 'boolean',
            'consent_given_at' => 'datetime',
            'data_locations' => 'array',
        ];
    }

    /**
     * Get the user that owns this data residency record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the data region for this user.
     */
    public function dataRegion(): BelongsTo
    {
        return $this->belongsTo(DataRegion::class);
    }

    /**
     * Record consent for data storage in the assigned region.
     */
    public function recordConsent(): self
    {
        $this->update([
            'consent_given_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if consent has been given.
     */
    public function hasConsent(): bool
    {
        return $this->consent_given_at !== null;
    }

    /**
     * Mark that the user manually selected this region.
     */
    public function markAsUserSelected(): self
    {
        $this->update([
            'user_selected' => true,
        ]);

        return $this;
    }

    /**
     * Update the data locations tracking.
     */
    public function updateDataLocations(array $locations): self
    {
        $currentLocations = $this->data_locations ?? [];
        $mergedLocations = array_merge($currentLocations, $locations);

        $this->update([
            'data_locations' => $mergedLocations,
        ]);

        return $this;
    }

    /**
     * Get a specific data location.
     */
    public function getDataLocation(string $dataType): ?string
    {
        return $this->data_locations[$dataType] ?? null;
    }

    /**
     * Check if the user's data is stored in a specific storage location.
     */
    public function hasDataIn(string $storage): bool
    {
        if (! $this->data_locations) {
            return false;
        }

        return in_array($storage, $this->data_locations, true);
    }

    /**
     * Get the storage path prefix for this user.
     */
    public function getStoragePathPrefix(): string
    {
        return "regions/{$this->dataRegion->code}/users/{$this->user_id}";
    }

    /**
     * Scope: Users who have given consent.
     */
    public function scopeWithConsent($query)
    {
        return $query->whereNotNull('consent_given_at');
    }

    /**
     * Scope: Users who manually selected their region.
     */
    public function scopeUserSelected($query)
    {
        return $query->where('user_selected', true);
    }

    /**
     * Scope: Filter by region.
     */
    public function scopeInRegion($query, $regionId)
    {
        return $query->where('data_region_id', $regionId);
    }

    /**
     * Scope: Filter by detected country.
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('detected_country', strtoupper($countryCode));
    }
}
