<?php

namespace App\Mail;

use App\Models\AgencyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * AGY-REG-004: Agency Worker Invitation Mail
 *
 * Fallback mailable for sending agency worker invitations
 * when notification system is not available.
 */
class AgencyWorkerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public AgencyInvitation $invitation;
    public string $agencyName;
    public string $invitationUrl;
    public string $expiryDate;

    /**
     * Create a new message instance.
     */
    public function __construct(AgencyInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->agencyName = $invitation->agency->agencyProfile->agency_name ?? $invitation->agency->name;
        $this->invitationUrl = $invitation->getInvitationUrl();
        $this->expiryDate = $invitation->expires_at->format('F j, Y');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->agencyName} on OvertimeStaff",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.agency-worker-invitation',
            with: [
                'invitation' => $this->invitation,
                'agencyName' => $this->agencyName,
                'invitationUrl' => $this->invitationUrl,
                'expiryDate' => $this->expiryDate,
                'recipientName' => $this->invitation->name ?? 'there',
                'personalMessage' => $this->invitation->personal_message,
                'commissionRate' => $this->invitation->preset_commission_rate,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
