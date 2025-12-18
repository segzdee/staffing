<?php

use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\WorkerSuspension;
use App\Services\SuspensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SuspensionService::class);

    // Create a worker
    $this->worker = User::factory()->create([
        'user_type' => 'worker',
        'strike_count' => 0,
        'is_suspended' => false,
        'status' => 'active',
    ]);

    // Create an admin
    $this->admin = User::factory()->create([
        'user_type' => 'admin',
        'role' => 'admin',
    ]);
});

describe('issueSuspension', function () {
    it('creates a suspension record', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Worker did not show up for scheduled shift on December 15th.',
        ], $this->admin);

        expect($suspension)->toBeInstanceOf(WorkerSuspension::class)
            ->and($suspension->user_id)->toBe($this->worker->id)
            ->and($suspension->type)->toBe('temporary')
            ->and($suspension->reason_category)->toBe('no_show')
            ->and($suspension->status)->toBe('active')
            ->and($suspension->issued_by)->toBe($this->admin->id);
    });

    it('updates worker status to suspended', function () {
        $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Worker no-showed shift.',
        ], $this->admin);

        $this->worker->refresh();

        expect($this->worker->is_suspended)->toBeTrue()
            ->and($this->worker->status)->toBe('suspended')
            ->and($this->worker->strike_count)->toBe(1);
    });

    it('calculates duration based on strike count', function () {
        // First offense - should be 24 hours for no_show
        $suspension1 = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'First no-show.',
        ], $this->admin);

        expect($suspension1->getDurationHours())->toBe(24);

        // Lift first suspension
        $this->service->liftSuspension($suspension1);

        // Second offense - should be 72 hours for no_show
        $suspension2 = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Second no-show.',
        ], $this->admin);

        expect($suspension2->getDurationHours())->toBe(72);
    });

    it('allows custom duration override', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Custom duration test.',
            'duration_hours' => 48,
        ], $this->admin);

        expect($suspension->getDurationHours())->toBe(48);
    });

    it('creates indefinite suspension when duration is null', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'indefinite',
            'reason_category' => 'fraud',
            'reason_details' => 'Suspected fraudulent activity.',
        ], $this->admin);

        expect($suspension->ends_at)->toBeNull()
            ->and($suspension->type)->toBe('indefinite');
    });
});

describe('liftSuspension', function () {
    it('marks suspension as completed', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'late_cancellation',
            'reason_details' => 'Cancelled shift with less than 24 hours notice.',
        ], $this->admin);

        $lifted = $this->service->liftSuspension($suspension, 'Early lift approved');

        expect($lifted->status)->toBe('completed');
    });

    it('reactivates worker account', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'late_cancellation',
            'reason_details' => 'Cancelled shift late.',
        ], $this->admin);

        $this->service->liftSuspension($suspension);

        $this->worker->refresh();

        expect($this->worker->is_suspended)->toBeFalse()
            ->and($this->worker->status)->toBe('active');
    });
});

describe('submitAppeal', function () {
    it('creates an appeal for a suspension', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Worker was late.',
        ], $this->admin);

        $appeal = $this->service->submitAppeal($suspension, $this->worker, [
            'appeal_reason' => 'I had a medical emergency and could not make it. I have documentation to prove this.',
        ]);

        expect($appeal)->toBeInstanceOf(SuspensionAppeal::class)
            ->and($appeal->status)->toBe('pending')
            ->and($appeal->user_id)->toBe($this->worker->id)
            ->and($appeal->suspension_id)->toBe($suspension->id);
    });

    it('updates suspension status to appealed', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'misconduct',
            'reason_details' => 'Inappropriate behavior reported.',
        ], $this->admin);

        $this->service->submitAppeal($suspension, $this->worker, [
            'appeal_reason' => 'I believe this report was made in error. I have witnesses who can confirm my conduct was professional.',
        ]);

        $suspension->refresh();

        expect($suspension->status)->toBe('appealed');
    });

    it('throws exception if worker does not own suspension', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'No show.',
        ], $this->admin);

        $anotherWorker = User::factory()->create(['user_type' => 'worker']);

        expect(fn () => $this->service->submitAppeal($suspension, $anotherWorker, [
            'appeal_reason' => 'Test appeal.',
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('prevents appealing permanent suspensions', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'permanent',
            'reason_category' => 'fraud',
            'reason_details' => 'Fraud confirmed.',
        ], $this->admin);

        expect(fn () => $this->service->submitAppeal($suspension, $this->worker, [
            'appeal_reason' => 'I appeal this.',
        ]))->toThrow(InvalidArgumentException::class);
    });
});

describe('reviewAppeal', function () {
    it('approves appeal and overturns suspension', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'No show for shift.',
        ], $this->admin);

        $appeal = $this->service->submitAppeal($suspension, $this->worker, [
            'appeal_reason' => 'Medical emergency, documentation attached.',
        ]);

        $reviewed = $this->service->reviewAppeal(
            $appeal,
            'approved',
            'Medical documentation verified. Suspension overturned.',
            $this->admin
        );

        expect($reviewed->status)->toBe('approved')
            ->and($reviewed->review_notes)->toContain('Medical documentation verified');

        $suspension->refresh();
        expect($suspension->status)->toBe('overturned');

        $this->worker->refresh();
        expect($this->worker->is_suspended)->toBeFalse();
    });

    it('denies appeal and maintains suspension', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'misconduct',
            'reason_details' => 'Misconduct reported.',
        ], $this->admin);

        $appeal = $this->service->submitAppeal($suspension, $this->worker, [
            'appeal_reason' => 'I dispute this claim.',
        ]);

        $reviewed = $this->service->reviewAppeal(
            $appeal,
            'denied',
            'Multiple witnesses confirmed the incident.',
            $this->admin
        );

        expect($reviewed->status)->toBe('denied');

        $suspension->refresh();
        expect($suspension->status)->toBe('active');

        $this->worker->refresh();
        expect($this->worker->is_suspended)->toBeTrue();
    });
});

