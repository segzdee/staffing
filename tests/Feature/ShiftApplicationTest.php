<?php

use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    // Create worker
    $this->worker = User::factory()->create([
        'user_type' => 'worker',
    ]);

    $this->workerProfile = WorkerProfile::factory()->create([
        'user_id' => $this->worker->id,
        'rating_average' => 4.5,
        'reliability_score' => 95,
    ]);

    // Create business
    $this->business = User::factory()->create([
        'user_type' => 'business',
    ]);

    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
    ]);

    // Create shift
    $this->shift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'base_rate' => 2500,
        'start_datetime' => now()->addDays(2),
        'end_datetime' => now()->addDays(2)->addHours(8),
    ]);
});

test('worker can apply to shift', function () {
    $application = ShiftApplication::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'status' => 'pending',
        'cover_letter' => 'I am interested in this shift.',
    ]);

    expect($application)->toBeInstanceOf(ShiftApplication::class)
        ->and($application->status)->toBe('pending')
        ->and($application->worker_id)->toBe($this->worker->id)
        ->and($application->shift_id)->toBe($this->shift->id);
});

test('shift application belongs to worker and shift', function () {
    $application = ShiftApplication::factory()->create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
    ]);

    expect($application->worker)->toBeInstanceOf(User::class)
        ->and($application->worker->id)->toBe($this->worker->id)
        ->and($application->shift)->toBeInstanceOf(Shift::class)
        ->and($application->shift->id)->toBe($this->shift->id);
});

test('business can accept application and create assignment', function () {
    $application = ShiftApplication::factory()->create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'status' => 'pending',
    ]);

    // Accept the application
    $application->status = 'accepted';
    $application->responded_at = now();
    $application->save();

    // Create assignment
    $assignment = ShiftAssignment::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'assigned_by' => $this->business->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    expect($assignment)->toBeInstanceOf(ShiftAssignment::class)
        ->and($assignment->status)->toBe('assigned')
        ->and($assignment->worker_id)->toBe($this->worker->id)
        ->and($assignment->assigned_by)->toBe($this->business->id);
});

test('high-rated worker can instant claim shift', function () {
    // High-rated worker (4.5+ rating)
    expect($this->workerProfile->rating_average)->toBeGreaterThanOrEqual(4.5);

    // Create assignment directly (instant claim - worker assigns themselves)
    $assignment = ShiftAssignment::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'assigned_by' => $this->worker->id, // Self-assigned = instant claim
        'status' => 'assigned',
    ]);

    expect($assignment->assigned_by)->toBe($this->worker->id)
        ->and($assignment->worker_id)->toBe($this->worker->id)
        ->and($assignment->status)->toBe('assigned');
});

test('worker can view own applications', function () {
    // Create multiple applications
    $shift1 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
    ]);

    $shift2 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
    ]);

    ShiftApplication::factory()->create([
        'shift_id' => $shift1->id,
        'worker_id' => $this->worker->id,
    ]);

    ShiftApplication::factory()->create([
        'shift_id' => $shift2->id,
        'worker_id' => $this->worker->id,
    ]);

    $applications = ShiftApplication::where('worker_id', $this->worker->id)->get();

    expect($applications)->toHaveCount(2);
});

test('business can view applications for their shifts', function () {
    $application1 = ShiftApplication::factory()->create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
    ]);

    // Create another worker and application
    $worker2 = User::factory()->create(['user_type' => 'worker']);
    WorkerProfile::factory()->create(['user_id' => $worker2->id]);

    $application2 = ShiftApplication::factory()->create([
        'shift_id' => $this->shift->id,
        'worker_id' => $worker2->id,
    ]);

    $applications = ShiftApplication::where('shift_id', $this->shift->id)->get();

    expect($applications)->toHaveCount(2);
});
