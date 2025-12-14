<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Certification;

class CertificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds common certifications for staffing marketplace workers.
     *
     * @return void
     */
    public function run()
    {
        $certifications = [
            // Food Safety
            ['name' => 'Food Handler Certificate', 'industry' => 'Food Safety', 'issuing_organization' => 'ServSafe / State Health Dept', 'description' => 'Basic food safety handling certification required for food service workers'],
            ['name' => 'ServSafe Food Manager', 'industry' => 'Food Safety', 'issuing_organization' => 'ServSafe', 'description' => 'Advanced food safety certification for food service managers'],
            ['name' => 'ServSafe Allergen', 'industry' => 'Food Safety', 'issuing_organization' => 'ServSafe', 'description' => 'Certification for handling food allergies in food service'],
            ['name' => 'Food Protection Manager', 'industry' => 'Food Safety', 'issuing_organization' => 'Various', 'description' => 'Comprehensive food protection management certification'],

            // Alcohol Service
            ['name' => 'TIPS Certification', 'industry' => 'Alcohol Service', 'issuing_organization' => 'TIPS', 'description' => 'Training for Intervention Procedures - responsible alcohol service'],
            ['name' => 'Alcohol Server Permit', 'industry' => 'Alcohol Service', 'issuing_organization' => 'State Issued', 'description' => 'State-required permit to serve alcoholic beverages'],
            ['name' => 'RBS Certification', 'industry' => 'Alcohol Service', 'issuing_organization' => 'CA ABC', 'description' => 'California Responsible Beverage Service certification'],
            ['name' => 'TABC Certification', 'industry' => 'Alcohol Service', 'issuing_organization' => 'Texas ABC', 'description' => 'Texas Alcoholic Beverage Commission certification'],
            ['name' => 'TAM Card', 'industry' => 'Alcohol Service', 'issuing_organization' => 'Nevada', 'description' => 'Nevada Techniques of Alcohol Management certification'],

            // Healthcare / Medical
            ['name' => 'CPR Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AHA / Red Cross', 'description' => 'Cardiopulmonary resuscitation certification'],
            ['name' => 'First Aid Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AHA / Red Cross', 'description' => 'Basic first aid certification'],
            ['name' => 'CPR/AED Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AHA / Red Cross', 'description' => 'CPR and Automated External Defibrillator certification'],
            ['name' => 'BLS Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AHA', 'description' => 'Basic Life Support for healthcare providers'],
            ['name' => 'ACLS Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AHA', 'description' => 'Advanced Cardiovascular Life Support certification'],
            ['name' => 'CNA License', 'industry' => 'Healthcare', 'issuing_organization' => 'State Board of Nursing', 'description' => 'Certified Nursing Assistant license'],
            ['name' => 'HHA Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'State Issued', 'description' => 'Home Health Aide certification'],
            ['name' => 'Phlebotomy Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'Various', 'description' => 'Certification for drawing blood'],
            ['name' => 'Medical Assistant Certification', 'industry' => 'Healthcare', 'issuing_organization' => 'AAMA / AMT', 'description' => 'Certified Medical Assistant credential'],
            ['name' => 'HIPAA Training', 'industry' => 'Healthcare', 'issuing_organization' => 'Various', 'description' => 'Health Insurance Portability and Accountability Act compliance training'],

            // Safety / OSHA
            ['name' => 'OSHA 10-Hour', 'industry' => 'Safety', 'issuing_organization' => 'OSHA', 'description' => '10-hour OSHA safety training for workers'],
            ['name' => 'OSHA 30-Hour', 'industry' => 'Safety', 'issuing_organization' => 'OSHA', 'description' => '30-hour OSHA safety training for supervisors'],
            ['name' => 'OSHA Construction Safety', 'industry' => 'Safety', 'issuing_organization' => 'OSHA', 'description' => 'Construction-specific OSHA safety certification'],
            ['name' => 'OSHA General Industry', 'industry' => 'Safety', 'issuing_organization' => 'OSHA', 'description' => 'General industry OSHA safety certification'],
            ['name' => 'Fall Protection Certification', 'industry' => 'Safety', 'issuing_organization' => 'Various', 'description' => 'Training for working at heights and fall prevention'],
            ['name' => 'Confined Space Entry', 'industry' => 'Safety', 'issuing_organization' => 'Various', 'description' => 'Certification for entering confined spaces'],
            ['name' => 'Lockout/Tagout (LOTO)', 'industry' => 'Safety', 'issuing_organization' => 'Various', 'description' => 'Energy control procedures certification'],

            // Warehouse / Equipment
            ['name' => 'Forklift Certification', 'industry' => 'Warehouse', 'issuing_organization' => 'Employer / OSHA', 'description' => 'Powered industrial truck operator certification'],
            ['name' => 'Forklift - Sit-Down', 'industry' => 'Warehouse', 'issuing_organization' => 'Various', 'description' => 'Sit-down counterbalance forklift certification'],
            ['name' => 'Forklift - Stand-Up', 'industry' => 'Warehouse', 'issuing_organization' => 'Various', 'description' => 'Stand-up reach truck certification'],
            ['name' => 'Forklift - Order Picker', 'industry' => 'Warehouse', 'issuing_organization' => 'Various', 'description' => 'Order picker/cherry picker certification'],
            ['name' => 'Pallet Jack Certification', 'industry' => 'Warehouse', 'issuing_organization' => 'Employer', 'description' => 'Electric pallet jack operation certification'],
            ['name' => 'Aerial Lift Certification', 'industry' => 'Warehouse', 'issuing_organization' => 'Various', 'description' => 'Scissor lift and boom lift certification'],
            ['name' => 'Crane Operator Certification', 'industry' => 'Warehouse', 'issuing_organization' => 'NCCCO', 'description' => 'National crane operator certification'],

            // Security
            ['name' => 'Security Guard License', 'industry' => 'Security', 'issuing_organization' => 'State Issued', 'description' => 'State-required security guard license'],
            ['name' => 'Unarmed Security Guard', 'industry' => 'Security', 'issuing_organization' => 'State Issued', 'description' => 'Unarmed security guard certification'],
            ['name' => 'Armed Security Guard', 'industry' => 'Security', 'issuing_organization' => 'State Issued', 'description' => 'Armed security guard certification with firearms permit'],
            ['name' => 'Security Guard Card', 'industry' => 'Security', 'issuing_organization' => 'BSIS (California)', 'description' => 'California Bureau of Security and Investigative Services guard card'],
            ['name' => 'Crowd Management', 'industry' => 'Security', 'issuing_organization' => 'Various', 'description' => 'Crowd management and control certification'],

            // Driving / Transportation
            ['name' => 'Commercial Driver License (CDL) - Class A', 'industry' => 'Driving', 'issuing_organization' => 'State DMV', 'description' => 'CDL for combination vehicles over 26,001 lbs'],
            ['name' => 'Commercial Driver License (CDL) - Class B', 'industry' => 'Driving', 'issuing_organization' => 'State DMV', 'description' => 'CDL for single vehicles over 26,001 lbs'],
            ['name' => 'Commercial Driver License (CDL) - Class C', 'industry' => 'Driving', 'issuing_organization' => 'State DMV', 'description' => 'CDL for passenger vehicles and hazmat'],
            ['name' => 'Passenger Endorsement', 'industry' => 'Driving', 'issuing_organization' => 'State DMV', 'description' => 'CDL endorsement for passenger transport'],
            ['name' => 'HAZMAT Endorsement', 'industry' => 'Driving', 'issuing_organization' => 'State DMV / TSA', 'description' => 'CDL endorsement for hazardous materials'],
            ['name' => 'DOT Medical Card', 'industry' => 'Driving', 'issuing_organization' => 'DOT', 'description' => 'Department of Transportation medical certification'],
            ['name' => 'Clean Driving Record', 'industry' => 'Driving', 'issuing_organization' => 'State DMV', 'description' => 'Verified clean driving record with no violations'],
            ['name' => 'Defensive Driving Course', 'industry' => 'Driving', 'issuing_organization' => 'NSC / Various', 'description' => 'Defensive driving techniques certification'],

            // HAZMAT / Environmental
            ['name' => 'HAZMAT Certification', 'industry' => 'HAZMAT', 'issuing_organization' => 'OSHA / DOT', 'description' => 'Hazardous materials handling certification'],
            ['name' => 'HAZWOPER 40-Hour', 'industry' => 'HAZMAT', 'issuing_organization' => 'OSHA', 'description' => '40-hour hazardous waste operations training'],
            ['name' => 'HAZWOPER 24-Hour', 'industry' => 'HAZMAT', 'issuing_organization' => 'OSHA', 'description' => '24-hour hazardous waste operations training'],
            ['name' => 'Bloodborne Pathogens', 'industry' => 'HAZMAT', 'issuing_organization' => 'OSHA', 'description' => 'Bloodborne pathogen exposure prevention training'],

            // Technology / IT
            ['name' => 'CompTIA A+', 'industry' => 'Technology', 'issuing_organization' => 'CompTIA', 'description' => 'IT technician certification'],
            ['name' => 'CompTIA Network+', 'industry' => 'Technology', 'issuing_organization' => 'CompTIA', 'description' => 'Network technician certification'],
            ['name' => 'CompTIA Security+', 'industry' => 'Technology', 'issuing_organization' => 'CompTIA', 'description' => 'IT security certification'],

            // Construction / Trades
            ['name' => 'Flagger Certification', 'industry' => 'Construction', 'issuing_organization' => 'ATSSA / State', 'description' => 'Traffic control flagger certification'],
            ['name' => 'Scaffold User Certification', 'industry' => 'Construction', 'issuing_organization' => 'OSHA', 'description' => 'Scaffold safety and use certification'],
            ['name' => 'Welding Certification', 'industry' => 'Construction', 'issuing_organization' => 'AWS', 'description' => 'American Welding Society certification'],
            ['name' => 'Electrical License - Journeyman', 'industry' => 'Construction', 'issuing_organization' => 'State Board', 'description' => 'Journeyman electrician license'],
            ['name' => 'Plumbing License - Journeyman', 'industry' => 'Construction', 'issuing_organization' => 'State Board', 'description' => 'Journeyman plumber license'],
            ['name' => 'HVAC Certification', 'industry' => 'Construction', 'issuing_organization' => 'EPA / Various', 'description' => 'HVAC technician certification'],
            ['name' => 'EPA 608 Certification', 'industry' => 'Construction', 'issuing_organization' => 'EPA', 'description' => 'Refrigerant handling certification'],

            // Hospitality / Events
            ['name' => 'Guest Services Professional', 'industry' => 'Hospitality', 'issuing_organization' => 'AHLEI', 'description' => 'Hospitality guest services certification'],
            ['name' => 'Event Planning Certification', 'industry' => 'Events', 'issuing_organization' => 'Various', 'description' => 'Professional event planning certification'],
            ['name' => 'Certified Meeting Professional', 'industry' => 'Events', 'issuing_organization' => 'MPI', 'description' => 'Meeting and event professional certification'],

            // General
            ['name' => 'Background Check Cleared', 'industry' => 'General', 'issuing_organization' => 'Various', 'description' => 'Verified clear background check'],
            ['name' => 'Drug Test Cleared', 'industry' => 'General', 'issuing_organization' => 'Various', 'description' => 'Verified negative drug test result'],
            ['name' => 'COVID-19 Vaccination', 'industry' => 'General', 'issuing_organization' => 'CDC / Healthcare Provider', 'description' => 'COVID-19 vaccination verification'],
            ['name' => 'TB Test (Negative)', 'industry' => 'General', 'issuing_organization' => 'Healthcare Provider', 'description' => 'Tuberculosis test negative result'],
            ['name' => 'Physical Exam Clearance', 'industry' => 'General', 'issuing_organization' => 'Healthcare Provider', 'description' => 'Medical physical examination clearance'],
        ];

        // Use updateOrCreate to avoid duplicates
        foreach ($certifications as $cert) {
            Certification::updateOrCreate(
                ['name' => $cert['name']],
                $cert
            );
        }

        // Count by industry
        $industries = collect($certifications)->groupBy('industry')->map->count();

        $this->command->info('Certifications seeded: ' . count($certifications));
        $this->command->info('Certifications by industry:');
        foreach ($industries as $industry => $count) {
            $this->command->info("  - {$industry}: {$count}");
        }
    }
}
