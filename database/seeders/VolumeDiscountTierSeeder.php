<?php

namespace Database\Seeders;

use App\Models\VolumeDiscountTier;
use Illuminate\Database\Seeder;

/**
 * FIN-001: Volume Discount Tier Seeder
 *
 * Seeds the default volume discount tiers:
 * - Starter: 0-10 shifts/mo = 35% fee (base rate)
 * - Growth: 11-50 shifts/mo = 30% fee (14% discount)
 * - Scale: 51-200 shifts/mo = 25% fee (29% discount)
 * - Enterprise: 200+ shifts/mo = 20% fee (43% discount)
 */
class VolumeDiscountTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'min_shifts_monthly' => 0,
                'max_shifts_monthly' => 10,
                'platform_fee_percent' => 20.00,
                'min_monthly_spend' => null,
                'max_monthly_spend' => null,
                'benefits' => [
                    'Standard support',
                    'Basic analytics dashboard',
                    'Single location posting',
                ],
                'badge_color' => 'gray',
                'badge_icon' => 'star',
                'description' => 'Perfect for getting started. Post up to 10 shifts per month with standard pricing.',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'min_shifts_monthly' => 11,
                'max_shifts_monthly' => 50,
                'platform_fee_percent' => 17.00,
                'min_monthly_spend' => null,
                'max_monthly_spend' => null,
                'benefits' => [
                    'Priority support',
                    'Advanced analytics dashboard',
                    'Multi-location posting',
                    'Worker favorites list',
                    'Shift templates',
                ],
                'badge_color' => 'blue',
                'badge_icon' => 'trending-up',
                'description' => 'Growing your workforce? Save 14% on platform fees and unlock advanced features.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'min_shifts_monthly' => 51,
                'max_shifts_monthly' => 200,
                'platform_fee_percent' => 15.00,
                'min_monthly_spend' => null,
                'max_monthly_spend' => null,
                'benefits' => [
                    'Dedicated account manager',
                    'Premium analytics with insights',
                    'Unlimited locations',
                    'Worker favorites list',
                    'Shift templates',
                    'Bulk shift posting',
                    'Priority worker matching',
                    'Custom reporting',
                ],
                'badge_color' => 'purple',
                'badge_icon' => 'chart-bar',
                'description' => 'Scaling your operations? Save 29% on platform fees with dedicated support.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'min_shifts_monthly' => 201,
                'max_shifts_monthly' => null, // Unlimited
                'platform_fee_percent' => 12.00,
                'min_monthly_spend' => null,
                'max_monthly_spend' => null,
                'benefits' => [
                    'Dedicated account team',
                    'Enterprise analytics suite',
                    'Unlimited locations',
                    'Worker favorites list',
                    'Shift templates',
                    'Bulk shift posting',
                    'Priority worker matching',
                    'Custom reporting',
                    'API access',
                    'Custom integrations',
                    'SLA guarantees',
                    'Invoice billing',
                    'Multi-user accounts',
                ],
                'badge_color' => 'gold',
                'badge_icon' => 'crown',
                'description' => 'Enterprise-level staffing made easy. Save 43% on platform fees with our premium tier.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($tiers as $tierData) {
            VolumeDiscountTier::updateOrCreate(
                ['slug' => $tierData['slug']],
                $tierData
            );
        }

        $this->command->info('FIN-001: Seeded '.count($tiers).' volume discount tiers');
    }
}
