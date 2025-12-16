<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Industry;

/**
 * IndustriesSeeder
 * BIZ-REG-003: Seeds industries master list
 */
class IndustriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Top-level industries
        $industries = [
            [
                'code' => 'hospitality',
                'name' => 'Hospitality',
                'description' => 'Hotels, restaurants, tourism, and related services',
                'icon' => 'concierge-bell',
                'naics_code' => '72',
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'common_certifications' => ['food_handler', 'alcohol_service', 'hospitality_management'],
                'common_skills' => ['customer_service', 'food_service', 'pos_systems', 'communication'],
                'children' => [
                    ['code' => 'restaurants', 'name' => 'Restaurants & Food Service', 'naics_code' => '722'],
                    ['code' => 'hotels_accommodation', 'name' => 'Hotels & Accommodation', 'naics_code' => '721'],
                    ['code' => 'catering', 'name' => 'Catering & Events', 'naics_code' => '7223'],
                    ['code' => 'bars_nightlife', 'name' => 'Bars & Nightlife', 'naics_code' => '7224'],
                ],
            ],
            [
                'code' => 'healthcare',
                'name' => 'Healthcare',
                'description' => 'Hospitals, clinics, and medical services',
                'icon' => 'stethoscope',
                'naics_code' => '62',
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'common_certifications' => ['rn', 'lpn', 'cna', 'bls', 'acls'],
                'common_skills' => ['patient_care', 'medical_terminology', 'emr_systems', 'vital_signs'],
                'compliance_requirements' => ['hipaa', 'background_check', 'drug_test'],
                'children' => [
                    ['code' => 'hospitals', 'name' => 'Hospitals', 'naics_code' => '622'],
                    ['code' => 'nursing_homes', 'name' => 'Nursing & Residential Care', 'naics_code' => '623'],
                    ['code' => 'ambulatory', 'name' => 'Ambulatory Health Services', 'naics_code' => '621'],
                    ['code' => 'home_healthcare', 'name' => 'Home Healthcare', 'naics_code' => '6216'],
                ],
            ],
            [
                'code' => 'retail',
                'name' => 'Retail Trade',
                'description' => 'Stores, shops, and retail establishments',
                'icon' => 'store',
                'naics_code' => '44-45',
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'common_certifications' => [],
                'common_skills' => ['customer_service', 'sales', 'inventory_management', 'pos_systems'],
                'children' => [
                    ['code' => 'grocery', 'name' => 'Grocery & Supermarkets', 'naics_code' => '4451'],
                    ['code' => 'general_merchandise', 'name' => 'General Merchandise', 'naics_code' => '452'],
                    ['code' => 'clothing_accessories', 'name' => 'Clothing & Accessories', 'naics_code' => '448'],
                    ['code' => 'electronics_appliances', 'name' => 'Electronics & Appliances', 'naics_code' => '443'],
                ],
            ],
            [
                'code' => 'manufacturing',
                'name' => 'Manufacturing',
                'description' => 'Production and manufacturing facilities',
                'icon' => 'industry',
                'naics_code' => '31-33',
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => ['osha_10', 'osha_30', 'forklift', 'first_aid'],
                'common_skills' => ['assembly', 'quality_control', 'machine_operation', 'safety_procedures'],
                'compliance_requirements' => ['osha', 'safety_training'],
                'children' => [
                    ['code' => 'food_manufacturing', 'name' => 'Food Manufacturing', 'naics_code' => '311'],
                    ['code' => 'machinery', 'name' => 'Machinery Manufacturing', 'naics_code' => '333'],
                    ['code' => 'electronics_mfg', 'name' => 'Electronics Manufacturing', 'naics_code' => '334'],
                ],
            ],
            [
                'code' => 'logistics',
                'name' => 'Logistics & Transportation',
                'description' => 'Warehousing, distribution, and transportation',
                'icon' => 'truck-loading',
                'naics_code' => '48-49',
                'sort_order' => 5,
                'is_active' => true,
                'is_featured' => true,
                'common_certifications' => ['forklift', 'cdl', 'hazmat', 'osha_10'],
                'common_skills' => ['warehouse_operations', 'inventory_management', 'shipping_receiving', 'forklift_operation'],
                'children' => [
                    ['code' => 'warehousing', 'name' => 'Warehousing & Storage', 'naics_code' => '493'],
                    ['code' => 'trucking', 'name' => 'Trucking & Delivery', 'naics_code' => '484'],
                    ['code' => 'courier_delivery', 'name' => 'Courier & Delivery Services', 'naics_code' => '492'],
                ],
            ],
            [
                'code' => 'construction',
                'name' => 'Construction',
                'description' => 'Building and construction services',
                'icon' => 'hard-hat',
                'naics_code' => '23',
                'sort_order' => 6,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => ['osha_10', 'osha_30', 'first_aid', 'fall_protection'],
                'common_skills' => ['construction_safety', 'blueprint_reading', 'power_tools', 'physical_labor'],
                'compliance_requirements' => ['osha', 'safety_training', 'background_check'],
                'children' => [
                    ['code' => 'building_construction', 'name' => 'Building Construction', 'naics_code' => '236'],
                    ['code' => 'specialty_trades', 'name' => 'Specialty Trade Contractors', 'naics_code' => '238'],
                ],
            ],
            [
                'code' => 'events',
                'name' => 'Events & Entertainment',
                'description' => 'Event venues, entertainment, and recreation',
                'icon' => 'ticket',
                'naics_code' => '71',
                'sort_order' => 7,
                'is_active' => true,
                'is_featured' => true,
                'common_certifications' => ['alcohol_service', 'crowd_management', 'first_aid'],
                'common_skills' => ['event_setup', 'customer_service', 'crowd_control', 'audio_visual'],
                'children' => [
                    ['code' => 'event_venues', 'name' => 'Event & Conference Venues', 'naics_code' => '7139'],
                    ['code' => 'sports_venues', 'name' => 'Sports & Recreation', 'naics_code' => '7112'],
                    ['code' => 'entertainment_venues', 'name' => 'Entertainment Venues', 'naics_code' => '7111'],
                ],
            ],
            [
                'code' => 'professional_services',
                'name' => 'Professional Services',
                'description' => 'Business and professional services',
                'icon' => 'briefcase',
                'naics_code' => '54',
                'sort_order' => 8,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => [],
                'common_skills' => ['administrative', 'communication', 'computer_skills', 'organization'],
                'children' => [
                    ['code' => 'accounting', 'name' => 'Accounting & Bookkeeping', 'naics_code' => '5412'],
                    ['code' => 'legal_services', 'name' => 'Legal Services', 'naics_code' => '5411'],
                    ['code' => 'consulting', 'name' => 'Management Consulting', 'naics_code' => '5416'],
                ],
            ],
            [
                'code' => 'education',
                'name' => 'Education',
                'description' => 'Schools, universities, and training',
                'icon' => 'school',
                'naics_code' => '61',
                'sort_order' => 9,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => ['teaching_certificate', 'cpr', 'first_aid'],
                'common_skills' => ['teaching', 'communication', 'patience', 'organization'],
                'compliance_requirements' => ['background_check', 'fingerprinting'],
                'children' => [
                    ['code' => 'k12_education', 'name' => 'K-12 Education', 'naics_code' => '6111'],
                    ['code' => 'higher_education', 'name' => 'Higher Education', 'naics_code' => '6113'],
                    ['code' => 'vocational_training', 'name' => 'Vocational Training', 'naics_code' => '6115'],
                ],
            ],
            [
                'code' => 'technology',
                'name' => 'Technology',
                'description' => 'IT, software, and technology services',
                'icon' => 'laptop-code',
                'naics_code' => '51',
                'sort_order' => 10,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => [],
                'common_skills' => ['technical_support', 'programming', 'data_entry', 'troubleshooting'],
                'children' => [
                    ['code' => 'software', 'name' => 'Software Development', 'naics_code' => '5112'],
                    ['code' => 'it_services', 'name' => 'IT Services', 'naics_code' => '5415'],
                    ['code' => 'data_processing', 'name' => 'Data Processing', 'naics_code' => '5182'],
                ],
            ],
            [
                'code' => 'finance',
                'name' => 'Finance & Insurance',
                'description' => 'Banking, insurance, and financial services',
                'icon' => 'university',
                'naics_code' => '52',
                'sort_order' => 11,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => [],
                'common_skills' => ['data_entry', 'customer_service', 'attention_to_detail', 'math'],
                'compliance_requirements' => ['background_check', 'credit_check'],
                'children' => [
                    ['code' => 'banking', 'name' => 'Banking', 'naics_code' => '522'],
                    ['code' => 'insurance', 'name' => 'Insurance', 'naics_code' => '524'],
                ],
            ],
            [
                'code' => 'government',
                'name' => 'Government & Public Sector',
                'description' => 'Government agencies and public services',
                'icon' => 'landmark',
                'naics_code' => '92',
                'sort_order' => 12,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => [],
                'common_skills' => ['administrative', 'public_service', 'communication'],
                'compliance_requirements' => ['background_check', 'security_clearance'],
                'children' => [
                    ['code' => 'federal_gov', 'name' => 'Federal Government', 'naics_code' => '921'],
                    ['code' => 'state_local_gov', 'name' => 'State & Local Government', 'naics_code' => '922'],
                ],
            ],
            [
                'code' => 'other',
                'name' => 'Other Industries',
                'description' => 'Other industries not listed above',
                'icon' => 'ellipsis-h',
                'naics_code' => null,
                'sort_order' => 99,
                'is_active' => true,
                'is_featured' => false,
                'common_certifications' => [],
                'common_skills' => [],
                'children' => [],
            ],
        ];

        foreach ($industries as $industryData) {
            $children = $industryData['children'] ?? [];
            unset($industryData['children']);

            $industry = Industry::updateOrCreate(
                ['code' => $industryData['code']],
                array_merge($industryData, ['level' => 1])
            );

            // Create child industries
            foreach ($children as $index => $childData) {
                Industry::updateOrCreate(
                    ['code' => $childData['code']],
                    array_merge($childData, [
                        'parent_id' => $industry->id,
                        'level' => 2,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'is_featured' => false,
                    ])
                );
            }
        }

        $this->command->info('Industries seeded successfully!');
    }
}
