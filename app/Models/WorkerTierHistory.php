<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-007: Worker Career Tiers System - History Tracking
 *
 * Records all tier changes for workers, including upgrades, downgrades,
 * and initial tier assignments. Stores the metrics at the time of change
 * for auditing and analysis.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $from_tier_id
 * @property int $to_tier_id
 * @property string $change_type
 * @property array $metrics_at_change
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read WorkerTier|null $fromTier
 * @property-read WorkerTier $toTier
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory upgrades()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTierHistory downgrades()
 */
class WorkerTierHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'worker_tier_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'from_tier_id',
        'to_tier_id',
        'change_type',
        'metrics_at_change',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metrics_at_change' => 'array',
    ];

    /**
     * Change type constants.
     */
    public const CHANGE_TYPE_UPGRADE = 'upgrade';

    public const CHANGE_TYPE_DOWNGRADE = 'downgrade';

    public const CHANGE_TYPE_INITIAL = 'initial';

    /**
     * Get the user this history entry belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tier the worker transitioned from.
     */
    public function fromTier(): BelongsTo
    {
        return $this->belongsTo(WorkerTier::class, 'from_tier_id');
    }

    /**
     * Get the tier the worker transitioned to.
     */
    public function toTier(): BelongsTo
    {
        return $this->belongsTo(WorkerTier::class, 'to_tier_id');
    }

    /**
     * Scope to only include upgrades.
     */
    public function scopeUpgrades($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_UPGRADE);
    }

    /**
     * Scope to only include downgrades.
     */
    public function scopeDowngrades($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_DOWNGRADE);
    }

    /**
     * Scope to only include initial assignments.
     */
    public function scopeInitial($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_INITIAL);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get a formatted description of the tier change.
     */
    public function getChangeDescription(): string
    {
        $fromName = $this->fromTier?->name ?? 'None';
        $toName = $this->toTier->name;

        return match ($this->change_type) {
            self::CHANGE_TYPE_UPGRADE => "Upgraded from {$fromName} to {$toName}",
            self::CHANGE_TYPE_DOWNGRADE => "Downgraded from {$fromName} to {$toName}",
            self::CHANGE_TYPE_INITIAL => "Started as {$toName}",
            default => "Changed to {$toName}",
        };
    }

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
     * Get the level difference.
     */
    public function getLevelDifference(): int
    {
        $fromLevel = $this->fromTier?->level ?? 0;
        $toLevel = $this->toTier->level;

        return $toLevel - $fromLevel;
    }

    /**
     * Create an upgrade history entry.
     */
    public static function recordUpgrade(
        User $user,
        WorkerTier $fromTier,
        WorkerTier $toTier,
        array $metrics,
        ?string $notes = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'from_tier_id' => $fromTier->id,
            'to_tier_id' => $toTier->id,
            'change_type' => self::CHANGE_TYPE_UPGRADE,
            'metrics_at_change' => $metrics,
            'notes' => $notes,
        ]);
    }

    /**
     * Create a downgrade history entry.
     */
    public static function recordDowngrade(
        User $user,
        WorkerTier $fromTier,
        WorkerTier $toTier,
        array $metrics,
        ?string $notes = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'from_tier_id' => $fromTier->id,
            'to_tier_id' => $toTier->id,
            'change_type' => self::CHANGE_TYPE_DOWNGRADE,
            'metrics_at_change' => $metrics,
            'notes' => $notes,
        ]);
    }

    /**
     * Create an initial tier assignment history entry.
     */
    public static function recordInitial(
        User $user,
        WorkerTier $tier,
        array $metrics,
        ?string $notes = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'from_tier_id' => null,
            'to_tier_id' => $tier->id,
            'change_type' => self::CHANGE_TYPE_INITIAL,
            'metrics_at_change' => $metrics,
            'notes' => $notes,
        ]);
    }
}
