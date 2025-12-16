<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * STAFF-REG-007: Enhanced WorkerSkill Model
 *
 * Worker's skills with experience level and verification status.
 *
 * @property int $id
 * @property int $worker_id
 * @property int $skill_id
 * @property string $proficiency_level
 * @property string $experience_level
 * @property int $years_experience
 * @property string|null $experience_notes
 * @property \Illuminate\Support\Carbon|null $last_used_date
 * @property bool $verified
 * @property bool $self_assessed
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkerSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'skill_id',
        'proficiency_level',
        'experience_level',
        'years_experience',
        'experience_notes',
        'last_used_date',
        'verified',
        'self_assessed',
        'verified_at',
        'verified_by',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'verified' => 'boolean',
        'self_assessed' => 'boolean',
        'is_active' => 'boolean',
        'last_used_date' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the worker who has this skill.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the skill definition.
     */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Get the user who verified this skill.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope: Only verified skills.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope: Only active skills.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by experience level.
     */
    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope: Filter by industry.
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->whereHas('skill', function ($q) use ($industry) {
            $q->where('industry', $industry);
        });
    }

    /**
     * Check if this worker skill meets certification requirements.
     */
    public function meetsCertificationRequirements(): bool
    {
        if (!$this->skill || !$this->skill->hasCertificationRequirements()) {
            return true;
        }

        $requiredCertTypes = $this->skill->certificationRequirements()
            ->where('is_active', true)
            ->where('requirement_level', 'required')
            ->pluck('certification_type_id');

        if ($requiredCertTypes->isEmpty()) {
            return true;
        }

        $workerCertTypeIds = WorkerCertification::where('worker_id', $this->worker_id)
            ->whereNotNull('certification_type_id')
            ->where('verified', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->pluck('certification_type_id');

        return $requiredCertTypes->diff($workerCertTypeIds)->isEmpty();
    }

    /**
     * Get missing certifications for this skill.
     */
    public function getMissingCertifications(): array
    {
        if (!$this->skill || !$this->skill->hasCertificationRequirements()) {
            return [];
        }

        $requiredCertTypes = $this->skill->certificationRequirements()
            ->where('is_active', true)
            ->where('requirement_level', 'required')
            ->with('certificationType')
            ->get();

        $workerCertTypeIds = WorkerCertification::where('worker_id', $this->worker_id)
            ->whereNotNull('certification_type_id')
            ->where('verified', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->pluck('certification_type_id')
            ->toArray();

        $missing = [];
        foreach ($requiredCertTypes as $req) {
            if (!in_array($req->certification_type_id, $workerCertTypeIds)) {
                $missing[] = $req->certificationType;
            }
        }

        return $missing;
    }

    /**
     * Verify this skill.
     */
    public function verify(int $verifiedBy): void
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }

    /**
     * Deactivate skill (e.g., if certification expires).
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactivate skill.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Get the experience level label.
     */
    public function getExperienceLevelLabelAttribute(): string
    {
        return Skill::EXPERIENCE_LEVELS[$this->experience_level] ?? $this->experience_level;
    }

    /**
     * Calculate recommended experience level based on years.
     */
    public static function calculateExperienceLevel(int $years): string
    {
        return match (true) {
            $years >= 5 => Skill::LEVEL_EXPERT,
            $years >= 3 => Skill::LEVEL_ADVANCED,
            $years >= 1 => Skill::LEVEL_INTERMEDIATE,
            default => Skill::LEVEL_ENTRY,
        };
    }
}
