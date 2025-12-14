<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use App\Models\AiAgentProfile;
use App\Models\Skill;
use App\Models\WorkerSkill;
use Carbon\Carbon;

class DevCredentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 5 dev accounts (Worker, Business, Agency, AI Agent, Admin) with 7-day expiration.
     */
    public function run(): void
    {
        $expiresAt = Carbon::now()->addDays(7);

        // ============================================================
        // DEV WORKER
        // ============================================================
        $devWorker = User::updateOrCreate(
            ['email' => 'dev.worker@overtimestaff.io'],
            [
                'name' => 'Dev Worker',
                'username' => 'devworker',
                'password' => Hash::make('Dev007!'),
                'user_type' => 'worker',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_dev_account' => true,
                'dev_expires_at' => $expiresAt,
            ]
        );

        // Create Worker Profile
        $workerProfile = WorkerProfile::updateOrCreate(
            ['user_id' => $devWorker->id],
            [
                'bio' => 'Development worker account for testing worker dashboard features.',
                'hourly_rate_min' => 15.00,
                'hourly_rate_max' => 25.00,
                'years_experience' => 3,
                'rating_average' => 4.5,
                'total_shifts_completed' => 50,
                'reliability_score' => 95.0,
                'location_city' => 'San Francisco',
                'location_state' => 'CA',
                'location_country' => 'USA',
                'location_lat' => 37.7749,
                'location_lng' => -122.4194,
                'preferred_radius' => 25,
                'onboarding_completed' => true,
                'identity_verified' => true,
                'identity_verified_at' => now(),
            ]
        );
        
        // Mark profile as complete (if field exists)
        if (isset($workerProfile->is_complete)) {
            $workerProfile->update(['is_complete' => true]);
        }

        // Add sample skills to worker
        $this->addWorkerSkills($devWorker->id, [
            'Customer Service',
            'Food Handling',
            'Cash Management',
            'Event Setup',
            'Inventory Management',
        ]);

        $this->command->info('✓ Dev Worker created: dev.worker@overtimestaff.io / Dev007!');

        // ============================================================
        // DEV BUSINESS
        // ============================================================
        $devBusiness = User::updateOrCreate(
            ['email' => 'dev.business@overtimestaff.io'],
            [
                'name' => 'Dev Business',
                'username' => 'devbusiness',
                'password' => Hash::make('Dev007!'),
                'user_type' => 'business',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_dev_account' => true,
                'dev_expires_at' => $expiresAt,
            ]
        );

        // Create Business Profile
        $businessProfile = BusinessProfile::updateOrCreate(
            ['user_id' => $devBusiness->id],
            [
                'business_name' => 'Dev Business Inc.',
                'business_type' => 'restaurant',
                'industry' => 'hospitality',
                'business_address' => '123 Dev Street',
                'business_city' => 'San Francisco',
                'business_state' => 'CA',
                'business_country' => 'USA',
                'business_phone' => '+1-555-0100',
                'rating_average' => 4.8,
                'total_shifts_posted' => 100,
                'total_shifts_completed' => 95,
                'fill_rate' => 95.0,
                'is_verified' => true,
                'verified_at' => now(),
                'verification_status' => 'verified',
                'onboarding_completed' => true,
                'account_in_good_standing' => true,
                'can_post_shifts' => true,
            ]
        );
        
        // Mark profile as complete and set payment method (if fields exist)
        if (isset($businessProfile->is_complete)) {
            $businessProfile->update(['is_complete' => true]);
        }
        if (isset($businessProfile->has_payment_method)) {
            $businessProfile->update(['has_payment_method' => true]);
        }

        $this->command->info('✓ Dev Business created: dev.business@overtimestaff.io / Dev007!');

        // ============================================================
        // DEV AGENCY
        // ============================================================
        $devAgency = User::updateOrCreate(
            ['email' => 'dev.agency@overtimestaff.io'],
            [
                'name' => 'Dev Agency',
                'username' => 'devagency',
                'password' => Hash::make('Dev007!'),
                'user_type' => 'agency',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_dev_account' => true,
                'dev_expires_at' => $expiresAt,
            ]
        );

        // Create Agency Profile
        AgencyProfile::updateOrCreate(
            ['user_id' => $devAgency->id],
            [
                'agency_name' => 'Dev Staffing Agency',
                'license_number' => 'DEV-AGENCY-001',
                'license_verified' => true,
                'business_model' => 'full_service',
                'commission_rate' => 5.00,
                'total_shifts_managed' => 200,
                'total_workers_managed' => 25,
            ]
        );

        $this->command->info('✓ Dev Agency created: dev.agency@overtimestaff.io / Dev007!');

        // ============================================================
        // DEV AI AGENT
        // ============================================================
        $devAgent = User::updateOrCreate(
            ['email' => 'dev.agent@overtimestaff.io'],
            [
                'name' => 'Dev AI Agent',
                'username' => 'devagent',
                'password' => Hash::make('Dev007!'),
                'user_type' => 'ai_agent',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_dev_account' => true,
                'dev_expires_at' => $expiresAt,
            ]
        );

        // Create AI Agent Profile
        AiAgentProfile::updateOrCreate(
            ['user_id' => $devAgent->id],
            [
                'agent_name' => 'Dev AI Assistant',
                'api_key' => 'dev_' . str()->random(32),
                'capabilities' => [
                    'shift_search',
                    'worker_search',
                    'matching',
                    'application_submission',
                ],
                'rate_limits' => [
                    'per_minute' => 60,
                    'per_hour' => 1000,
                ],
                'is_active' => true,
                'last_activity_at' => now(),
                'total_api_calls' => 0,
                'total_shifts_created' => 0,
                'total_workers_matched' => 0,
            ]
        );

        $this->command->info('✓ Dev AI Agent created: dev.agent@overtimestaff.io / Dev007!');

        // ============================================================
        // DEV ADMIN
        // ============================================================
        $devAdmin = User::updateOrCreate(
            ['email' => 'dev.admin@overtimestaff.io'],
            [
                'name' => 'Dev Admin',
                'username' => 'devadmin',
                'password' => Hash::make('Dev007!'),
                'user_type' => 'business', // Admin uses business type but has admin role
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_dev_account' => true,
                'dev_expires_at' => $expiresAt,
            ]
        );

        $this->command->info('✓ Dev Admin created: dev.admin@overtimestaff.io / Dev007!');

        $this->command->info("\n✅ All 5 dev accounts created successfully!");
        $this->command->info("📅 Expiration date: {$expiresAt->format('Y-m-d H:i:s')}");
        $this->command->info("⏰ Expires in: 7 days\n");
    }

    /**
     * Add skills to a worker profile.
     */
    private function addWorkerSkills(int $workerId, array $skillNames): void
    {
        foreach ($skillNames as $skillName) {
            // Find or create the skill
            $skill = Skill::firstOrCreate(
                ['name' => $skillName],
                [
                    'industry' => 'general',
                    'description' => "Skill: {$skillName}",
                ]
            );

            // Attach skill to worker if not already attached
            WorkerSkill::firstOrCreate(
                [
                    'worker_id' => $workerId,
                    'skill_id' => $skill->id,
                ],
                [
                    'proficiency_level' => 'intermediate',
                    'years_experience' => rand(1, 5),
                    'verified' => true,
                ]
            );
        }
    }
}
