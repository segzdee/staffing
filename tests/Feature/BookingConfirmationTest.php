<?php

use App\Models\BookingConfirmation;
use App\Models\ConfirmationReminder;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingDeclinedNotification;
use App\Notifications\BookingPendingConfirmationNotification;
use App\Notifications\ConfirmationReminderNotification;
use App\Services\BookingConfirmationService;
use Illuminate\Support\Facades\Notification;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

/**
 * SL-004: Booking Confirmation System Tests
 *
 * Tests the dual-confirmation workflow for shift bookings.
 */
beforeEach(function () {
    $this->initializeMigrations();

    // Create test users
    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->business = User::factory()->create(['user_type' => 'business']);

    // Create a shift for testing
    $this->shift = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
        'shift_date' => now()->addDays(3),
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    $this->service = app(BookingConfirmationService::class);
});

// =========================================
// Model Tests
// =========================================

it('generates unique confirmation codes', function () {
    $code1 = BookingConfirmation::generateConfirmationCode();
    $code2 = BookingConfirmation::generateConfirmationCode();

    expect($code1)->toHaveLength(8)
        ->and($code2)->toHaveLength(8)
        ->and($code1)->not->toBe($code2);
});

it('creates booking confirmation with correct attributes', function () {
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($confirmation->exists)->toBeTrue()
        ->and($confirmation->shift_id)->toBe($this->shift->id)
        ->and($confirmation->worker_id)->toBe($this->worker->id)
        ->and($confirmation->business_id)->toBe($this->business->id)
        ->and($confirmation->status)->toBe(BookingConfirmation::STATUS_PENDING)
        ->and($confirmation->worker_confirmed)->toBeFalse()
        ->and($confirmation->business_confirmed)->toBeFalse();
});

it('has correct relationships', function () {
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($confirmation->shift->id)->toBe($this->shift->id)
        ->and($confirmation->worker->id)->toBe($this->worker->id)
        ->and($confirmation->business->id)->toBe($this->business->id);
});

// =========================================
// Status Check Tests
// =========================================

it('correctly identifies pending status', function () {
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($confirmation->isPending())->toBeTrue()
        ->and($confirmation->isActionable())->toBeTrue()
        ->and($confirmation->isFullyConfirmed())->toBeFalse()
        ->and($confirmation->isDeclined())->toBeFalse()
        ->and($confirmation->isExpired())->toBeFalse();
});

it('correctly identifies expired status', function () {
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->subHours(1), // Already expired
    ]);

    expect($confirmation->isExpired())->toBeTrue()
        ->and($confirmation->isActionable())->toBeFalse();
});

it('correctly identifies fully confirmed status', function () {
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_FULLY_CONFIRMED,
        'worker_confirmed' => true,
        'worker_confirmed_at' => now(),
        'business_confirmed' => true,
        'business_confirmed_at' => now(),
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($confirmation->isFullyConfirmed())->toBeTrue()
        ->and($confirmation->isActionable())->toBeFalse();
});

// =========================================
// Service Tests
// =========================================

it('creates confirmation through service', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    expect($confirmation)->toBeInstanceOf(BookingConfirmation::class)
        ->and($confirmation->shift_id)->toBe($this->shift->id)
        ->and($confirmation->worker_id)->toBe($this->worker->id)
        ->and($confirmation->business_id)->toBe($this->business->id)
        ->and($confirmation->status)->toBe(BookingConfirmation::STATUS_PENDING);

    // Verify notifications were sent
    Notification::assertSentTo($this->worker, BookingPendingConfirmationNotification::class);
    Notification::assertSentTo($this->business, BookingPendingConfirmationNotification::class);
});

it('prevents duplicate confirmations for same worker and shift', function () {
    Notification::fake();

    $this->service->createConfirmation($this->shift, $this->worker);

    // Try to create another confirmation - should throw exception
    $this->service->createConfirmation($this->shift, $this->worker);
})->throws(Exception::class, 'A confirmation request already exists');

it('allows worker to confirm booking', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $updated = $this->service->workerConfirm($confirmation, $this->worker, 'Looking forward to it!');

    expect($updated->worker_confirmed)->toBeTrue()
        ->and($updated->worker_confirmed_at)->not->toBeNull()
        ->and($updated->worker_notes)->toBe('Looking forward to it!')
        ->and($updated->status)->toBe(BookingConfirmation::STATUS_WORKER_CONFIRMED);
});

