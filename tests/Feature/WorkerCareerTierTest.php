<?php

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerTier;
use App\Models\WorkerTierHistory;
use App\Services\WorkerTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed the worker tiers
    $this->artisan('db:seed', ['--class' => 'WorkerTierSeeder']);
});

describe('WorkerTierService', function () {
    beforeEach(function () {
        // Create a fresh worker user with profile for each test
        $this->worker = User::factory()->create(['user_type' => 'worker']);
        $this->workerProfile = WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
        ]);
        $this->service = app(WorkerTierService::class);
    });

    test('returns rookie tier for new workers with no shifts', function () {
        $tier = $this->service->determineEligibleTier($this->worker);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('rookie')
            ->and((int) $tier->level)->toBe(1);
    });

    test('calculateWorkerMetrics returns correct default values for new worker', function () {
        $metrics = $this->service->calculateWorkerMetrics($this->worker);

        expect($metrics)->toBeArray()
            ->and($metrics['shifts_completed'])->toBe(0)
            ->and($metrics['rating'])->toBe(0.0)
            ->and($metrics['hours_worked'])->toBe(0.0)
            ->and($metrics['months_active'])->toBe(0);
    });

    test('initializeWorkerTier assigns rookie tier to new worker', function () {
        $tier = $this->service->initializeWorkerTier($this->worker);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('rookie');

        $this->workerProfile->refresh();
        expect($this->workerProfile->worker_tier_id)->toBe($tier->id);
    });

    test('getTierProgress returns progress information', function () {
        $this->service->initializeWorkerTier($this->worker);

        $progress = $this->service->getTierProgress($this->worker);

        expect($progress)->toBeArray()
            ->and($progress)->toHaveKeys([
                'current_tier',
                'metrics',
                'next_tier',
                'overall_progress',
            ])
            ->and($progress['current_tier']['slug'])->toBe('rookie')
            ->and($progress['next_tier']['slug'])->toBe('regular');
    });

    test('upgradeTier upgrades worker to higher tier', function () {
        $this->service->initializeWorkerTier($this->worker);

        $regularTier = WorkerTier::findBySlug('regular');

        $result = $this->service->upgradeTier($this->worker, $regularTier);

        expect($result)->toBeTrue();

        $this->workerProfile->refresh();
        expect($this->workerProfile->worker_tier_id)->toBe($regularTier->id);
    });

    test('upgradeTier records history entry', function () {
        $this->service->initializeWorkerTier($this->worker);

        // Refresh the worker to get updated relationships
        $this->worker->refresh();
        $this->workerProfile->refresh();

        $regularTier = WorkerTier::findBySlug('regular');
        $this->service->upgradeTier($this->worker, $regularTier);

        $history = WorkerTierHistory::where('user_id', $this->worker->id)
            ->where('change_type', 'upgrade')
            ->first();

        expect($history)->not->toBeNull()
            ->and($history->to_tier_id)->toBe($regularTier->id);
    });

    test('downgradeTier downgrades worker to lower tier', function () {
        // Start with regular tier
        $regularTier = WorkerTier::findBySlug('regular');
        $this->workerProfile->update(['worker_tier_id' => $regularTier->id]);

        $rookieTier = WorkerTier::findBySlug('rookie');

        $result = $this->service->downgradeTier($this->worker, $rookieTier);

        expect($result)->toBeTrue();

        $this->workerProfile->refresh();
        expect($this->workerProfile->worker_tier_id)->toBe($rookieTier->id);
    });

    test('downgradeTier records history entry', function () {
        $regularTier = WorkerTier::findBySlug('regular');
        $this->workerProfile->update(['worker_tier_id' => $regularTier->id]);

        $rookieTier = WorkerTier::findBySlug('rookie');
        $this->service->downgradeTier($this->worker, $rookieTier);

        $history = WorkerTierHistory::where('user_id', $this->worker->id)
            ->where('change_type', 'downgrade')
            ->first();

        expect($history)->not->toBeNull()
            ->and($history->from_tier_id)->toBe($regularTier->id)
            ->and($history->to_tier_id)->toBe($rookieTier->id);
    });

    test('calculateFeeWithDiscount returns correct discount for tier', function () {
        $proTier = WorkerTier::findBySlug('pro');
        $this->workerProfile->update(['worker_tier_id' => $proTier->id]);

        $result = $this->service->calculateFeeWithDiscount($this->worker, 100.00);

        expect($result)->toBeArray()
            ->and($result['base_fee'])->toBe(100.00)
            ->and((float) $result['discount_percent'])->toBe(5.00)
            ->and($result['discount_amount'])->toBe(5.00)
            ->and($result['final_fee'])->toBe(95.00)
            ->and($result['tier_applied'])->toBe('Pro');
    });

    test('calculateFeeWithDiscount returns no discount for rookie tier', function () {
        $rookieTier = WorkerTier::findBySlug('rookie');
        $this->workerProfile->update(['worker_tier_id' => $rookieTier->id]);

        $result = $this->service->calculateFeeWithDiscount($this->worker, 100.00);

        expect($result['discount_percent'])->toBe(0)
            ->and($result['discount_amount'])->toBe(0)
            ->and($result['final_fee'])->toBe(100.00);
    });

    test('getTierBenefits returns correct benefit structure', function () {
        $proTier = WorkerTier::findBySlug('pro');

        $benefits = $this->service->getTierBenefits($proTier);

        expect($benefits)->toBeArray()
            ->and($benefits)->toHaveKeys(['tier', 'benefits', 'benefits_list', 'requirements'])
            ->and($benefits['tier']['name'])->toBe('Pro')
            ->and($benefits['benefits']['fee_discount_percent'])->toBe('5.00')
            ->and($benefits['benefits']['instant_payout'])->toBeTrue();
    });

    test('getLeaderboard returns ranked workers', function () {
        // Create multiple workers with different tiers
        $eliteTier = WorkerTier::findBySlug('elite');
        $proTier = WorkerTier::findBySlug('pro');

        $this->workerProfile->update([
            'worker_tier_id' => $eliteTier->id,
            'lifetime_shifts' => 200,
            'rating_average' => 4.8,
        ]);

        $worker2 = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create([
            'user_id' => $worker2->id,
            'worker_tier_id' => $proTier->id,
            'lifetime_shifts' => 100,
            'rating_average' => 4.5,
        ]);

        $leaderboard = $this->service->getLeaderboard(10);

        expect($leaderboard)->toHaveCount(2)
            ->and($leaderboard[0]['rank'])->toBe(1)
            ->and($leaderboard[0]['user_id'])->toBe($this->worker->id);
    });

    test('getWorkerTierHistory returns history entries', function () {
        $this->service->initializeWorkerTier($this->worker);

        $regularTier = WorkerTier::findBySlug('regular');
        $this->service->upgradeTier($this->worker, $regularTier);

        $history = $this->service->getWorkerTierHistory($this->worker);

        expect($history)->toHaveCount(2); // Initial + upgrade
    });
});

