<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * New Message Notification
 * Sent when a user receives a new message
 */
class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Message $message;

    protected User $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->sender->name ?? 'Someone';
        $preview = \Illuminate\Support\Str::limit($this->message->message ?? '', 100);

        return (new MailMessage)
            ->subject("New message from {$senderName}")
            ->greeting('You have a new message')
            ->line("{$senderName} sent you a message:")
            ->line("\"{$preview}\"")
            ->action('View Message', url("/messages/{$this->message->conversation_id}"))
            ->line('Reply to continue the conversation.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'title' => 'New Message',
            'message' => "{$this->sender->name} sent you a message",
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'preview' => \Illuminate\Support\Str::limit($this->message->message ?? '', 50),
            'action_url' => url("/messages/{$this->message->conversation_id}"),
        ];
    }
}
