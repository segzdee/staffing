<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BusinessAddress Model
 * BIZ-REG-003: Stores address information for businesses
 *
 * @property int $id
 * @property int $business_profile_id
 * @property string $address_type
 * @property string|null $label
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $city
 * @property string $state_province
 * @property string $postal_code
 * @property string $country_code
 * @property string $country_name
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property string|null $jurisdiction_code
 * @property string|null $tax_region
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property bool $is_primary
 * @property bool $is_active
 * @property bool $is_verified
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read BusinessProfile $businessProfile
 */
class BusinessAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_profile_id',
        'address_type',
        'label',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country_code',
        'country_name',
        'latitude',
        'longitude',
        'timezone',
        'jurisdiction_code',
        'tax_region',
        'contact_name',
        'contact_phone',
        'is_primary',
        'is_active',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Address type constants
     */
    const TYPE_REGISTERED = 'registered';
    const TYPE_BILLING = 'billing';
    const TYPE_OPERATING = 'operating';
    const TYPE_MAILING = 'mailing';
    const TYPE_HEADQUARTERS = 'headquarters';

    /**
     * Get the business profile that owns this address.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the full formatted address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country_name,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the short formatted address (city, state, country).
     */
    public function getShortAddressAttribute(): string
    {
        $parts = [
            $this->city,
            $this->state_province,
            $this->country_code,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the street address (line 1 and 2).
     */
    public function getStreetAddressAttribute(): string
    {
        $parts = [$this->address_line_1];
        if ($this->address_line_2) {
            $parts[] = $this->address_line_2;
        }
        return implode(', ', $parts);
    }

    /**
     * Check if address has geolocation data.
     */
    public function hasGeolocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasGeolocation()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Set coordinates from array or individual values.
     */
    public function setCoordinates(float $latitude, float $longitude): void
    {
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * Mark this address as primary.
     */
    public function markAsPrimary(): void
    {
        // Remove primary flag from other addresses of same type
        static::where('business_profile_id', $this->business_profile_id)
            ->where('address_type', $this->address_type)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Mark address as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Calculate distance to another address in kilometers.
     */
    public function distanceTo(BusinessAddress $other): ?float
    {
        if (!$this->hasGeolocation() || !$other->hasGeolocation()) {
            return null;
        }

        // Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Scope to get active addresses only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary addresses only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to filter by address type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('address_type', $type);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeInCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope to get verified addresses only.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to find addresses within a radius (km) of coordinates.
     */
    public function scopeWithinRadius($query, float $lat, float $lng, float $radiusKm)
    {
        // Using Haversine formula in SQL
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }
}