describe('WorkerTier Model', function () {
    test('all seeded tiers exist', function () {
        expect(WorkerTier::count())->toBe(5);

        expect(WorkerTier::findBySlug('rookie'))->not->toBeNull();
        expect(WorkerTier::findBySlug('regular'))->not->toBeNull();
        expect(WorkerTier::findBySlug('pro'))->not->toBeNull();
        expect(WorkerTier::findBySlug('elite'))->not->toBeNull();
        expect(WorkerTier::findBySlug('legend'))->not->toBeNull();
    });

    test('getDefaultTier returns rookie tier', function () {
        $defaultTier = WorkerTier::getDefaultTier();

        expect($defaultTier)->not->toBeNull()
            ->and($defaultTier->slug)->toBe('rookie')
            ->and((int) $defaultTier->level)->toBe(1);
    });

    test('getNextTier returns correct next tier', function () {
        $rookie = WorkerTier::findBySlug('rookie');
        $pro = WorkerTier::findBySlug('pro');
        $legend = WorkerTier::findBySlug('legend');

        expect($rookie->getNextTier()->slug)->toBe('regular');
        expect($pro->getNextTier()->slug)->toBe('elite');
        expect($legend->getNextTier())->toBeNull(); // No next tier
    });

    test('getPreviousTier returns correct previous tier', function () {
        $regular = WorkerTier::findBySlug('regular');
        $legend = WorkerTier::findBySlug('legend');
        $rookie = WorkerTier::findBySlug('rookie');

        expect($regular->getPreviousTier()->slug)->toBe('rookie');
        expect($legend->getPreviousTier()->slug)->toBe('elite');
        expect($rookie->getPreviousTier())->toBeNull(); // No previous tier
    });

    test('meetsRequirements returns correct boolean', function () {
        $regularTier = WorkerTier::findBySlug('regular');

        $meetingMetrics = [
            'shifts_completed' => 15,
            'rating' => 4.2,
            'hours_worked' => 50,
            'months_active' => 2,
        ];

        $notMeetingMetrics = [
            'shifts_completed' => 5,
            'rating' => 3.5,
            'hours_worked' => 20,
            'months_active' => 0,
        ];

        expect($regularTier->meetsRequirements($meetingMetrics))->toBeTrue()
            ->and($regularTier->meetsRequirements($notMeetingMetrics))->toBeFalse();
    });

    test('getProgressTowards calculates progress correctly', function () {
        $regularTier = WorkerTier::findBySlug('regular');

        $metrics = [
            'shifts_completed' => 5,
            'rating' => 3.0,
            'hours_worked' => 20,
            'months_active' => 0,
        ];

        $progress = $regularTier->getProgressTowards($metrics);

        expect($progress)->toHaveKeys(['shifts', 'rating', 'hours', 'months'])
            ->and($progress['shifts']['current'])->toBe(5)
            ->and($progress['shifts']['required'])->toBe(10)
            ->and($progress['shifts']['remaining'])->toBe(5)
            ->and((int) $progress['shifts']['percent'])->toBe(50);
    });

    test('getAllBenefits returns formatted benefits array', function () {
        $proTier = WorkerTier::findBySlug('pro');

        $benefits = $proTier->getAllBenefits();

        expect($benefits)->toBeArray()
            ->and(count($benefits))->toBeGreaterThan(0);

        // Check for fee discount benefit (format may vary based on decimal)
        $hasFeeDiscount = collect($benefits)->contains(fn ($b) => str_contains($b, 'fee discount'));
        $hasInstantPayout = collect($benefits)->contains(fn ($b) => str_contains(strtolower($b), 'instant payout'));

        expect($hasFeeDiscount)->toBeTrue()
            ->and($hasInstantPayout)->toBeTrue();
    });

    test('isHighestTier returns correct boolean', function () {
        $legend = WorkerTier::findBySlug('legend');
        $pro = WorkerTier::findBySlug('pro');

        expect($legend->isHighestTier())->toBeTrue()
            ->and($pro->isHighestTier())->toBeFalse();
    });

    test('isLowestTier returns correct boolean', function () {
        $rookie = WorkerTier::findBySlug('rookie');
        $regular = WorkerTier::findBySlug('regular');

        expect($rookie->isLowestTier())->toBeTrue()
            ->and($regular->isLowestTier())->toBeFalse();
    });
});