describe('getActiveSuspension', function () {
    it('returns current active suspension', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Test suspension.',
        ], $this->admin);

        $active = $this->service->getActiveSuspension($this->worker);

        expect($active)->not->toBeNull()
            ->and($active->id)->toBe($suspension->id);
    });

    it('returns null when no active suspension', function () {
        $active = $this->service->getActiveSuspension($this->worker);

        expect($active)->toBeNull();
    });

    it('returns null for completed suspensions', function () {
        $suspension = $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'late_cancellation',
            'reason_details' => 'Test.',
        ], $this->admin);

        $this->service->liftSuspension($suspension);

        $active = $this->service->getActiveSuspension($this->worker);

        expect($active)->toBeNull();
    });
});

describe('getSuspensionHistory', function () {
    it('returns all suspensions for a worker', function () {
        // Create multiple suspensions
        for ($i = 0; $i < 3; $i++) {
            $suspension = $this->service->issueSuspension($this->worker, [
                'type' => 'temporary',
                'reason_category' => 'no_show',
                'reason_details' => "Suspension {$i}",
            ], $this->admin);
            $this->service->liftSuspension($suspension);
        }

        $history = $this->service->getSuspensionHistory($this->worker);

        expect($history)->toHaveCount(3);
    });

    it('respects limit parameter', function () {
        for ($i = 0; $i < 5; $i++) {
            $suspension = $this->service->issueSuspension($this->worker, [
                'type' => 'temporary',
                'reason_category' => 'late_cancellation',
                'reason_details' => "Suspension {$i}",
            ], $this->admin);
            $this->service->liftSuspension($suspension);
        }

        $history = $this->service->getSuspensionHistory($this->worker, 3);

        expect($history)->toHaveCount(3);
    });
});

describe('calculateStrikeExpiry', function () {
    it('returns null when no strikes', function () {
        $expiry = $this->service->calculateStrikeExpiry($this->worker);

        expect($expiry)->toBeNull();
    });

    it('calculates expiry based on config', function () {
        $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Test.',
        ], $this->admin);

        $this->worker->refresh();

        $expiry = $this->service->calculateStrikeExpiry($this->worker);

        expect($expiry)->not->toBeNull();

        $expectedExpiry = $this->worker->last_strike_at->copy()->addMonths(
            config('suspensions.strike_expiry_months', 12)
        );

        expect($expiry->format('Y-m-d'))->toBe($expectedExpiry->format('Y-m-d'));
    });
});

describe('resetStrikes', function () {
    it('resets strike count to zero', function () {
        // Issue multiple suspensions to build up strikes
        for ($i = 0; $i < 3; $i++) {
            $suspension = $this->service->issueSuspension($this->worker, [
                'type' => 'temporary',
                'reason_category' => 'no_show',
                'reason_details' => "Strike {$i}",
            ], $this->admin);
            $this->service->liftSuspension($suspension);
        }

        $this->worker->refresh();
        expect($this->worker->strike_count)->toBe(3);

        $this->service->resetStrikes($this->worker, 'Good behavior for 6 months', $this->admin);

        $this->worker->refresh();
        expect($this->worker->strike_count)->toBe(0)
            ->and($this->worker->last_strike_at)->toBeNull();
    });
});

describe('canWorkerBook', function () {
    it('returns true when not suspended', function () {
        expect($this->service->canWorkerBook($this->worker))->toBeTrue();
    });

    it('returns false when suspended with booking restriction', function () {
        $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Test.',
            'affects_booking' => true,
        ], $this->admin);

        expect($this->service->canWorkerBook($this->worker))->toBeFalse();
    });

    it('returns true for warning without booking restriction', function () {
        $this->service->issueSuspension($this->worker, [
            'type' => 'warning',
            'reason_category' => 'other',
            'reason_details' => 'Verbal warning.',
            'affects_booking' => false,
        ], $this->admin);

        expect($this->service->canWorkerBook($this->worker))->toBeTrue();
    });
});

describe('getAnalytics', function () {
    it('returns suspension analytics', function () {
        // Create some suspensions
        $this->service->issueSuspension($this->worker, [
            'type' => 'temporary',
            'reason_category' => 'no_show',
            'reason_details' => 'Test 1.',
        ], $this->admin);

        $worker2 = User::factory()->create(['user_type' => 'worker']);
        $this->service->issueSuspension($worker2, [
            'type' => 'indefinite',
            'reason_category' => 'misconduct',
            'reason_details' => 'Test 2.',
        ], $this->admin);

        $analytics = $this->service->getAnalytics();

        expect($analytics)->toBeArray()
            ->and($analytics['total_active'])->toBe(2)
            ->and($analytics)->toHaveKey('by_category')
            ->and($analytics)->toHaveKey('by_type')
            ->and($analytics)->toHaveKey('pending_appeals');
    });
});
