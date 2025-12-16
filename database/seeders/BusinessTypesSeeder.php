<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessType;

/**
 * BusinessTypesSeeder
 * BIZ-REG-003: Seeds business types master list
 */
class BusinessTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = [
            [
                'code' => 'restaurant_bar',
                'name' => 'Restaurant / Bar',
                'description' => 'Restaurants, cafes, bars, pubs, and food service establishments',
                'icon' => 'utensils',
                'category' => 'hospitality',
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['server', 'bartender', 'host', 'busser', 'cook', 'dishwasher'],
                    'requires_certification' => false,
                    'requires_background_check' => false,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'hotel',
                'name' => 'Hotel / Accommodation',
                'description' => 'Hotels, motels, resorts, and accommodation providers',
                'icon' => 'hotel',
                'category' => 'hospitality',
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['front_desk', 'housekeeping', 'concierge', 'bellhop', 'maintenance'],
                    'requires_certification' => false,
                    'requires_background_check' => true,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'event_venue',
                'name' => 'Event Venue',
                'description' => 'Convention centers, banquet halls, and event spaces',
                'icon' => 'calendar-event',
                'category' => 'events',
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled'],
                    'worker_types' => ['server', 'bartender', 'setup_crew', 'event_coordinator', 'security'],
                    'requires_certification' => false,
                    'requires_background_check' => true,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'retail',
                'name' => 'Retail',
                'description' => 'Retail stores, shops, and boutiques',
                'icon' => 'shopping-bag',
                'category' => 'retail',
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['sales_associate', 'cashier', 'stock_associate', 'visual_merchandiser'],
                    'requires_certification' => false,
                    'requires_background_check' => false,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'warehouse',
                'name' => 'Warehouse / Distribution',
                'description' => 'Warehouses, distribution centers, and logistics facilities',
                'icon' => 'warehouse',
                'category' => 'logistics',
                'sort_order' => 5,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['picker', 'packer', 'forklift_operator', 'loader', 'inventory_clerk'],
                    'requires_certification' => true,
                    'requires_background_check' => false,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'healthcare',
                'name' => 'Healthcare',
                'description' => 'Hospitals, clinics, care homes, and medical facilities',
                'icon' => 'hospital',
                'category' => 'healthcare',
                'sort_order' => 6,
                'is_active' => true,
                'is_featured' => true,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['nurse', 'cna', 'medical_assistant', 'phlebotomist', 'caregiver'],
                    'requires_certification' => true,
                    'requires_background_check' => true,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'corporate',
                'name' => 'Corporate / Office',
                'description' => 'Office buildings, corporate facilities, and business centers',
                'icon' => 'building',
                'category' => 'corporate',
                'sort_order' => 7,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['scheduled', 'recurring'],
                    'worker_types' => ['receptionist', 'admin_assistant', 'data_entry', 'facilities'],
                    'requires_certification' => false,
                    'requires_background_check' => true,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'manufacturing',
                'name' => 'Manufacturing',
                'description' => 'Manufacturing plants, factories, and production facilities',
                'icon' => 'industry',
                'category' => 'manufacturing',
                'sort_order' => 8,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['scheduled', 'recurring'],
                    'worker_types' => ['assembly_worker', 'machine_operator', 'quality_inspector', 'maintenance'],
                    'requires_certification' => true,
                    'requires_background_check' => false,
                    'requires_uniform' => true,
                ],
            ],
            [
                'code' => 'logistics',
                'name' => 'Logistics / Delivery',
                'description' => 'Delivery services, courier companies, and transportation',
                'icon' => 'truck',
                'category' => 'logistics',
                'sort_order' => 9,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled'],
                    'worker_types' => ['driver', 'courier', 'dispatcher', 'loader'],
                    'requires_certification' => true,
                    'requires_background_check' => true,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'education',
                'name' => 'Education',
                'description' => 'Schools, universities, and educational institutions',
                'icon' => 'graduation-cap',
                'category' => 'education',
                'sort_order' => 10,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['scheduled', 'recurring'],
                    'worker_types' => ['substitute_teacher', 'tutor', 'cafeteria_worker', 'custodian'],
                    'requires_certification' => true,
                    'requires_background_check' => true,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'government',
                'name' => 'Government',
                'description' => 'Government agencies and public sector organizations',
                'icon' => 'landmark',
                'category' => 'government',
                'sort_order' => 11,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['scheduled'],
                    'worker_types' => ['admin_support', 'clerk', 'facilities'],
                    'requires_certification' => false,
                    'requires_background_check' => true,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'non_profit',
                'name' => 'Non-Profit / Charity',
                'description' => 'Non-profit organizations and charitable foundations',
                'icon' => 'heart',
                'category' => 'non_profit',
                'sort_order' => 12,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled'],
                    'worker_types' => ['event_staff', 'admin_support', 'outreach_worker'],
                    'requires_certification' => false,
                    'requires_background_check' => true,
                    'requires_uniform' => false,
                ],
            ],
            [
                'code' => 'other',
                'name' => 'Other',
                'description' => 'Other business types not listed above',
                'icon' => 'ellipsis',
                'category' => 'other',
                'sort_order' => 99,
                'is_active' => true,
                'is_featured' => false,
                'enabled_features' => [
                    'shift_types' => ['on_demand', 'scheduled', 'recurring'],
                    'worker_types' => ['general'],
                    'requires_certification' => false,
                    'requires_background_check' => false,
                    'requires_uniform' => false,
                ],
            ],
        ];

        foreach ($businessTypes as $type) {
            BusinessType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('Business types seeded successfully!');
    }
}