describe('WorkerTierHistory Model', function () {
    beforeEach(function () {
        $this->worker = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create(['user_id' => $this->worker->id]);
    });

    test('recordInitial creates initial history entry', function () {
        $rookieTier = WorkerTier::findBySlug('rookie');
        $metrics = ['shifts_completed' => 0, 'rating' => 0, 'hours_worked' => 0, 'months_active' => 0];

        $history = WorkerTierHistory::recordInitial($this->worker, $rookieTier, $metrics);

        expect($history->change_type)->toBe('initial')
            ->and($history->from_tier_id)->toBeNull()
            ->and($history->to_tier_id)->toBe($rookieTier->id);
    });

    test('recordUpgrade creates upgrade history entry', function () {
        $rookieTier = WorkerTier::findBySlug('rookie');
        $regularTier = WorkerTier::findBySlug('regular');
        $metrics = ['shifts_completed' => 15, 'rating' => 4.2, 'hours_worked' => 50, 'months_active' => 2];

        $history = WorkerTierHistory::recordUpgrade($this->worker, $rookieTier, $regularTier, $metrics);

        expect($history->change_type)->toBe('upgrade')
            ->and($history->from_tier_id)->toBe($rookieTier->id)
            ->and($history->to_tier_id)->toBe($regularTier->id);
    });

    test('recordDowngrade creates downgrade history entry', function () {
        $regularTier = WorkerTier::findBySlug('regular');
        $rookieTier = WorkerTier::findBySlug('rookie');
        $metrics = ['shifts_completed' => 5, 'rating' => 3.5, 'hours_worked' => 20, 'months_active' => 1];

        $history = WorkerTierHistory::recordDowngrade($this->worker, $regularTier, $rookieTier, $metrics);

        expect($history->change_type)->toBe('downgrade')
            ->and($history->from_tier_id)->toBe($regularTier->id)
            ->and($history->to_tier_id)->toBe($rookieTier->id);
    });

    test('getChangeDescription returns correct description', function () {
        $rookieTier = WorkerTier::findBySlug('rookie');
        $regularTier = WorkerTier::findBySlug('regular');
        $metrics = ['shifts_completed' => 15, 'rating' => 4.2, 'hours_worked' => 50, 'months_active' => 2];

        $upgradeHistory = WorkerTierHistory::recordUpgrade($this->worker, $rookieTier, $regularTier, $metrics);
        $initialHistory = WorkerTierHistory::recordInitial($this->worker, $rookieTier, $metrics);

        expect($upgradeHistory->getChangeDescription())->toBe('Upgraded from Rookie to Regular')
            ->and($initialHistory->getChangeDescription())->toBe('Started as Rookie');
    });

    test('scopes filter correctly', function () {
        $rookieTier = WorkerTier::findBySlug('rookie');
        $regularTier = WorkerTier::findBySlug('regular');
        $metrics = ['shifts_completed' => 0, 'rating' => 0, 'hours_worked' => 0, 'months_active' => 0];

        WorkerTierHistory::recordInitial($this->worker, $rookieTier, $metrics);
        WorkerTierHistory::recordUpgrade($this->worker, $rookieTier, $regularTier, $metrics);
        WorkerTierHistory::recordDowngrade($this->worker, $regularTier, $rookieTier, $metrics);

        expect(WorkerTierHistory::upgrades()->count())->toBe(1)
            ->and(WorkerTierHistory::downgrades()->count())->toBe(1)
            ->and(WorkerTierHistory::initial()->count())->toBe(1);
    });
});

