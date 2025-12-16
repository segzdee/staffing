<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\BusinessProfile;
use App\Models\WorkerProfile;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
    ]);

    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->workerProfile = WorkerProfile::factory()->create([
        'user_id' => $this->worker->id,
    ]);
});

describe('Shift Lifecycle', function () {

    it('allows business to create a shift', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'title' => 'Bartender',
            'description' => 'Evening bartender needed',
            'base_rate' => 3500, // $35.00 in cents
            'required_workers' => 2,
            'shift_date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '02:00',
            'location_address' => '123 Main St',
            'industry' => 'hospitality',
            'status' => 'open',
        ]);

        expect(Shift::where('title', 'Bartender')->exists())->toBeTrue()
            ->and($shift->business_id)->toBe($this->business->id)
            ->and($shift->status)->toBe('open');
    });

    it('allows worker to apply to shift', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
            'start_datetime' => now()->addDays(3),
        ]);

        $application = ShiftApplication::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'pending',
        ]);

        expect(ShiftApplication::where([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
        ])->exists())->toBeTrue()
            ->and($application->status)->toBe('pending');
    });

    it('allows business to accept application and create assignment', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
            'required_workers' => 1,
            'filled_workers' => 0,
        ]);

        $application = ShiftApplication::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'pending',
        ]);

        // Simulate business accepting application
        $application->update(['status' => 'accepted', 'responded_at' => now()]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        expect($application->fresh()->status)->toBe('accepted')
            ->and(ShiftAssignment::where([
                'shift_id' => $shift->id,
                'worker_id' => $this->worker->id,
            ])->exists())->toBeTrue()
            ->and($assignment->status)->toBe('assigned');
    });

    it('prevents double-booking workers', function () {
        $shift1 = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '23:00',
            'start_datetime' => now()->addDays(3)->setTime(18, 0),
            'end_datetime' => now()->addDays(3)->setTime(23, 0),
        ]);

        $shift2 = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(3)->toDateString(),
            'start_time' => '20:00', // Overlaps with shift1
            'end_time' => '02:00',
            'start_datetime' => now()->addDays(3)->setTime(20, 0),
            'end_datetime' => now()->addDays(4)->setTime(2, 0),
        ]);

        // Assign worker to shift1
        ShiftAssignment::factory()->create([
            'shift_id' => $shift1->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        // Check that worker is already assigned to a shift on the same day
        $existingAssignment = ShiftAssignment::where('worker_id', $this->worker->id)
            ->whereHas('shift', function ($query) use ($shift1) {
                $query->where('shift_date', $shift1->shift_date);
            })
            ->exists();

        expect($existingAssignment)->toBeTrue()
            ->and(ShiftAssignment::where('worker_id', $this->worker->id)->count())->toBe(1);
    });

    it('tracks shift status progression', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'draft',
        ]);

        expect($shift->status)->toBe('draft');

        // Publish shift
        $shift->update(['status' => 'open']);
        expect($shift->fresh()->status)->toBe('open');

        // Assign worker
        $shift->update(['status' => 'assigned', 'filled_workers' => 1]);
        expect($shift->fresh()->status)->toBe('assigned');

        // Start shift
        $shift->update(['status' => 'in_progress', 'started_at' => now()]);
        expect($shift->fresh()->status)->toBe('in_progress');

        // Complete shift
        $shift->update(['status' => 'completed', 'completed_at' => now()]);
        expect($shift->fresh()->status)->toBe('completed');
    });

});
