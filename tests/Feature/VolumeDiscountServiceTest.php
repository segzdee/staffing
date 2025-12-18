<?php

use App\Models\BusinessProfile;
use App\Models\BusinessVolumeTracking;
use App\Models\User;
use App\Models\VolumeDiscountTier;
use App\Services\VolumeDiscountService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed the volume discount tiers
    $this->artisan('db:seed', ['--class' => 'VolumeDiscountTierSeeder']);
});

describe('VolumeDiscountService', function () {
    beforeEach(function () {
        // Create a fresh business user with profile for each test
        $this->business = User::factory()->create(['role' => 'business']);
        $this->businessProfile = BusinessProfile::factory()->create([
            'user_id' => $this->business->id,
            'business_name' => 'Test Business',
        ]);
        $this->service = app(VolumeDiscountService::class);
    });

    test('returns starter tier for new businesses with no shifts', function () {
        $tier = $this->service->getCurrentTier($this->business);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('starter')
            ->and($tier->platform_fee_percent)->toBe('35.00');
    });

    test('calculates fee with discount correctly', function () {
        $result = $this->service->calculateFeeWithDiscount($this->business, 1000.00);

        expect($result)->toBeArray()
            ->and($result['base_amount'])->toBe(1000.00)
            ->and((float) $result['fee_percent'])->toBe(35.00)
            ->and($result['fee_amount'])->toBe(350.00)
            ->and($result['discount_amount'])->toBe(0.0)
            ->and($result['tier_name'])->toBe('Starter');
    });

    test('returns default fee percent for starter tier', function () {
        $feePercent = $this->service->getEffectiveFeePercent($this->business);

        expect($feePercent)->toBe(35.00);
    });

    test('returns growth tier fee for businesses with 15 shifts', function () {
        // Get or create volume tracking and update with 15 shifts
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->business->id);
        $tracking->update(['shifts_posted' => 15]);

        // Recalculate tier
        $tier = $this->service->recalculateTier($this->business);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('growth')
            ->and($tier->platform_fee_percent)->toBe('30.00');

        // Check fee calculation
        $result = $this->service->calculateFeeWithDiscount($this->business, 1000.00);
        expect((float) $result['fee_percent'])->toBe(30.00)
            ->and($result['fee_amount'])->toBe(300.00)
            ->and($result['discount_amount'])->toBe(50.00);
    });

    test('returns scale tier fee for businesses with 75 shifts', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->business->id);
        $tracking->update(['shifts_posted' => 75]);

        $tier = $this->service->recalculateTier($this->business);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('scale')
            ->and($tier->platform_fee_percent)->toBe('25.00');
    });

    test('returns enterprise tier fee for businesses with 250 shifts', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->business->id);
        $tracking->update(['shifts_posted' => 250]);

        $tier = $this->service->recalculateTier($this->business);

        expect($tier)->not->toBeNull()
            ->and($tier->slug)->toBe('enterprise')
            ->and($tier->platform_fee_percent)->toBe('20.00');
    });

    test('custom pricing overrides tier-based pricing', function () {
        // Set custom pricing
        $this->businessProfile->update([
            'custom_pricing' => true,
            'custom_fee_percent' => 15.00,
        ]);

        $feePercent = $this->service->getEffectiveFeePercent($this->business->fresh());

        expect($feePercent)->toBe(15.00);
    });

    test('expired custom pricing falls back to tier pricing', function () {
        // Set expired custom pricing
        $this->businessProfile->update([
            'custom_pricing' => true,
            'custom_fee_percent' => 15.00,
            'custom_pricing_expires_at' => Carbon::now()->subDay(),
        ]);

        $feePercent = $this->service->getEffectiveFeePercent($this->business->fresh());

        expect($feePercent)->toBe(35.00); // Falls back to starter tier
    });

    test('getNextTierProgress returns correct information', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->business->id);
        $tracking->update(['shifts_posted' => 5]);

        $progress = $this->service->getNextTierProgress($this->business);

        expect($progress)->toBeArray()
            ->and($progress['has_next_tier'])->toBeTrue()
            ->and($progress['current_shifts'])->toBe(5)
            ->and($progress['next_tier_name'])->toBe('Growth')
            ->and($progress['shifts_needed'])->toBe(6); // 11 - 5 = 6
    });

    test('getVolumeStats returns comprehensive statistics', function () {
        $stats = $this->service->getVolumeStats($this->business);

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys([
                'current_tier',
                'custom_pricing',
                'current_month',
                'next_tier',
                'lifetime',
                'history',
            ])
            ->and($stats['current_tier']['name'])->toBe('Starter');
    });

    test('applyCustomPricing sets custom pricing correctly', function () {
        $result = $this->service->applyCustomPricing(
            $this->business,
            18.50,
            'Enterprise contract negotiated',
            Carbon::now()->addYear()
        );

        expect($result)->toBeTrue();

        $this->businessProfile->refresh();

        expect($this->businessProfile->custom_pricing)->toBeTrue()
            ->and($this->businessProfile->custom_fee_percent)->toBe('18.50')
            ->and($this->businessProfile->custom_pricing_notes)->toBe('Enterprise contract negotiated');
    });

    test('removeCustomPricing removes custom pricing and recalculates tier', function () {
        $this->businessProfile->update([
            'custom_pricing' => true,
            'custom_fee_percent' => 15.00,
        ]);

        $result = $this->service->removeCustomPricing($this->business);

        expect($result)->toBeTrue();

        $this->businessProfile->refresh();

        expect($this->businessProfile->custom_pricing)->toBeFalse()
            ->and($this->businessProfile->custom_fee_percent)->toBeNull();
    });

    test('getAvailableTiers returns all tiers with qualification status', function () {
        $tiers = $this->service->getAvailableTiers($this->business);

        expect($tiers)->toBeArray()
            ->and($tiers)->toHaveCount(4);

        $starterTier = collect($tiers)->firstWhere('slug', 'starter');
        expect($starterTier['qualifies'])->toBeTrue()
            ->and($starterTier['is_current'])->toBeTrue();
    });
});

