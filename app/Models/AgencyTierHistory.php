<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyTierHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'agency_tier_history';

    /**
     * Change type constants.
     */
    public const CHANGE_TYPE_UPGRADE = 'upgrade';

    public const CHANGE_TYPE_DOWNGRADE = 'downgrade';

    public const CHANGE_TYPE_INITIAL = 'initial';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_id',
        'from_tier_id',
        'to_tier_id',
        'change_type',
        'metrics_at_change',
        'notes',
        'processed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics_at_change' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the agency user this history belongs to.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the source tier (null for initial assignment).
     */
    public function fromTier(): BelongsTo
    {
        return $this->belongsTo(AgencyTier::class, 'from_tier_id');
    }

    /**
     * Get the destination tier.
     */
    public function toTier(): BelongsTo
    {
        return $this->belongsTo(AgencyTier::class, 'to_tier_id');
    }

    /**
     * Get the user who processed the tier change (for manual adjustments).
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only upgrades.
     */
    public function scopeUpgrades($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_UPGRADE);
    }

    /**
     * Scope to only downgrades.
     */
    public function scopeDowngrades($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_DOWNGRADE);
    }

    /**
     * Scope to initial tier assignments.
     */
    public function scopeInitial($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_INITIAL);
    }

    /**
     * Scope for a specific agency.
     */
    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get a human-readable description of the tier change.
     */
    public function getDescriptionAttribute(): string
    {
        $fromName = $this->fromTier?->name ?? 'None';
        $toName = $this->toTier?->name ?? 'Unknown';

        return match ($this->change_type) {
            self::CHANGE_TYPE_INITIAL => "Assigned to {$toName} tier",
            self::CHANGE_TYPE_UPGRADE => "Upgraded from {$fromName} to {$toName}",
            self::CHANGE_TYPE_DOWNGRADE => "Downgraded from {$fromName} to {$toName}",
            default => "Changed from {$fromName} to {$toName}",
        };
    }

    /**
     * Get CSS class for the change type badge.
     */
    public function getChangeTypeBadgeClassAttribute(): string
    {
        return match ($this->change_type) {
            self::CHANGE_TYPE_UPGRADE => 'bg-green-100 text-green-800',
            self::CHANGE_TYPE_DOWNGRADE => 'bg-red-100 text-red-800',
            self::CHANGE_TYPE_INITIAL => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get icon for the change type.
     */
    public function getChangeTypeIconAttribute(): string
    {
        return match ($this->change_type) {
            self::CHANGE_TYPE_UPGRADE => 'heroicon-o-arrow-trending-up',
            self::CHANGE_TYPE_DOWNGRADE => 'heroicon-o-arrow-trending-down',
            self::CHANGE_TYPE_INITIAL => 'heroicon-o-plus-circle',
            default => 'heroicon-o-arrow-path',
        };
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if this was an upgrade.
     */
    public function isUpgrade(): bool
    {
        return $this->change_type === self::CHANGE_TYPE_UPGRADE;
    }

    /**
     * Check if this was a downgrade.
     */
    public function isDowngrade(): bool
    {
        return $this->change_type === self::CHANGE_TYPE_DOWNGRADE;
    }

    /**
     * Check if this was an initial assignment.
     */
    public function isInitial(): bool
    {
        return $this->change_type === self::CHANGE_TYPE_INITIAL;
    }

    /**
     * Check if this change was processed by an admin.
     */
    public function wasManuallyProcessed(): bool
    {
        return $this->processed_by !== null;
    }

    /**
     * Get a specific metric from the snapshot.
     */
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return $this->metrics_at_change[$key] ?? $default;
    }
}
