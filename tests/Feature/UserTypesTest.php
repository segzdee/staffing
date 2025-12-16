<?php

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create worker user', function () {
    $user = User::factory()->create([
        'user_type' => 'worker',
        'name' => 'Test Worker',
        'email' => 'worker@test.com',
    ]);

    expect($user->isWorker())->toBeTrue()
        ->and($user->isBusiness())->toBeFalse()
        ->and($user->isAgency())->toBeFalse()
        ->and($user->user_type)->toBe('worker');
});

test('can create business user', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'name' => 'Test Business',
        'email' => 'business@test.com',
    ]);

    expect($user->isBusiness())->toBeTrue()
        ->and($user->isWorker())->toBeFalse()
        ->and($user->isAgency())->toBeFalse()
        ->and($user->user_type)->toBe('business');
});

test('can create agency user', function () {
    $user = User::factory()->create([
        'user_type' => 'agency',
        'name' => 'Test Agency',
        'email' => 'agency@test.com',
    ]);

    expect($user->isAgency())->toBeTrue()
        ->and($user->isWorker())->toBeFalse()
        ->and($user->isBusiness())->toBeFalse()
        ->and($user->user_type)->toBe('agency');
});

test('can create admin user', function () {
    $user = User::factory()->create([
        'user_type' => 'admin',
        'role' => 'admin',
        'name' => 'Admin User',
        'email' => 'admin@test.com',
    ]);

    expect($user->isAdmin())->toBeTrue()
        ->and($user->user_type)->toBe('admin')
        ->and($user->role)->toBe('admin');
});

test('worker has worker profile relationship', function () {
    $worker = User::factory()->create([
        'user_type' => 'worker',
    ]);

    $profile = WorkerProfile::factory()->create([
        'user_id' => $worker->id,
        'bio' => 'Test bio',
        'hourly_rate' => 2500,
    ]);

    expect($worker->workerProfile)->toBeInstanceOf(WorkerProfile::class)
        ->and($worker->workerProfile->id)->toBe($profile->id)
        ->and($worker->workerProfile->bio)->toBe('Test bio');
});

test('business has business profile relationship', function () {
    $business = User::factory()->create([
        'user_type' => 'business',
    ]);

    $profile = BusinessProfile::factory()->create([
        'user_id' => $business->id,
        'business_name' => 'Test Company',
    ]);

    expect($business->businessProfile)->toBeInstanceOf(BusinessProfile::class)
        ->and($business->businessProfile->id)->toBe($profile->id)
        ->and($business->businessProfile->business_name)->toBe('Test Company');
});

test('agency has agency profile relationship', function () {
    $agency = User::factory()->create([
        'user_type' => 'agency',
    ]);

    $profile = AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'agency_name' => 'Test Agency',
        'license_number' => 'LIC123',
    ]);

    expect($agency->agencyProfile)->toBeInstanceOf(AgencyProfile::class)
        ->and($agency->agencyProfile->id)->toBe($profile->id)
        ->and($agency->agencyProfile->agency_name)->toBe('Test Agency');
});

test('worker can authenticate', function () {
    $worker = User::factory()->create([
        'user_type' => 'worker',
        'password' => bcrypt('password'),
    ]);

    $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
        ->post('/login', [
            'email' => $worker->email,
            'password' => 'password',
        ]);

    expect($response->status())->toBe(302)
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->user()->id)->toBe($worker->id);
});

test('business can authenticate', function () {
    $business = User::factory()->create([
        'user_type' => 'business',
        'password' => bcrypt('password'),
    ]);

    $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
        ->post('/login', [
            'email' => $business->email,
            'password' => 'password',
        ]);

    expect($response->status())->toBe(302)
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->user()->id)->toBe($business->id);
});