describe('VolumeDiscountTier Model', function () {
    test('all seeded tiers exist', function () {
        expect(VolumeDiscountTier::count())->toBe(4);

        expect(VolumeDiscountTier::findBySlug('starter'))->not->toBeNull();
        expect(VolumeDiscountTier::findBySlug('growth'))->not->toBeNull();
        expect(VolumeDiscountTier::findBySlug('scale'))->not->toBeNull();
        expect(VolumeDiscountTier::findBySlug('enterprise'))->not->toBeNull();
    });

    test('getTierForShiftCount returns correct tier', function () {
        expect(VolumeDiscountTier::getTierForShiftCount(5)->slug)->toBe('starter');
        expect(VolumeDiscountTier::getTierForShiftCount(15)->slug)->toBe('growth');
        expect(VolumeDiscountTier::getTierForShiftCount(100)->slug)->toBe('scale');
        expect(VolumeDiscountTier::getTierForShiftCount(500)->slug)->toBe('enterprise');
    });

    test('getDefaultTier returns starter tier', function () {
        $defaultTier = VolumeDiscountTier::getDefaultTier();

        expect($defaultTier)->not->toBeNull()
            ->and($defaultTier->is_default)->toBeTrue()
            ->and($defaultTier->slug)->toBe('starter');
    });

    test('getNextTier returns correct next tier', function () {
        $starter = VolumeDiscountTier::findBySlug('starter');
        $growth = VolumeDiscountTier::findBySlug('growth');
        $enterprise = VolumeDiscountTier::findBySlug('enterprise');

        expect($starter->getNextTier()->slug)->toBe('growth');
        expect($growth->getNextTier()->slug)->toBe('scale');
        expect($enterprise->getNextTier())->toBeNull(); // No next tier
    });

    test('shiftsNeededFrom calculates correctly', function () {
        $growth = VolumeDiscountTier::findBySlug('growth');

        expect($growth->shiftsNeededFrom(5))->toBe(6);  // 11 - 5 = 6
        expect($growth->shiftsNeededFrom(11))->toBe(0); // Already at tier
        expect($growth->shiftsNeededFrom(50))->toBe(0); // Above tier
    });

    test('shift_range accessor returns correct format', function () {
        $starter = VolumeDiscountTier::findBySlug('starter');
        $enterprise = VolumeDiscountTier::findBySlug('enterprise');

        expect($starter->shift_range)->toBe('0-10 shifts/mo');
        expect($enterprise->shift_range)->toBe('201+ shifts/mo');
    });
});

describe('BusinessVolumeTracking Model', function () {
    beforeEach(function () {
        // Create a fresh business user for tracking tests
        $this->trackingBusiness = User::factory()->create(['role' => 'business']);
        BusinessProfile::factory()->create(['user_id' => $this->trackingBusiness->id]);
    });

    test('getOrCreateForMonth creates new tracking record', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);

        expect($tracking)->not->toBeNull()
            ->and($tracking->business_id)->toBe($this->trackingBusiness->id)
            ->and($tracking->shifts_posted)->toBe(0);
    });

    test('getOrCreateForMonth returns existing record', function () {
        $tracking1 = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);
        $tracking1->update(['shifts_posted' => 10]);

        $tracking2 = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);

        expect($tracking2->id)->toBe($tracking1->id)
            ->and($tracking2->shifts_posted)->toBe(10);
    });

    test('incrementShiftsPosted increments count', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);
        $tracking->incrementShiftsPosted(5);

        expect($tracking->fresh()->shifts_posted)->toBe(5);
    });

    test('addSpend updates total spend and average', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);
        $tracking->update(['shifts_posted' => 2]);

        $tracking->addSpend(200.00);

        $tracking->refresh();
        expect($tracking->total_spend)->toBe('200.00')
            ->and($tracking->average_shift_value)->toBe('100.00');
    });

    test('fill_rate accessor calculates correctly', function () {
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($this->trackingBusiness->id);
        $tracking->update([
            'shifts_posted' => 10,
            'shifts_filled' => 8,
        ]);

        expect($tracking->fill_rate)->toBe(80.0);
    });
});
