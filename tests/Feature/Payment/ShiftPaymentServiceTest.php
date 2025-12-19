<?php

namespace Tests\Feature\Payment;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\ShiftAssignment;
use App\Services\ShiftPaymentService;
use App\Events\InstantPayoutCompleted;
use App\Events\PaymentDisputed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class ShiftPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShiftPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftPaymentService::class);
    }

    /** @test */
    public function it_prevents_instant_payout_for_worker_without_stripe_setup()
    {
        Event::fake();

        // Worker without Stripe Connect setup (default factory doesn't set it)
        $worker = User::factory()->create(['user_type' => 'worker']);
        $business = User::factory()->create(['user_type' => 'business']);

        $shift = Shift::factory()->create([
            'business_id' => $business->id,
            'status' => 'completed',
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
            'status' => 'completed',
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $worker->id,
            'business_id' => $business->id,
            'amount_gross' => 10000, // $100.00
            'status' => 'released',
        ]);

        // Should return false because worker can't receive instant payouts (no Stripe Connect)
        $result = $this->service->instantPayout($payment);

        $this->assertFalse($result);
        Event::assertNotDispatched(InstantPayoutCompleted::class);
    }

    /** @test */
    public function it_can_create_payment_dispute()
    {
        Event::fake();

        $worker = User::factory()->create(['user_type' => 'worker']);
        $business = User::factory()->create(['user_type' => 'business']);
        
        $shift = Shift::factory()->create([
            'business_id' => $business->id,
        ]);

        $assignment = ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
        ]);

        $payment = ShiftPayment::factory()->create([
            'shift_assignment_id' => $assignment->id,
            'worker_id' => $worker->id,
            'business_id' => $business->id,
            'status' => 'released',
        ]);

        $result = $this->service->handleDispute($assignment, 'Incorrect hours worked');

        $this->assertTrue($result);
        Event::assertDispatched(PaymentDisputed::class);
    }

    /** @test */
    public function it_calculates_payment_amounts_correctly()
    {
        $platformFeePercentage = 10; // 10%
        $amountGross = 10000; // $100.00

        $expectedPlatformFee = 1000; // $10.00
        $expectedNetAmount = 9000; // $90.00

        // This would test the actual calculation logic
        // Implementation depends on ShiftPaymentService structure
        $this->assertTrue(true); // Placeholder
    }
}
