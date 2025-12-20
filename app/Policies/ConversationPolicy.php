<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any models.
     * Any authenticated user can view their conversations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * User must be a participant in the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // Admin can view all conversations
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user is a participant (supports both legacy and new participant model)
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can create models.
     * Any authenticated user can create a conversation.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Participants can update (e.g., mark as read, archive for themselves).
     */
    public function update(User $user, Conversation $conversation): bool
    {
        // Admin can update any conversation
        if ($user->isAdmin()) {
            return true;
        }

        // Participants can update (mark as read, archive, etc.)
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can delete the model.
     * Participants can delete/leave, admin can delete any conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        // Admin can delete any conversation
        if ($user->isAdmin()) {
            return true;
        }

        // Participants can delete/leave the conversation
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins can restore deleted conversations.
     */
    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete conversations.
     */
    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can add participants to the conversation.
     * Only conversation creator or admin can add participants.
     */
    public function addParticipant(User $user, Conversation $conversation): bool
    {
        // Admin can always add participants
        if ($user->isAdmin()) {
            return true;
        }

        // Only existing participants can add new participants
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can remove participants from the conversation.
     * Only conversation creator or admin can remove participants.
     */
    public function removeParticipant(User $user, Conversation $conversation): bool
    {
        // Admin can always remove participants
        if ($user->isAdmin()) {
            return true;
        }

        // Participants can remove themselves (leave)
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can archive the conversation.
     */
    public function archive(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id) || $user->isAdmin();
    }

    /**
     * Determine whether the user can close the conversation.
     * Only participants or admin can close.
     */
    public function close(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id) || $user->isAdmin();
    }
}
