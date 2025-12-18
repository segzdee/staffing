<?php

namespace Database\Seeders;

use App\Models\AgencyTier;
use Illuminate\Database\Seeder;

class AgencyTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'level' => 1,
                'min_monthly_revenue' => 0,
                'min_active_workers' => 5,
                'min_fill_rate' => 0,
                'min_rating' => 0,
                'commission_rate' => 15.00,
                'priority_booking_hours' => 0,
                'dedicated_support' => false,
                'custom_branding' => false,
                'api_access' => false,
                'additional_benefits' => [
                    'Access to basic shift marketplace',
                    'Standard email support',
                    'Basic reporting dashboard',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'level' => 2,
                'min_monthly_revenue' => 5000.00,
                'min_active_workers' => 20,
                'min_fill_rate' => 80.00,
                'min_rating' => 4.00,
                'commission_rate' => 12.00,
                'priority_booking_hours' => 2,
                'dedicated_support' => false,
                'custom_branding' => false,
                'api_access' => false,
                'additional_benefits' => [
                    '2-hour priority booking window',
                    'Priority email support',
                    'Enhanced reporting dashboard',
                    'Bulk worker management tools',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'level' => 3,
                'min_monthly_revenue' => 20000.00,
                'min_active_workers' => 50,
                'min_fill_rate' => 85.00,
                'min_rating' => 4.20,
                'commission_rate' => 10.00,
                'priority_booking_hours' => 6,
                'dedicated_support' => true,
                'custom_branding' => false,
                'api_access' => true,
                'additional_benefits' => [
                    '6-hour priority booking window',
                    'Dedicated account manager',
                    'API access for integrations',
                    'Advanced analytics & insights',
                    'Custom worker pools',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'level' => 4,
                'min_monthly_revenue' => 50000.00,
                'min_active_workers' => 100,
                'min_fill_rate' => 90.00,
                'min_rating' => 4.30,
                'commission_rate' => 8.00,
                'priority_booking_hours' => 12,
                'dedicated_support' => true,
                'custom_branding' => true,
                'api_access' => true,
                'additional_benefits' => [
                    '12-hour priority booking window',
                    'Dedicated success team',
                    'Custom branding options',
                    'White-label capabilities',
                    'Priority dispute resolution',
                    'Quarterly business reviews',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Diamond',
                'slug' => 'diamond',
                'level' => 5,
                'min_monthly_revenue' => 100000.00,
                'min_active_workers' => 200,
                'min_fill_rate' => 92.00,
                'min_rating' => 4.50,
                'commission_rate' => 5.00,
                'priority_booking_hours' => 24,
                'dedicated_support' => true,
                'custom_branding' => true,
                'api_access' => true,
                'additional_benefits' => [
                    '24-hour priority booking window',
                    'Executive account team',
                    'Full white-label solution',
                    'Custom integration development',
                    'Guaranteed SLA for support',
                    'Monthly strategy sessions',
                    'Early access to new features',
                    'Co-marketing opportunities',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tierData) {
            AgencyTier::updateOrCreate(
                ['slug' => $tierData['slug']],
                $tierData
            );
        }

        $this->command->info('Agency tiers seeded successfully!');
        $this->command->table(
            ['Name', 'Level', 'Min Revenue', 'Min Workers', 'Commission'],
            collect($tiers)->map(fn ($t) => [
                $t['name'],
                $t['level'],
                '$'.number_format($t['min_monthly_revenue'], 0),
                $t['min_active_workers'],
                $t['commission_rate'].'%',
            ])->toArray()
        );
    }
}
