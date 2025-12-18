<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Services\InAppMessagingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * COM-001: Conversation List Livewire Component
 *
 * Displays the list of conversations for the current user.
 */
class ConversationList extends Component
{
    use WithPagination;

    public ?int $selectedConversationId = null;

    public string $filter = 'all';

    public string $search = '';

    public bool $showArchived = false;

    protected InAppMessagingService $messagingService;

    public function boot(InAppMessagingService $messagingService): void
    {
        $this->messagingService = $messagingService;
    }

    public function mount(?int $conversationId = null): void
    {
        $this->selectedConversationId = $conversationId;
    }

    #[Computed]
    public function conversations()
    {
        $user = Auth::user();

        $query = Conversation::forUser($user->id)
            ->with([
                'lastMessage.sender:id,name,email',
                'worker:id,name,email,user_type',
                'business:id,name,email,user_type',
                'participants.user:id,name,email,user_type',
            ])
            ->where('is_archived', $this->showArchived)
            ->orderBy('last_message_at', 'desc');

        if ($this->filter !== 'all') {
            $query->where('type', $this->filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhereHas('messages', function ($mq) {
                        $mq->where('message', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return $this->messagingService->getUnreadCount(Auth::user());
    }

    #[Computed]
    public function statistics(): array
    {
        return $this->messagingService->getStatistics(Auth::user());
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->resetPage();
    }

    public function archiveConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->messagingService->archiveConversation($conversation, Auth::user());

        if ($this->selectedConversationId === $conversationId) {
            $this->selectedConversationId = null;
        }
    }

    public function unarchiveConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->messagingService->unarchiveConversation($conversation, Auth::user());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('message-sent')]
    #[On('refresh-conversations')]
    public function refreshList(): void
    {
        unset($this->conversations);
        unset($this->unreadCount);
    }

    public function getDisplayName(Conversation $conversation): string
    {
        return $conversation->getDisplayNameFor(Auth::id());
    }

    public function getUnreadCountFor(Conversation $conversation): int
    {
        return $conversation->getUnreadCountFor(Auth::id());
    }

    public function render()
    {
        return view('livewire.messaging.conversation-list');
    }
}
