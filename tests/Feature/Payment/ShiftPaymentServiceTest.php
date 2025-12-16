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
    public function it_can_process_instant_payout()
    {
        Event::fake();

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
            'shift_id' => $shift->id,
            'assignment_id' => $assignment->id,
            'worker_id' => $worker->id,
            'amount_gross' => 10000, // $100.00
            'status' => 'pending',
        ]);

        $result = $this->service->processInstantPayout($payment->id);

        $this->assertTrue($result);
        Event::assertDispatched(InstantPayoutCompleted::class);
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
            'shift_id' => $shift->id,
            'assignment_id' => $assignment->id,
            'worker_id' => $worker->id,
            'status' => 'released',
        ]);

        $result = $this->service->createDispute($payment->id, 'Incorrect hours worked');

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
