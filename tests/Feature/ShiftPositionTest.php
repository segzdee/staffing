<?php

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftPosition;
use App\Models\ShiftPositionAssignment;
use App\Models\User;
use App\Services\ShiftPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ShiftPosition Model', function () {

    it('can be created with required fields', function () {
        $shift = Shift::factory()->create();

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Bartender',
            'description' => 'Mix and serve drinks',
            'hourly_rate' => 25.00,
            'required_workers' => 3,
            'filled_workers' => 0,
            'required_skills' => [1, 2],
            'required_certifications' => [1],
            'minimum_experience_hours' => 100,
            'status' => 'open',
        ]);

        expect($position)->toBeInstanceOf(ShiftPosition::class);
        expect($position->title)->toBe('Bartender');
        expect($position->hourly_rate)->toBe('25.00');
        expect($position->required_workers)->toBe(3);
        expect($position->status)->toBe('open');
    });

    it('calculates remaining slots correctly', function () {
        $shift = Shift::factory()->create();

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Server',
            'hourly_rate' => 20.00,
            'required_workers' => 5,
            'filled_workers' => 2,
        ]);

        expect($position->remaining_slots)->toBe(3);
        expect($position->remainingSlots())->toBe(3);
    });

    it('correctly identifies fully filled positions', function () {
        $shift = Shift::factory()->create();

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Security',
            'hourly_rate' => 30.00,
            'required_workers' => 2,
            'filled_workers' => 2,
        ]);

        expect($position->is_fully_filled)->toBeTrue();
        expect($position->isFullyFilled())->toBeTrue();
    });

    it('can be queried using scopes', function () {
        $shift = Shift::factory()->create();

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Open Position',
            'hourly_rate' => 20.00,
            'required_workers' => 2,
            'status' => 'open',
        ]);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Filled Position',
            'hourly_rate' => 20.00,
            'required_workers' => 1,
            'filled_workers' => 1,
            'status' => 'filled',
        ]);

        expect(ShiftPosition::open()->count())->toBe(1);
        expect(ShiftPosition::filled()->count())->toBe(1);
        expect(ShiftPosition::available()->count())->toBe(1);
    });

    it('belongs to a shift', function () {
        $shift = Shift::factory()->create();

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Host',
            'hourly_rate' => 18.00,
        ]);

        expect($position->shift)->toBeInstanceOf(Shift::class);
        expect($position->shift->id)->toBe($shift->id);
    });

    it('calculates fill percentage correctly', function () {
        $shift = Shift::factory()->create();

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Runner',
            'hourly_rate' => 15.00,
            'required_workers' => 4,
            'filled_workers' => 2,
        ]);

        expect($position->fill_percentage)->toBe(50.0);
    });

});

describe('Shift Multi-Position Features', function () {

    it('can have multiple positions', function () {
        $shift = Shift::factory()->create();

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Bartender',
            'hourly_rate' => 25.00,
            'required_workers' => 2,
        ]);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Server',
            'hourly_rate' => 20.00,
            'required_workers' => 4,
        ]);

        expect($shift->positions()->count())->toBe(2);
        expect($shift->isMultiPosition())->toBeTrue();
        expect($shift->hasPositions())->toBeTrue();
    });

    it('correctly identifies single position shifts', function () {
        $shift = Shift::factory()->create();

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'General Staff',
            'hourly_rate' => 18.00,
            'required_workers' => 5,
        ]);

        expect($shift->isMultiPosition())->toBeFalse();
        expect($shift->hasPositions())->toBeTrue();
    });

    it('calculates total workers correctly across positions', function () {
        $shift = Shift::factory()->create();

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Bartender',
            'hourly_rate' => 25.00,
            'required_workers' => 3,
            'filled_workers' => 2,
        ]);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Server',
            'hourly_rate' => 20.00,
            'required_workers' => 4,
            'filled_workers' => 1,
        ]);

        expect($shift->getTotalPositionWorkersRequired())->toBe(7);
        expect($shift->getTotalPositionWorkersFilled())->toBe(3);
    });

    it('provides position summary', function () {
        $shift = Shift::factory()->create();

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Open',
            'hourly_rate' => 20.00,
            'status' => 'open',
        ]);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Filled',
            'hourly_rate' => 20.00,
            'status' => 'filled',
        ]);

        $summary = $shift->getPositionsSummary();

        expect($summary['total'])->toBe(2);
        expect($summary['open'])->toBe(1);
        expect($summary['filled'])->toBe(1);
    });

});

