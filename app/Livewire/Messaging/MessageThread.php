<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\InAppMessagingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * COM-001: Message Thread Livewire Component
 *
 * Displays messages for a conversation and handles sending new messages.
 */
class MessageThread extends Component
{
    use WithFileUploads, WithPagination;

    public ?Conversation $conversation = null;

    public string $messageBody = '';

    public array $attachments = [];

    public bool $isTyping = false;

    public array $typingUsers = [];

    protected InAppMessagingService $messagingService;

    public function boot(InAppMessagingService $messagingService): void
    {
        $this->messagingService = $messagingService;
    }

    public function mount(?int $conversationId = null): void
    {
        if ($conversationId) {
            $this->loadConversation($conversationId);
        }
    }

    public function loadConversation(int $conversationId): void
    {
        $conversation = Conversation::with(['participants.user', 'worker', 'business'])->find($conversationId);

        if ($conversation && $conversation->hasParticipant(Auth::id())) {
            $this->conversation = $conversation;
            $this->markAsRead();
        }
    }

    #[Computed]
    public function messages()
    {
        if (! $this->conversation) {
            return collect();
        }

        return $this->conversation->messages()
            ->with(['sender', 'reads'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    #[Computed]
    public function otherParticipants()
    {
        if (! $this->conversation) {
            return collect();
        }

        return $this->conversation->getOtherParticipants(Auth::id());
    }

    public function sendMessage(): void
    {
        $this->validate([
            'messageBody' => 'required|string|max:'.config('messaging.max_message_length', 5000),
            'attachments.*' => 'nullable|file|max:'.(config('messaging.max_attachment_size', 10485760) / 1024),
        ]);

        if (! $this->conversation) {
            return;
        }

        // Process attachments
        $attachmentData = [];
        foreach ($this->attachments as $file) {
            $attachmentData[] = $this->messagingService->uploadAttachment($file);
        }

        // Send the message
        $this->messagingService->sendMessage(
            $this->conversation,
            Auth::user(),
            [
                'body' => $this->messageBody,
                'type' => empty($attachmentData) ? Message::TYPE_TEXT : Message::TYPE_FILE,
                'attachments' => $attachmentData,
            ]
        );

        // Reset form
        $this->messageBody = '';
        $this->attachments = [];

        // Refresh messages
        unset($this->messages);

        // Notify other components
        $this->dispatch('message-sent');
    }

    public function markAsRead(): void
    {
        if ($this->conversation) {
            $this->messagingService->markAsRead($this->conversation, Auth::user());
        }
    }

    public function editMessage(int $messageId, string $newContent): void
    {
        $message = Message::find($messageId);
        if ($message && $message->from_user_id === Auth::id()) {
            try {
                $this->messagingService->editMessage($message, Auth::user(), $newContent);
                unset($this->messages);
            } catch (\InvalidArgumentException $e) {
                $this->addError('edit', $e->getMessage());
            }
        }
    }

    public function deleteMessage(int $messageId): void
    {
        $message = Message::find($messageId);
        if ($message && $message->from_user_id === Auth::id()) {
            try {
                $this->messagingService->deleteMessage($message, Auth::user());
                unset($this->messages);
            } catch (\InvalidArgumentException $e) {
                $this->addError('delete', $e->getMessage());
            }
        }
    }

    public function typing(): void
    {
        if ($this->conversation && config('messaging.typing_indicators', true)) {
            $this->messagingService->broadcastTyping($this->conversation, Auth::user(), true);
        }
    }

    public function stoppedTyping(): void
    {
        if ($this->conversation && config('messaging.typing_indicators', true)) {
            $this->messagingService->broadcastTyping($this->conversation, Auth::user(), false);
        }
    }

    /**
     * Get the listeners for the component.
     * Dynamic Echo listeners are registered only when a conversation is selected.
     */
    public function getListeners(): array
    {
        $listeners = [
            'conversation-selected' => 'loadConversation',
            'new-message-received' => 'handleNewMessage',
        ];

        // Only register Echo listeners if we have a valid conversation
        if ($this->conversation) {
            $conversationId = $this->conversation->id;
            $listeners["echo-private:conversation.{$conversationId},message.sent"] = 'handleNewMessage';
            $listeners["echo-private:conversation.{$conversationId},user.typing"] = 'handleTypingIndicator';
            $listeners["echo-private:conversation.{$conversationId},messages.read"] = 'handleReadReceipt';
        }

        return $listeners;
    }

    public function handleNewMessage(array $event = []): void
    {
        // Only refresh if the message is from someone else
        if (empty($event) || (isset($event['sender']['id']) && $event['sender']['id'] !== Auth::id())) {
            unset($this->messages);
            $this->markAsRead();
        }
    }

    public function handleTypingIndicator(array $event): void
    {
        if ($event['user_id'] !== Auth::id()) {
            if ($event['is_typing']) {
                $this->typingUsers[$event['user_id']] = $event['user_name'];
            } else {
                unset($this->typingUsers[$event['user_id']]);
            }
        }
    }

    public function handleReadReceipt(): void
    {
        unset($this->messages);
    }

    public function getMessageClass(Message $message): string
    {
        if ($message->isSystem()) {
            return 'message-system';
        }

        return $message->from_user_id === Auth::id() ? 'message-sent' : 'message-received';
    }

    public function render()
    {
        return view('livewire.messaging.message-thread');
    }
}
