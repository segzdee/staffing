<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Services\InAppMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * COM-001: In-App Messaging System Tests
 */
class InAppMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected InAppMessagingService $messagingService;

    protected User $worker;

    protected User $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messagingService = app(InAppMessagingService::class);

        // Create test users
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
    public function it_can_start_a_direct_conversation(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals(Conversation::TYPE_DIRECT, $conversation->type);
        $this->assertEquals($this->worker->id, $conversation->worker_id);
        $this->assertEquals($this->business->id, $conversation->business_id);

        // Check participants were created
        $this->assertEquals(2, $conversation->participants()->count());
        $this->assertTrue($conversation->hasParticipant($this->worker->id));
        $this->assertTrue($conversation->hasParticipant($this->business->id));
    }

    /** @test */
    public function it_returns_existing_conversation_for_direct_message(): void
    {
        $conversation1 = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        $conversation2 = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        $this->assertEquals($conversation1->id, $conversation2->id);
    }

    /** @test */
    public function it_can_send_a_message(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        $message = $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Hello, I am interested in your shifts!']
        );

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($conversation->id, $message->conversation_id);
        $this->assertEquals($this->worker->id, $message->from_user_id);
        $this->assertEquals('Hello, I am interested in your shifts!', $message->message);
        $this->assertEquals(Message::TYPE_TEXT, $message->message_type);
    }

    /** @test */
    public function it_validates_message_length(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum length');

        $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => str_repeat('a', 6000)] // Over 5000 char limit
        );
    }

    /** @test */
    public function it_prevents_non_participants_from_sending_messages(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $otherUser = User::factory()->create(['user_type' => 'worker']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a participant');

        $this->messagingService->sendMessage(
            $conversation,
            $otherUser,
            ['body' => 'This should fail']
        );
    }

    /** @test */
    public function it_can_mark_messages_as_read(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        // Send message from worker
        $message = $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Hello!']
        );

        // Business reads the message
        $readCount = $this->messagingService->markAsRead($conversation, $this->business);

        $this->assertEquals(1, $readCount);

        // Verify read receipt was created
        $this->assertTrue(MessageRead::hasRead($message->id, $this->business->id));
    }

    /** @test */
    public function it_can_get_unread_count(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        // Send 3 messages from worker
        for ($i = 0; $i < 3; $i++) {
            $this->messagingService->sendMessage(
                $conversation,
                $this->worker,
                ['body' => "Message $i"]
            );
        }

        $unreadCount = $this->messagingService->getUnreadCount($this->business);
        $this->assertEquals(3, $unreadCount);

        // After reading
        $this->messagingService->markAsRead($conversation, $this->business);
        $unreadCount = $this->messagingService->getUnreadCount($this->business);
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_can_archive_and_unarchive_conversation(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        // Archive
        $this->messagingService->archiveConversation($conversation, $this->worker);
        $conversation->refresh();
        $this->assertTrue($conversation->is_archived);

        // Unarchive
        $this->messagingService->unarchiveConversation($conversation, $this->worker);
        $conversation->refresh();
        $this->assertFalse($conversation->is_archived);
    }

    /** @test */
    public function it_can_leave_conversation(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $result = $this->messagingService->leaveConversation($conversation, $this->business);

        $this->assertTrue($result);

        $participant = $conversation->getParticipant($this->business->id);
        $this->assertNotNull($participant->left_at);
    }

    /** @test */
    public function it_can_search_messages(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Hello, I can work on Monday']
        );

        $this->messagingService->sendMessage(
            $conversation,
            $this->business,
            ['body' => 'Great, see you then!']
        );

        $results = $this->messagingService->searchMessages($this->worker, 'Monday');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Monday', $results->first()->message);
    }

    /** @test */
    public function it_can_get_conversations_with_filters(): void
    {
        // Create direct conversation
        $direct = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        // Create another conversation and archive it
        $otherBusiness = User::factory()->create(['user_type' => 'business']);
        $archived = $this->messagingService->startConversation(
            $this->worker,
            [$otherBusiness->id],
            ['type' => Conversation::TYPE_DIRECT]
        );
        $this->messagingService->archiveConversation($archived, $this->worker);

        // Get active conversations
        $conversations = $this->messagingService->getConversations($this->worker, ['archived' => false]);
        $this->assertCount(1, $conversations);

        // Get archived conversations
        $archived = $this->messagingService->getConversations($this->worker, ['archived' => true]);
        $this->assertCount(1, $archived);
    }

    /** @test */
    public function it_can_edit_own_message(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $message = $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Original message']
        );

        $this->messagingService->editMessage($message, $this->worker, 'Edited message');
        $message->refresh();

        $this->assertEquals('Edited message', $message->message);
        $this->assertTrue($message->is_edited);
        $this->assertNotNull($message->edited_at);
    }

    /** @test */
    public function it_prevents_editing_others_messages(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $message = $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Worker message']
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only the sender can edit');

        $this->messagingService->editMessage($message, $this->business, 'Trying to edit');
    }

    /** @test */
    public function it_can_delete_own_message(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        $message = $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Message to delete']
        );

        $this->messagingService->deleteMessage($message, $this->worker);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    /** @test */
    public function it_creates_system_messages_for_participant_changes(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id]
        );

        // Add a new participant
        $newUser = User::factory()->create(['user_type' => 'worker']);
        $this->messagingService->addParticipant($conversation, $newUser);

        // Check system message was created
        $systemMessage = $conversation->messages()->where('message_type', Message::TYPE_SYSTEM)->first();
        $this->assertNotNull($systemMessage);
        $this->assertStringContainsString('was added', $systemMessage->message);
    }

    /** @test */
    public function it_gets_statistics_for_user(): void
    {
        $conversation = $this->messagingService->startConversation(
            $this->worker,
            [$this->business->id],
            ['type' => Conversation::TYPE_DIRECT]
        );

        $this->messagingService->sendMessage(
            $conversation,
            $this->worker,
            ['body' => 'Test message']
        );

        $stats = $this->messagingService->getStatistics($this->worker);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('unread', $stats);
        $this->assertArrayHasKey('archived', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertEquals(1, $stats['total']);
    }
}
