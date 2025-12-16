<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $worker_id
 * @property string $badge_type
 * @property string $badge_name
 * @property string $description
 * @property string|null $icon
 * @property array<array-key, mixed>|null $criteria
 * @property int $level
 * @property \Illuminate\Support\Carbon $earned_at
 * @property bool $is_active
 * @property bool $display_on_profile
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge displayable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereBadgeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereBadgeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereDisplayOnProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereEarnedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBadge whereWorkerId($value)
 * @mixin \Eloquent
 */
class WorkerBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'badge_type',
        'badge_name',
        'description',
        'icon',
        'criteria',
        'level',
        'earned_at',
        'is_active',
        'display_on_profile',
    ];

    protected $casts = [
        'criteria' => 'array',
        'level' => 'integer',
        'earned_at' => 'datetime',
        'is_active' => 'boolean',
        'display_on_profile' => 'boolean',
    ];

    /**
     * Worker relationship
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Badge definitions
     */
    public static function getBadgeDefinitions()
    {
        return [
            'first_shift' => [
                'name' => 'First Shift',
                'description' => 'Complete your first shift',
                'levels' => [
                    1 => ['min_shifts' => 1, 'name' => 'First Shift'],
                ],
            ],
            'five_shifts' => [
                'name' => 'Five Shifts',
                'description' => 'Complete 5 shifts',
                'levels' => [
                    1 => ['min_shifts' => 5, 'name' => 'Five Shifts'],
                ],
            ],
            'ten_shifts' => [
                'name' => 'Ten Shifts',
                'description' => 'Complete 10 shifts',
                'levels' => [
                    1 => ['min_shifts' => 10, 'name' => 'Ten Shifts'],
                ],
            ],
            'fifty_shifts' => [
                'name' => 'Fifty Shifts',
                'description' => 'Complete 50 shifts',
                'levels' => [
                    1 => ['min_shifts' => 50, 'name' => 'Fifty Shifts'],
                ],
            ],
            'hundred_shifts' => [
                'name' => 'Hundred Shifts',
                'description' => 'Complete 100 shifts',
                'levels' => [
                    1 => ['min_shifts' => 100, 'name' => 'Hundred Shifts'],
                ],
            ],
            'perfect_week' => [
                'name' => 'Perfect Week',
                'description' => 'Complete 5+ shifts in a week, all 5-star ratings',
                'levels' => [
                    1 => ['perfect_weeks' => 1, 'name' => 'Perfect Week'],
                ],
            ],
            'early_bird' => [
                'name' => 'Early Bird',
                'description' => 'Check in 10+ mins early, 10 times',
                'levels' => [
                    1 => ['early_checkins' => 10, 'name' => 'Early Bird'],
                ],
            ],
            'reliable' => [
                'name' => 'Reliable Worker',
                'description' => '95%+ completion rate after 20 shifts',
                'levels' => [
                    1 => ['min_shifts' => 20, 'min_reliability' => 0.95, 'name' => 'Reliable Worker'],
                ],
            ],
            'five_star' => [
                'name' => 'Five Star',
                'description' => 'Maintain 5.0 rating for 10+ shifts',
                'levels' => [
                    1 => ['min_shifts' => 10, 'min_rating' => 5.0, 'name' => 'Five Star'],
                ],
            ],
            'quick_claimer' => [
                'name' => 'Quick Claimer',
                'description' => 'Instant claim 5 shifts',
                'levels' => [
                    1 => ['applications_within_1h' => 5, 'name' => 'Quick Claimer'],
                ],
            ],
            'veteran' => [
                'name' => 'Platform Veteran',
                'description' => '1 year on platform',
                'levels' => [
                    1 => ['months_active' => 12, 'name' => 'Veteran'],
                ],
            ],
            'top_earner' => [
                'name' => 'Top Earner',
                'description' => 'Earn $5000+ in a month',
                'levels' => [
                    1 => ['monthly_earnings' => 5000, 'name' => 'Top Earner'],
                ],
            ],
            'skill_master' => [
                'name' => 'Skill Master',
                'description' => 'Max proficiency in 5+ skills',
                'levels' => [
                    1 => ['max_proficiency_skills' => 5, 'name' => 'Skill Master'],
                ],
            ],
            // Legacy badges (keep for compatibility)
            'reliability' => [
                'name' => 'Reliability Champion',
                'description' => 'Exceptional attendance and punctuality',
                'levels' => [
                    1 => ['min_shifts' => 10, 'min_reliability' => 0.95, 'name' => 'Bronze Reliability'],
                    2 => ['min_shifts' => 25, 'min_reliability' => 0.97, 'name' => 'Silver Reliability'],
                    3 => ['min_shifts' => 50, 'min_reliability' => 0.99, 'name' => 'Gold Reliability'],
                ],
            ],
            'top_rated' => [
                'name' => 'Top Rated Worker',
                'description' => 'Consistently high ratings from businesses',
                'levels' => [
                    1 => ['min_shifts' => 10, 'min_rating' => 4.5, 'name' => 'Bronze Star'],
                    2 => ['min_shifts' => 25, 'min_rating' => 4.7, 'name' => 'Silver Star'],
                    3 => ['min_shifts' => 50, 'min_rating' => 4.9, 'name' => 'Gold Star'],
                ],
            ],
            'fast_responder' => [
                'name' => 'Quick Responder',
                'description' => 'Applies to shifts quickly',
                'levels' => [
                    1 => ['applications_within_1h' => 10, 'name' => 'Bronze Speed'],
                    2 => ['applications_within_1h' => 25, 'name' => 'Silver Speed'],
                    3 => ['applications_within_1h' => 50, 'name' => 'Gold Speed'],
                ],
            ],
            'multi_skilled' => [
                'name' => 'Multi-Skilled Professional',
                'description' => 'Verified skills across multiple industries',
                'levels' => [
                    1 => ['verified_skills' => 3, 'name' => 'Bronze Versatility'],
                    2 => ['verified_skills' => 5, 'name' => 'Silver Versatility'],
                    3 => ['verified_skills' => 8, 'name' => 'Gold Versatility'],
                ],
            ],
            'night_owl' => [
                'name' => 'Night Owl',
                'description' => 'Excels at night shifts',
                'levels' => [
                    1 => ['night_shifts' => 10, 'name' => 'Bronze Night Owl'],
                    2 => ['night_shifts' => 25, 'name' => 'Silver Night Owl'],
                    3 => ['night_shifts' => 50, 'name' => 'Gold Night Owl'],
                ],
            ],
        ];
    }

    /**
     * Get badge icon/emoji
     */
    public function getIconAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Default icons based on type
        $icons = [
            'reliability' => '🎯',
            'top_rated' => '⭐',
            'fast_responder' => '⚡',
            'multi_skilled' => '🎨',
            'veteran' => '🏆',
            'early_bird' => '🌅',
            'night_owl' => '🦉',
        ];

        return $icons[$this->badge_type] ?? '🏅';
    }

    /**
     * Get level name
     */
    public function getLevelName()
    {
        $levels = ['', 'Bronze', 'Silver', 'Gold'];
        return $levels[$this->level] ?? '';
    }

    /**
     * Scope: Active badges
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Displayable badges
     */
    public function scopeDisplayable($query)
    {
        return $query->where('is_active', true)
            ->where('display_on_profile', true);
    }

    /**
     * Scope: For badge type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('badge_type', $type);
    }

    /**
     * Get available badge types
     * Returns simple list of badge types with descriptions
     *
     * @return array
     */
    public static function getAvailableBadgeTypes()
    {
        return [
            'punctuality' => 'Always on time',
            'reliability' => 'Never cancels',
            'top_performer' => 'Highest ratings',
            'veteran' => '100+ shifts completed',
            'certified' => 'All certifications current',
            'five_star' => 'Perfect 5.0 rating',
        ];
    }
}
