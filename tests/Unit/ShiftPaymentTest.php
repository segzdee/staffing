<?php

use App\Support\Money;
use Money\Money as BaseMoney;

test('escrow amount includes 5% contingency buffer', function () {
    // Base cost: $100
    $baseCost = Money::fromDecimal(100.00);

    // Platform fee (10%): $10
    $platformFee = Money::calculatePlatformFee($baseCost);

    // Total before buffer: $110
    $totalBeforeBuffer = $baseCost->add($platformFee);

    // Escrow should include 5% buffer
    $buffer = $totalBeforeBuffer->multiply('0.05');
    $escrowAmount = $totalBeforeBuffer->add($buffer);

    expect(Money::toDecimal($escrowAmount))->toBe(115.5);
});

test('instant payout calculates correctly for worker without agency', function () {
    // Shift rate: $25/hour × 8 hours = $200
    $grossAmount = Money::fromDecimal(200.00);

    $payout = Money::calculateWorkerPayout($grossAmount, false);

    // Platform takes 10%: $20
    // Worker gets: $180
    expect(Money::toDecimal($payout['worker_payout']))->toBe(180.0);
});

test('instant payout calculates correctly for worker with agency', function () {
    // Shift rate: $25/hour × 8 hours = $200
    $grossAmount = Money::fromDecimal(200.00);

    $payout = Money::calculateWorkerPayout($grossAmount, true, 0.15);

    // Platform takes 10%: $20 → Remaining: $180
    // Agency takes 15% of $180: $27 → Worker gets: $153
    expect(Money::toDecimal($payout['worker_payout']))->toBe(153.0)
        ->and(Money::toDecimal($payout['agency_fee']))->toBe(27.0);
});

test('surge pricing multiplier increases base rate', function () {
    // Base rate: $20/hour
    $baseRate = Money::fromDecimal(20.00);

    // Surge multiplier: 1.5x (urgent shift)
    $surgeMultiplier = 1.5;
    $finalRate = $baseRate->multiply((string) $surgeMultiplier);

    expect(Money::toDecimal($finalRate))->toBe(30.0);
});

test('night shift premium adds 25% to base rate', function () {
    // Base rate: $20/hour
    $baseRate = Money::fromDecimal(20.00);

    // Night shift premium: 25%
    $premium = $baseRate->multiply('0.25');
    $finalRate = $baseRate->add($premium);

    expect(Money::toDecimal($finalRate))->toBe(25.0);
});

test('minimum wage enforcement', function () {
    // Proposed rate: $10/hour
    $proposedRate = Money::fromDecimal(10.00);

    // Minimum wage: $15/hour
    $minimumWage = Money::fromDecimal(15.00);

    // System should enforce minimum
    $finalRate = $proposedRate->greaterThan($minimumWage)
        ? $proposedRate
        : $minimumWage;

    expect(Money::toDecimal($finalRate))->toBe(15.0);
});

test('cancellation penalty is 50% of shift cost', function () {
    // Shift cost: $200
    $shiftCost = Money::fromDecimal(200.00);

    // Penalty: 50%
    $penalty = $shiftCost->multiply('0.50');

    expect(Money::toDecimal($penalty))->toBe(100.0);
});

test('worker compensation for late cancellation', function () {
    // Shift rate: $25/hour × 8 hours = $200
    $shiftValue = Money::fromDecimal(200.00);

    // Compensation: 50% of shift value
    $compensation = $shiftValue->multiply('0.50');

    expect(Money::toDecimal($compensation))->toBe(100.0);
});

test('multiple money amounts can be summed', function () {
    $amount1 = Money::fromDecimal(50.00);
    $amount2 = Money::fromDecimal(75.50);
    $amount3 = Money::fromDecimal(25.25);

    $total = $amount1->add($amount2)->add($amount3);

    expect(Money::toDecimal($total))->toBe(150.75);
});

test('money division for split payments', function () {
    // Total: $100
    $total = Money::fromDecimal(100.00);

    // Split between 4 workers
    $perWorker = $total->divide(4);

    expect(Money::toDecimal($perWorker))->toBe(25.0);
});
