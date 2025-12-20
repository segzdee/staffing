<?php

use App\Jobs\AutoClockOutJob;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\TimeAdjustment;
use App\Models\TimeTrackingRecord;
use App\Models\User;
use App\Services\TimeTrackingService;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

/**
 * Time Tracking System Tests
 *
 * Tests clock-in/out, breaks, and time adjustments.
 */
beforeEach(function () {
    $this->initializeMigrations();

    // Create test users
    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->business = User::factory()->create(['user_type' => 'business']);

    // Create a shift for today with times relative to now
    // Shift starts 5 minutes ago and ends in 8 hours (valid clock-in window)
    $shiftStart = now()->subMinutes(5);
    $shiftEnd = $shiftStart->copy()->addHours(8);

    $this->shift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'shift_date' => now()->toDateString(),
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
        'start_datetime' => $shiftStart,
        'end_datetime' => $shiftEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    // Create an assignment for the worker
    $this->assignment = ShiftAssignment::factory()->create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'status' => 'assigned',
    ]);

    $this->service = app(TimeTrackingService::class);
});

// =========================================
// Clock-In Tests
// =========================================

it('can clock in worker with valid location', function () {
    // Location within geofence
    $verificationData = [
        'location' => [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'accuracy' => 10,
        ],
    ];

    $result = $this->service->processClockIn($this->assignment, $verificationData);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('clocked in');

    $this->assignment->refresh();
    expect($this->assignment->actual_clock_in)->not->toBeNull()
        ->and($this->assignment->status)->toBe('checked_in');
});

it('rejects clock in when already clocked in', function () {
    $this->assignment->update([
        'actual_clock_in' => now(),
        'status' => 'checked_in',
    ]);

    $verificationData = [
        'location' => [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ],
    ];

    $result = $this->service->processClockIn($this->assignment, $verificationData);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('ALREADY_CLOCKED_IN');
});

it('rejects clock in when too early', function () {
    // Create a fresh shift that starts in 2 hours (outside the 15-minute early window)
    $futureStart = now()->addHours(2);
    $futureEnd = $futureStart->copy()->addHours(8);

    $futureShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'shift_date' => $futureStart->toDateString(),
        'start_time' => $futureStart->format('H:i:s'),
        'end_time' => $futureEnd->format('H:i:s'),
        'start_datetime' => $futureStart,
        'end_datetime' => $futureEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $futureAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $futureShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'assigned',
    ]);

    $verificationData = [
        'location' => [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ],
    ];

    $result = $this->service->processClockIn($futureAssignment, $verificationData);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('TIME_RESTRICTION');
});

it('records lateness when clocking in late', function () {
    // Create a fresh shift that started 20 minutes ago (within 30-min late window)
    $lateStart = now()->subMinutes(20);
    $lateEnd = $lateStart->copy()->addHours(8);

    $lateShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'shift_date' => $lateStart->toDateString(),
        'start_time' => $lateStart->format('H:i:s'),
        'end_time' => $lateEnd->format('H:i:s'),
        'start_datetime' => $lateStart,
        'end_datetime' => $lateEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $lateAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $lateShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'assigned',
    ]);

    $verificationData = [
        'location' => [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ],
    ];

    $result = $this->service->processClockIn($lateAssignment, $verificationData);

    expect($result['success'])->toBeTrue();

    $lateAssignment->refresh();
    expect($lateAssignment->was_late)->toBeTrue()
        ->and($lateAssignment->late_minutes)->toBeGreaterThan(0);
});

// =========================================
// Clock-Out Tests
// =========================================

it('can clock out worker after clocking in', function () {
    // Create a fresh assignment that was clocked in 4 hours ago
    $clockInTime = now()->subHours(4);
    $shiftStart = now()->subHours(5);
    $shiftEnd = now()->addHours(3);

    $clockOutShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'in_progress',
        'shift_date' => now()->toDateString(),
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
        'start_datetime' => $shiftStart,
        'end_datetime' => $shiftEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $clockOutAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $clockOutShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'checked_in',
        'actual_clock_in' => $clockInTime,
        'check_in_time' => $clockInTime,
    ]);

    // Explicitly load the shift relationship
    $clockOutAssignment->load('shift');

    $result = $this->service->processClockOut($clockOutAssignment, []);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('clocked out');

    $clockOutAssignment->refresh();
    expect($clockOutAssignment->actual_clock_out)->not->toBeNull()
        ->and($clockOutAssignment->status)->toBe('checked_out')
        ->and($clockOutAssignment->hours_worked)->toBeGreaterThan(0);
});

it('rejects clock out when not clocked in', function () {
    $result = $this->service->processClockOut($this->assignment, []);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('NO_CLOCK_IN');
});

