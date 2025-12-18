<?php

namespace Database\Seeders;

use App\Models\SafetyCertification;
use Illuminate\Database\Seeder;

/**
 * SAF-003: Seeds common safety certifications across various industries.
 */
class SafetyCertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certifications = [
            // Food Safety Certifications
            [
                'name' => 'Food Handler Certificate',
                'slug' => 'food-handler-certificate',
                'description' => 'Basic food safety and handling certification required for food service workers.',
                'category' => SafetyCertification::CATEGORY_FOOD_SAFETY,
                'issuing_authority' => 'State Health Department',
                'validity_months' => 24,
                'requires_renewal' => true,
                'applicable_industries' => ['hospitality', 'food_service', 'catering', 'retail'],
                'applicable_positions' => ['cook', 'chef', 'food_prep', 'server', 'bartender', 'kitchen_staff'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'ServSafe Food Protection Manager',
                'slug' => 'servsafe-food-protection',
                'description' => 'Comprehensive food safety management certification for supervisors and managers.',
                'category' => SafetyCertification::CATEGORY_FOOD_SAFETY,
                'issuing_authority' => 'National Restaurant Association',
                'validity_months' => 60,
                'requires_renewal' => true,
                'applicable_industries' => ['hospitality', 'food_service', 'catering'],
                'applicable_positions' => ['kitchen_manager', 'chef', 'food_service_manager', 'supervisor'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'TIPS Alcohol Certification',
                'slug' => 'tips-alcohol-certification',
                'description' => 'Training for Intervention ProcedureS - responsible alcohol service certification.',
                'category' => SafetyCertification::CATEGORY_FOOD_SAFETY,
                'issuing_authority' => 'Health Communications, Inc.',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['hospitality', 'events', 'entertainment'],
                'applicable_positions' => ['bartender', 'server', 'bar_manager', 'event_staff'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Responsible Service of Alcohol (RSA)',
                'slug' => 'rsa-certification',
                'description' => 'Certification for responsible service of alcohol in licensed venues.',
                'category' => SafetyCertification::CATEGORY_FOOD_SAFETY,
                'issuing_authority' => 'State Liquor Authority',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['hospitality', 'events', 'entertainment'],
                'applicable_positions' => ['bartender', 'server', 'door_staff', 'venue_manager'],
                'is_mandatory' => false,
                'is_active' => true,
            ],

            // Health & Medical Certifications
            [
                'name' => 'First Aid/CPR Certification',
                'slug' => 'first-aid-cpr',
                'description' => 'Basic first aid and cardiopulmonary resuscitation certification.',
                'category' => SafetyCertification::CATEGORY_HEALTH,
                'issuing_authority' => 'American Red Cross / American Heart Association',
                'validity_months' => 24,
                'requires_renewal' => true,
                'applicable_industries' => null, // All industries
                'applicable_positions' => null, // All positions
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Basic Life Support (BLS)',
                'slug' => 'basic-life-support',
                'description' => 'Healthcare provider-level CPR and emergency cardiovascular care.',
                'category' => SafetyCertification::CATEGORY_HEALTH,
                'issuing_authority' => 'American Heart Association',
                'validity_months' => 24,
                'requires_renewal' => true,
                'applicable_industries' => ['healthcare', 'medical', 'fitness', 'childcare'],
                'applicable_positions' => ['nurse', 'medical_assistant', 'paramedic', 'lifeguard', 'personal_trainer'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Bloodborne Pathogens Training',
                'slug' => 'bloodborne-pathogens',
                'description' => 'OSHA-compliant training on handling bloodborne pathogens.',
                'category' => SafetyCertification::CATEGORY_HEALTH,
                'issuing_authority' => 'OSHA-approved providers',
                'validity_months' => 12,
                'requires_renewal' => true,
                'applicable_industries' => ['healthcare', 'cleaning', 'hospitality'],
                'applicable_positions' => ['cleaner', 'housekeeper', 'nurse', 'medical_assistant'],
                'is_mandatory' => false,
                'is_active' => true,
            ],

            // Security Certifications
            [
                'name' => 'Security Guard License',
                'slug' => 'security-guard-license',
                'description' => 'State-issued license to work as a security guard.',
                'category' => SafetyCertification::CATEGORY_SECURITY,
                'issuing_authority' => 'State Bureau of Security',
                'validity_months' => 12,
                'requires_renewal' => true,
                'applicable_industries' => ['security', 'events', 'hospitality', 'retail'],
                'applicable_positions' => ['security_guard', 'door_staff', 'bouncer', 'loss_prevention'],
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Crowd Management Certification',
                'slug' => 'crowd-management',
                'description' => 'Training for managing large crowds and public gatherings.',
                'category' => SafetyCertification::CATEGORY_SECURITY,
                'issuing_authority' => 'Event Safety Alliance',
                'validity_months' => 24,
                'requires_renewal' => true,
                'applicable_industries' => ['events', 'entertainment', 'security'],
                'applicable_positions' => ['event_security', 'crowd_control', 'venue_manager', 'event_coordinator'],
                'is_mandatory' => false,
                'is_active' => true,
            ],

            // Industry Specific Certifications
            [
                'name' => 'Forklift Operator Certification',
                'slug' => 'forklift-operator',
                'description' => 'OSHA-compliant certification for forklift operation.',
                'category' => SafetyCertification::CATEGORY_INDUSTRY_SPECIFIC,
                'issuing_authority' => 'OSHA-approved trainers',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['warehousing', 'logistics', 'manufacturing', 'retail'],
                'applicable_positions' => ['forklift_operator', 'warehouse_worker', 'stock_clerk'],
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Hazmat Handling Certification',
                'slug' => 'hazmat-handling',
                'description' => 'Certification for handling hazardous materials safely.',
                'category' => SafetyCertification::CATEGORY_INDUSTRY_SPECIFIC,
                'issuing_authority' => 'DOT / OSHA',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['logistics', 'manufacturing', 'cleaning', 'healthcare'],
                'applicable_positions' => ['hazmat_handler', 'driver', 'warehouse_worker'],
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Aerial Lift/Scissor Lift Certification',
                'slug' => 'aerial-lift-certification',
                'description' => 'Certification for operating aerial work platforms.',
                'category' => SafetyCertification::CATEGORY_INDUSTRY_SPECIFIC,
                'issuing_authority' => 'OSHA-approved trainers',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['construction', 'warehousing', 'events', 'maintenance'],
                'applicable_positions' => ['rigger', 'stagehand', 'maintenance_worker', 'warehouse_worker'],
                'is_mandatory' => true,
                'is_active' => true,
            ],

            // General Safety Certifications
            [
                'name' => 'OSHA 10-Hour General Industry',
                'slug' => 'osha-10-hour',
                'description' => 'Basic occupational safety and health awareness training.',
                'category' => SafetyCertification::CATEGORY_GENERAL,
                'issuing_authority' => 'OSHA',
                'validity_months' => null, // Does not expire
                'requires_renewal' => false,
                'applicable_industries' => null, // All industries
                'applicable_positions' => null, // All positions
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'OSHA 30-Hour General Industry',
                'slug' => 'osha-30-hour',
                'description' => 'Advanced occupational safety and health training for supervisors.',
                'category' => SafetyCertification::CATEGORY_GENERAL,
                'issuing_authority' => 'OSHA',
                'validity_months' => null, // Does not expire
                'requires_renewal' => false,
                'applicable_industries' => null,
                'applicable_positions' => ['supervisor', 'manager', 'foreman', 'safety_officer'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Fire Safety and Evacuation Training',
                'slug' => 'fire-safety-training',
                'description' => 'Training on fire prevention, fire extinguisher use, and evacuation procedures.',
                'category' => SafetyCertification::CATEGORY_GENERAL,
                'issuing_authority' => 'Local Fire Department / NFPA',
                'validity_months' => 12,
                'requires_renewal' => true,
                'applicable_industries' => null, // All industries
                'applicable_positions' => null, // All positions
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manual Handling Training',
                'slug' => 'manual-handling',
                'description' => 'Training on safe lifting techniques and preventing musculoskeletal injuries.',
                'category' => SafetyCertification::CATEGORY_GENERAL,
                'issuing_authority' => 'Various providers',
                'validity_months' => 24,
                'requires_renewal' => true,
                'applicable_industries' => ['warehousing', 'logistics', 'healthcare', 'hospitality', 'retail'],
                'applicable_positions' => null,
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Working at Heights Certification',
                'slug' => 'working-at-heights',
                'description' => 'Safety certification for workers who perform tasks at elevated levels.',
                'category' => SafetyCertification::CATEGORY_GENERAL,
                'issuing_authority' => 'OSHA-approved trainers',
                'validity_months' => 36,
                'requires_renewal' => true,
                'applicable_industries' => ['construction', 'events', 'maintenance', 'warehousing'],
                'applicable_positions' => ['rigger', 'stagehand', 'construction_worker', 'maintenance_worker'],
                'is_mandatory' => false,
                'is_active' => true,
            ],
        ];

        foreach ($certifications as $certification) {
            SafetyCertification::updateOrCreate(
                ['slug' => $certification['slug']],
                $certification
            );
        }

        $this->command->info('SafetyCertificationSeeder: Created/updated '.count($certifications).' safety certifications.');
    }
}
