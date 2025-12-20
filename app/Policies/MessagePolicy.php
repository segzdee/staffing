<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Determine whether the user can view any models.
     * User can view their own messages (messages where they are sender or recipient).
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view their messages list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * User must be a participant in the conversation (sender or recipient).
     */
    public function view(User $user, Message $message): bool
    {
        // User is the sender
        if ($user->id === $message->from_user_id) {
            return true;
        }

        // User is the recipient
        if ($user->id === $message->to_user_id) {
            return true;
        }

        // User is a participant in the conversation
        if ($message->conversation) {
            return $message->conversation->hasParticipant($user->id);
        }

        // Admin can view all messages
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * User must be a participant in the conversation.
     */
    public function create(User $user, ?Conversation $conversation = null): bool
    {
        // If no conversation specified, any authenticated user can start a conversation
        if ($conversation === null) {
            return true;
        }

        // Check if user is a participant in the conversation
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can update the model.
     * Only the sender can update (edit) their message.
     */
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->from_user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Sender can delete their own messages, admin can delete any message.
     */
    public function delete(User $user, Message $message): bool
    {
        // Admin can delete any message
        if ($user->isAdmin()) {
            return true;
        }

        // Sender can delete their own message
        return $user->id === $message->from_user_id;
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins can restore deleted messages.
     */
    public function restore(User $user, Message $message): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete messages.
     */
    public function forceDelete(User $user, Message $message): bool
    {
        return $user->isAdmin();
    }
}
