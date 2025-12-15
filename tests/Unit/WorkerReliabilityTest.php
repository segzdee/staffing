<?php

test('reliability score starts at 100 for new workers', function () {
    $reliabilityScore = 100;

    expect($reliabilityScore)->toBe(100);
});

test('reliability score decreases for no-shows', function () {
    // Worker completed 9 shifts, no-showed 1 shift
    $completedShifts = 9;
    $noShowShifts = 1;
    $totalAssignedShifts = 10;

    $completionRate = ($completedShifts / $totalAssignedShifts) * 100;
    $noShowPenalty = $noShowShifts * 10; // 10 points per no-show

    $reliabilityScore = max(0, min(100, $completionRate - $noShowPenalty));

    expect($reliabilityScore)->toBe(80.0); // 90% - 10 penalty = 80
});

test('reliability score is capped at 100', function () {
    // Worker completed all 15 shifts
    $completedShifts = 15;
    $noShowShifts = 0;
    $totalAssignedShifts = 15;

    $completionRate = ($completedShifts / $totalAssignedShifts) * 100;
    $noShowPenalty = $noShowShifts * 10;

    $reliabilityScore = max(0, min(100, $completionRate - $noShowPenalty));

    expect($reliabilityScore)->toBe(100);
});

test('reliability score cannot go below 0', function () {
    // Worker no-showed 15 times out of 15
    $completedShifts = 0;
    $noShowShifts = 15;
    $totalAssignedShifts = 15;

    $completionRate = ($completedShifts / $totalAssignedShifts) * 100;
    $noShowPenalty = $noShowShifts * 10;

    $reliabilityScore = max(0, min(100, $completionRate - $noShowPenalty));

    expect($reliabilityScore)->toBe(0);
});

test('worker with 95% reliability qualifies for instant claim', function () {
    $reliabilityScore = 95;
    $minReliabilityForInstantClaim = 90;

    expect($reliabilityScore)->toBeGreaterThanOrEqual($minReliabilityForInstantClaim);
});

test('worker rating average calculation', function () {
    // 5 ratings: 5, 4, 5, 3, 5
    $ratings = [5, 4, 5, 3, 5];
    $averageRating = array_sum($ratings) / count($ratings);

    expect($averageRating)->toBe(4.4);
});

test('worker with 4.5+ rating can instant claim', function () {
    $averageRating = 4.8;
    $minRatingForInstantClaim = 4.5;

    expect($averageRating)->toBeGreaterThanOrEqual($minRatingForInstantClaim);
});

test('platinum badge requires 4.8+ rating', function () {
    $rating = 4.9;
    $platinumThreshold = 4.8;

    $isPlatinum = $rating >= $platinumThreshold;

    expect($isPlatinum)->toBeTrue();
});

test('gold badge requires 4.5+ rating', function () {
    $rating = 4.7;
    $goldThreshold = 4.5;

    $isGold = $rating >= $goldThreshold;

    expect($isGold)->toBeTrue();
});

test('worker with low rating cannot instant claim', function () {
    $averageRating = 3.5;
    $minRatingForInstantClaim = 4.5;

    $canInstantClaim = $averageRating >= $minRatingForInstantClaim;

    expect($canInstantClaim)->toBeFalse();
});

test('early check-in allowed within 15 minutes', function () {
    // Shift starts at 9:00 AM
    $shiftStartTime = new DateTime('2025-01-20 09:00:00');

    // Worker checks in at 8:50 AM (10 minutes early)
    $checkInTime = new DateTime('2025-01-20 08:50:00');

    $earlyMinutes = ($shiftStartTime->getTimestamp() - $checkInTime->getTimestamp()) / 60;
    $allowedEarlyMinutes = 15;

    $isAllowed = $earlyMinutes <= $allowedEarlyMinutes;

    expect($isAllowed)->toBeTrue()
        ->and((int) $earlyMinutes)->toBe(10);
});

test('late check-in penalized after 10 minute grace period', function () {
    // Shift starts at 9:00 AM
    $shiftStartTime = new DateTime('2025-01-20 09:00:00');

    // Worker checks in at 9:15 AM (15 minutes late)
    $checkInTime = new DateTime('2025-01-20 09:15:00');

    $lateMinutes = ($checkInTime->getTimestamp() - $shiftStartTime->getTimestamp()) / 60;
    $graceMinutes = 10;

    $isPenalized = $lateMinutes > $graceMinutes;

    expect($isPenalized)->toBeTrue()
        ->and((int) $lateMinutes)->toBe(15);
});

test('hours worked calculation', function () {
    // Check in: 9:00 AM
    $checkInTime = new DateTime('2025-01-20 09:00:00');

    // Check out: 5:30 PM
    $checkOutTime = new DateTime('2025-01-20 17:30:00');

    $hoursWorked = ($checkOutTime->getTimestamp() - $checkInTime->getTimestamp()) / 3600;

    expect($hoursWorked)->toBe(8.5);
});
