<?php

use App\Support\Money;
use Money\Money as BaseMoney;

test('can create money from decimal', function () {
    $money = Money::fromDecimal(125.50);

    expect($money)->toBeInstanceOf(BaseMoney::class)
        ->and(Money::toCents($money))->toBe(12550)
        ->and(Money::toDecimal($money))->toBe(125.5);
});

test('can create money from cents', function () {
    $money = Money::fromCents(12550);

    expect($money)->toBeInstanceOf(BaseMoney::class)
        ->and(Money::toCents($money))->toBe(12550)
        ->and(Money::toDecimal($money))->toBe(125.5);
});

test('can format money for display', function () {
    $money = Money::fromDecimal(125.50);
    $formatted = Money::format($money);

    expect($formatted)->toBe('$125.50');
});

test('calculates platform fee correctly', function () {
    $amount = Money::fromDecimal(100.00);
    $platformFee = Money::calculatePlatformFee($amount);

    expect(Money::toDecimal($platformFee))->toBe(10.0)
        ->and(Money::format($platformFee))->toBe('$10.00');
});

test('calculates agency commission correctly', function () {
    $amount = Money::fromDecimal(100.00);
    $agencyFee = Money::calculateAgencyCommission($amount, 0.15);

    expect(Money::toDecimal($agencyFee))->toBe(15.0)
        ->and(Money::format($agencyFee))->toBe('$15.00');
});

test('calculates worker payout without agency', function () {
    $grossAmount = Money::fromDecimal(100.00);
    $payout = Money::calculateWorkerPayout($grossAmount, false);

    expect($payout)->toHaveKey('gross')
        ->and($payout)->toHaveKey('platform_fee')
        ->and($payout)->toHaveKey('agency_fee')
        ->and($payout)->toHaveKey('worker_payout')
        ->and(Money::toDecimal($payout['gross']))->toBe(100.0)
        ->and(Money::toDecimal($payout['platform_fee']))->toBe(10.0)
        ->and(Money::toDecimal($payout['agency_fee']))->toBe(0.0)
        ->and(Money::toDecimal($payout['worker_payout']))->toBe(90.0);
});

test('calculates worker payout with agency', function () {
    $grossAmount = Money::fromDecimal(100.00);
    $payout = Money::calculateWorkerPayout($grossAmount, true, 0.15);

    expect($payout)->toHaveKey('gross')
        ->and($payout)->toHaveKey('platform_fee')
        ->and($payout)->toHaveKey('agency_fee')
        ->and($payout)->toHaveKey('worker_payout')
        ->and(Money::toDecimal($payout['gross']))->toBe(100.0)
        ->and(Money::toDecimal($payout['platform_fee']))->toBe(10.0)
        ->and(Money::toDecimal($payout['agency_fee']))->toBe(13.5)
        ->and(Money::toDecimal($payout['worker_payout']))->toBe(76.5);
});

test('money calculations are precise', function () {
    // Test that we don't have floating point precision issues
    $amount1 = Money::fromDecimal(0.1);
    $amount2 = Money::fromDecimal(0.2);

    $sum = $amount1->add($amount2);

    expect(Money::toDecimal($sum))->toBe(0.3);
});

test('can multiply money amounts', function () {
    $amount = Money::fromDecimal(10.00);
    $multiplied = $amount->multiply('1.5');

    expect(Money::toDecimal($multiplied))->toBe(15.0);
});

test('can subtract money amounts', function () {
    $amount1 = Money::fromDecimal(100.00);
    $amount2 = Money::fromDecimal(25.50);

    $result = $amount1->subtract($amount2);

    expect(Money::toDecimal($result))->toBe(74.5);
});
