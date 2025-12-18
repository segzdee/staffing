<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-005: COVID/Health Protocols - Health Declaration Model
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property bool $fever_free
 * @property bool $no_symptoms
 * @property bool $no_exposure
 * @property bool $fit_for_work
 * @property \Illuminate\Support\Carbon $declared_at
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Shift|null $shift
 */
class HealthDeclaration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shift_id',
        'fever_free',
        'no_symptoms',
        'no_exposure',
        'fit_for_work',
        'declared_at',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fever_free' => 'boolean',
        'no_symptoms' => 'boolean',
        'no_exposure' => 'boolean',
        'fit_for_work' => 'boolean',
        'declared_at' => 'datetime',
    ];

    /**
     * Get the user who made this declaration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift this declaration is for (if any).
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Check if the declaration indicates the worker is cleared to work.
     */
    public function isClearedToWork(): bool
    {
        return $this->fever_free
            && $this->no_symptoms
            && $this->no_exposure
            && $this->fit_for_work;
    }

    /**
     * Check if the declaration is still valid (within 24 hours).
     */
    public function isValid(): bool
    {
        return $this->declared_at->diffInHours(now()) < 24;
    }

    /**
     * Check if this declaration is valid for the given shift.
     */
    public function isValidForShift(Shift $shift): bool
    {
        // Declaration must be cleared
        if (! $this->isClearedToWork()) {
            return false;
        }

        // Declaration must be recent (within 24 hours)
        if (! $this->isValid()) {
            return false;
        }

        // If declaration is tied to a specific shift, it must match
        if ($this->shift_id && $this->shift_id !== $shift->id) {
            return false;
        }

        return true;
    }

    /**
     * Get the list of health concerns based on the declaration.
     */
    public function getHealthConcerns(): array
    {
        $concerns = [];

        if (! $this->fever_free) {
            $concerns[] = 'Reported fever or elevated temperature';
        }

        if (! $this->no_symptoms) {
            $concerns[] = 'Reported symptoms (cough, shortness of breath, etc.)';
        }

        if (! $this->no_exposure) {
            $concerns[] = 'Reported recent exposure to infectious disease';
        }

        if (! $this->fit_for_work) {
            $concerns[] = 'Reported not fit for work';
        }

        return $concerns;
    }

    /**
     * Scope to get declarations for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get declarations for a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to get recent declarations (within specified hours).
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('declared_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to get cleared declarations only.
     */
    public function scopeCleared($query)
    {
        return $query->where('fever_free', true)
            ->where('no_symptoms', true)
            ->where('no_exposure', true)
            ->where('fit_for_work', true);
    }

    /**
     * Scope to get flagged declarations (with health concerns).
     */
    public function scopeFlagged($query)
    {
        return $query->where(function ($q) {
            $q->where('fever_free', false)
                ->orWhere('no_symptoms', false)
                ->orWhere('no_exposure', false)
                ->orWhere('fit_for_work', false);
        });
    }
}