describe('ShiftPositionAssignment Model', function () {

    it('can link worker to position', function () {
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['user_type' => 'worker']);

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Bartender',
            'hourly_rate' => 25.00,
            'required_workers' => 2,
        ]);

        $shiftAssignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
            'assigned_by' => $shift->business_id,
            'status' => 'assigned',
            'payment_status' => 'pending',
        ]);

        $positionAssignment = ShiftPositionAssignment::create([
            'shift_position_id' => $position->id,
            'shift_assignment_id' => $shiftAssignment->id,
            'user_id' => $worker->id,
        ]);

        expect($positionAssignment->shiftPosition->id)->toBe($position->id);
        expect($positionAssignment->shiftAssignment->id)->toBe($shiftAssignment->id);
        expect($positionAssignment->user->id)->toBe($worker->id);
    });

    it('has convenience accessors', function () {
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['user_type' => 'worker']);

        $position = ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Server',
            'hourly_rate' => 22.50,
            'required_workers' => 1,
        ]);

        $shiftAssignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
            'assigned_by' => $shift->business_id,
            'status' => 'assigned',
            'payment_status' => 'pending',
        ]);

        $positionAssignment = ShiftPositionAssignment::create([
            'shift_position_id' => $position->id,
            'shift_assignment_id' => $shiftAssignment->id,
            'user_id' => $worker->id,
        ]);

        expect($positionAssignment->position_title)->toBe('Server');
        expect($positionAssignment->hourly_rate)->toBe(22.50);
    });

});

describe('ShiftPositionService', function () {

    it('creates multiple positions for a shift', function () {
        $shift = Shift::factory()->create();
        $service = app(ShiftPositionService::class);

        $positions = $service->createPositions($shift, [
            [
                'title' => 'Bartender',
                'hourly_rate' => 25.00,
                'required_workers' => 2,
                'required_skills' => [1, 2],
            ],
            [
                'title' => 'Server',
                'hourly_rate' => 20.00,
                'required_workers' => 4,
            ],
        ]);

        expect($positions)->toHaveCount(2);
        expect($shift->fresh()->positions)->toHaveCount(2);
    });

    it('gets available positions for a shift', function () {
        $shift = Shift::factory()->create();
        $service = app(ShiftPositionService::class);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Open',
            'hourly_rate' => 20.00,
            'required_workers' => 2,
            'filled_workers' => 1,
            'status' => 'partially_filled',
        ]);

        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Filled',
            'hourly_rate' => 20.00,
            'required_workers' => 1,
            'filled_workers' => 1,
            'status' => 'filled',
        ]);

        $available = $service->getAvailablePositions($shift);

        expect($available)->toHaveCount(1);
        expect($available->first()->title)->toBe('Open');
    });

    it('provides position summary for a shift', function () {
        $shift = Shift::factory()->create();
        $service = app(ShiftPositionService::class);

        $service->createPositions($shift, [
            ['title' => 'Bartender', 'hourly_rate' => 25.00, 'required_workers' => 2],
            ['title' => 'Server', 'hourly_rate' => 20.00, 'required_workers' => 3],
        ]);

        $summary = $service->getPositionsSummary($shift);

        expect($summary['total_positions'])->toBe(2);
        expect($summary['total_required_workers'])->toBe(5);
        expect($summary['total_filled_workers'])->toBe(0);
    });

    it('identifies multi-position shifts', function () {
        $shift = Shift::factory()->create();
        $service = app(ShiftPositionService::class);

        // Single position - not multi
        $service->createPositions($shift, [
            ['title' => 'General', 'hourly_rate' => 18.00],
        ]);

        expect($service->isMultiPositionShift($shift))->toBeFalse();

        // Add another position - now multi
        ShiftPosition::create([
            'shift_id' => $shift->id,
            'title' => 'Lead',
            'hourly_rate' => 22.00,
        ]);

        expect($service->isMultiPositionShift($shift))->toBeTrue();
    });

});
