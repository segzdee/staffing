<?php

namespace Tests\Feature\Worker;

use App\Models\EarningsSummary;
use App\Models\User;
use App\Models\WorkerEarning;
use App\Models\WorkerProfile;
use App\Services\EarningsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WKR-006: Earnings Dashboard Tests
 *
 * Tests for the EarningsService that powers the worker earnings dashboard.
 */
class EarningsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EarningsService $earningsService;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->earningsService = app(EarningsService::class);

        // Create a worker user with profile
        $this->worker = User::factory()->create([
            'user_type' => 'worker',
            'is_verified_worker' => true,
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
        ]);
    }

    // ==================== RECORD EARNINGS TESTS ====================

    /** @test */
    public function it_can_record_a_shift_earning()
    {
        $earning = $this->earningsService->recordEarning($this->worker, [
            'type' => WorkerEarning::TYPE_SHIFT_PAY,
            'gross_amount' => 150.00,
            'platform_fee' => 15.00,
            'net_amount' => 135.00,
            'description' => 'Shift at Restaurant ABC',
            'earned_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(WorkerEarning::class, $earning);
        $this->assertEquals(150.00, $earning->gross_amount);
        $this->assertEquals(15.00, $earning->platform_fee);
        $this->assertEquals(135.00, $earning->net_amount);
        $this->assertEquals(WorkerEarning::TYPE_SHIFT_PAY, $earning->type);
        $this->assertEquals(WorkerEarning::STATUS_PENDING, $earning->status);
    }

    /** @test */
    public function it_can_record_bonus_earnings()
    {
        $earning = $this->earningsService->recordEarning($this->worker, [
            'type' => WorkerEarning::TYPE_BONUS,
            'gross_amount' => 50.00,
            'platform_fee' => 0,
            'net_amount' => 50.00,
            'description' => 'Weekend bonus',
            'earned_date' => now()->toDateString(),
        ]);

        $this->assertEquals(WorkerEarning::TYPE_BONUS, $earning->type);
        $this->assertEquals(50.00, $earning->gross_amount);
    }

    /** @test */
    public function it_can_record_tip_earnings()
    {
        $earning = $this->earningsService->recordEarning($this->worker, [
            'type' => WorkerEarning::TYPE_TIP,
            'gross_amount' => 25.00,
            'platform_fee' => 0,
            'net_amount' => 25.00,
            'description' => 'Customer tip',
            'earned_date' => now()->toDateString(),
        ]);

        $this->assertEquals(WorkerEarning::TYPE_TIP, $earning->type);
    }

    /** @test */
    public function recorded_earnings_belong_to_correct_worker()
    {
        $earning = $this->earningsService->recordEarning($this->worker, [
            'type' => WorkerEarning::TYPE_SHIFT_PAY,
            'gross_amount' => 100.00,
            'platform_fee' => 10.00,
            'net_amount' => 90.00,
            'earned_date' => now()->toDateString(),
        ]);

        $this->assertEquals($this->worker->id, $earning->user_id);
    }

    // ==================== EARNINGS BY PERIOD TESTS ====================

    /** @test */
    public function it_can_get_earnings_for_today()
    {
        // Create earnings for today
        WorkerEarning::factory()->count(3)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->toDateString(),
            'gross_amount' => 50.00,
            'net_amount' => 45.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create earnings for yesterday (should not be included)
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->subDay()->toDateString(),
            'gross_amount' => 100.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $earnings = $this->earningsService->getEarningsByPeriod($this->worker, 'today');

        $this->assertCount(3, $earnings);
    }

    /** @test */
    public function it_can_get_earnings_for_this_week()
    {
        // Create earnings for this week
        WorkerEarning::factory()->count(5)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->startOfWeek()->addDays(2)->toDateString(),
            'gross_amount' => 80.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create earnings for last week (should not be included)
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->subWeek()->toDateString(),
            'gross_amount' => 100.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $earnings = $this->earningsService->getEarningsByPeriod($this->worker, 'this_week');

        $this->assertCount(5, $earnings);
    }

    /** @test */
    public function it_can_get_earnings_for_this_month()
    {
        // Create earnings for this month
        WorkerEarning::factory()->count(10)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'gross_amount' => 120.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $earnings = $this->earningsService->getEarningsByPeriod($this->worker, 'this_month');

        $this->assertCount(10, $earnings);
    }

    /** @test */
    public function it_can_get_earnings_with_custom_date_range()
    {
        $startDate = Carbon::parse('2024-06-01');
        $endDate = Carbon::parse('2024-06-15');

        // Create earnings within range
        WorkerEarning::factory()->count(4)->create([
            'user_id' => $this->worker->id,
            'earned_date' => '2024-06-10',
            'gross_amount' => 70.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create earnings outside range
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => '2024-05-15',
            'gross_amount' => 100.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $earnings = $this->earningsService->getEarningsByPeriod(
            $this->worker,
            'custom',
            $startDate,
            $endDate
        );

        $this->assertCount(4, $earnings);
    }

    // ==================== EARNINGS SUMMARY TESTS ====================

    /** @test */
    public function it_can_calculate_daily_earnings_summary()
    {
        $today = now()->toDateString();

        // Create earnings for today
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => $today,
            'gross_amount' => 150.00,
            'platform_fee' => 15.00,
            'net_amount' => 135.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => $today,
            'gross_amount' => 100.00,
            'platform_fee' => 10.00,
            'net_amount' => 90.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $summary = $this->earningsService->calculateEarningsSummary(
            $this->worker,
            'daily',
            now()
        );

        $this->assertInstanceOf(EarningsSummary::class, $summary);
        $this->assertEquals(250.00, $summary->gross_earnings);
        $this->assertEquals(25.00, $summary->total_fees);
        $this->assertEquals(225.00, $summary->net_earnings);
    }

    /** @test */
    public function it_can_calculate_weekly_earnings_summary()
    {
        $startOfWeek = now()->startOfWeek();

        // Create earnings for this week
        for ($i = 0; $i < 5; $i++) {
            WorkerEarning::factory()->create([
                'user_id' => $this->worker->id,
                'earned_date' => $startOfWeek->copy()->addDays($i)->toDateString(),
                'gross_amount' => 100.00,
                'platform_fee' => 10.00,
                'net_amount' => 90.00,
                'status' => WorkerEarning::STATUS_PAID,
            ]);
        }

        $summary = $this->earningsService->calculateEarningsSummary(
            $this->worker,
            'weekly',
            now()
        );

        $this->assertEquals(500.00, $summary->gross_earnings);
        $this->assertEquals(50.00, $summary->total_fees);
        $this->assertEquals(450.00, $summary->net_earnings);
    }

    /** @test */
    public function it_can_calculate_monthly_earnings_summary()
    {
        $startOfMonth = now()->startOfMonth();

        // Create earnings for this month
        for ($i = 0; $i < 15; $i++) {
            WorkerEarning::factory()->create([
                'user_id' => $this->worker->id,
                'earned_date' => $startOfMonth->copy()->addDays($i)->toDateString(),
                'gross_amount' => 80.00,
                'platform_fee' => 8.00,
                'net_amount' => 72.00,
                'status' => WorkerEarning::STATUS_PAID,
            ]);
        }

        $summary = $this->earningsService->calculateEarningsSummary(
            $this->worker,
            'monthly',
            now()
        );

        $this->assertEquals(1200.00, $summary->gross_earnings);
        $this->assertEquals(120.00, $summary->total_fees);
    }

    // ==================== YEAR TO DATE TESTS ====================

    /** @test */
    public function it_can_get_year_to_date_earnings()
    {
        // Create earnings spread throughout the year
        $months = [1, 2, 3, 4, 5, 6];
        foreach ($months as $month) {
            WorkerEarning::factory()->create([
                'user_id' => $this->worker->id,
                'earned_date' => Carbon::create(now()->year, $month, 15)->toDateString(),
                'gross_amount' => 200.00,
                'platform_fee' => 20.00,
                'net_amount' => 180.00,
                'status' => WorkerEarning::STATUS_PAID,
            ]);
        }

        // Create earnings from previous year (should not be included)
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => Carbon::create(now()->year - 1, 12, 15)->toDateString(),
            'gross_amount' => 500.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $ytd = $this->earningsService->getYearToDateEarnings($this->worker);

        $this->assertEquals(1200.00, $ytd['gross_earnings']);
        $this->assertEquals(120.00, $ytd['total_fees']);
        $this->assertEquals(1080.00, $ytd['net_earnings']);
    }

    // ==================== AVERAGE HOURLY RATE TESTS ====================

    /** @test */
    public function it_returns_zero_average_rate_when_no_hours()
    {
        $avgRate = $this->earningsService->getAverageHourlyRate($this->worker, 30);

        $this->assertEquals(0, $avgRate);
    }

    // ==================== PERIOD COMPARISON TESTS ====================

    /** @test */
    public function it_can_compare_week_over_week_earnings()
    {
        // Create earnings for this week
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->startOfWeek()->addDays(2)->toDateString(),
            'gross_amount' => 150.00,
            'net_amount' => 135.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create earnings for last week
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->subWeek()->startOfWeek()->addDays(2)->toDateString(),
            'gross_amount' => 100.00,
            'net_amount' => 90.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $comparison = $this->earningsService->compareEarnings($this->worker, 'this_week');

        $this->assertEquals(150.00, $comparison['current']['gross_earnings']);
        $this->assertEquals(100.00, $comparison['previous']['gross_earnings']);
        $this->assertEquals(50.0, $comparison['changes']['gross_earnings']); // 50% increase
    }

    /** @test */
    public function it_can_compare_month_over_month_earnings()
    {
        // Create earnings for this month
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'gross_amount' => 500.00,
            'net_amount' => 450.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create earnings for last month
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->subMonth()->startOfMonth()->addDays(5)->toDateString(),
            'gross_amount' => 600.00,
            'net_amount' => 540.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $comparison = $this->earningsService->compareEarnings($this->worker, 'this_month');

        $this->assertEquals(500.00, $comparison['current']['gross_earnings']);
        $this->assertEquals(600.00, $comparison['previous']['gross_earnings']);
        $this->assertLessThan(0, $comparison['changes']['gross_earnings']); // Decrease
    }

    // ==================== TAX SUMMARY TESTS ====================

    /** @test */
    public function it_can_generate_tax_summary()
    {
        $year = now()->year;

        // Create earnings with tax withholding
        for ($month = 1; $month <= 6; $month++) {
            WorkerEarning::factory()->create([
                'user_id' => $this->worker->id,
                'earned_date' => Carbon::create($year, $month, 15)->toDateString(),
                'gross_amount' => 1000.00,
                'platform_fee' => 100.00,
                'tax_withheld' => 150.00,
                'net_amount' => 750.00,
                'status' => WorkerEarning::STATUS_PAID,
            ]);
        }

        $taxSummary = $this->earningsService->getTaxSummary($this->worker, $year);

        $this->assertArrayHasKey('summary', $taxSummary);
        $this->assertArrayHasKey('quarters', $taxSummary);
        $this->assertArrayHasKey('monthly', $taxSummary);
        $this->assertArrayHasKey('tax_info', $taxSummary);

        // Total gross should be $6000.00
        $this->assertEquals(6000.00, $taxSummary['summary']['total_gross_earnings']);
        $this->assertEquals(900.00, $taxSummary['summary']['total_tax_withheld']);
    }

    /** @test */
    public function tax_summary_indicates_1099_requirement()
    {
        $year = now()->year;

        // Create earnings above threshold ($600)
        WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'earned_date' => Carbon::create($year, 6, 15)->toDateString(),
            'gross_amount' => 1000.00, // $1000.00 - above threshold
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $taxSummary = $this->earningsService->getTaxSummary($this->worker, $year);

        $this->assertTrue($taxSummary['tax_info']['requires_1099']);
    }

    // ==================== EXPORT TESTS ====================

    /** @test */
    public function it_can_export_earnings_to_csv()
    {
        // Create some earnings
        WorkerEarning::factory()->count(5)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->toDateString(),
            'gross_amount' => 100.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $export = $this->earningsService->exportEarnings($this->worker, 'csv', [
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->assertStringContainsString('Date', $export);
        $this->assertStringContainsString('Gross Amount', $export);
        $this->assertStringContainsString('Net Amount', $export);
    }

    // ==================== DASHBOARD DATA TESTS ====================

    /** @test */
    public function it_can_get_dashboard_data()
    {
        // Create recent earnings
        WorkerEarning::factory()->count(5)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->toDateString(),
            'gross_amount' => 100.00,
            'net_amount' => 90.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        // Create pending earnings
        WorkerEarning::factory()->count(2)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->toDateString(),
            'gross_amount' => 80.00,
            'status' => WorkerEarning::STATUS_PENDING,
        ]);

        $dashboard = $this->earningsService->getDashboardData($this->worker);

        $this->assertArrayHasKey('year_to_date', $dashboard);
        $this->assertArrayHasKey('current_month', $dashboard);
        $this->assertArrayHasKey('pending', $dashboard);
        $this->assertArrayHasKey('avg_hourly_rate', $dashboard);
        $this->assertArrayHasKey('recent_transactions', $dashboard);
        $this->assertArrayHasKey('comparison', $dashboard);
    }

    // ==================== REFRESH SUMMARIES TESTS ====================

    /** @test */
    public function it_can_refresh_all_summaries_for_worker()
    {
        // Create earnings
        WorkerEarning::factory()->count(10)->create([
            'user_id' => $this->worker->id,
            'earned_date' => now()->toDateString(),
            'gross_amount' => 100.00,
            'status' => WorkerEarning::STATUS_PAID,
        ]);

        $stats = $this->earningsService->refreshAllSummaries($this->worker);

        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('weekly', $stats);
        $this->assertArrayHasKey('monthly', $stats);
        $this->assertArrayHasKey('yearly', $stats);
    }

    // ==================== EARNINGS STATUS TESTS ====================

    /** @test */
    public function earnings_can_be_approved()
    {
        $earning = WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'status' => WorkerEarning::STATUS_PENDING,
        ]);

        $earning->approve();

        $this->assertEquals(WorkerEarning::STATUS_APPROVED, $earning->fresh()->status);
    }

    /** @test */
    public function earnings_can_be_marked_as_paid()
    {
        $earning = WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'status' => WorkerEarning::STATUS_APPROVED,
        ]);

        $earning->markPaid();

        $earning->refresh();
        $this->assertEquals(WorkerEarning::STATUS_PAID, $earning->status);
        $this->assertNotNull($earning->paid_at);
    }

    /** @test */
    public function earnings_can_be_disputed()
    {
        $earning = WorkerEarning::factory()->create([
            'user_id' => $this->worker->id,
            'status' => WorkerEarning::STATUS_PENDING,
        ]);

        $earning->dispute('Incorrect amount calculated');

        $earning->refresh();
        $this->assertEquals(WorkerEarning::STATUS_DISPUTED, $earning->status);
        $this->assertEquals('Incorrect amount calculated', $earning->dispute_reason);
    }
}
