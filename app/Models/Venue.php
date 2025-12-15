<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_profile_id',
        'name',
        'code',
        'description',
        'address',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'contact_person',
        'monthly_budget',
        'current_month_spend',
        'ytd_spend',
        'total_shifts',
        'completed_shifts',
        'cancelled_shifts',
        'fill_rate',
        'average_rating',
        'is_active',
    ];

    protected $casts = [
        'monthly_budget' => 'integer',
        'current_month_spend' => 'integer',
        'ytd_spend' => 'integer',
        'total_shifts' => 'integer',
        'completed_shifts' => 'integer',
        'cancelled_shifts' => 'integer',
        'fill_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business profile that owns the venue.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get all shifts for this venue.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Calculate budget utilization percentage.
     */
    public function getBudgetUtilizationAttribute()
    {
        if ($this->monthly_budget <= 0) {
            return 0;
        }

        return round(($this->current_month_spend / $this->monthly_budget) * 100, 2);
    }

    /**
     * Check if budget alert threshold is reached.
     */
    public function hasBudgetAlertThreshold($threshold)
    {
        return $this->budget_utilization >= $threshold;
    }

    /**
     * Get remaining budget for the month.
     */
    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->monthly_budget - $this->current_month_spend);
    }

    /**
     * Format money values from cents to dollars.
     */
    public function getMonthlyBudgetDollarsAttribute()
    {
        return $this->monthly_budget / 100;
    }

    public function getCurrentMonthSpendDollarsAttribute()
    {
        return $this->current_month_spend / 100;
    }

    public function getYtdSpendDollarsAttribute()
    {
        return $this->ytd_spend / 100;
    }

    /**
     * Scope to get active venues only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get venues by business.
     */
    public function scopeForBusiness($query, $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }
}