describe('User Tier Methods', function () {
    beforeEach(function () {
        $this->worker = User::factory()->create(['user_type' => 'worker']);
        $this->workerProfile = WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
        ]);
    });

    test('getWorkerTierAttribute returns tier for worker', function () {
        $proTier = WorkerTier::findBySlug('pro');
        $this->workerProfile->update(['worker_tier_id' => $proTier->id]);

        expect($this->worker->fresh()->workerTier)->not->toBeNull()
            ->and($this->worker->fresh()->workerTier->slug)->toBe('pro');
    });

    test('getWorkerTierAttribute returns null for non-worker', function () {
        $business = User::factory()->create(['user_type' => 'business']);

        expect($business->workerTier)->toBeNull();
    });

    test('hasPremiumShiftsAccess returns correct boolean', function () {
        $proTier = WorkerTier::findBySlug('pro');
        $eliteTier = WorkerTier::findBySlug('elite');

        $this->workerProfile->update(['worker_tier_id' => $proTier->id]);
        expect($this->worker->fresh()->hasPremiumShiftsAccess())->toBeFalse();

        $this->workerProfile->update(['worker_tier_id' => $eliteTier->id]);
        expect($this->worker->fresh()->hasPremiumShiftsAccess())->toBeTrue();
    });

    test('hasTierInstantPayout returns correct boolean', function () {
        $regularTier = WorkerTier::findBySlug('regular');
        $proTier = WorkerTier::findBySlug('pro');

        $this->workerProfile->update(['worker_tier_id' => $regularTier->id]);
        expect($this->worker->fresh()->hasTierInstantPayout())->toBeFalse();

        $this->workerProfile->update(['worker_tier_id' => $proTier->id]);
        expect($this->worker->fresh()->hasTierInstantPayout())->toBeTrue();
    });

    test('getTierFeeDiscount returns correct discount percentage', function () {
        $legendTier = WorkerTier::findBySlug('legend');
        $this->workerProfile->update(['worker_tier_id' => $legendTier->id]);

        expect((float) $this->worker->fresh()->getTierFeeDiscount())->toBe(12.0);
    });

    test('getTierPriorityHours returns correct hours', function () {
        $eliteTier = WorkerTier::findBySlug('elite');
        $this->workerProfile->update(['worker_tier_id' => $eliteTier->id]);

        expect($this->worker->fresh()->getTierPriorityHours())->toBe(12);
    });
});

describe('ProcessMonthlyTierReview Command', function () {
    test('command runs successfully with dry-run option', function () {
        $this->artisan('workers:process-tier-review', ['--dry-run' => true])
            ->assertSuccessful();
    });

    test('command processes workers and updates tiers', function () {
        // Create workers with different metrics
        $worker1 = User::factory()->create(['user_type' => 'worker']);
        WorkerProfile::factory()->create([
            'user_id' => $worker1->id,
            'lifetime_shifts' => 0,
        ]);

        $this->artisan('workers:process-tier-review')
            ->assertSuccessful();
    });
});
