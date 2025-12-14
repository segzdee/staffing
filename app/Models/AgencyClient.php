<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'company_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'industry',
        'default_markup_percent',
        'status',
    ];

    protected $casts = [
        'default_markup_percent' => 'decimal:2',
    ];

    /**
     * Get the agency that owns this client.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get all shifts posted for this client.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'agency_client_id');
    }

    /**
     * Scope to get only active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
