<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\TypingIndicator;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * COM-001: In-App Messaging Service
 *
 * Comprehensive messaging system for communication between
 * workers, businesses, and agencies.
 */
class InAppMessagingService
{
    /**
     * Start a new conversation.
     *
     * @param  array<int>  $participantIds
     * @param  array{type?: string, subject?: string, shift_id?: int}  $data
     */
    public function startConversation(User $initiator, array $participantIds, array $data = []): Conversation
    {
        return DB::transaction(function () use ($initiator, $participantIds, $data) {
            $type = $data['type'] ?? Conversation::TYPE_DIRECT;

            // For direct conversations, check if one already exists between these users
            if ($type === Conversation::TYPE_DIRECT && count($participantIds) === 1) {
                $existingConversation = $this->findExistingDirectConversation($initiator->id, $participantIds[0]);
                if ($existingConversation) {
                    return $existingConversation;
                }
            }

            // Determine worker_id and business_id for legacy compatibility
            $workerId = null;
            $businessId = null;

            if ($initiator->isWorker()) {
                $workerId = $initiator->id;
                $businessUser = User::find($participantIds[0]);
                if ($businessUser && $businessUser->isBusiness()) {
                    $businessId = $businessUser->id;
                }
            } elseif ($initiator->isBusiness()) {
                $businessId = $initiator->id;
                $workerUser = User::find($participantIds[0]);
                if ($workerUser && $workerUser->isWorker()) {
                    $workerId = $workerUser->id;
                }
            }

            // Create the conversation
            $conversation = Conversation::create([
                'type' => $type,
                'shift_id' => $data['shift_id'] ?? null,
                'worker_id' => $workerId,
                'business_id' => $businessId,
                'subject' => $data['subject'] ?? null,
                'status' => Conversation::STATUS_ACTIVE,
                'is_archived' => false,
                'last_message_at' => now(),
            ]);

            // Add initiator as owner
            $conversation->participants()->create([
                'user_id' => $initiator->id,
                'role' => ConversationParticipant::ROLE_OWNER,
            ]);

            // Add other participants
            foreach ($participantIds as $participantId) {
                if ($participantId !== $initiator->id) {
                    $conversation->participants()->create([
                        'user_id' => $participantId,
                        'role' => ConversationParticipant::ROLE_PARTICIPANT,
                    ]);
                }
            }

            Log::info('COM-001: New conversation started', [
                'conversation_id' => $conversation->id,
                'initiator_id' => $initiator->id,
                'type' => $type,
                'participant_count' => count($participantIds) + 1,
            ]);

            return $conversation;
        });
    }

