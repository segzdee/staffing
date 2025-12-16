<?php

namespace Tests\Feature\Worker;

use Tests\TestCase;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\Skill;
use App\Services\WorkerActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkerActivationTest extends TestCase
{
    use RefreshDatabase;

    protected WorkerActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkerActivationService::class);
    }

    /** @test */
    public function it_activates_worker_when_all_requirements_met()
    {
        $worker = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
            'name' => 'Test Worker',
        ]);

        $profile = WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'phone_verified' => true,
            'identity_verified' => true,
            'rtw_verified' => true,
            'background_check_status' => 'approved',
            'payment_setup_complete' => true,
            // Profile completion requirements (80%+)
            'bio' => str_repeat('a', 50), // 50+ character bio
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'US',
            'location_city' => 'New York',
            'location_state' => 'NY',
            'location_country' => 'US',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'years_experience' => 5,
            'hourly_rate_min' => 15.00,
            'hourly_rate_max' => 25.00,
            'availability_schedule' => ['monday' => ['start' => '09:00', 'end' => '17:00']],
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '9876543210',
        ]);

        // Add skills to meet profile completion (need 3+ for 80%+ completion)
        $skill1 = Skill::create(['name' => 'Customer Service', 'industry' => 'hospitality']);
        $skill2 = Skill::create(['name' => 'Food Handling', 'industry' => 'hospitality']);
        $skill3 = Skill::create(['name' => 'Cash Handling', 'industry' => 'retail']);
        // Use the profile's user_id for the worker_id in the pivot table
        $profile->skills()->attach([
            $skill1->id => ['proficiency_level' => 'intermediate'],
            $skill2->id => ['proficiency_level' => 'intermediate'],
            $skill3->id => ['proficiency_level' => 'intermediate'],
        ]);

        $result = $this->service->activateWorker($worker);

        $this->assertTrue($result['success']);
        $this->assertTrue($profile->fresh()->is_activated);
    }

    /** @test */
    public function it_requires_background_check_for_activation()
    {
        $worker = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'phone_verified' => true,
            'identity_verified' => true,
            'rtw_verified' => true,
            'background_check_status' => 'not_started', // Not approved/pending/clear
            'payment_setup_complete' => true,
        ]);

        $result = $this->service->canActivate($worker->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_requires_right_to_work_verification()
    {
        $worker = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'phone_verified' => true,
            'identity_verified' => true,
            'rtw_verified' => false, // Not verified
            'background_check_status' => 'approved',
            'payment_setup_complete' => true,
        ]);

        $result = $this->service->canActivate($worker->id);

        $this->assertFalse($result);
    }
}
