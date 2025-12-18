<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-003: ShiftCertificationRequirement Model
 *
 * Pivot model representing the requirement for a safety certification on a shift.
 * Links shifts to the safety certifications workers must have to be eligible.
 *
 * @property int $id
 * @property int $shift_id
 * @property int $safety_certification_id
 * @property bool $is_mandatory
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ShiftCertificationRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'safety_certification_id',
        'is_mandatory',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    /**
     * Get the shift this requirement belongs to.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the safety certification required.
     */
    public function safetyCertification(): BelongsTo
    {
        return $this->belongsTo(SafetyCertification::class);
    }

    /**
     * Scope: Mandatory requirements only.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope: Optional requirements only.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    /**
     * Check if a worker meets this requirement.
     */
    public function workerMeetsRequirement(User $worker): bool
    {
        return WorkerCertification::where('worker_id', $worker->id)
            ->where('safety_certification_id', $this->safety_certification_id)
            ->where('verification_status', WorkerCertification::STATUS_VERIFIED)
            ->where('verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->exists();
    }
}
