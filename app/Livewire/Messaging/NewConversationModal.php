<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\User;
use App\Services\InAppMessagingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * COM-001: New Conversation Modal Livewire Component
 *
 * Modal for starting a new conversation with users.
 */
class NewConversationModal extends Component
{
    public bool $show = false;

    public string $search = '';

    public array $selectedUsers = [];

    public string $subject = '';

    public string $initialMessage = '';

    public string $type = Conversation::TYPE_DIRECT;

    public ?int $shiftId = null;

    protected InAppMessagingService $messagingService;

    public function boot(InAppMessagingService $messagingService): void
    {
        $this->messagingService = $messagingService;
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        $currentUser = Auth::user();

        $query = User::where('id', '!=', $currentUser->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            });

        // Filter based on current user type
        if ($currentUser->isWorker()) {
            $query->where('user_type', 'business');
        } elseif ($currentUser->isBusiness()) {
            $query->whereIn('user_type', ['worker', 'agency']);
        } elseif ($currentUser->isAgency()) {
            $query->whereIn('user_type', ['worker', 'business']);
        }

        return $query->take(10)->get();
    }

    public function open(?string $type = null, ?int $shiftId = null): void
    {
        $this->reset(['search', 'selectedUsers', 'subject', 'initialMessage']);
        $this->type = $type ?? Conversation::TYPE_DIRECT;
        $this->shiftId = $shiftId;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function selectUser(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // For direct messages, only allow one recipient
        if ($this->type === Conversation::TYPE_DIRECT) {
            $this->selectedUsers = [$userId => $user->name];
        } else {
            // For group conversations, allow multiple
            $maxParticipants = config("messaging.types.{$this->type}.max_participants", 50);
            if (count($this->selectedUsers) < $maxParticipants - 1) {
                $this->selectedUsers[$userId] = $user->name;
            }
        }

        $this->search = '';
    }

    public function removeUser(int $userId): void
    {
        unset($this->selectedUsers[$userId]);
    }

    public function startConversation(): void
    {
        $this->validate([
            'selectedUsers' => 'required|array|min:1',
            'initialMessage' => 'required|string|max:'.config('messaging.max_message_length', 5000),
            'subject' => 'nullable|string|max:255',
        ]);

        $participantIds = array_keys($this->selectedUsers);

        // Start the conversation
        $conversation = $this->messagingService->startConversation(
            Auth::user(),
            $participantIds,
            [
                'type' => $this->type,
                'subject' => $this->subject ?: null,
                'shift_id' => $this->shiftId,
            ]
        );

        // Send the initial message
        $this->messagingService->sendMessage(
            $conversation,
            Auth::user(),
            ['body' => $this->initialMessage]
        );

        $this->close();

        // Navigate to the new conversation
        $this->dispatch('conversation-created', conversationId: $conversation->id);
        $this->redirect(route('messages.show', $conversation->id));
    }

    public function render()
    {
        return view('livewire.messaging.new-conversation-modal');
    }
}
