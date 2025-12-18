<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * FIN-011: Subscription Plan Seeder
 *
 * Seeds default subscription plans for workers, businesses, and agencies.
 */
class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Worker Plans
            [
                'name' => 'Worker Pro',
                'slug' => 'worker-pro-monthly',
                'type' => SubscriptionPlan::TYPE_WORKER,
                'interval' => SubscriptionPlan::INTERVAL_MONTHLY,
                'price' => 9.99,
                'currency' => 'USD',
                'features' => [
                    'priority_matching',
                    'earnings_analytics',
                    'early_payout',
                    'profile_boost',
                ],
                'description' => 'Get matched to shifts faster with priority placement and unlock early payout access.',
                'trial_days' => 7,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 10,
                'commission_rate' => 5.00,
            ],
            [
                'name' => 'Worker Pro',
                'slug' => 'worker-pro-yearly',
                'type' => SubscriptionPlan::TYPE_WORKER,
                'interval' => SubscriptionPlan::INTERVAL_YEARLY,
                'price' => 99.99,
                'currency' => 'USD',
                'features' => [
                    'priority_matching',
                    'earnings_analytics',
                    'early_payout',
                    'profile_boost',
                    'no_commission',
                ],
                'description' => 'Get matched to shifts faster with priority placement, early payout, and zero platform commission.',
                'trial_days' => 7,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 11,
                'commission_rate' => 0.00,
            ],

            // Business Plans
            [
                'name' => 'Business Essential',
                'slug' => 'business-essential-monthly',
                'type' => SubscriptionPlan::TYPE_BUSINESS,
                'interval' => SubscriptionPlan::INTERVAL_MONTHLY,
                'price' => 49.99,
                'currency' => 'USD',
                'features' => [
                    'unlimited_posts',
                    'roster_management',
                    'analytics',
                    'team_management',
                ],
                'description' => 'Post unlimited shifts, manage your roster, and access business analytics.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 20,
                'max_users' => 5,
                'max_shifts_per_month' => null,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'Business Essential',
                'slug' => 'business-essential-yearly',
                'type' => SubscriptionPlan::TYPE_BUSINESS,
                'interval' => SubscriptionPlan::INTERVAL_YEARLY,
                'price' => 499.99,
                'currency' => 'USD',
                'features' => [
                    'unlimited_posts',
                    'roster_management',
                    'analytics',
                    'team_management',
                ],
                'description' => 'Post unlimited shifts, manage your roster, and access business analytics. Save with annual billing.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 21,
                'max_users' => 5,
                'max_shifts_per_month' => null,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'Business Pro',
                'slug' => 'business-pro-monthly',
                'type' => SubscriptionPlan::TYPE_BUSINESS,
                'interval' => SubscriptionPlan::INTERVAL_MONTHLY,
                'price' => 149.99,
                'currency' => 'USD',
                'features' => [
                    'all_essential',
                    'unlimited_posts',
                    'roster_management',
                    'analytics',
                    'team_management',
                    'api_access',
                    'dedicated_support',
                    'custom_branding',
                    'bulk_posting',
                ],
                'description' => 'Everything in Essential plus API access, dedicated support, and custom branding options.',
                'trial_days' => 14,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 22,
                'max_users' => 20,
                'max_shifts_per_month' => null,
                'commission_rate' => 8.00,
            ],
            [
                'name' => 'Business Pro',
                'slug' => 'business-pro-yearly',
                'type' => SubscriptionPlan::TYPE_BUSINESS,
                'interval' => SubscriptionPlan::INTERVAL_YEARLY,
                'price' => 1499.99,
                'currency' => 'USD',
                'features' => [
                    'all_essential',
                    'unlimited_posts',
                    'roster_management',
                    'analytics',
                    'team_management',
                    'api_access',
                    'dedicated_support',
                    'custom_branding',
                    'bulk_posting',
                ],
                'description' => 'Everything in Essential plus API access, dedicated support, and custom branding. Save with annual billing.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 23,
                'max_users' => 20,
                'max_shifts_per_month' => null,
                'commission_rate' => 8.00,
            ],

            // Agency Plans
            [
                'name' => 'Agency Growth',
                'slug' => 'agency-growth-monthly',
                'type' => SubscriptionPlan::TYPE_AGENCY,
                'interval' => SubscriptionPlan::INTERVAL_MONTHLY,
                'price' => 199.99,
                'currency' => 'USD',
                'features' => [
                    'worker_management',
                    'white_label',
                    'commission_reduction',
                    'multi_client',
                    'analytics',
                ],
                'description' => 'Manage your worker pool, access white-label features, and reduce platform commission.',
                'trial_days' => 14,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 30,
                'max_users' => 50,
                'commission_rate' => 5.00,
            ],
            [
                'name' => 'Agency Growth',
                'slug' => 'agency-growth-yearly',
                'type' => SubscriptionPlan::TYPE_AGENCY,
                'interval' => SubscriptionPlan::INTERVAL_YEARLY,
                'price' => 1999.99,
                'currency' => 'USD',
                'features' => [
                    'worker_management',
                    'white_label',
                    'commission_reduction',
                    'multi_client',
                    'analytics',
                ],
                'description' => 'Manage your worker pool, access white-label features, and reduce platform commission. Save with annual billing.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 31,
                'max_users' => 50,
                'commission_rate' => 5.00,
            ],
            [
                'name' => 'Agency Enterprise',
                'slug' => 'agency-enterprise-monthly',
                'type' => SubscriptionPlan::TYPE_AGENCY,
                'interval' => SubscriptionPlan::INTERVAL_MONTHLY,
                'price' => 499.99,
                'currency' => 'USD',
                'features' => [
                    'all_growth',
                    'worker_management',
                    'white_label',
                    'commission_reduction',
                    'multi_client',
                    'analytics',
                    'reporting_suite',
                    'api_access',
                    'dedicated_support',
                ],
                'description' => 'Full-featured agency management with dedicated support, API access, and advanced reporting.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 32,
                'max_users' => null,
                'commission_rate' => 3.00,
            ],
            [
                'name' => 'Agency Enterprise',
                'slug' => 'agency-enterprise-yearly',
                'type' => SubscriptionPlan::TYPE_AGENCY,
                'interval' => SubscriptionPlan::INTERVAL_YEARLY,
                'price' => 4999.99,
                'currency' => 'USD',
                'features' => [
                    'all_growth',
                    'worker_management',
                    'white_label',
                    'commission_reduction',
                    'multi_client',
                    'analytics',
                    'reporting_suite',
                    'api_access',
                    'dedicated_support',
                ],
                'description' => 'Full-featured agency management with dedicated support, API access, and advanced reporting. Save with annual billing.',
                'trial_days' => 14,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 33,
                'max_users' => null,
                'commission_rate' => 3.00,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Subscription plans seeded successfully.');
    }
}