    /**
     * Start a conversation for a shift.
     */
    public function startShiftConversation(Shift $shift): Conversation
    {
        // Check if a shift conversation already exists
        $existing = Conversation::where('shift_id', $shift->id)
            ->where('type', Conversation::TYPE_SHIFT)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($shift) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_SHIFT,
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'subject' => "Shift: {$shift->title}",
                'status' => Conversation::STATUS_ACTIVE,
                'is_archived' => false,
                'last_message_at' => now(),
            ]);

            // Add business as owner
            $conversation->participants()->create([
                'user_id' => $shift->business_id,
                'role' => ConversationParticipant::ROLE_OWNER,
            ]);

            // Add all assigned workers as participants
            $assignedWorkerIds = $shift->assignments()
                ->whereIn('status', ['assigned', 'checked_in', 'completed'])
                ->pluck('worker_id');

            foreach ($assignedWorkerIds as $workerId) {
                $conversation->participants()->create([
                    'user_id' => $workerId,
                    'role' => ConversationParticipant::ROLE_PARTICIPANT,
                ]);
            }

            // Create a system message
            Message::createSystemMessage(
                $conversation,
                "Conversation started for shift: {$shift->title}",
                ['shift_id' => $shift->id]
            );

            Log::info('COM-001: Shift conversation started', [
                'conversation_id' => $conversation->id,
                'shift_id' => $shift->id,
            ]);

            return $conversation;
        });
    }

    /**
     * Send a message in a conversation.
     *
     * @param  array{body: string, type?: string, attachments?: array, metadata?: array}  $data
     */
    public function sendMessage(Conversation $conversation, User $sender, array $data): Message
    {
        // Validate sender is a participant
        if (! $conversation->hasParticipant($sender->id)) {
            throw new \InvalidArgumentException('Sender is not a participant in this conversation');
        }

        // Validate message length
        $maxLength = config('messaging.max_message_length', 5000);
        if (strlen($data['body']) > $maxLength) {
            throw new \InvalidArgumentException("Message exceeds maximum length of {$maxLength} characters");
        }

        // Validate attachments
        $attachments = $data['attachments'] ?? [];
        $maxAttachments = config('messaging.max_attachments', 5);
        if (count($attachments) > $maxAttachments) {
            throw new \InvalidArgumentException("Maximum {$maxAttachments} attachments allowed");
        }

        return DB::transaction(function () use ($conversation, $sender, $data, $attachments) {
            // Determine recipient for legacy direct conversations
            $recipientId = 0;
            if ($conversation->isDirect()) {
                $other = $conversation->getOtherParticipant($sender->id);
                $recipientId = $other ? $other->id : 0;
            }

            // Create the message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'from_user_id' => $sender->id,
                'to_user_id' => $recipientId,
                'message' => $data['body'],
                'message_type' => $data['type'] ?? Message::TYPE_TEXT,
                'attachments' => $attachments,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Update conversation timestamp
            $conversation->update(['last_message_at' => now()]);

            // Broadcast the message to other participants
            $this->broadcastMessage($conversation, $message);

            Log::info('COM-001: Message sent', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
            ]);

            return $message;
        });
    }

    /**
     * Mark a conversation as read for a user.
     */
    public function markAsRead(Conversation $conversation, User $user): int
    {
        // Get participant record
        $participant = $conversation->getParticipant($user->id);
        if (! $participant) {
            return 0;
        }

        // Get unread message IDs
        $unreadQuery = $conversation->messages()
            ->where('from_user_id', '!=', $user->id);

        if ($participant->last_read_at) {
            $unreadQuery->where('created_at', '>', $participant->last_read_at);
        }

        $unreadMessageIds = $unreadQuery->pluck('id')->toArray();

        if (empty($unreadMessageIds)) {
            return 0;
        }

        // Record reads for all unread messages
        $readCount = MessageRead::recordBulkRead($unreadMessageIds, $user->id);

        // Update participant's last_read_at
        $participant->markAsRead();

        // Update legacy is_read field for direct conversations
        if ($conversation->isDirect()) {
            $conversation->messages()
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        // Broadcast read receipt
        $this->broadcastReadReceipt($conversation, $user, $readCount);

        return $readCount;
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        // Get all conversations the user participates in
        $conversationIds = ConversationParticipant::where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('conversation_id');

        // Also include legacy conversations
        $legacyConversationIds = Conversation::where(function ($q) use ($user) {
            $q->where('worker_id', $user->id)
                ->orWhere('business_id', $user->id);
        })->pluck('id');

        $allConversationIds = $conversationIds->merge($legacyConversationIds)->unique();

        if ($allConversationIds->isEmpty()) {
            return 0;
        }

        // Count unread messages (not sent by user, not read)
        return Message::whereIn('conversation_id', $allConversationIds)
            ->where('from_user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }

    /**
     * Archive a conversation for a user.
     */
    public function archiveConversation(Conversation $conversation, User $user): bool
    {
        if (! $conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        // For multi-participant, we could track per-user archive status
        // For now, archive the whole conversation
        return $conversation->archive();
    }

    /**
     * Unarchive a conversation for a user.
     */
    public function unarchiveConversation(Conversation $conversation, User $user): bool
    {
        if (! $conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        return $conversation->unarchive();
    }

    /**
     * Leave a conversation.
     */
    public function leaveConversation(Conversation $conversation, User $user): bool
    {
        $participant = $conversation->getParticipant($user->id);
        if (! $participant) {
            return false;
        }

        // Can't leave if you're the only owner
        if ($participant->isOwner()) {
            $otherOwners = $conversation->participants()
                ->where('user_id', '!=', $user->id)
                ->where('role', ConversationParticipant::ROLE_OWNER)
                ->count();

            if ($otherOwners === 0) {
                // Transfer ownership to another participant
                $newOwner = $conversation->participants()
                    ->where('user_id', '!=', $user->id)
                    ->whereNull('left_at')
                    ->first();

                if ($newOwner) {
                    $newOwner->update(['role' => ConversationParticipant::ROLE_OWNER]);
                }
            }
        }

        $participant->leave();

        // Create system message
        Message::createSystemMessage(
            $conversation,
            "{$user->name} left the conversation",
            ['user_id' => $user->id, 'action' => 'left']
        );

        Log::info('COM-001: User left conversation', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Search messages for a user.
     *
     * @return LengthAwarePaginator<Message>
     */
    public function searchMessages(User $user, string $query, int $perPage = 20): LengthAwarePaginator
    {
        // Get conversations the user participates in
        $conversationIds = $this->getUserConversationIds($user);

        return Message::whereIn('conversation_id', $conversationIds)
            ->where('message', 'LIKE', "%{$query}%")
            ->with(['conversation', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get conversations for a user.
     *
     * @return LengthAwarePaginator<Conversation>
     */
    public function getConversations(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Conversation::forUser($user->id)
            ->with(['lastMessage', 'participants.user', 'worker', 'business'])
            ->orderBy('last_message_at', 'desc');

        // Apply filters
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['archived'])) {
            $query->where('is_archived', $filters['archived']);
        } else {
            $query->where('is_archived', false);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get messages for a conversation.
     *
     * @return LengthAwarePaginator<Message>
     */
    public function getMessages(Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with(['sender', 'reads'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add a participant to a conversation.
     */
    public function addParticipant(Conversation $conversation, User $user, string $role = ConversationParticipant::ROLE_PARTICIPANT): ConversationParticipant
    {
        $participant = $conversation->addParticipant($user, $role);

        // Create system message
        Message::createSystemMessage(
            $conversation,
            "{$user->name} was added to the conversation",
            ['user_id' => $user->id, 'action' => 'added']
        );

        return $participant;
    }

    /**
     * Edit a message.
     */
    public function editMessage(Message $message, User $user, string $newContent): bool
    {
        // Only the sender can edit their message
        if ($message->from_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the sender can edit this message');
        }

        // Can't edit system messages
        if ($message->isSystem()) {
            throw new \InvalidArgumentException('System messages cannot be edited');
        }

        // Check edit time limit (e.g., 15 minutes)
        $editTimeLimit = config('messaging.edit_time_limit', 15);
        if ($message->created_at->diffInMinutes(now()) > $editTimeLimit) {
            throw new \InvalidArgumentException("Messages can only be edited within {$editTimeLimit} minutes");
        }

        return $message->edit($newContent);
    }

    /**
     * Delete a message (soft delete).
     */
    public function deleteMessage(Message $message, User $user): bool
    {
        // Only the sender can delete their message
        if ($message->from_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the sender can delete this message');
        }

        return $message->delete();
    }

    /**
     * Upload an attachment.
     *
     * @return array{url: string, type: string, name: string, size: int}
     */
    public function uploadAttachment($file): array
    {
        $maxSize = config('messaging.max_attachment_size', 10 * 1024 * 1024);
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File exceeds maximum size limit');
        }

        $allowedTypes = config('messaging.allowed_file_types', ['pdf', 'doc', 'docx', 'jpg', 'png', 'gif']);
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedTypes)) {
            throw new \InvalidArgumentException('File type not allowed');
        }

        $path = $file->store('message-attachments', 'public');

        return [
            'url' => Storage::url($path),
            'type' => $extension,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Broadcast typing indicator.
     */
    public function broadcastTyping(Conversation $conversation, User $user, bool $isTyping): void
    {
        try {
            broadcast(new TypingIndicator($conversation, $user, $isTyping))->toOthers();
        } catch (\Exception $e) {
            Log::warning('COM-001: Failed to broadcast typing indicator', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find an existing direct conversation between two users.
     */
    protected function findExistingDirectConversation(int $userId1, int $userId2): ?Conversation
    {
        // Check via participants table
        $conversation = Conversation::where('type', Conversation::TYPE_DIRECT)
            ->whereHas('participants', function ($q) use ($userId1) {
                $q->where('user_id', $userId1)->whereNull('left_at');
            })
            ->whereHas('participants', function ($q) use ($userId2) {
                $q->where('user_id', $userId2)->whereNull('left_at');
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Check legacy worker/business columns
        return Conversation::where(function ($q) use ($userId1, $userId2) {
            $q->where('worker_id', $userId1)->where('business_id', $userId2);
        })->orWhere(function ($q) use ($userId1, $userId2) {
            $q->where('worker_id', $userId2)->where('business_id', $userId1);
        })->first();
    }

    /**
     * Get all conversation IDs for a user.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function getUserConversationIds(User $user): \Illuminate\Support\Collection
    {
        $participantIds = ConversationParticipant::where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('conversation_id');

        $legacyIds = Conversation::where(function ($q) use ($user) {
            $q->where('worker_id', $user->id)
                ->orWhere('business_id', $user->id);
        })->pluck('id');

        return $participantIds->merge($legacyIds)->unique();
    }

    /**
     * Broadcast a new message to conversation participants.
     */
    protected function broadcastMessage(Conversation $conversation, Message $message): void
    {
        try {
            broadcast(new MessageSent($conversation, $message))->toOthers();
        } catch (\Exception $e) {
            Log::warning('COM-001: Failed to broadcast message', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);
        }
    }

    /**
     * Broadcast read receipt.
     */
    protected function broadcastReadReceipt(Conversation $conversation, User $user, int $count): void
    {
        try {
            broadcast(new MessagesRead($conversation, $user, $count))->toOthers();
        } catch (\Exception $e) {
            Log::warning('COM-001: Failed to broadcast read receipt', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get conversation statistics for a user.
     *
     * @return array{total: int, unread: int, archived: int, by_type: array}
     */
    public function getStatistics(User $user): array
    {
        $conversationIds = $this->getUserConversationIds($user);

        return [
            'total' => $conversationIds->count(),
            'unread' => $this->getUnreadCount($user),
            'archived' => Conversation::whereIn('id', $conversationIds)
                ->where('is_archived', true)
                ->count(),
            'by_type' => Conversation::whereIn('id', $conversationIds)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }
}
