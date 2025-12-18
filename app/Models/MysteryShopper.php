<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QUA-002: Quality Audits - Mystery Shopper Model
 *
 * Represents users who are enrolled in the mystery shopper program
 * to perform undercover quality audits at venues.
 *
 * @property int $id
 * @property int $user_id
 * @property bool $is_active
 * @property int $audits_completed
 * @property float|null $avg_quality_score
 * @property array|null $specializations
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MysteryShopper extends Model
{
    use HasFactory;

    /**
     * Minimum audits for reliable quality score.
     */
    public const MIN_AUDITS_FOR_RELIABLE_SCORE = 5;

    /**
     * Quality score threshold for preferred shopper status.
     */
    public const PREFERRED_SCORE_THRESHOLD = 85.0;

    protected $fillable = [
        'user_id',
        'is_active',
        'audits_completed',
        'avg_quality_score',
        'specializations',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'audits_completed' => 'integer',
        'avg_quality_score' => 'decimal:2',
        'specializations' => 'array',
    ];

    protected $appends = [
        'status_label',
        'is_preferred',
        'experience_level',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the user associated with this mystery shopper profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get audits performed by this mystery shopper.
     */
    public function audits()
    {
        return $this->hasMany(ShiftAudit::class, 'auditor_id', 'user_id')
            ->where('audit_type', ShiftAudit::TYPE_MYSTERY_SHOPPER);
    }

    /**
     * Get completed audits.
     */
    public function completedAudits()
    {
        return $this->audits()->completed();
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Check if this is a preferred mystery shopper.
     */
    public function getIsPreferredAttribute(): bool
    {
        return $this->audits_completed >= self::MIN_AUDITS_FOR_RELIABLE_SCORE
            && $this->avg_quality_score >= self::PREFERRED_SCORE_THRESHOLD;
    }

    /**
     * Get the experience level based on completed audits.
     */
    public function getExperienceLevelAttribute(): string
    {
        return match (true) {
            $this->audits_completed >= 100 => 'Expert',
            $this->audits_completed >= 50 => 'Senior',
            $this->audits_completed >= 20 => 'Experienced',
            $this->audits_completed >= 5 => 'Intermediate',
            default => 'Novice'
        };
    }

    /**
     * Get the experience level color for UI.
     */
    public function getExperienceLevelColorAttribute(): string
    {
        return match ($this->experience_level) {
            'Expert' => 'purple',
            'Senior' => 'blue',
            'Experienced' => 'green',
            'Intermediate' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get specializations as a readable string.
     */
    public function getSpecializationsDisplayAttribute(): string
    {
        if (empty($this->specializations)) {
            return 'All venue types';
        }

        // Map venue type codes to labels
        $labels = collect($this->specializations)->map(function ($type) {
            return Venue::TYPES[$type] ?? ucfirst($type);
        });

        return $labels->implode(', ');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get active mystery shoppers only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get preferred mystery shoppers.
     */
    public function scopePreferred($query)
    {
        return $query->where('audits_completed', '>=', self::MIN_AUDITS_FOR_RELIABLE_SCORE)
            ->where('avg_quality_score', '>=', self::PREFERRED_SCORE_THRESHOLD);
    }

    /**
     * Scope to get mystery shoppers with a specific specialization.
     */
    public function scopeWithSpecialization($query, string $venueType)
    {
        return $query->where(function ($q) use ($venueType) {
            $q->whereNull('specializations')
                ->orWhereJsonContains('specializations', $venueType);
        });
    }

    /**
     * Scope to get mystery shoppers ordered by quality score.
     */
    public function scopeOrderByQuality($query, string $direction = 'desc')
    {
        return $query->orderBy('avg_quality_score', $direction)
            ->orderBy('audits_completed', 'desc');
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Activate this mystery shopper.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this mystery shopper.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Add a specialization.
     */
    public function addSpecialization(string $venueType): void
    {
        $specializations = $this->specializations ?? [];
        if (! in_array($venueType, $specializations)) {
            $specializations[] = $venueType;
            $this->update(['specializations' => $specializations]);
        }
    }

    /**
     * Remove a specialization.
     */
    public function removeSpecialization(string $venueType): void
    {
        $specializations = $this->specializations ?? [];
        $specializations = array_values(array_filter(
            $specializations,
            fn ($s) => $s !== $venueType
        ));
        $this->update(['specializations' => $specializations]);
    }

    /**
     * Check if mystery shopper has a specific specialization.
     */
    public function hasSpecialization(string $venueType): bool
    {
        // If no specializations set, they can audit any venue type
        if (empty($this->specializations)) {
            return true;
        }

        return in_array($venueType, $this->specializations);
    }

    /**
     * Record a completed audit and update statistics.
     */
    public function recordAuditCompletion(ShiftAudit $audit): void
    {
        // Get all completed audits for this mystery shopper
        $completedAudits = ShiftAudit::where('auditor_id', $this->user_id)
            ->where('audit_type', ShiftAudit::TYPE_MYSTERY_SHOPPER)
            ->completed()
            ->whereNotNull('overall_score')
            ->get();

        // Calculate new average quality score
        $avgScore = $completedAudits->avg('overall_score');

        $this->update([
            'audits_completed' => $completedAudits->count(),
            'avg_quality_score' => round($avgScore, 2),
        ]);
    }

    /**
     * Check if mystery shopper can be assigned to a venue.
     */
    public function canAuditVenue(Venue $venue): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->hasSpecialization($venue->type);
    }

    /**
     * Get audit history statistics.
     */
    public function getAuditStatistics(): array
    {
        $audits = $this->completedAudits()->get();

        return [
            'total_completed' => $audits->count(),
            'average_score' => round($audits->avg('overall_score'), 2),
            'pass_rate' => $audits->count() > 0
                ? round(($audits->where('passed', true)->count() / $audits->count()) * 100, 2)
                : 0,
            'this_month' => $audits->where('completed_at', '>=', now()->startOfMonth())->count(),
            'this_year' => $audits->where('completed_at', '>=', now()->startOfYear())->count(),
        ];
    }

    /**
     * Find an available mystery shopper for a venue.
     */
    public static function findAvailableForVenue(Venue $venue, ?int $excludeUserId = null): ?self
    {
        $query = self::active()
            ->withSpecialization($venue->type)
            ->orderByQuality();

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->first();
    }
}
