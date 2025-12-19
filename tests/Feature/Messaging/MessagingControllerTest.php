<?php

namespace Tests\Feature\Messaging;

use App\Models\Message;
use App\Models\User;
use App\Services\InAppMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * COM-001: Messaging Controller Tests
 */
class MessagingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $worker;

    protected User $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        $this->business = User::factory()->create([
            'user_type' => 'business',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_messages(): void
    {
        $response = $this->get(route('messages.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_prevents_non_participants_from_viewing_conversation(): void
    {
        $messagingService = app(InAppMessagingService::class);

        $conversation = $messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $otherUser = User::factory()->create(['user_type' => 'worker']);

        $response = $this->actingAs($otherUser)
            ->get(route('messages.show', $conversation->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_send_message_via_legacy_endpoint(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->worker)
            ->post(route('messages.send'), [
                'to_user_id' => $this->business->id,
                'message' => 'Hello from worker!',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'from_user_id' => $this->worker->id,
            'to_user_id' => $this->business->id,
            'message' => 'Hello from worker!',
        ]);
    }

    /** @test */
    public function it_validates_message_content(): void
    {
        $response = $this->actingAs($this->worker)
            ->post(route('messages.send'), [
                'to_user_id' => $this->business->id,
                'message' => '', // Empty message
            ]);

        $response->assertSessionHasErrors('message');
    }

    /** @test */
    public function it_can_archive_conversation(): void
    {
        $messagingService = app(InAppMessagingService::class);

        $conversation = $messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $response = $this->actingAs($this->worker)
            ->post(route('messages.archive', $conversation->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 'archived',
        ]);
    }

    /** @test */
    public function it_can_restore_archived_conversation(): void
    {
        $messagingService = app(InAppMessagingService::class);

        $conversation = $messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $conversation->update(['status' => 'archived']);

        $response = $this->actingAs($this->worker)
            ->post(route('messages.restore', $conversation->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_returns_unread_count_via_ajax(): void
    {
        $messagingService = app(InAppMessagingService::class);

        $conversation = $messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        // Business sends a message
        $messagingService->sendMessage(
            $conversation,
            $this->business,
            ['body' => 'Hello worker!']
        );

        $response = $this->actingAs($this->worker)
            ->get(route('messages.unread.count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 1]);
    }

    /** @test */
    public function worker_can_only_message_businesses(): void
    {
        $otherWorker = User::factory()->create(['user_type' => 'worker']);

        $response = $this->actingAs($this->worker)
            ->post(route('messages.send'), [
                'to_user_id' => $otherWorker->id,
                'message' => 'Hello fellow worker!',
            ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function business_can_only_message_workers(): void
    {
        $otherBusiness = User::factory()->create(['user_type' => 'business']);

        $response = $this->actingAs($this->business)
            ->post(route('messages.send'), [
                'to_user_id' => $otherBusiness->id,
                'message' => 'Hello fellow business!',
            ]);

        $response->assertSessionHas('error');
    }
}
