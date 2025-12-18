<?php

use App\Models\AgencyProfile;
use App\Models\AgencyTier;
use App\Models\AgencyTierHistory;
use App\Models\User;
use App\Services\AgencyTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed default tiers for testing
    AgencyTier::create([
        'name' => 'Bronze',
        'slug' => 'bronze',
        'level' => 1,
        'min_monthly_revenue' => 0,
        'min_active_workers' => 5,
        'min_fill_rate' => 0,
        'min_rating' => 0,
        'commission_rate' => 15.00,
        'priority_booking_hours' => 0,
        'dedicated_support' => false,
        'custom_branding' => false,
        'api_access' => false,
        'is_active' => true,
    ]);

    AgencyTier::create([
        'name' => 'Silver',
        'slug' => 'silver',
        'level' => 2,
        'min_monthly_revenue' => 5000,
        'min_active_workers' => 20,
        'min_fill_rate' => 80,
        'min_rating' => 4.0,
        'commission_rate' => 12.00,
        'priority_booking_hours' => 2,
        'dedicated_support' => false,
        'custom_branding' => false,
        'api_access' => false,
        'is_active' => true,
    ]);

    AgencyTier::create([
        'name' => 'Gold',
        'slug' => 'gold',
        'level' => 3,
        'min_monthly_revenue' => 20000,
        'min_active_workers' => 50,
        'min_fill_rate' => 85,
        'min_rating' => 4.2,
        'commission_rate' => 10.00,
        'priority_booking_hours' => 6,
        'dedicated_support' => true,
        'custom_branding' => false,
        'api_access' => true,
        'is_active' => true,
    ]);

    $this->tierService = app(AgencyTierService::class);
});

test('it can calculate agency metrics', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $metrics = $this->tierService->calculateAgencyMetrics($agency);

    expect($metrics)->toHaveKeys([
        'monthly_revenue',
        'active_workers',
        'fill_rate',
        'rating',
        'total_shifts',
        'completed_shifts',
        'calculated_at',
    ]);

    expect($metrics['monthly_revenue'])->toBeNumeric();
    expect($metrics['active_workers'])->toBeInt();
    expect($metrics['fill_rate'])->toBeNumeric();
});

test('it determines eligible tier based on metrics', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    AgencyProfile::factory()->create(['user_id' => $agency->id]);

    // With no workers, should get Bronze (lowest)
    $eligibleTier = $this->tierService->determineEligibleTier($agency);

    expect($eligibleTier)->not->toBeNull();
    expect($eligibleTier->name)->toBe('Bronze');
});

test('it can upgrade an agency tier', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $profile = AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $silverTier = AgencyTier::where('slug', 'silver')->first();

    // First assign bronze
    $this->tierService->upgradeTier($agency, $bronzeTier);
    $profile->refresh();

    expect($profile->agency_tier_id)->toBe($bronzeTier->id);

    // Now upgrade to silver
    $history = $this->tierService->upgradeTier($agency, $silverTier);
    $profile->refresh();

    expect($profile->agency_tier_id)->toBe($silverTier->id);
    expect($history->change_type)->toBe(AgencyTierHistory::CHANGE_TYPE_UPGRADE);
    expect($history->from_tier_id)->toBe($bronzeTier->id);
    expect($history->to_tier_id)->toBe($silverTier->id);
});

test('it can downgrade an agency tier', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $profile = AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $silverTier = AgencyTier::where('slug', 'silver')->first();

    // First assign silver
    $this->tierService->upgradeTier($agency, $silverTier);
    $profile->refresh();

    // Now downgrade to bronze
    $history = $this->tierService->downgradeTier($agency, $bronzeTier);
    $profile->refresh();

    expect($profile->agency_tier_id)->toBe($bronzeTier->id);
    expect($history->change_type)->toBe(AgencyTierHistory::CHANGE_TYPE_DOWNGRADE);
    expect($history->from_tier_id)->toBe($silverTier->id);
    expect($history->to_tier_id)->toBe($bronzeTier->id);
});

test('it creates history record on tier change', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();

    $history = $this->tierService->upgradeTier($agency, $bronzeTier);

    expect($history)->toBeInstanceOf(AgencyTierHistory::class);
    expect($history->agency_id)->toBe($agency->id);
    expect($history->to_tier_id)->toBe($bronzeTier->id);
    expect($history->change_type)->toBe(AgencyTierHistory::CHANGE_TYPE_INITIAL);
    expect($history->metrics_at_change)->toBeArray();
});

test('it updates commission rate on tier change', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $profile = AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'commission_rate' => 20.00,
    ]);

    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();

    $this->tierService->upgradeTier($agency, $bronzeTier);
    $profile->refresh();

    expect($profile->commission_rate)->toBe('15.00');
});

test('it can assign initial tier to new agency', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $profile = AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $history = $this->tierService->assignInitialTier($agency);

    expect($history)->not->toBeNull();
    expect($history->change_type)->toBe(AgencyTierHistory::CHANGE_TYPE_INITIAL);

    $profile->refresh();
    expect($profile->agency_tier_id)->not->toBeNull();
});

test('it does not reassign tier if already assigned', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $profile = AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'agency_tier_id' => $bronzeTier->id,
    ]);

    $history = $this->tierService->assignInitialTier($agency);

    expect($history)->toBeNull();
});

