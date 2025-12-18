<?php

namespace Database\Seeders;

use App\Models\WorkerTier;
use Illuminate\Database\Seeder;

/**
 * WKR-007: Worker Career Tiers Seeder
 *
 * Seeds the default worker career tiers with their requirements and benefits.
 */
class WorkerTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Rookie',
                'slug' => 'rookie',
                'level' => 1,
                'min_shifts_completed' => 0,
                'min_rating' => 0.00,
                'min_hours_worked' => 0,
                'min_months_active' => 0,
                'fee_discount_percent' => 0.00,
                'priority_booking_hours' => 0,
                'instant_payout' => false,
                'premium_shifts_access' => false,
                'additional_benefits' => [
                    'Access to standard shifts',
                    'Basic platform support',
                ],
                'badge_color' => '#6B7280', // Gray
                'badge_icon' => 'seedling',
                'is_active' => true,
            ],
            [
                'name' => 'Regular',
                'slug' => 'regular',
                'level' => 2,
                'min_shifts_completed' => 10,
                'min_rating' => 4.00,
                'min_hours_worked' => 40,
                'min_months_active' => 1,
                'fee_discount_percent' => 2.00,
                'priority_booking_hours' => 2,
                'instant_payout' => false,
                'premium_shifts_access' => false,
                'additional_benefits' => [
                    'Profile badge displayed',
                    'Priority customer support',
                ],
                'badge_color' => '#10B981', // Green
                'badge_icon' => 'user-check',
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'level' => 3,
                'min_shifts_completed' => 50,
                'min_rating' => 4.30,
                'min_hours_worked' => 200,
                'min_months_active' => 3,
                'fee_discount_percent' => 5.00,
                'priority_booking_hours' => 6,
                'instant_payout' => true,
                'premium_shifts_access' => false,
                'additional_benefits' => [
                    'Featured in search results',
                    'Priority matching algorithm',
                    'Access to shift bundles',
                ],
                'badge_color' => '#3B82F6', // Blue
                'badge_icon' => 'award',
                'is_active' => true,
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'level' => 4,
                'min_shifts_completed' => 150,
                'min_rating' => 4.50,
                'min_hours_worked' => 600,
                'min_months_active' => 6,
                'fee_discount_percent' => 8.00,
                'priority_booking_hours' => 12,
                'instant_payout' => true,
                'premium_shifts_access' => true,
                'additional_benefits' => [
                    'Elite badge prominence',
                    'Direct invite opportunities',
                    'Dedicated account manager',
                    'Early access to new features',
                ],
                'badge_color' => '#8B5CF6', // Purple
                'badge_icon' => 'star',
                'is_active' => true,
            ],
            [
                'name' => 'Legend',
                'slug' => 'legend',
                'level' => 5,
                'min_shifts_completed' => 500,
                'min_rating' => 4.70,
                'min_hours_worked' => 2000,
                'min_months_active' => 12,
                'fee_discount_percent' => 12.00,
                'priority_booking_hours' => 24,
                'instant_payout' => true,
                'premium_shifts_access' => true,
                'additional_benefits' => [
                    'Legend status recognition',
                    'VIP support line',
                    'Exclusive high-paying shifts',
                    'Profile featured on homepage',
                    'Priority for recurring shifts',
                    'Referral bonus multiplier',
                ],
                'badge_color' => '#F59E0B', // Gold/Amber
                'badge_icon' => 'crown',
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tierData) {
            WorkerTier::updateOrCreate(
                ['slug' => $tierData['slug']],
                $tierData
            );
        }

        $this->command->info('Worker career tiers seeded successfully.');
    }
}
