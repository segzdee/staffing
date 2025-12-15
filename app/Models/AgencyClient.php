<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $agency_id
 * @property string $company_name
 * @property string $contact_name
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property string|null $address
 * @property string|null $industry
 * @property numeric $default_markup_percent
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereDefaultMarkupPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyClient whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
