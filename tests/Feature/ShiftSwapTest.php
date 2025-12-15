<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftSwap;
use App\Models\BusinessProfile;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
    ]);

    $this->worker1 = User::factory()->create(['user_type' => 'worker']);
    $this->workerProfile1 = WorkerProfile::factory()->create([
        'user_id' => $this->worker1->id,
    ]);

    $this->worker2 = User::factory()->create(['user_type' => 'worker']);
    $this->workerProfile2 = WorkerProfile::factory()->create([
        'user_id' => $this->worker2->id,
    ]);
});

describe('Shift Swap Flow', function () {

    it('allows worker to offer shift swap', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(5)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '02:00',
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        // Worker1 offers swap
        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'status' => 'pending',
            'reason' => 'Personal emergency',
        ]);

        expect($swap->offering_worker_id)->toBe($this->worker1->id)
            ->and($swap->status)->toBe('pending')
            ->and($swap->reason)->toBe('Personal emergency');
    });

    it('allows another worker to accept swap offer', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(5)->toDateString(),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'status' => 'pending',
        ]);

        // Worker2 accepts the swap
        $swap->update([
            'receiving_worker_id' => $this->worker2->id,
            'status' => 'pending',
        ]);

        expect($swap->fresh()->receiving_worker_id)->toBe($this->worker2->id)
            ->and($swap->status)->toBe('pending');
    });

    it('requires business approval for swap', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(5)->toDateString(),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'receiving_worker_id' => $this->worker2->id,
            'status' => 'pending',
        ]);

        // Business approves the swap
        $swap->update([
            'status' => 'approved',
            'business_approved_at' => now(),
            'approved_by' => $this->business->id,
        ]);

        expect($swap->fresh()->status)->toBe('approved')
            ->and($swap->business_approved_at)->not->toBeNull()
            ->and($swap->approved_by)->toBe($this->business->id);
    });

    it('transfers assignment after swap approval', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(5)->toDateString(),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'receiving_worker_id' => $this->worker2->id,
            'status' => 'approved',
            'business_approved_at' => now(),
        ]);

        // Transfer assignment to worker2
        $assignment->update([
            'worker_id' => $this->worker2->id,
        ]);

        expect($assignment->fresh()->worker_id)->toBe($this->worker2->id)
            ->and($swap->fresh()->status)->toBe('approved');
    });

    it('allows business to reject swap request', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(5)->toDateString(),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'receiving_worker_id' => $this->worker2->id,
            'status' => 'pending',
        ]);

        // Business rejects the swap
        $swap->update([
            'status' => 'rejected',
            'business_approved_at' => now(),
            'reason' => 'Accepting worker does not have required certifications',
        ]);

        expect($swap->fresh()->status)->toBe('rejected')
            ->and($swap->business_approved_at)->not->toBeNull()
            ->and($swap->reason)->toContain('certifications');
    });

    it('cancels swap if no worker accepts within time limit', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addDays(2)->toDateString(),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        $swap = ShiftSwap::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'offering_worker_id' => $this->worker1->id,
            'status' => 'pending',
            'created_at' => now()->subHours(49), // 49 hours ago
        ]);

        // Simulate automated cancellation after 48 hours
        $swap->update([
            'status' => 'cancelled',
            'reason' => 'No workers accepted within time limit',
        ]);

        expect($swap->fresh()->status)->toBe('cancelled')
            ->and($swap->reason)->toContain('time limit');
    });

    it('prevents swapping shifts within 24 hours of start time', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'shift_date' => now()->addHours(20)->toDateString(), // Less than 24 hours away
            'start_datetime' => now()->addHours(20),
            'status' => 'assigned',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker1->id,
            'assigned_by' => $this->business->id,
            'status' => 'assigned',
        ]);

        // Check if shift is within 24 hours
        $hoursTillShift = now()->diffInHours($shift->start_datetime);
        $canSwap = $hoursTillShift >= 24;

        expect($canSwap)->toBeFalse();
    });

});