it('detects early departure', function () {
    // Create a shift that ends in 4 hours (worker leaving early)
    $shiftStart = now()->subHours(4);
    $shiftEnd = now()->addHours(4);
    $clockInTime = now()->subHours(4);

    $earlyShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'in_progress',
        'shift_date' => now()->toDateString(),
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
        'start_datetime' => $shiftStart,
        'end_datetime' => $shiftEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $earlyAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $earlyShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'checked_in',
        'actual_clock_in' => $clockInTime,
        'check_in_time' => $clockInTime,
    ]);

    // Explicitly load the shift relationship to avoid caching issues
    $earlyAssignment->load('shift');

    $result = $this->service->processClockOut($earlyAssignment, []);

    expect($result['success'])->toBeTrue()
        ->and($result['early_departure'])->toBeTrue();

    $earlyAssignment->refresh();
    expect($earlyAssignment->early_departure)->toBeTrue()
        ->and($earlyAssignment->early_departure_minutes)->toBeGreaterThan(0);
});

it('calculates overtime correctly', function () {
    // Create a shift for 8 hours that ended 2 hours ago
    // Worker clocked in 10 hours ago, so they have 2 hours overtime
    $shiftStart = now()->subHours(10);
    $shiftEnd = now()->subHours(2);
    $clockInTime = now()->subHours(10);

    $overtimeShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'in_progress',
        'shift_date' => now()->toDateString(),
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
        'start_datetime' => $shiftStart,
        'end_datetime' => $shiftEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $overtimeAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $overtimeShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'checked_in',
        'actual_clock_in' => $clockInTime,
        'check_in_time' => $clockInTime,
    ]);

    // Explicitly load the shift relationship to avoid caching issues
    $overtimeAssignment->load('shift');

    $result = $this->service->processClockOut($overtimeAssignment, []);

    expect($result['success'])->toBeTrue()
        ->and($result['overtime_minutes'])->toBeGreaterThan(0);

    $overtimeAssignment->refresh();
    expect($overtimeAssignment->overtime_worked)->toBeTrue();
});

// =========================================
// Break Tests
// =========================================

it('can start a break when clocked in', function () {
    $this->assignment->update([
        'actual_clock_in' => now()->subHours(2),
        'status' => 'checked_in',
    ]);

    $result = $this->service->processBreakStart($this->assignment);

    expect($result['success'])->toBeTrue()
        ->and($result['started_at'])->not->toBeNull();
});

it('cannot start break when not clocked in', function () {
    $result = $this->service->processBreakStart($this->assignment);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('NOT_CLOCKED_IN');
});

it('cannot start break when already on break', function () {
    $this->assignment->update([
        'actual_clock_in' => now()->subHours(2),
        'status' => 'checked_in',
        'current_break_started_at' => now()->subMinutes(10),
    ]);

    $this->assignment->refresh();

    $result = $this->service->processBreakStart($this->assignment);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('ALREADY_ON_BREAK');
});

it('can end a break and track duration', function () {
    $this->assignment->update([
        'actual_clock_in' => now()->subHours(2),
        'status' => 'checked_in',
        'current_break_started_at' => now()->subMinutes(15),
    ]);

    $this->assignment->refresh();

    $result = $this->service->processBreakEnd($this->assignment);

    expect($result['success'])->toBeTrue()
        ->and($result['break_duration_minutes'])->toBeGreaterThan(0);

    $this->assignment->refresh();
    expect($this->assignment->total_break_minutes)->toBeGreaterThan(0)
        ->and($this->assignment->current_break_started_at)->toBeNull();
});

it('tracks mandatory break compliance for long shifts', function () {
    // Create a long shift (8 hours) that should require mandatory break
    $shiftStart = now()->subHours(7);
    $shiftEnd = $shiftStart->copy()->addHours(8);

    $longShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'in_progress',
        'shift_date' => now()->toDateString(),
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
        'start_datetime' => $shiftStart,
        'end_datetime' => $shiftEnd,
        'duration_hours' => 8,
        'location_lat' => 51.5074,
        'location_lng' => -0.1278,
        'geofence_radius' => 100,
    ]);

    $breakAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $longShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'checked_in',
        'actual_clock_in' => now()->subHours(7),
        'check_in_time' => now()->subHours(7),
        'total_break_minutes' => 30,
        'mandatory_break_taken' => true, // Worker took required break
    ]);

    $breakStatus = $this->service->getBreakStatus($breakAssignment);

    expect($breakStatus['mandatory_break_required'])->toBeTrue()
        ->and($breakStatus['mandatory_break_taken'])->toBeTrue();
});

// =========================================
// Time Adjustment Tests
// =========================================

it('can create hours adjustment', function () {
    // Update assignment hours first
    $this->assignment->net_hours_worked = 8.0;
    $this->assignment->save();
    $this->assignment->refresh();

    $adjustment = TimeAdjustment::adjustHours(
        $this->assignment,
        7.5,
        'Worker took extended lunch break',
        $this->business
    );

    expect($adjustment->exists)->toBeTrue()
        ->and($adjustment->adjustment_type)->toBe(TimeAdjustment::TYPE_HOURS)
        ->and((float) $adjustment->new_value)->toBe(7.50)
        ->and($adjustment->status)->toBe(TimeAdjustment::STATUS_PENDING);
});

