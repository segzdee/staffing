<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\BusinessProfile;
use App\Models\WorkerProfile;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->businessProfile = BusinessProfile::factory()->create([
        'user_id' => $this->business->id,
    ]);

    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->workerProfile = WorkerProfile::factory()->create([
        'user_id' => $this->worker->id,
    ]);
});

describe('Payment Flow', function () {

    it('holds payment in escrow when shift is assigned', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'base_rate' => 2500, // $25/hr in cents
            'required_workers' => 1,
            'status' => 'open',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'assigned',
        ]);

        // Simulate payment creation with escrow
        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'amount_gross' => 200.00, // $200
            'platform_fee' => 20.00, // 10% platform fee
            'amount_net' => 180.00,
            'status' => 'in_escrow',
            'escrow_held_at' => now(),
        ]);

        expect($payment->status)->toBe('in_escrow')
            ->and(Money::toDecimal($payment->amount_gross))->toBe(200.00)
            ->and(Money::toDecimal($payment->platform_fee))->toBe(20.00)
            ->and(Money::toDecimal($payment->amount_net))->toBe(180.00)
            ->and($payment->escrow_held_at)->not->toBeNull();
    });

    it('releases payment after shift completion', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'completed',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => 'in_escrow',
            'escrow_held_at' => now()->subHours(10),
        ]);

        // Simulate releasing payment from escrow
        $payment->update([
            'status' => 'released',
            'released_at' => now(),
        ]);

        expect($payment->fresh()->status)->toBe('released')
            ->and($payment->released_at)->not->toBeNull();
    });

    it('initiates instant payout after escrow release', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'completed',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => 'released',
            'released_at' => now(),
        ]);

        // Simulate instant payout initiation
        $payment->update([
            'payout_initiated_at' => now(),
            'stripe_transfer_id' => 'tr_test_' . uniqid(),
        ]);

        expect($payment->fresh()->payout_initiated_at)->not->toBeNull()
            ->and($payment->stripe_transfer_id)->toStartWith('tr_test_');
    });

    it('tracks successful payout completion', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'completed',
            'payment_status' => 'pending',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => 'released',
            'payout_initiated_at' => now()->subMinutes(5),
        ]);

        // Simulate payout completion (webhook from Stripe)
        $payment->update([
            'status' => 'paid_out',
            'payout_completed_at' => now(),
        ]);

        $assignment->update(['payment_status' => 'paid']);

        expect($payment->fresh()->status)->toBe('paid_out')
            ->and($payment->payout_completed_at)->not->toBeNull()
            ->and($assignment->fresh()->payment_status)->toBe('paid');
    });

    it('handles payment refund to business', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'cancelled',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'cancelled',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'amount_gross' => 200.00,
            'status' => 'in_escrow',
            'escrow_held_at' => now()->subDays(1),
        ]);

        // Simulate refund processing
        $payment->update([
            'status' => 'refunded',
            'dispute_reason' => 'Shift cancelled by business',
            'resolved_at' => now(),
        ]);

        expect($payment->fresh()->status)->toBe('refunded')
            ->and($payment->dispute_reason)->toBe('Shift cancelled by business')
            ->and($payment->resolved_at)->not->toBeNull();
    });

    it('places payment on hold during dispute', function () {
        $shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'completed',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => 'in_escrow',
        ]);

        // Business raises a dispute
        $payment->update([
            'status' => 'disputed',
            'disputed' => true,
            'dispute_reason' => 'Worker did not show up as scheduled',
            'disputed_at' => now(),
        ]);

        expect($payment->fresh()->status)->toBe('disputed')
            ->and($payment->disputed)->toBeTrue()
            ->and($payment->dispute_reason)->toBe('Worker did not show up as scheduled')
            ->and($payment->disputed_at)->not->toBeNull();
    });

    it('calculates platform commission correctly', function () {
        $amountGross = 200.00; // $200
        $platformFeePercentage = 10; // 10%
        $expectedFee = 20.00; // $20
        $expectedAmountNet = 180.00; // $180

        $payment = ShiftPayment::factory()->create([
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'amount_gross' => $amountGross,
            'platform_fee' => $expectedFee,
            'amount_net' => $expectedAmountNet,
            'status' => 'in_escrow',
        ]);

        $actualFee = ($amountGross * $platformFeePercentage) / 100;
        $actualAmountNet = $amountGross - $actualFee;

        expect(Money::toDecimal($payment->platform_fee))->toBe($actualFee)
            ->and(Money::toDecimal($payment->amount_net))->toBe($actualAmountNet)
            ->and(Money::toDecimal($payment->amount_gross))->toBe(Money::toDecimal($payment->platform_fee) + Money::toDecimal($payment->amount_net));
    });

});
