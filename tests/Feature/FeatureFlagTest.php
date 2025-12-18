<?php

use App\Models\FeatureFlag;
use App\Models\User;
use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FeatureFlag Model', function () {
    it('can create a feature flag', function () {
        $flag = FeatureFlag::create([
            'key' => 'test_feature',
            'name' => 'Test Feature',
            'description' => 'A test feature flag',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        expect($flag)->toBeInstanceOf(FeatureFlag::class)
            ->and($flag->key)->toBe('test_feature')
            ->and($flag->name)->toBe('Test Feature')
            ->and($flag->is_enabled)->toBeTrue();
    });

    it('can check if enabled for user with 100% rollout', function () {
        $flag = FeatureFlag::create([
            'key' => 'full_rollout',
            'name' => 'Full Rollout',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $user = User::factory()->create();

        expect($flag->isEnabledForUser($user))->toBeTrue();
    });

    it('returns false when disabled', function () {
        $flag = FeatureFlag::create([
            'key' => 'disabled_feature',
            'name' => 'Disabled Feature',
            'is_enabled' => false,
            'rollout_percentage' => 100,
        ]);

        $user = User::factory()->create();

        expect($flag->isEnabledForUser($user))->toBeFalse();
    });

    it('returns false for 0% rollout', function () {
        $flag = FeatureFlag::create([
            'key' => 'zero_rollout',
            'name' => 'Zero Rollout',
            'is_enabled' => true,
            'rollout_percentage' => 0,
        ]);

        $user = User::factory()->create();

        expect($flag->isEnabledForUser($user))->toBeFalse();
    });

    it('respects date range - before start', function () {
        $flag = FeatureFlag::create([
            'key' => 'scheduled_feature',
            'name' => 'Scheduled Feature',
            'is_enabled' => true,
            'rollout_percentage' => 100,
            'starts_at' => now()->addDays(1),
        ]);

        $user = User::factory()->create();

        expect($flag->isEnabledForUser($user))->toBeFalse()
            ->and($flag->isWithinDateRange())->toBeFalse();
    });

    it('respects date range - after end', function () {
        $flag = FeatureFlag::create([
            'key' => 'expired_feature',
            'name' => 'Expired Feature',
            'is_enabled' => true,
            'rollout_percentage' => 100,
            'ends_at' => now()->subDays(1),
        ]);

        $user = User::factory()->create();

        expect($flag->isEnabledForUser($user))->toBeFalse()
            ->and($flag->isWithinDateRange())->toBeFalse();
    });

    it('enables for specific user IDs', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $flag = FeatureFlag::create([
            'key' => 'user_specific',
            'name' => 'User Specific Feature',
            'is_enabled' => true,
            'rollout_percentage' => 0,
            'enabled_for_users' => [$user1->id],
        ]);

        expect($flag->isEnabledForUser($user1))->toBeTrue()
            ->and($flag->isEnabledForUser($user2))->toBeFalse();
    });

    it('enables for specific roles', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $worker = User::factory()->create(['role' => 'worker']);

        $flag = FeatureFlag::create([
            'key' => 'admin_only',
            'name' => 'Admin Only Feature',
            'is_enabled' => true,
            'rollout_percentage' => 0,
            'enabled_for_roles' => ['admin'],
        ]);

        expect($flag->isEnabledForUser($admin))->toBeTrue()
            ->and($flag->isEnabledForUser($worker))->toBeFalse();
    });

    it('uses consistent hashing for rollout percentage', function () {
        $user = User::factory()->create();

        $flag = FeatureFlag::create([
            'key' => 'partial_rollout',
            'name' => 'Partial Rollout',
            'is_enabled' => true,
            'rollout_percentage' => 50,
        ]);

        // The result should be consistent for the same user
        $result1 = $flag->meetsRolloutPercentage($user);
        $result2 = $flag->meetsRolloutPercentage($user);
        $result3 = $flag->meetsRolloutPercentage($user);

        expect($result1)->toBe($result2)->toBe($result3);
    });
});

describe('FeatureFlagService', function () {
    it('can check if feature is enabled', function () {
        FeatureFlag::create([
            'key' => 'service_test',
            'name' => 'Service Test',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $service = app(FeatureFlagService::class);
        $user = User::factory()->create();

        expect($service->isEnabled('service_test', $user))->toBeTrue()
            ->and($service->isEnabled('non_existent'))->toBeFalse();
    });

    it('can enable and disable flags', function () {
        $flag = FeatureFlag::create([
            'key' => 'toggle_test',
            'name' => 'Toggle Test',
            'is_enabled' => false,
            'rollout_percentage' => 100,
        ]);

        $service = app(FeatureFlagService::class);
        $user = User::factory()->create();

        expect($service->isEnabled('toggle_test', $user))->toBeFalse();

        $service->enable('toggle_test');
        expect($service->isEnabled('toggle_test', $user))->toBeTrue();

        $service->disable('toggle_test');
        expect($service->isEnabled('toggle_test', $user))->toBeFalse();
    });

    it('can set rollout percentage', function () {
        $flag = FeatureFlag::create([
            'key' => 'rollout_test',
            'name' => 'Rollout Test',
            'is_enabled' => true,
            'rollout_percentage' => 0,
        ]);

        $service = app(FeatureFlagService::class);

        $service->setRolloutPercentage('rollout_test', 100);

        $flag->refresh();
        expect($flag->rollout_percentage)->toBe(100);
    });

    it('can add users to enabled list', function () {
        $flag = FeatureFlag::create([
            'key' => 'user_test',
            'name' => 'User Test',
            'is_enabled' => true,
            'rollout_percentage' => 0,
        ]);

        $service = app(FeatureFlagService::class);
        $user = User::factory()->create();

        $service->enableForUsers('user_test', [$user->id]);

        $flag->refresh();
        expect($flag->enabled_for_users)->toContain($user->id);
    });

    it('can create a new flag', function () {
        $service = app(FeatureFlagService::class);

        $flag = $service->create([
            'key' => 'new_flag',
            'name' => 'New Flag',
            'description' => 'Created via service',
            'is_enabled' => true,
            'rollout_percentage' => 50,
        ]);

        expect($flag)->toBeInstanceOf(FeatureFlag::class)
            ->and($flag->key)->toBe('new_flag')
            ->and($flag->rollout_percentage)->toBe(50);
    });
});

describe('feature() helper', function () {
    it('returns false when flag does not exist', function () {
        expect(feature('non_existent_flag'))->toBeFalse();
    });

    it('returns true when flag is enabled with 100% rollout', function () {
        FeatureFlag::create([
            'key' => 'helper_test',
            'name' => 'Helper Test',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        expect(feature('helper_test'))->toBeTrue();
    });

    it('returns false when flag is disabled', function () {
        FeatureFlag::create([
            'key' => 'disabled_helper_test',
            'name' => 'Disabled Helper Test',
            'is_enabled' => false,
            'rollout_percentage' => 100,
        ]);

        expect(feature('disabled_helper_test'))->toBeFalse();
    });
});
