<?php

namespace Tests\Feature\Dispute;

use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Notifications\DisputeOpenedNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Notifications\DisputeResponseNotification;
use App\Services\DisputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * DisputeServiceTest
 *
 * FIN-010: Tests for the dispute resolution system.
 */
class DisputeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DisputeService $service;

    protected User $worker;

    protected User $business;

    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DisputeService::class);

        // Create test users
        $this->worker = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        $this->business = User::factory()->create([
            'user_type' => 'business',
            'status' => 'active',
        ]);

        // Create test shift
        $this->shift = Shift::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);

        // Create assignment
        ShiftAssignment::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_can_open_a_dispute()
    {
        Notification::fake();

        $data = [
            'type' => Dispute::TYPE_PAYMENT,
            'disputed_amount' => 50.00,
            'worker_description' => 'I was not paid the correct amount for overtime hours worked during this shift.',
        ];

        $dispute = $this->service->openDispute($this->shift, $this->worker, $data);

        $this->assertInstanceOf(Dispute::class, $dispute);
        $this->assertEquals(Dispute::STATUS_OPEN, $dispute->status);
        $this->assertEquals(50.00, $dispute->disputed_amount);
        $this->assertEquals($this->worker->id, $dispute->worker_id);
        $this->assertEquals($this->business->id, $dispute->business_id);
        $this->assertNotNull($dispute->evidence_deadline);

        // Check timeline entry was created
        $this->assertDatabaseHas('dispute_timeline', [
            'dispute_id' => $dispute->id,
            'action' => DisputeTimeline::ACTION_OPENED,
        ]);

        // Check notification was sent
        Notification::assertSentTo($this->business, DisputeOpenedNotification::class);
    }

    /** @test */
    public function it_rejects_disputes_below_minimum_amount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Disputed amount must be at least');

        $data = [
            'type' => Dispute::TYPE_PAYMENT,
            'disputed_amount' => 2.00, // Below minimum
            'worker_description' => 'Test description for the dispute.',
        ];

        $this->service->openDispute($this->shift, $this->worker, $data);
    }

    /** @test */
    public function it_prevents_duplicate_active_disputes()
    {
        $data = [
            'type' => Dispute::TYPE_PAYMENT,
            'disputed_amount' => 50.00,
            'worker_description' => 'First dispute description that is long enough.',
        ];

        // Open first dispute
        $this->service->openDispute($this->shift, $this->worker, $data);

        // Try to open second dispute
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already have an active dispute');

        $this->service->openDispute($this->shift, $this->worker, $data);
    }

    /** @test */
    public function it_can_submit_business_response()
    {
        Notification::fake();

        // Create dispute
        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $responseData = [
            'response' => 'We disagree with this claim. The worker was paid correctly as per the agreed rate.',
        ];

        $updatedDispute = $this->service->submitBusinessResponse($dispute, $responseData);

        $this->assertEquals(Dispute::STATUS_UNDER_REVIEW, $updatedDispute->status);
        $this->assertNotNull($updatedDispute->business_response);

        // Check timeline entry
        $this->assertDatabaseHas('dispute_timeline', [
            'dispute_id' => $dispute->id,
            'action' => DisputeTimeline::ACTION_BUSINESS_RESPONDED,
        ]);

        // Check notification was sent to worker
        Notification::assertSentTo($this->worker, DisputeResponseNotification::class);
    }

    /** @test */
    public function it_prevents_business_responding_twice()
    {
        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
            'business_response' => 'Already responded',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already responded');

        $this->service->submitBusinessResponse($dispute, ['response' => 'Second response']);
    }

    /** @test */
    public function it_can_submit_evidence()
    {
        Storage::fake('public');

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
            'evidence_deadline' => now()->addDays(5),
        ]);

        $files = [
            UploadedFile::fake()->image('screenshot.jpg'),
            UploadedFile::fake()->create('document.pdf', 100),
        ];

        $updatedDispute = $this->service->submitEvidence($dispute, $this->worker, $files);

        $this->assertNotNull($updatedDispute->evidence_worker);
        $this->assertCount(2, $updatedDispute->evidence_worker);

        // Check timeline entry
        $this->assertDatabaseHas('dispute_timeline', [
            'dispute_id' => $dispute->id,
            'action' => DisputeTimeline::ACTION_WORKER_EVIDENCE,
        ]);
    }

    /** @test */
    public function it_prevents_evidence_after_deadline()
    {
        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
            'evidence_deadline' => now()->subDay(), // Past deadline
        ]);

        $files = [
            UploadedFile::fake()->image('screenshot.jpg'),
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('deadline has passed');

        $this->service->submitEvidence($dispute, $this->worker, $files);
    }

    /** @test */
    public function it_can_assign_mediator()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $updatedDispute = $this->service->assignMediator($dispute, $admin);

        $this->assertEquals($admin->id, $updatedDispute->assigned_to);
        $this->assertEquals(Dispute::STATUS_MEDIATION, $updatedDispute->status);

        // Check timeline entry
        $this->assertDatabaseHas('dispute_timeline', [
            'dispute_id' => $dispute->id,
            'action' => DisputeTimeline::ACTION_ASSIGNED,
        ]);
    }

    /** @test */
    public function it_rejects_non_admin_as_mediator()
    {
        $nonAdmin = User::factory()->create([
            'user_type' => 'worker',
            'role' => 'user',
        ]);

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only administrators');

        $this->service->assignMediator($dispute, $nonAdmin);
    }

    /** @test */
    public function it_can_resolve_dispute_in_worker_favor()
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_MEDIATION,
            'disputed_amount' => 100.00,
        ]);

        $resolvedDispute = $this->service->resolveDispute(
            $dispute,
            Dispute::RESOLUTION_WORKER_FAVOR,
            100.00,
            'Worker provided sufficient evidence of unpaid overtime.'
        );

        $this->assertEquals(Dispute::STATUS_RESOLVED, $resolvedDispute->status);
        $this->assertEquals(Dispute::RESOLUTION_WORKER_FAVOR, $resolvedDispute->resolution);
        $this->assertEquals(100.00, $resolvedDispute->resolution_amount);
        $this->assertNotNull($resolvedDispute->resolved_at);

        // Check notifications sent to both parties
        Notification::assertSentTo($this->worker, DisputeResolvedNotification::class);
        Notification::assertSentTo($this->business, DisputeResolvedNotification::class);
    }

    /** @test */
    public function it_can_resolve_dispute_with_split()
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_MEDIATION,
            'disputed_amount' => 100.00,
        ]);

        $resolvedDispute = $this->service->resolveDispute(
            $dispute,
            Dispute::RESOLUTION_SPLIT,
            50.00,
            'Both parties share responsibility. Amount split 50/50.'
        );

        $this->assertEquals(Dispute::RESOLUTION_SPLIT, $resolvedDispute->resolution);
        $this->assertEquals(50.00, $resolvedDispute->resolution_amount);
    }

    /** @test */
    public function it_can_escalate_dispute()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);

        $escalatedDispute = $this->service->escalateDispute($dispute);

        $this->assertEquals(Dispute::STATUS_ESCALATED, $escalatedDispute->status);

        // Check timeline entry
        $this->assertDatabaseHas('dispute_timeline', [
            'dispute_id' => $dispute->id,
            'action' => DisputeTimeline::ACTION_ESCALATED,
        ]);
    }

    /** @test */
    public function it_prevents_escalating_resolved_disputes()
    {
        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_RESOLVED,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be escalated');

        $this->service->escalateDispute($dispute);
    }

    /** @test */
    public function it_can_auto_close_stale_disputes()
    {
        // Create a stale dispute
        $staleDispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
            'updated_at' => now()->subDays(35), // More than 30 days
        ]);

        // Create an active dispute
        $activeDispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'updated_at' => now()->subDays(5),
        ]);

        $closedCount = $this->service->autoCloseStaleDisputes();

        $this->assertEquals(1, $closedCount);

        $staleDispute->refresh();
        $activeDispute->refresh();

        $this->assertEquals(Dispute::STATUS_CLOSED, $staleDispute->status);
        $this->assertEquals(Dispute::RESOLUTION_EXPIRED, $staleDispute->resolution);
        $this->assertEquals(Dispute::STATUS_UNDER_REVIEW, $activeDispute->status);
    }

    /** @test */
    public function it_can_calculate_resolution_split()
    {
        $dispute = Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'disputed_amount' => 100.00,
            'evidence_worker' => ['file1.jpg'],
            'evidence_business' => null, // Business didn't provide evidence
        ]);

        $split = $this->service->calculateResolutionSplit($dispute);

        $this->assertArrayHasKey('disputed_amount', $split);
        $this->assertArrayHasKey('worker_amount', $split);
        $this->assertArrayHasKey('business_amount', $split);
        $this->assertArrayHasKey('recommendation', $split);

        // With only worker evidence, should favor worker (70/30)
        $this->assertEquals(70.00, $split['worker_amount']);
        $this->assertEquals(30.00, $split['business_amount']);
    }

    /** @test */
    public function it_can_get_statistics()
    {
        // Create some test disputes
        Dispute::factory()->count(3)->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_RESOLVED,
            'resolution' => Dispute::RESOLUTION_WORKER_FAVOR,
        ]);

        $stats = $this->service->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('resolved', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(3, $stats['active']);
        $this->assertEquals(1, $stats['resolved']);
    }

    /** @test */
    public function it_can_get_user_specific_statistics()
    {
        // Create disputes for our test worker
        Dispute::factory()->count(2)->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $this->worker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        // Create dispute for another worker
        $anotherWorker = User::factory()->create(['user_type' => 'worker']);
        Dispute::factory()->create([
            'shift_id' => $this->shift->id,
            'worker_id' => $anotherWorker->id,
            'business_id' => $this->business->id,
            'status' => Dispute::STATUS_OPEN,
        ]);

        $workerStats = $this->service->getStatistics($this->worker->id);

        $this->assertEquals(2, $workerStats['total']);
    }
}