it('can approve adjustment and apply to assignment', function () {
    // Update assignment hours first
    $this->assignment->net_hours_worked = 8.0;
    $this->assignment->save();
    $this->assignment->refresh();

    $adjustment = TimeAdjustment::adjustHours(
        $this->assignment,
        7.5,
        'Worker took extended lunch break',
        $this->business
    );

    $result = $adjustment->approve($this->business, 'Approved after review');

    expect($result)->toBeTrue()
        ->and($adjustment->status)->toBe(TimeAdjustment::STATUS_APPROVED)
        ->and($adjustment->approved_by)->toBe($this->business->id);
});

it('can reject adjustment', function () {
    $adjustment = TimeAdjustment::adjustHours(
        $this->assignment,
        7.5,
        'Worker took extended lunch break',
        $this->business
    );

    $result = $adjustment->reject($this->business, 'No evidence provided');

    expect($result)->toBeTrue()
        ->and($adjustment->status)->toBe(TimeAdjustment::STATUS_REJECTED);
});

// =========================================
// Auto Clock-Out Job Tests
// =========================================

it('auto clocks out workers after shift ends', function () {
    // Create an assignment that should be auto clocked out
    // Shift ended 1 hour ago
    $oldShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'shift_date' => now()->toDateString(),
        'start_time' => now()->subHours(9)->format('H:i:s'),
        'end_time' => now()->subHours(1)->format('H:i:s'),
    ]);

    $oldAssignment = ShiftAssignment::factory()->create([
        'shift_id' => $oldShift->id,
        'worker_id' => $this->worker->id,
        'status' => 'checked_in',
        'actual_clock_in' => now()->subHours(9),
        'check_in_time' => now()->subHours(9),
    ]);

    // Run the job
    $job = new AutoClockOutJob;
    $job->handle($this->service);

    $oldAssignment->refresh();
    expect($oldAssignment->auto_clocked_out)->toBeTrue()
        ->and($oldAssignment->status)->toBe('checked_out')
        ->and($oldAssignment->actual_clock_out)->not->toBeNull();
});

// =========================================
// API Endpoint Tests
// =========================================

it('returns active shift for authenticated worker', function () {
    $this->assignment->update([
        'status' => 'assigned',
    ]);

    $response = $this->actingAs($this->worker, 'sanctum')
        ->getJson('/api/worker/shifts/active');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.assignment_id', $this->assignment->id);
});

it('clock in requires authentication', function () {
    $response = $this->postJson("/api/worker/shifts/{$this->assignment->id}/clock-in", [
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $response->assertUnauthorized();
});

it('clock in requires location', function () {
    $response = $this->actingAs($this->worker, 'sanctum')
        ->postJson("/api/worker/shifts/{$this->assignment->id}/clock-in", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('clock in succeeds with valid data', function () {
    $response = $this->actingAs($this->worker, 'sanctum')
        ->postJson("/api/worker/shifts/{$this->assignment->id}/clock-in", [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'accuracy' => 10,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assignment->refresh();
    expect($this->assignment->actual_clock_in)->not->toBeNull();
});

it('prevents unauthorized access to another workers assignment', function () {
    $otherWorker = User::factory()->create(['user_type' => 'worker']);

    $response = $this->actingAs($otherWorker, 'sanctum')
        ->postJson("/api/worker/shifts/{$this->assignment->id}/clock-in", [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

    $response->assertForbidden();
});

// =========================================
// TimeTrackingRecord Tests
// =========================================

it('creates time tracking record on clock in', function () {
    $verificationData = [
        'location' => [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'accuracy' => 10,
        ],
    ];

    $result = $this->service->processClockIn($this->assignment, $verificationData);

    expect($result['success'])->toBeTrue()
        ->and($result['time_record_id'])->not->toBeNull();

    $record = TimeTrackingRecord::find($result['time_record_id']);
    expect($record)->not->toBeNull()
        ->and($record->type)->toBe(TimeTrackingRecord::TYPE_CLOCK_IN)
        ->and($record->assignment_id)->toBe($this->assignment->id);
});

it('creates time tracking record on clock out', function () {
    $clockInTime = now()->subHours(4);
    $this->assignment->update([
        'actual_clock_in' => $clockInTime,
        'check_in_time' => $clockInTime,
        'status' => 'checked_in',
    ]);

    $this->assignment->refresh();

    $result = $this->service->processClockOut($this->assignment, []);

    expect($result['success'])->toBeTrue()
        ->and($result['time_record_id'])->not->toBeNull();

    $record = TimeTrackingRecord::find($result['time_record_id']);
    expect($record)->not->toBeNull()
        ->and($record->type)->toBe(TimeTrackingRecord::TYPE_CLOCK_OUT);
});
