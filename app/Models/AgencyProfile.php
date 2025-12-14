<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_name',
        'license_number',
        'license_verified',
        'business_model',
        'commission_rate',
        'managed_workers',
        'total_shifts_managed',
        'total_workers_managed',
    ];

    protected $casts = [
        'license_verified' => 'boolean',
        'commission_rate' => 'decimal:2',
        'managed_workers' => 'array',
        'total_shifts_managed' => 'integer',
        'total_workers_managed' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLicenseVerified()
    {
        return $this->license_verified;
    }
}