it('allows business to confirm booking', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $updated = $this->service->businessConfirm($confirmation, $this->business, 'Welcome aboard!');

    expect($updated->business_confirmed)->toBeTrue()
        ->and($updated->business_confirmed_at)->not->toBeNull()
        ->and($updated->business_notes)->toBe('Welcome aboard!')
        ->and($updated->status)->toBe(BookingConfirmation::STATUS_BUSINESS_CONFIRMED);
});

it('fully confirms when both parties confirm', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    // Worker confirms first
    $this->service->workerConfirm($confirmation, $this->worker);

    // Then business confirms
    $updated = $this->service->businessConfirm($confirmation->fresh(), $this->business);

    expect($updated->status)->toBe(BookingConfirmation::STATUS_FULLY_CONFIRMED)
        ->and($updated->worker_confirmed)->toBeTrue()
        ->and($updated->business_confirmed)->toBeTrue();

    Notification::assertSentTo($this->worker, BookingConfirmedNotification::class);
    Notification::assertSentTo($this->business, BookingConfirmedNotification::class);
});

it('allows worker to decline booking', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $declined = $this->service->declineBooking($confirmation, $this->worker, 'Schedule conflict');

    expect($declined->status)->toBe(BookingConfirmation::STATUS_DECLINED)
        ->and($declined->declined_by)->toBe($this->worker->id)
        ->and($declined->decline_reason)->toBe('Schedule conflict')
        ->and($declined->declined_at)->not->toBeNull();

    Notification::assertSentTo($this->business, BookingDeclinedNotification::class);
});

it('allows business to decline booking', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $declined = $this->service->declineBooking($confirmation, $this->business, 'Position filled');

    expect($declined->status)->toBe(BookingConfirmation::STATUS_DECLINED)
        ->and($declined->declined_by)->toBe($this->business->id)
        ->and($declined->decline_reason)->toBe('Position filled');

    Notification::assertSentTo($this->worker, BookingDeclinedNotification::class);
});

it('prevents confirming expired booking', function () {
    Notification::fake();

    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->subHours(1), // Already expired
    ]);

    $this->service->workerConfirm($confirmation, $this->worker);
})->throws(Exception::class, 'no longer actionable');

it('prevents unauthorized user from confirming', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);
    $otherWorker = User::factory()->create(['user_type' => 'worker']);

    $this->service->workerConfirm($confirmation, $otherWorker);
})->throws(Exception::class, 'not authorized');

// =========================================
// Expiration Tests
// =========================================

it('expires stale confirmations', function () {
    // Create some confirmations - some expired, some not
    $expiredConfirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->subHours(1),
    ]);

    $worker2 = User::factory()->create(['user_type' => 'worker']);
    $activeConfirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $worker2->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(12),
    ]);

    $expiredCount = $this->service->expireStaleConfirmations();

    expect($expiredCount)->toBe(1)
        ->and($expiredConfirmation->fresh()->status)->toBe(BookingConfirmation::STATUS_EXPIRED)
        ->and($activeConfirmation->fresh()->status)->toBe(BookingConfirmation::STATUS_PENDING);
});

// =========================================
// Reminder Tests
// =========================================

it('sends reminders for confirmations needing them', function () {
    Notification::fake();

    // Create confirmation expiring in 4 hours (should need reminder)
    $confirmation = BookingConfirmation::create([
        'shift_id' => $this->shift->id,
        'worker_id' => $this->worker->id,
        'business_id' => $this->business->id,
        'status' => BookingConfirmation::STATUS_PENDING,
        'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        'expires_at' => now()->addHours(4),
        'reminder_sent_at' => null,
    ]);

    $result = $this->service->sendReminders();

    expect($result['worker'])->toBeGreaterThan(0)
        ->and($result['business'])->toBeGreaterThan(0);

    Notification::assertSentTo($this->worker, ConfirmationReminderNotification::class);
    Notification::assertSentTo($this->business, ConfirmationReminderNotification::class);
});

// =========================================
// Statistics Tests
// =========================================

