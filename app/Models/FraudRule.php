<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * FIN-015: Fraud Rule Model
 *
 * Defines rules for fraud detection evaluation.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $category
 * @property array $conditions
 * @property int $severity
 * @property string $action
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FraudRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'conditions',
        'severity',
        'action',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'severity' => 'integer',
        ];
    }

    /**
     * Category constants.
     */
    public const CATEGORY_VELOCITY = 'velocity';

    public const CATEGORY_DEVICE = 'device';

    public const CATEGORY_LOCATION = 'location';

    public const CATEGORY_BEHAVIOR = 'behavior';

    public const CATEGORY_IDENTITY = 'identity';

    public const CATEGORY_PAYMENT = 'payment';

    /**
     * Action constants.
     */
    public const ACTION_FLAG = 'flag';

    public const ACTION_BLOCK = 'block';

    public const ACTION_REVIEW = 'review';

    public const ACTION_NOTIFY = 'notify';

    /**
     * Get all categories.
     *
     * @return array<string, string>
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_VELOCITY => 'Velocity',
            self::CATEGORY_DEVICE => 'Device',
            self::CATEGORY_LOCATION => 'Location',
            self::CATEGORY_BEHAVIOR => 'Behavior',
            self::CATEGORY_IDENTITY => 'Identity',
            self::CATEGORY_PAYMENT => 'Payment',
        ];
    }

    /**
     * Get all actions.
     *
     * @return array<string, string>
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_FLAG => 'Flag User',
            self::ACTION_BLOCK => 'Block User',
            self::ACTION_REVIEW => 'Queue for Review',
            self::ACTION_NOTIFY => 'Notify Admin',
        ];
    }

    // ========== Scopes ==========

    /**
     * Scope for active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category.
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for blocking rules.
     */
    public function scopeBlocking($query)
    {
        return $query->where('action', self::ACTION_BLOCK);
    }

    // ========== Helpers ==========

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get action label.
     */
    public function getActionLabelAttribute(): string
    {
        return self::getActions()[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get severity label.
     */
    public function getSeverityLabelAttribute(): string
    {
        return match (true) {
            $this->severity >= 9 => 'Critical',
            $this->severity >= 7 => 'High',
            $this->severity >= 4 => 'Medium',
            default => 'Low',
        };
    }

    /**
     * Check if this rule triggers a block action.
     */
    public function shouldBlock(): bool
    {
        return $this->action === self::ACTION_BLOCK;
    }

    /**
     * Check if this rule requires review.
     */
    public function requiresReview(): bool
    {
        return $this->action === self::ACTION_REVIEW;
    }

    /**
     * Get condition field.
     */
    public function getConditionField(): ?string
    {
        return $this->conditions['field'] ?? null;
    }

    /**
     * Get condition operator.
     */
    public function getConditionOperator(): string
    {
        return $this->conditions['operator'] ?? '>';
    }

    /**
     * Get condition value.
     */
    public function getConditionValue()
    {
        return $this->conditions['value'] ?? 0;
    }

    /**
     * Get condition period.
     */
    public function getConditionPeriod(): string
    {
        return $this->conditions['period'] ?? '24h';
    }

    /**
     * Get default fraud rules for seeding.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDefaultRules(): array
    {
        return [
            [
                'name' => 'Rapid Signups from Same IP',
                'code' => 'RAPID_IP_SIGNUPS',
                'description' => 'Multiple account registrations from the same IP address within 24 hours',
                'category' => self::CATEGORY_VELOCITY,
                'conditions' => [
                    'field' => 'signup_count',
                    'operator' => '>',
                    'value' => 3,
                    'period' => '24h',
                ],
                'severity' => 7,
                'action' => self::ACTION_FLAG,
                'is_active' => true,
            ],
            [
                'name' => 'Rapid Shift Applications',
                'code' => 'RAPID_SHIFT_APPLICATIONS',
                'description' => 'More than 10 shift applications within 1 hour',
                'category' => self::CATEGORY_VELOCITY,
                'conditions' => [
                    'field' => 'shift_applications',
                    'operator' => '>',
                    'value' => 10,
                    'period' => '1h',
                ],
                'severity' => 6,
                'action' => self::ACTION_FLAG,
                'is_active' => true,
            ],
            [
                'name' => 'Multiple Failed Payments',
                'code' => 'FAILED_PAYMENT_VELOCITY',
                'description' => 'More than 3 failed payment attempts within 24 hours',
                'category' => self::CATEGORY_PAYMENT,
                'conditions' => [
                    'field' => 'failed_payments',
                    'operator' => '>',
                    'value' => 3,
                    'period' => '24h',
                ],
                'severity' => 8,
                'action' => self::ACTION_BLOCK,
                'is_active' => true,
            ],
            [
                'name' => 'Unusual Login Location',
                'code' => 'UNUSUAL_LOGIN_LOCATION',
                'description' => 'Login from a location more than 500km from usual location',
                'category' => self::CATEGORY_LOCATION,
                'conditions' => [
                    'field' => 'location_distance',
                    'operator' => '>',
                    'value' => 500,
                    'period' => '24h',
                ],
                'severity' => 5,
                'action' => self::ACTION_NOTIFY,
                'is_active' => true,
            ],
            [
                'name' => 'Rapid Profile Changes',
                'code' => 'RAPID_PROFILE_CHANGES',
                'description' => 'More than 5 profile changes within 1 hour',
                'category' => self::CATEGORY_BEHAVIOR,
                'conditions' => [
                    'field' => 'profile_changes',
                    'operator' => '>',
                    'value' => 5,
                    'period' => '1h',
                ],
                'severity' => 6,
                'action' => self::ACTION_FLAG,
                'is_active' => true,
            ],
            [
                'name' => 'Multiple Devices Same Account',
                'code' => 'MULTIPLE_DEVICES',
                'description' => 'More than 5 different devices used within 24 hours',
                'category' => self::CATEGORY_DEVICE,
                'conditions' => [
                    'field' => 'device_count',
                    'operator' => '>',
                    'value' => 5,
                    'period' => '24h',
                ],
                'severity' => 7,
                'action' => self::ACTION_REVIEW,
                'is_active' => true,
            ],
            [
                'name' => 'Blocked Device Access',
                'code' => 'BLOCKED_DEVICE_ACCESS',
                'description' => 'Access attempt from a previously blocked device',
                'category' => self::CATEGORY_DEVICE,
                'conditions' => [
                    'field' => 'blocked_device',
                    'operator' => '=',
                    'value' => true,
                    'period' => 'instant',
                ],
                'severity' => 9,
                'action' => self::ACTION_BLOCK,
                'is_active' => true,
            ],
            [
                'name' => 'High Risk Score',
                'code' => 'HIGH_RISK_SCORE',
                'description' => 'User risk score exceeds 75',
                'category' => self::CATEGORY_BEHAVIOR,
                'conditions' => [
                    'field' => 'risk_score',
                    'operator' => '>=',
                    'value' => 75,
                    'period' => 'instant',
                ],
                'severity' => 8,
                'action' => self::ACTION_REVIEW,
                'is_active' => true,
            ],
            [
                'name' => 'Rapid Location Change',
                'code' => 'IMPOSSIBLE_TRAVEL',
                'description' => 'Location changed faster than physically possible (impossible travel)',
                'category' => self::CATEGORY_LOCATION,
                'conditions' => [
                    'field' => 'travel_speed',
                    'operator' => '>',
                    'value' => 1000,
                    'period' => '6h',
                ],
                'severity' => 9,
                'action' => self::ACTION_BLOCK,
                'is_active' => true,
            ],
            [
                'name' => 'Duplicate Identity Documents',
                'code' => 'DUPLICATE_IDENTITY',
                'description' => 'Identity documents match another user account',
                'category' => self::CATEGORY_IDENTITY,
                'conditions' => [
                    'field' => 'duplicate_identity',
                    'operator' => '=',
                    'value' => true,
                    'period' => 'instant',
                ],
                'severity' => 10,
                'action' => self::ACTION_BLOCK,
                'is_active' => true,
            ],
        ];
    }
}
