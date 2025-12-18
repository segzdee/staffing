<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature Flag Log Model
 *
 * ADM-007: Feature Flags System
 * Audit trail for feature flag changes.
 *
 * @property int $id
 * @property int $feature_flag_id
 * @property int $user_id
 * @property string $action
 * @property array|null $old_value
 * @property array|null $new_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FeatureFlag $featureFlag
 * @property-read \App\Models\User $user
 */
class FeatureFlagLog extends Model
{
    use HasFactory;

    /**
     * Action constants for consistency.
     */
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_ENABLED = 'enabled';

    public const ACTION_DISABLED = 'disabled';

    public const ACTION_ROLLOUT_CHANGED = 'rollout_changed';

    public const ACTION_USERS_CHANGED = 'users_changed';

    public const ACTION_ROLES_CHANGED = 'roles_changed';

    public const ACTION_TIERS_CHANGED = 'tiers_changed';

    public const ACTION_SCHEDULE_CHANGED = 'schedule_changed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'feature_flag_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the feature flag this log belongs to.
     */
    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class);
    }

    /**
     * Get the user who made this change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable action description.
     */
    public function getActionDescriptionAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Created feature flag',
            self::ACTION_UPDATED => 'Updated feature flag',
            self::ACTION_DELETED => 'Deleted feature flag',
            self::ACTION_ENABLED => 'Enabled feature flag',
            self::ACTION_DISABLED => 'Disabled feature flag',
            self::ACTION_ROLLOUT_CHANGED => 'Changed rollout percentage',
            self::ACTION_USERS_CHANGED => 'Updated enabled users list',
            self::ACTION_ROLES_CHANGED => 'Updated enabled roles',
            self::ACTION_TIERS_CHANGED => 'Updated enabled tiers',
            self::ACTION_SCHEDULE_CHANGED => 'Updated schedule',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get action badge color.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'green',
            self::ACTION_DELETED => 'red',
            self::ACTION_ENABLED => 'green',
            self::ACTION_DISABLED => 'gray',
            self::ACTION_ROLLOUT_CHANGED => 'blue',
            default => 'gray',
        };
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get formatted change summary.
     */
    public function getChangeSummary(): string
    {
        if ($this->action === self::ACTION_ROLLOUT_CHANGED) {
            $old = $this->old_value['rollout_percentage'] ?? 0;
            $new = $this->new_value['rollout_percentage'] ?? 0;

            return "Rollout: {$old}% -> {$new}%";
        }

        if ($this->action === self::ACTION_ENABLED) {
            return 'Status: Disabled -> Enabled';
        }

        if ($this->action === self::ACTION_DISABLED) {
            return 'Status: Enabled -> Disabled';
        }

        if ($this->action === self::ACTION_CREATED) {
            return 'New feature flag created';
        }

        if ($this->action === self::ACTION_DELETED) {
            return 'Feature flag deleted';
        }

        // Generic change summary
        $changes = [];
        if (is_array($this->old_value) && is_array($this->new_value)) {
            foreach ($this->new_value as $key => $newVal) {
                $oldVal = $this->old_value[$key] ?? null;
                if ($oldVal !== $newVal) {
                    $changes[] = $key;
                }
            }
        }

        if (! empty($changes)) {
            return 'Changed: '.implode(', ', $changes);
        }

        return $this->action_description;
    }
}