it('returns correct confirmation statistics', function () {
    Notification::fake();

    // Create various confirmations
    $this->service->createConfirmation($this->shift, $this->worker);

    $worker2 = User::factory()->create(['user_type' => 'worker']);
    $shift2 = Shift::factory()->create([
        'business_id' => $this->business->id,
        'status' => 'open',
    ]);
    $confirmation2 = $this->service->createConfirmation($shift2, $worker2);
    $this->service->workerConfirm($confirmation2, $worker2);
    $this->service->businessConfirm($confirmation2->fresh(), $this->business);

    $stats = $this->service->getConfirmationStats($this->business);

    expect($stats['total'])->toBeGreaterThanOrEqual(2)
        ->and($stats['fully_confirmed'])->toBeGreaterThanOrEqual(1)
        ->and(isset($stats['pending']))->toBeTrue()
        ->and(isset($stats['awaiting_my_confirmation']))->toBeTrue();
});

// =========================================
// Code Lookup Tests
// =========================================

it('finds confirmation by code', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);
    $code = $confirmation->confirmation_code;

    $found = $this->service->getConfirmationByCode($code);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($confirmation->id);
});

it('returns null for invalid code', function () {
    $found = $this->service->getConfirmationByCode('INVALID1');

    expect($found)->toBeNull();
});

it('regenerates confirmation code', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);
    $originalCode = $confirmation->confirmation_code;

    $updated = $this->service->regenerateConfirmationCode($confirmation);

    expect($updated->confirmation_code)->not->toBe($originalCode)
        ->and($updated->confirmation_code)->toHaveLength(8);
});

// =========================================
// Bulk Operations Tests
// =========================================

it('bulk confirms multiple bookings', function () {
    Notification::fake();

    // Create multiple confirmations
    $confirmations = [];
    for ($i = 0; $i < 3; $i++) {
        $worker = User::factory()->create(['user_type' => 'worker']);
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'open',
        ]);
        $confirmation = $this->service->createConfirmation($shift, $worker);
        // Worker confirms
        $this->service->workerConfirm($confirmation, $worker);
        $confirmations[] = $confirmation->fresh();
    }

    $ids = collect($confirmations)->pluck('id')->toArray();

    $result = $this->service->bulkConfirm($ids, $this->business, 'Welcome all!');

    expect(count($result['confirmed']))->toBe(3)
        ->and(count($result['failed']))->toBe(0);
});

// =========================================
// Scope Tests
// =========================================

it('filters by worker correctly', function () {
    Notification::fake();

    $this->service->createConfirmation($this->shift, $this->worker);

    $results = BookingConfirmation::forWorker($this->worker->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->worker_id)->toBe($this->worker->id);
});

it('filters by business correctly', function () {
    Notification::fake();

    $this->service->createConfirmation($this->shift, $this->worker);

    $results = BookingConfirmation::forBusiness($this->business->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->business_id)->toBe($this->business->id);
});

// =========================================
// Reminder Model Tests
// =========================================

it('creates confirmation reminders', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $reminder = ConfirmationReminder::createForConfirmation(
        $confirmation,
        ConfirmationReminder::TYPE_EMAIL,
        ConfirmationReminder::RECIPIENT_WORKER
    );

    expect($reminder->exists)->toBeTrue()
        ->and($reminder->booking_confirmation_id)->toBe($confirmation->id)
        ->and($reminder->type)->toBe(ConfirmationReminder::TYPE_EMAIL)
        ->and($reminder->recipient_type)->toBe(ConfirmationReminder::RECIPIENT_WORKER)
        ->and($reminder->sent_at)->not->toBeNull();
});

it('marks reminder as delivered', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $reminder = ConfirmationReminder::createForConfirmation(
        $confirmation,
        ConfirmationReminder::TYPE_EMAIL,
        ConfirmationReminder::RECIPIENT_WORKER
    );

    $reminder->markAsDelivered();

    expect($reminder->delivered)->toBeTrue()
        ->and($reminder->delivered_at)->not->toBeNull();
});

// =========================================
// QR Code Tests
// =========================================

it('generates QR code URL correctly', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $qrUrl = $confirmation->getQrCodeUrl();

    expect($qrUrl)->toContain('/confirm/')
        ->and($qrUrl)->toContain($confirmation->confirmation_code);
});

it('generates QR code data correctly', function () {
    Notification::fake();

    $confirmation = $this->service->createConfirmation($this->shift, $this->worker);

    $qrData = $confirmation->getQrCodeData();

    expect($qrData['code'])->toBe($confirmation->confirmation_code)
        ->and($qrData['shift_id'])->toBe($confirmation->shift_id)
        ->and($qrData['worker_id'])->toBe($confirmation->worker_id)
        ->and(isset($qrData['url']))->toBeTrue()
        ->and(isset($qrData['expires_at']))->toBeTrue();
});
