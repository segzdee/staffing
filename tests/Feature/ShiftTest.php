<?php

use App\Models\Shift;
use App\Models\User;
use App\Models\BusinessProfile;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    $this->initializeMigrations();

    // Create a business user with profile
    $this->business = User::factory()->create([
        'user_type' => 'business',
        'email_verified_at' => now(),
    ]);

    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
        'business_name' => 'Test Company',
    ]);
});

test('can create a shift with monetary values', function () {
    $shift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'role_type' => 'Warehouse Worker',
        'base_rate' => 2500, // $25.00 in cents
        'status' => 'draft',
    ]);

    expect($shift)->toBeInstanceOf(Shift::class)
        ->and($shift->base_rate)->toBeInstanceOf(\Money\Money::class)
        ->and($shift->base_rate->getAmount())->toBe('2500');
});

test('open scope returns only open shifts', function () {
    // Create open shift
    $openShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'start_datetime' => now()->addDays(2),
    ]);

    // Create closed shift
    $closedShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'filled',
        'start_datetime' => now()->addDays(2),
    ]);

    // Create past shift
    $pastShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'start_datetime' => now()->subDays(1),
    ]);

    // Query only this business's shifts to avoid interference from other tests
    $openShifts = Shift::where('business_id', $this->business->id)->open()->get();

    expect($openShifts)->toHaveCount(1)
        ->and($openShifts->first()->id)->toBe($openShift->id);
});

test('upcoming scope returns shifts ordered by start time', function () {
    $shift1 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'start_datetime' => now()->addDays(3),
    ]);

    $shift2 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'start_datetime' => now()->addDays(1),
    ]);

    $shift3 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'start_datetime' => now()->addDays(2),
    ]);

    // Query only this business's shifts to avoid interference from other tests
    $upcomingShifts = Shift::where('business_id', $this->business->id)->upcoming()->get();

    expect($upcomingShifts)->toHaveCount(3)
        ->and($upcomingShifts->first()->id)->toBe($shift2->id)
        ->and($upcomingShifts->last()->id)->toBe($shift1->id);
});

test('nearby scope returns shifts within radius', function () {
    // San Francisco coordinates
    $sfLat = 37.7749;
    $sfLng = -122.4194;

    // Create shift in SF
    $nearbyShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'location_lat' => $sfLat,
        'location_lng' => $sfLng,
    ]);

    // Create shift in LA (far away)
    $farShift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'location_lat' => 34.0522, // Los Angeles
        'location_lng' => -118.2437,
    ]);

    // Search within 50 miles of SF
    $shifts = Shift::nearby($sfLat, $sfLng, 50)->get();

    expect($shifts)->toHaveCount(1)
        ->and($shifts->first()->id)->toBe($nearbyShift->id);
});

test('shift belongs to business user', function () {
    $shift = Shift::factory()->create([
        'business_id' => $this->business->id,
    ]);

    expect($shift->business)->toBeInstanceOf(User::class)
        ->and($shift->business->id)->toBe($this->business->id)
        ->and($shift->business->user_type)->toBe('business');
});
