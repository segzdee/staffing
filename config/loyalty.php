<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loyalty Points Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the loyalty points system including earning rates, tier
    | thresholds, and point expiration settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Points Earning Rules
    |--------------------------------------------------------------------------
    */
    'earning' => [
        // Points earned per hour worked
        'points_per_hour' => env('LOYALTY_POINTS_PER_HOUR', 10),

        // Bonus points for receiving a 5-star rating
        'five_star_bonus' => env('LOYALTY_FIVE_STAR_BONUS', 50),

        // Points for referring a new user who completes their first shift
        'referral_bonus' => env('LOYALTY_REFERRAL_BONUS', 100),

        // Bonus points for completing first shift
        'first_shift_bonus' => env('LOYALTY_FIRST_SHIFT_BONUS', 25),

        // Points for completing profile (all fields filled)
        'profile_completion_bonus' => env('LOYALTY_PROFILE_COMPLETION_BONUS', 50),

        // Points for maintaining perfect attendance (weekly bonus)
        'perfect_week_bonus' => env('LOYALTY_PERFECT_WEEK_BONUS', 100),

        // Points for early check-in (10+ mins early)
        'early_checkin_bonus' => env('LOYALTY_EARLY_CHECKIN_BONUS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Thresholds (Lifetime Points)
    |--------------------------------------------------------------------------
    |
    | The minimum lifetime points required to achieve each tier.
    |
    */
    'tier_thresholds' => [
        'bronze' => 0,
        'silver' => 500,
        'gold' => 2000,
        'platinum' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Benefits
    |--------------------------------------------------------------------------
    |
    | Benefits and perks for each tier level.
    |
    */
    'tier_benefits' => [
        'bronze' => [
            'fee_discount_percent' => 0,
            'priority_matching' => false,
            'exclusive_shifts' => false,
            'support_priority' => 'standard',
        ],
        'silver' => [
            'fee_discount_percent' => 2,
            'priority_matching' => false,
            'exclusive_shifts' => false,
            'support_priority' => 'standard',
        ],
        'gold' => [
            'fee_discount_percent' => 5,
            'priority_matching' => true,
            'exclusive_shifts' => true,
            'support_priority' => 'priority',
        ],
        'platinum' => [
            'fee_discount_percent' => 10,
            'priority_matching' => true,
            'exclusive_shifts' => true,
            'support_priority' => 'vip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Maintenance Period (in months)
    |--------------------------------------------------------------------------
    |
    | How long a tier is maintained after being earned. Set to null for
    | permanent tiers that never expire.
    |
    */
    'tier_maintenance_period' => env('LOYALTY_TIER_MAINTENANCE_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Points Expiration
    |--------------------------------------------------------------------------
    |
    | Configuration for point expiration. Set days to null for non-expiring points.
    |
    */
    'expiration' => [
        // Days until points expire (null = never expire)
        'days' => env('LOYALTY_POINTS_EXPIRY_DAYS', 365),

        // Warning notifications before expiration (days)
        'warning_days' => [30, 7, 1],

        // Whether to notify users of expiring points
        'notify_users' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Limits
    |--------------------------------------------------------------------------
    |
    | Maximum points that can be earned per day (null = unlimited).
    |
    */
    'daily_limit' => env('LOYALTY_DAILY_LIMIT', null),

    /*
    |--------------------------------------------------------------------------
    | Multipliers
    |--------------------------------------------------------------------------
    |
    | Point multipliers for special events or conditions.
    |
    */
    'multipliers' => [
        // Weekend shift multiplier
        'weekend' => env('LOYALTY_WEEKEND_MULTIPLIER', 1.5),

        // Holiday shift multiplier
        'holiday' => env('LOYALTY_HOLIDAY_MULTIPLIER', 2.0),

        // Night shift multiplier (after 10pm)
        'night_shift' => env('LOYALTY_NIGHT_MULTIPLIER', 1.25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reward Types Configuration
    |--------------------------------------------------------------------------
    */
    'reward_types' => [
        'cash_bonus' => [
            'name' => 'Cash Bonus',
            'description' => 'Add cash bonus to your next payout',
            'requires_fulfillment' => false,
        ],
        'fee_discount' => [
            'name' => 'Fee Discount',
            'description' => 'Reduced platform fees for a period',
            'requires_fulfillment' => false,
        ],
        'priority_matching' => [
            'name' => 'Priority Matching',
            'description' => 'Get matched to shifts before other workers',
            'requires_fulfillment' => false,
        ],
        'badge' => [
            'name' => 'Exclusive Badge',
            'description' => 'Special profile badge',
            'requires_fulfillment' => false,
        ],
        'merch' => [
            'name' => 'Merchandise',
            'description' => 'Physical merchandise items',
            'requires_fulfillment' => true,
        ],
    ],
];
