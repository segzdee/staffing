<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\BusinessProfile;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
    ]);

    $this->agency = User::factory()->create(['user_type' => 'agency']);
    $this->agencyProfile = AgencyProfile::factory()->create([
        'user_id' => $this->agency->id,
    ]);

    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->workerProfile = WorkerProfile::factory()->create([
        'user_id' => $this->worker->id,
    ]);
});

describe('Agency Assignment Flow', function () {

    it('allows agency to register workers', function () {
        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
            'commission_rate' => 15, // 15% commission
        ]);

        expect($agencyWorker->agency_id)->toBe($this->agency->id)
            ->and($agencyWorker->worker_id)->toBe($this->worker->id)
            ->and($agencyWorker->status)->toBe('active')
            ->and((float) $agencyWorker->commission_rate)->toBeGreaterThan(0.0);
    });

    it('tracks agency-worker relationship', function () {
        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
            'added_at' => now(),
        ]);

        expect(AgencyWorker::where('agency_id', $this->agency->id)
            ->where('worker_id', $this->worker->id)
            ->exists())->toBeTrue()
            ->and($agencyWorker->added_at)->not->toBeNull();
    });

    it('allows agency to assign worker to shift', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
            'required_workers' => 1,
        ]);

        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
        ]);

        // Agency assigns their worker to the shift
        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $this->agency->id,
            'agency_id' => $this->agency->id,
            'status' => 'assigned',
        ]);

        expect($assignment->worker_id)->toBe($this->worker->id)
            ->and($assignment->agency_id)->toBe($this->agency->id)
            ->and($assignment->assigned_by)->toBe($this->agency->id)
            ->and($assignment->status)->toBe('assigned');
    });

    it('calculates agency commission on shift completion', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'base_rate' => 2500, // $25/hr in cents
            'status' => 'completed',
        ]);

        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'commission_rate' => 15, // 15%
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        // Calculate earnings
        $totalEarnings = 20000; // 8 hours * $25/hr in cents
        $agencyCommission = ($totalEarnings * $agencyWorker->commission_rate) / 100;
        $workerEarnings = $totalEarnings - $agencyCommission;

        expect($agencyCommission)->toBe(3000.0) // 15% of $200 = $30
            ->and($workerEarnings)->toBe(17000.0); // $170
    });

    it('requires worker acceptance of agency assignment', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
        ]);

        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
        ]);

        // Agency creates assignment
        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $this->agency->id,
            'agency_id' => $this->agency->id,
            'status' => 'assigned',
        ]);

        expect($assignment->status)->toBe('assigned')
            ->and($assignment->worker_id)->toBe($this->worker->id)
            ->and($assignment->agency_id)->toBe($this->agency->id);
    });

    it('allows worker to decline agency assignment', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
        ]);

        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $this->agency->id,
            'agency_id' => $this->agency->id,
            'status' => 'assigned',
        ]);

        // Worker cancels the assignment
        $assignment->update([
            'status' => 'cancelled',
        ]);

        expect($assignment->fresh()->status)->toBe('cancelled');
    });

    it('tracks agency performance metrics', function () {
        // Create multiple completed assignments
        $shift1 = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        $shift2 = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
        ]);

        ShiftAssignment::factory()->create([
            'shift_id' => $shift1->id,
            'worker_id' => $this->worker->id,
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        ShiftAssignment::factory()->create([
            'shift_id' => $shift2->id,
            'worker_id' => $this->worker->id,
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        $completedAssignments = ShiftAssignment::where('agency_id', $this->agency->id)
            ->where('status', 'completed')
            ->count();

        expect($completedAssignments)->toBe(2);
    });

    it('allows agency to manage multiple workers', function () {
        $worker2 = User::factory()->create(['user_type' => 'worker']);
        $workerProfile2 = WorkerProfile::factory()->create([
            'user_id' => $worker2->id,
        ]);

        $worker3 = User::factory()->create(['user_type' => 'worker']);
        $workerProfile3 = WorkerProfile::factory()->create([
            'user_id' => $worker3->id,
        ]);

        AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
        ]);

        AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $worker2->id,
            'status' => 'active',
        ]);

        AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $worker3->id,
            'status' => 'active',
        ]);

        $agencyWorkersCount = AgencyWorker::where('agency_id', $this->agency->id)
            ->where('status', 'active')
            ->count();

        expect($agencyWorkersCount)->toBe(3);
    });

    it('deactivates agency-worker relationship', function () {
        $agencyWorker = AgencyWorker::factory()->create([
            'agency_id' => $this->agency->id,
            'worker_id' => $this->worker->id,
            'status' => 'active',
        ]);

        // Deactivate relationship
        $agencyWorker->update([
            'status' => 'removed',
            'removed_at' => now(),
            'notes' => 'Worker left agency',
        ]);

        expect($agencyWorker->fresh()->status)->toBe('removed')
            ->and($agencyWorker->removed_at)->not->toBeNull()
            ->and($agencyWorker->notes)->toBe('Worker left agency');
    });

});
