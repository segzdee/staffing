<?php

namespace Tests\Feature;

use App\Models\PayrollDeduction;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Notifications\PayrollReadyForApprovalNotification;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * PayrollServiceTest - FIN-005: Payroll Processing System
 *
 * Tests for payroll calculation, processing, and notification functionality.
 */
class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollService $payrollService;

    protected User $admin;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payrollService = new PayrollService;

        // Create admin user
        $this->admin = User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin',
        ]);

        // Create worker user
        $this->worker = User::factory()->create([
            'user_type' => 'worker',
        ]);
    }

    /** @test */
    public function it_creates_a_payroll_run_with_correct_reference()
    {
        $periodStart = Carbon::now()->subWeek()->startOfWeek();
        $periodEnd = Carbon::now()->subWeek()->endOfWeek();
        $payDate = Carbon::now()->addDays(3);

        $payrollRun = $this->payrollService->createPayrollRun(
            $periodStart,
            $periodEnd,
            $payDate,
            $this->admin
        );

        $this->assertNotNull($payrollRun);
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{3}$/', $payrollRun->reference);
        $this->assertEquals(PayrollRun::STATUS_DRAFT, $payrollRun->status);
        $this->assertEquals($this->admin->id, $payrollRun->created_by);
        $this->assertEquals($periodStart->toDateString(), $payrollRun->period_start->toDateString());
        $this->assertEquals($periodEnd->toDateString(), $payrollRun->period_end->toDateString());
        $this->assertEquals($payDate->toDateString(), $payrollRun->pay_date->toDateString());
    }

    /** @test */
    public function it_generates_sequential_reference_numbers()
    {
        $periodStart = Carbon::now()->subWeek();
        $periodEnd = Carbon::now();
        $payDate = Carbon::now()->addWeek();

        $run1 = $this->payrollService->createPayrollRun($periodStart, $periodEnd, $payDate, $this->admin);
        $run2 = $this->payrollService->createPayrollRun($periodStart, $periodEnd, $payDate, $this->admin);
        $run3 = $this->payrollService->createPayrollRun($periodStart, $periodEnd, $payDate, $this->admin);

        $this->assertEquals('PR-'.Carbon::now()->year.'-001', $run1->reference);
        $this->assertEquals('PR-'.Carbon::now()->year.'-002', $run2->reference);
        $this->assertEquals('PR-'.Carbon::now()->year.'-003', $run3->reference);
    }

    /** @test */
    public function it_generates_payroll_items_from_completed_shift_assignments()
    {
        // Create a business
        $business = User::factory()->create(['user_type' => 'business']);

        // Create a shift in the pay period
        $shift = Shift::factory()->create([
            'business_id' => $business->id,
            'shift_date' => Carbon::now()->subDays(3),
            'base_rate' => 2500, // $25.00 in cents
            'status' => 'completed',
        ]);

        // Create a completed assignment
        $assignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $this->worker->id,
            'assigned_by' => $business->id,
            'status' => 'completed',
            'hours_worked' => 8.0,
            'net_hours_worked' => 7.5,
        ]);

        // Create payroll run
        $payrollRun = $this->payrollService->createPayrollRun(
            Carbon::now()->subWeek(),
            Carbon::now(),
            Carbon::now()->addDays(3),
            $this->admin
        );

        // Generate items
        $itemCount = $this->payrollService->generatePayrollItems($payrollRun);

        $this->assertGreaterThanOrEqual(1, $itemCount);

        // Verify item was created
        $item = PayrollItem::where('payroll_run_id', $payrollRun->id)
            ->where('user_id', $this->worker->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals($shift->id, $item->shift_id);
        $this->assertEquals(PayrollItem::TYPE_REGULAR, $item->type);
    }

    /** @test */
    public function it_calculates_deductions_correctly()
    {
        // Set platform fee rate in config
        config(['payroll.platform_fee_rate' => 10]);

        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-001',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test shift',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        $this->payrollService->calculateDeductions($item);

        $item->refresh();

        // Platform fee should be 10% of $200 = $20
        $this->assertEquals(20.00, $item->deductions);

        // Verify deduction record was created
        $deduction = PayrollDeduction::where('payroll_item_id', $item->id)
            ->where('type', PayrollDeduction::TYPE_PLATFORM_FEE)
            ->first();

        $this->assertNotNull($deduction);
        $this->assertEquals(20.00, $deduction->amount);
    }

    /** @test */
    public function it_calculates_net_amount_after_deductions()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-002',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test shift',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 20.00,
            'tax_withheld' => 15.00,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        $item->calculateNetAmount();

        // Net = Gross - Deductions - Taxes = 200 - 20 - 15 = 165
        $this->assertEquals(165.00, $item->net_amount);
    }

    /** @test */
    public function it_can_add_manual_bonus_item()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-003',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $item = $this->payrollService->addManualItem(
            $payrollRun,
            $this->worker,
            PayrollItem::TYPE_BONUS,
            'Q4 Performance Bonus',
            100.00
        );

        $this->assertNotNull($item);
        $this->assertEquals(PayrollItem::TYPE_BONUS, $item->type);
        $this->assertEquals('Q4 Performance Bonus', $item->description);
        $this->assertEquals(100.00, $item->gross_amount);
    }

    /** @test */
    public function it_can_add_reimbursement_without_deductions()
    {
        config(['payroll.platform_fee_rate' => 10]);

        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-004',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $item = $this->payrollService->addManualItem(
            $payrollRun,
            $this->worker,
            PayrollItem::TYPE_REIMBURSEMENT,
            'Travel expense reimbursement',
            50.00
        );

        // Reimbursements should not have deductions
        $this->assertEquals(0, $item->deductions);
        $this->assertEquals(50.00, $item->net_amount);
    }

    /** @test */
    public function it_recalculates_payroll_totals_correctly()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-005',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        // Create multiple items
        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Shift 1',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 20.00,
            'tax_withheld' => 10.00,
            'net_amount' => 170.00,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Shift 2',
            'hours' => 6,
            'rate' => 25.00,
            'gross_amount' => 150.00,
            'deductions' => 15.00,
            'tax_withheld' => 7.50,
            'net_amount' => 127.50,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        $payrollRun->recalculateTotals();

        $this->assertEquals(1, $payrollRun->total_workers);
        $this->assertEquals(350.00, $payrollRun->gross_amount);
        $this->assertEquals(35.00, $payrollRun->total_deductions);
        $this->assertEquals(17.50, $payrollRun->total_taxes);
        $this->assertEquals(297.50, $payrollRun->net_amount);
    }

    /** @test */
    public function it_can_submit_payroll_for_approval()
    {
        Notification::fake();

        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-006',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        // Add at least one item
        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        $result = $this->payrollService->submitForApproval($payrollRun);

        $this->assertTrue($result);
        $this->assertEquals(PayrollRun::STATUS_PENDING_APPROVAL, $payrollRun->fresh()->status);

        // Verify notification was sent to admins
        Notification::assertSentTo(
            $this->admin,
            PayrollReadyForApprovalNotification::class
        );
    }

    /** @test */
    public function it_cannot_submit_empty_payroll_for_approval()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-007',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot submit empty payroll for approval');

        $this->payrollService->submitForApproval($payrollRun);
    }

    /** @test */
    public function it_can_approve_payroll_run()
    {
        $approver = User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin',
        ]);

        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-008',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_PENDING_APPROVAL,
            'created_by' => $this->admin->id,
        ]);

        $result = $this->payrollService->approvePayrollRun($payrollRun, $approver);

        $this->assertTrue($result);

        $payrollRun->refresh();
        $this->assertEquals(PayrollRun::STATUS_APPROVED, $payrollRun->status);
        $this->assertEquals($approver->id, $payrollRun->approved_by);
        $this->assertNotNull($payrollRun->approved_at);
    }

    /** @test */
    public function it_cannot_approve_own_payroll_when_configured()
    {
        config(['payroll.require_different_approver' => true]);

        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-009',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_PENDING_APPROVAL,
            'created_by' => $this->admin->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot approve your own payroll run');

        $this->payrollService->approvePayrollRun($payrollRun, $this->admin);
    }

    /** @test */
    public function it_generates_worker_paystub_data()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-010',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test shift',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 20.00,
            'tax_withheld' => 10.00,
            'net_amount' => 170.00,
            'status' => PayrollItem::STATUS_PAID,
        ]);

        $paystub = $this->payrollService->getWorkerPaystub($payrollRun, $this->worker);

        $this->assertArrayHasKey('payroll_run', $paystub);
        $this->assertArrayHasKey('worker', $paystub);
        $this->assertArrayHasKey('earnings', $paystub);
        $this->assertArrayHasKey('deductions', $paystub);
        $this->assertArrayHasKey('totals', $paystub);

        $this->assertEquals($payrollRun->reference, $paystub['payroll_run']['reference']);
        $this->assertEquals($this->worker->id, $paystub['worker']['id']);
        $this->assertEquals(200.00, $paystub['totals']['gross']);
        $this->assertEquals(170.00, $paystub['totals']['net']);
    }

    /** @test */
    public function it_exports_payroll_to_csv()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-011',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test shift',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 20.00,
            'tax_withheld' => 10.00,
            'net_amount' => 170.00,
            'status' => PayrollItem::STATUS_PAID,
        ]);

        $csv = $this->payrollService->exportPayroll($payrollRun, 'csv');

        $this->assertStringContainsString('Payroll Ref', $csv);
        $this->assertStringContainsString($payrollRun->reference, $csv);
        $this->assertStringContainsString('Test shift', $csv);
    }

    /** @test */
    public function it_exports_payroll_to_json()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-012',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Test shift',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 20.00,
            'tax_withheld' => 10.00,
            'net_amount' => 170.00,
            'status' => PayrollItem::STATUS_PAID,
        ]);

        $json = $this->payrollService->exportPayroll($payrollRun, 'json');
        $data = json_decode($json, true);

        $this->assertArrayHasKey('payroll_run', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertEquals($payrollRun->reference, $data['payroll_run']['reference']);
    }

    /** @test */
    public function it_returns_payroll_summary_correctly()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-013',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_APPROVED,
            'created_by' => $this->admin->id,
            'total_workers' => 1,
            'total_shifts' => 2,
            'gross_amount' => 400.00,
            'total_deductions' => 40.00,
            'total_taxes' => 20.00,
            'net_amount' => 340.00,
        ]);

        $summary = $this->payrollService->getPayrollSummary($payrollRun);

        $this->assertEquals($payrollRun->reference, $summary['reference']);
        $this->assertEquals('approved', $summary['status']);
        $this->assertEquals(1, $summary['totals']['workers']);
        $this->assertEquals(400.00, $summary['totals']['gross']);
        $this->assertEquals(340.00, $summary['totals']['net']);
    }

    /** @test */
    public function it_tracks_progress_percentage_correctly()
    {
        $payrollRun = PayrollRun::create([
            'reference' => 'PR-TEST-014',
            'period_start' => Carbon::now()->subWeek(),
            'period_end' => Carbon::now(),
            'pay_date' => Carbon::now()->addDays(3),
            'status' => PayrollRun::STATUS_PROCESSING,
            'created_by' => $this->admin->id,
        ]);

        // Create 4 items: 2 paid, 1 failed, 1 pending
        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Item 1',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PAID,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Item 2',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PAID,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Item 3',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_FAILED,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $payrollRun->id,
            'user_id' => $this->worker->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => 'Item 4',
            'hours' => 8,
            'rate' => 25.00,
            'gross_amount' => 200.00,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => 200.00,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        // 3 out of 4 are processed (paid or failed) = 75%
        $this->assertEquals(75, $payrollRun->getProgressPercentage());
    }
}
