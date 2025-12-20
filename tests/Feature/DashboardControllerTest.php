<?php

use App\Models\AgencyProfile;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/*
|--------------------------------------------------------------------------
| Worker Dashboard Tests
|--------------------------------------------------------------------------
*/

it('worker can access dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'worker',
        'role' => 'worker',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    WorkerProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('worker.dashboard'));

    $response->assertStatus(200);
});

it('worker dashboard handles empty data gracefully', function () {
    $user = User::factory()->create([
        'user_type' => 'worker',
        'role' => 'worker',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    WorkerProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('worker.dashboard'));

    $response->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Business Dashboard Tests
|--------------------------------------------------------------------------
*/

it('business can access dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'role' => 'business',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    BusinessProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('business.dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('totalShifts');
});

it('business can access reports analytics page', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'role' => 'business',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    BusinessProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('business.reports.analytics'));

    $response->assertStatus(200);
    $response->assertViewHas('shiftsLastSixMonths');
});

it('business can access reports spending page', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'role' => 'business',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    BusinessProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('business.reports.spending'));

    $response->assertStatus(200);
    $response->assertViewHas('monthlySpending');
});

it('business can access shifts upcoming page', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'role' => 'business',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    BusinessProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('business.shifts.upcoming'));

    $response->assertStatus(200);
    $response->assertViewHas('shifts');
});

/*
|--------------------------------------------------------------------------
| Agency Dashboard Tests
|--------------------------------------------------------------------------
*/

it('agency can access dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'agency',
        'role' => 'agency',
        'email_verified_at' => now(),
        'status' => 'active',
        'is_dev_account' => true, // Bypass profile completeness checks
    ]);

    AgencyProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('agency.dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('totalWorkers');
});

it('agency can access analytics dashboard page', function () {
    $user = User::factory()->create([
        'user_type' => 'agency',
        'role' => 'agency',
        'email_verified_at' => now(),
        'status' => 'active',
        'is_dev_account' => true,
    ]);

    AgencyProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('agency.analytics.dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('totalWorkers');
});

it('agency can access analytics utilization page', function () {
    $user = User::factory()->create([
        'user_type' => 'agency',
        'role' => 'agency',
        'email_verified_at' => now(),
        'status' => 'active',
        'is_dev_account' => true,
    ]);

    AgencyProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('agency.analytics.utilization'));

    $response->assertStatus(200);
    $response->assertViewHas('utilizationRate');
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard Tests
|--------------------------------------------------------------------------
*/

it('admin can access dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'admin',
        'role' => 'admin',
        'email_verified_at' => now(),
        'status' => 'active',
        'is_dev_account' => true,
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('total_users');
});

it('admin dashboard shows correct user counts', function () {
    User::factory()->count(5)->create(['user_type' => 'worker']);
    User::factory()->count(3)->create(['user_type' => 'business']);
    User::factory()->count(2)->create(['user_type' => 'agency']);

    $admin = User::factory()->create([
        'user_type' => 'admin',
        'role' => 'admin',
        'email_verified_at' => now(),
        'status' => 'active',
        'is_dev_account' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('total_workers', 5);
    $response->assertViewHas('total_businesses', 3);
    $response->assertViewHas('total_agencies', 2);
});

/*
|--------------------------------------------------------------------------
| Dashboard Access Control Tests
|--------------------------------------------------------------------------
*/

it('unauthenticated users cannot access any dashboard', function () {
    $this->get(route('worker.dashboard'))->assertRedirect(route('login'));
    $this->get(route('business.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('worker cannot access business dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'worker',
        'role' => 'worker',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    WorkerProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('business.dashboard'));

    // Should redirect or return 403
    $this->assertTrue(in_array($response->status(), [302, 403]));
});

it('business cannot access worker dashboard', function () {
    $user = User::factory()->create([
        'user_type' => 'business',
        'role' => 'business',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    BusinessProfile::factory()->for($user)->create([
        'onboarding_completed' => true,
        'is_verified' => true,
        'is_complete' => true,
    ]);

    $response = $this->actingAs($user)->get(route('worker.dashboard'));

    // Should redirect or return 403
    $this->assertTrue(in_array($response->status(), [302, 403]));
});
