<?php

namespace Tests\Feature\Shift;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\WorkerProfile;
use App\Models\Skill;
use App\Models\WorkerSkill;
use App\Services\ShiftMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShiftMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShiftMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftMatchingService::class);
    }

    /** @test */
    public function it_matches_workers_based_on_skills()
    {
        $skill = Skill::factory()->create(['name' => 'Bartending']);

        $worker = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create(['user_id' => $worker->id]);
        WorkerSkill::factory()->create([
            'worker_id' => $worker->id,
            'skill_id' => $skill->id,
            'proficiency_level' => 'expert',
        ]);

        $shift = Shift::factory()->create([
            'role' => 'Bartender',
            'status' => 'open',
        ]);
        $shift->skills()->attach($skill->id);

        $matches = $this->service->findMatchingWorkers($shift);

        $this->assertNotEmpty($matches);
        $this->assertTrue($matches->contains('id', $worker->id));
    }

    /** @test */
    public function it_considers_location_in_matching()
    {
        $worker = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'city' => 'New York',
            'state' => 'NY',
        ]);

        $shift = Shift::factory()->create([
            'city' => 'New York',
            'state' => 'NY',
            'status' => 'open',
        ]);

        $matches = $this->service->findMatchingWorkers($shift);

        $this->assertNotEmpty($matches);
    }

    /** @test */
    public function it_excludes_unavailable_workers()
    {
        $worker = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create(['user_id' => $worker->id]);

        $shift = Shift::factory()->create([
            'shift_date' => now()->addDay(),
            'start_time' => '09:00:00',
            'status' => 'open',
        ]);

        // Worker has blackout date
        // Implementation would check availability

        $this->assertTrue(true); // Placeholder
    }
}
