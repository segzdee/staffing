<?php

namespace App\Notifications;

use App\Models\AdminDisputeQueue;
use App\Models\DisputeMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeMessageNotification
 *
 * Notifies all parties when a new message is posted in a dispute thread.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * Channels: Database, Mail
 */
class DisputeMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute.
     *
     * @var AdminDisputeQueue
     */
    protected $dispute;

    /**
     * The message that was posted.
     *
     * @var DisputeMessage
     */
    protected $message;

    /**
     * Create a new notification instance.
     *
     * @param AdminDisputeQueue $dispute
     * @param DisputeMessage $message
     * @return void
     */
    public function __construct(AdminDisputeQueue $dispute, DisputeMessage $message)
    {
        $this->dispute = $dispute;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $senderLabel = $this->message->getSenderTypeLabel();
        $messagePreview = substr($this->message->message, 0, 300);

        if (strlen($this->message->message) > 300) {
            $messagePreview .= '...';
        }

        $mailMessage = (new MailMessage)
            ->subject("New Message in Dispute #{$this->dispute->id}")
            ->greeting("New Dispute Message")
            ->line("A new message has been posted in dispute #{$this->dispute->id}.")
            ->line("")
            ->line("**From:** {$senderLabel}")
            ->line("**Message Type:** " . ucfirst($this->message->message_type))
            ->line("**Posted:** {$this->message->created_at->format('M d, Y g:ia')}")
            ->line("")
            ->line("**Message:**")
            ->line($messagePreview);

        // Add attachment info if present
        if ($this->message->hasAttachments()) {
            $attachmentCount = $this->message->getAttachmentCount();
            $mailMessage->line("")
                ->line("**Attachments:** {$attachmentCount} file(s) attached");
        }

        $mailMessage
            ->line("")
            ->action('View Full Thread', $this->getActionUrl($notifiable))
            ->line("")
            ->line("Please respond to continue the conversation.");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'dispute_message',
            'dispute_id' => $this->dispute->id,
            'message_id' => $this->message->id,
            'message_type' => $this->message->message_type,
            'sender_type' => $this->message->getSenderTypeLabel(),
            'sender_id' => $this->message->sender_id,
            'message_preview' => substr($this->message->message, 0, 100),
            'has_attachments' => $this->message->hasAttachments(),
            'attachment_count' => $this->message->getAttachmentCount(),
            'action_url' => $this->getActionUrl($notifiable),
        ];
    }

    /**
     * Get the appropriate action URL based on user type.
     *
     * @param mixed $notifiable
     * @return string
     */
    protected function getActionUrl($notifiable): string
    {
        // Admin gets admin panel link
        if ($notifiable->role === 'admin') {
            return url("/panel/admin/disputes/{$this->dispute->id}");
        }

        // Worker gets worker portal link
        if ($notifiable->id === $this->dispute->worker_id) {
            return url("/worker/disputes/{$this->dispute->id}");
        }

        // Business gets business portal link
        if ($notifiable->id === $this->dispute->business_id) {
            return url("/business/disputes/{$this->dispute->id}");
        }

        // Default fallback
        return url("/disputes/{$this->dispute->id}");
    }
}
