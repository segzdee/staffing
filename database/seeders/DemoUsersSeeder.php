<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds - Creates comprehensive demo users for testing
     *
     * @return void
     */
    public function run()
    {
        // ============================================================
        // DEMO WORKER - Full Access to Worker Dashboard
        // ============================================================
        $demoWorker = User::firstOrCreate(
            ['email' => 'worker@demo.com'],
            [
                'name' => 'Demo Worker',
                'username' => 'demoworker',
                'password' => Hash::make('password'),
                'user_type' => 'worker',
                'role' => 'normal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create Worker Profile
        WorkerProfile::firstOrCreate(
            ['user_id' => $demoWorker->id],
            [
                'phone' => '+1-555-0101',
                'date_of_birth' => '1995-06-15',
                'address' => '123 Worker Street',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'zip_code' => 'M5H 2N2',
                'country' => 'Canada',
                'emergency_contact_name' => 'Jane Worker',
                'emergency_contact_phone' => '+1-555-0102',
                'bio' => 'Experienced hospitality worker with 5+ years in customer service. Reliable, punctual, and customer-focused.',
                'skills' => json_encode(['Customer Service', 'POS Systems', 'Food Handling', 'Cash Management']),
                'certifications' => json_encode(['Food Safety Certificate', 'First Aid', 'Smart Serve']),
                'hourly_rate' => 25.00,
                'rating' => 4.8,
                'completed_shifts' => 127,
                'reliability_score' => 95,
                'is_available' => true,
                'is_complete' => true,
            ]
        );

        $this->command->info('✓ Demo Worker created: worker@demo.com / password');

        // ============================================================
        // DEMO BUSINESS - Full Access to Business Dashboard
        // ============================================================
        $demoBusiness = User::firstOrCreate(
            ['email' => 'business@demo.com'],
            [
                'name' => 'Demo Business Owner',
                'username' => 'demobusiness',
                'password' => Hash::make('password'),
                'user_type' => 'business',
                'role' => 'normal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create Business Profile
        BusinessProfile::firstOrCreate(
            ['user_id' => $demoBusiness->id],
            [
                'business_name' => 'Demo Restaurant & Bar',
                'business_type' => 'hospitality',
                'business_registration_number' => 'BN123456789',
                'phone' => '+1-555-0201',
                'website' => 'https://demorestaurant.com',
                'address' => '456 Business Avenue',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'zip_code' => 'M5J 2L7',
                'country' => 'Canada',
                'description' => 'Premium dining establishment specializing in contemporary cuisine. We regularly need skilled hospitality staff for events and peak seasons.',
                'total_locations' => 3,
                'employee_count' => '50-100',
                'rating' => 4.6,
                'total_shifts_posted' => 89,
                'has_payment_method' => true,
                'is_verified' => true,
                'is_complete' => true,
            ]
        );

        $this->command->info('✓ Demo Business created: business@demo.com / password');

        // ============================================================
        // DEMO AGENCY - Full Access to Agency Dashboard
        // ============================================================
        $demoAgency = User::firstOrCreate(
            ['email' => 'agency@demo.com'],
            [
                'name' => 'Demo Agency Manager',
                'username' => 'demoagency',
                'password' => Hash::make('password'),
                'user_type' => 'agency',
                'role' => 'normal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create Agency Profile
        AgencyProfile::firstOrCreate(
            ['user_id' => $demoAgency->id],
            [
                'agency_name' => 'Demo Staffing Solutions',
                'business_registration_number' => 'AN987654321',
                'phone' => '+1-555-0301',
                'website' => 'https://demostaffing.com',
                'address' => '789 Agency Plaza',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'zip_code' => 'M5K 3J5',
                'country' => 'Canada',
                'description' => 'Leading staffing agency providing qualified workers across multiple industries. Specializing in hospitality, retail, and event staffing with a pool of 500+ verified workers.',
                'specializations' => json_encode(['Hospitality', 'Retail', 'Events', 'Food Service']),
                'total_workers' => 523,
                'total_placements' => 1247,
                'rating' => 4.7,
                'is_verified' => true,
                'verified_at' => now()->subDays(30),
                'is_complete' => true,
            ]
        );

        $this->command->info('✓ Demo Agency created: agency@demo.com / password');

        // ============================================================
        // DEMO ADMIN - Full Access to Admin Panel with MFA
        // ============================================================
        $demoAdmin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'username' => 'demoadmin',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'mfa_enabled' => false, // Set to true when implementing MFA
            ]
        );

        $this->command->info('✓ Demo Admin created: admin@demo.com / password');

        // ============================================================
        // ADDITIONAL DEMO USERS FOR TESTING
        // ============================================================

        // Demo Worker 2 - For testing worker interactions
        $demoWorker2 = User::firstOrCreate(
            ['email' => 'worker2@demo.com'],
            [
                'name' => 'Sarah Johnson',
                'username' => 'sarahj',
                'password' => Hash::make('password'),
                'user_type' => 'worker',
                'role' => 'normal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        WorkerProfile::firstOrCreate(
            ['user_id' => $demoWorker2->id],
            [
                'phone' => '+1-555-0103',
                'date_of_birth' => '1992-03-22',
                'address' => '234 Main Street',
                'city' => 'Vancouver',
                'state' => 'British Columbia',
                'zip_code' => 'V6B 2M9',
                'country' => 'Canada',
                'bio' => 'Healthcare professional with RN certification. 8 years of experience in acute care.',
                'skills' => json_encode(['IV Therapy', 'Patient Care', 'EMR Systems', 'ACLS']),
                'certifications' => json_encode(['RN License', 'ACLS', 'BLS', 'PALS']),
                'hourly_rate' => 45.00,
                'rating' => 4.9,
                'completed_shifts' => 203,
                'reliability_score' => 98,
                'is_available' => true,
                'is_complete' => true,
            ]
        );

        // Demo Business 2 - For testing business interactions
        $demoBusiness2 = User::firstOrCreate(
            ['email' => 'business2@demo.com'],
            [
                'name' => 'Robert Chen',
                'username' => 'robertc',
                'password' => Hash::make('password'),
                'user_type' => 'business',
                'role' => 'normal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        BusinessProfile::firstOrCreate(
            ['user_id' => $demoBusiness2->id],
            [
                'business_name' => 'RetailMax Stores',
                'business_type' => 'retail',
                'business_registration_number' => 'BN456789123',
                'phone' => '+1-555-0202',
                'address' => '567 Commerce Drive',
                'city' => 'Montreal',
                'state' => 'Quebec',
                'zip_code' => 'H3B 4W8',
                'country' => 'Canada',
                'description' => 'Regional retail chain with 15 locations. Regular need for seasonal and promotional staff.',
                'total_locations' => 15,
                'employee_count' => '200-500',
                'rating' => 4.5,
                'total_shifts_posted' => 156,
                'has_payment_method' => true,
                'is_verified' => true,
                'is_complete' => true,
            ]
        );

        $this->command->info('✓ Additional demo users created for testing');

        // ============================================================
        // SUMMARY
        // ============================================================
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════════════╗');
        $this->command->info('║          DEMO USERS CREATED SUCCESSFULLY               ║');
        $this->command->info('╚════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('📧 Primary Demo Accounts (All passwords: "password"):');
        $this->command->info('');
        $this->command->info('👷 WORKER:   worker@demo.com');
        $this->command->info('   Access: Worker Dashboard, Shift Applications, Calendar');
        $this->command->info('');
        $this->command->info('🏢 BUSINESS: business@demo.com');
        $this->command->info('   Access: Business Dashboard, Post Shifts, Manage Workers');
        $this->command->info('');
        $this->command->info('🏛️  AGENCY:   agency@demo.com');
        $this->command->info('   Access: Agency Dashboard, Worker Pool, Placements');
        $this->command->info('');
        $this->command->info('👨‍💼 ADMIN:    admin@demo.com');
        $this->command->info('   Access: Full Admin Panel, User Management, Disputes');
        $this->command->info('');
        $this->command->info('📝 Additional Test Accounts:');
        $this->command->info('   worker2@demo.com / business2@demo.com');
        $this->command->info('');
        $this->command->info('🔐 All passwords: password');
        $this->command->info('');
        $this->command->info('🚀 Ready to test all dashboards!');
        $this->command->info('');
    }
}