test('it returns empty metrics for agency without profile', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    // No profile created

    $metrics = $this->tierService->calculateAgencyMetrics($agency);

    expect($metrics['monthly_revenue'])->toBe(0.0);
    expect($metrics['active_workers'])->toBe(0);
    expect($metrics['fill_rate'])->toBe(0.0);
    expect($metrics['rating'])->toBe(0.0);
});

test('it can get tier distribution statistics', function () {
    // Create some agencies with different tiers
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $silverTier = AgencyTier::where('slug', 'silver')->first();

    for ($i = 0; $i < 3; $i++) {
        $agency = User::factory()->create(['user_type' => 'agency']);
        AgencyProfile::factory()->create([
            'user_id' => $agency->id,
            'agency_tier_id' => $bronzeTier->id,
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        $agency = User::factory()->create(['user_type' => 'agency']);
        AgencyProfile::factory()->create([
            'user_id' => $agency->id,
            'agency_tier_id' => $silverTier->id,
        ]);
    }

    $distribution = $this->tierService->getTierDistribution();

    expect($distribution)->toBeArray();
    expect($distribution)->toHaveCount(3); // Bronze, Silver, Gold

    $bronzeDistribution = collect($distribution)->firstWhere('tier_slug', 'bronze');
    $silverDistribution = collect($distribution)->firstWhere('tier_slug', 'silver');

    expect($bronzeDistribution['agency_count'])->toBe(3);
    expect($silverDistribution['agency_count'])->toBe(2);
});

test('it can get agency dashboard metrics', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'agency_tier_id' => $bronzeTier->id,
        'tier_achieved_at' => now(),
    ]);

    $dashboardMetrics = $this->tierService->getAgencyDashboardMetrics($agency);

    expect($dashboardMetrics)->toHaveKeys([
        'current_tier',
        'metrics',
        'next_tier',
        'tier_review_at',
        'tier_history',
    ]);

    expect($dashboardMetrics['current_tier']['name'])->toBe('Bronze');
    expect($dashboardMetrics['next_tier']['name'])->toBe('Silver');
});

test('it can get tier progress towards next tier', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'agency_tier_id' => $bronzeTier->id,
    ]);

    $progress = $this->tierService->getTierProgress($agency);

    expect($progress)->toHaveKeys([
        'next_tier',
        'progress',
        'overall_progress',
        'can_upgrade',
    ]);

    expect($progress['next_tier']['name'])->toBe('Silver');
    expect($progress['can_upgrade'])->toBeFalse(); // Doesn't meet requirements
});

test('it indicates max tier when at highest level', function () {
    $agency = User::factory()->create(['user_type' => 'agency']);
    $goldTier = AgencyTier::where('slug', 'gold')->first();
    AgencyProfile::factory()->create([
        'user_id' => $agency->id,
        'agency_tier_id' => $goldTier->id,
    ]);

    $progress = $this->tierService->getTierProgress($agency);

    expect($progress['is_max_tier'])->toBeTrue();
    expect($progress['next_tier'])->toBeNull();
    expect($progress['overall_progress'])->toBe(100);
});

test('tier meetsRequirements correctly evaluates metrics', function () {
    $silverTier = AgencyTier::where('slug', 'silver')->first();

    // Should not meet requirements
    $lowMetrics = [
        'monthly_revenue' => 1000,
        'active_workers' => 5,
        'fill_rate' => 50,
        'rating' => 3.5,
    ];
    expect($silverTier->meetsRequirements($lowMetrics))->toBeFalse();

    // Should meet requirements
    $highMetrics = [
        'monthly_revenue' => 10000,
        'active_workers' => 30,
        'fill_rate' => 90,
        'rating' => 4.5,
    ];
    expect($silverTier->meetsRequirements($highMetrics))->toBeTrue();
});

test('tier can get next and previous tiers', function () {
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $silverTier = AgencyTier::where('slug', 'silver')->first();
    $goldTier = AgencyTier::where('slug', 'gold')->first();

    expect($bronzeTier->getNextTier()->id)->toBe($silverTier->id);
    expect($bronzeTier->getPreviousTier())->toBeNull();

    expect($silverTier->getNextTier()->id)->toBe($goldTier->id);
    expect($silverTier->getPreviousTier()->id)->toBe($bronzeTier->id);

    expect($goldTier->getNextTier())->toBeNull();
    expect($goldTier->getPreviousTier()->id)->toBe($silverTier->id);
});

test('tier correctly identifies highest and lowest tiers', function () {
    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $goldTier = AgencyTier::where('slug', 'gold')->first();

    expect($bronzeTier->isLowestTier())->toBeTrue();
    expect($bronzeTier->isHighestTier())->toBeFalse();

    expect($goldTier->isLowestTier())->toBeFalse();
    expect($goldTier->isHighestTier())->toBeTrue();
});

test('manual tier adjustment records the admin user', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);
    $agency = User::factory()->create(['user_type' => 'agency']);
    $profile = AgencyProfile::factory()->create(['user_id' => $agency->id]);

    $bronzeTier = AgencyTier::where('slug', 'bronze')->first();
    $silverTier = AgencyTier::where('slug', 'silver')->first();

    // First assign bronze
    $this->tierService->upgradeTier($agency, $bronzeTier);

    // Manual adjustment to silver
    $history = $this->tierService->manualTierAdjustment(
        $agency,
        $silverTier,
        $admin->id,
        'Manual upgrade for excellent performance'
    );

    expect($history->processed_by)->toBe($admin->id);
    expect($history->wasManuallyProcessed())->toBeTrue();
});
