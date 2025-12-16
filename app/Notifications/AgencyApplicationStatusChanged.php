<?php

namespace App\Notifications;

use App\Models\AgencyApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for agency application status changes
 * AGY-REG-003
 */
class AgencyApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The agency application instance.
     *
     * @var AgencyApplication
     */
    protected $application;

    /**
     * The notification type.
     *
     * @var string
     */
    protected $notificationType;

    /**
     * Notification type configurations.
     *
     * @var array
     */
    protected const NOTIFICATION_CONFIGS = [
        'submitted' => [
            'subject' => 'Application Received - {company}',
            'greeting' => 'Application Submitted Successfully',
            'color' => 'blue',
        ],
        'documents_verified' => [
            'subject' => 'Documents Verified - {company}',
            'greeting' => 'Your Documents Have Been Verified',
            'color' => 'green',
        ],
        'documents_rejected' => [
            'subject' => 'Documents Need Attention - {company}',
            'greeting' => 'Some Documents Need Your Attention',
            'color' => 'orange',
        ],
        'compliance_started' => [
            'subject' => 'Compliance Review Started - {company}',
            'greeting' => 'Your Compliance Review Has Started',
            'color' => 'blue',
        ],
        'compliance_approved' => [
            'subject' => 'Compliance Approved - {company}',
            'greeting' => 'Your Compliance Review Has Passed',
            'color' => 'green',
        ],
        'approved' => [
            'subject' => 'Congratulations! Application Approved - {company}',
            'greeting' => 'Your Agency Application Has Been Approved!',
            'color' => 'green',
        ],
        'rejected' => [
            'subject' => 'Application Update - {company}',
            'greeting' => 'Application Status Update',
            'color' => 'red',
        ],
        'reviewer_assigned' => [
            'subject' => 'Review In Progress - {company}',
            'greeting' => 'A Reviewer Has Been Assigned',
            'color' => 'blue',
        ],
    ];

    /**
     * Create a new notification instance.
     *
     * @param AgencyApplication $application
     * @param string $notificationType
     */
    public function __construct(AgencyApplication $application, string $notificationType)
    {
        $this->application = $application;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $config = self::NOTIFICATION_CONFIGS[$this->notificationType] ?? [
            'subject' => 'Application Update - {company}',
            'greeting' => 'Application Status Update',
            'color' => 'blue',
        ];

        $subject = str_replace('{company}', $this->application->agency_name, $config['subject']);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . ($notifiable->name ?? 'Applicant') . ',')
            ->line($config['greeting']);

        // Add type-specific content
        switch ($this->notificationType) {
            case 'submitted':
                $mail->line('We have received your agency registration application for ' . $this->application->agency_name . '.')
                     ->line('Our team will review your application and documents within 3-5 business days.')
                     ->line('You will receive email updates as your application progresses through our review process.')
                     ->action('View Application Status', $this->getApplicationUrl());
                break;

            case 'documents_verified':
                $mail->line('Great news! All your submitted documents have been verified.')
                     ->line('Your application is now moving to the compliance review stage.')
                     ->line('This typically takes 2-3 business days.')
                     ->action('View Application Status', $this->getApplicationUrl());
                break;

            case 'documents_rejected':
                $rejectedDocs = $this->getRejectedDocuments();
                $mail->line('Some of your submitted documents require attention:')
                     ->line('');

                if (!empty($rejectedDocs)) {
                    foreach ($rejectedDocs as $doc) {
                        $mail->line('- ' . $doc['type'] . ': ' . ($doc['reason'] ?? 'Please resubmit'));
                    }
                }

                $mail->line('')
                     ->line('Please log in to your account to view the specific issues and upload corrected documents.')
                     ->action('Update Documents', $this->getApplicationUrl());
                break;

            case 'compliance_started':
                $mail->line('Your application has entered the compliance review phase.')
                     ->line('We are conducting necessary background and business verification checks.')
                     ->line('This process typically takes 3-5 business days.')
                     ->action('View Application Status', $this->getApplicationUrl());
                break;

            case 'compliance_approved':
                $mail->line('Your agency has passed all compliance checks.')
                     ->line('Your application is now in the final review stage.')
                     ->action('View Application Status', $this->getApplicationUrl());
                break;

            case 'approved':
                $mail->line('Congratulations! Your agency application for ' . $this->application->agency_name . ' has been approved.')
                     ->line('You can now access all agency features on the platform.')
                     ->line('')
                     ->line('Next steps:')
                     ->line('1. Complete your agency profile setup')
                     ->line('2. Add your team members')
                     ->line('3. Start managing workers and shifts')
                     ->line('')
                     ->action('Go to Agency Dashboard', $this->getDashboardUrl());
                break;

            case 'rejected':
                $mail->line('After careful review, we are unable to approve your agency application at this time.');

                if ($this->application->rejection_reason) {
                    $mail->line('')
                         ->line('Reason: ' . $this->application->rejection_reason);
                }

                $mail->line('')
                     ->line('If you believe this decision was made in error or have questions, please contact our support team.')
                     ->action('Contact Support', $this->getSupportUrl());
                break;

            case 'reviewer_assigned':
                $mail->line('Good news! A reviewer has been assigned to your application.')
                     ->line('They will be reviewing your documents and compliance information.')
                     ->line('You may be contacted if additional information is needed.')
                     ->action('View Application Status', $this->getApplicationUrl());
                break;

            default:
                $mail->line('There has been an update to your agency application.')
                     ->line('Current status: ' . $this->application->getStatusLabel())
                     ->action('View Application', $this->getApplicationUrl());
        }

        $mail->line('')
             ->line('Thank you for choosing ' . config('app.name') . '!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $config = self::NOTIFICATION_CONFIGS[$this->notificationType] ?? [];

        return [
            'type' => 'agency_application_status_changed',
            'notification_type' => $this->notificationType,
            'application_id' => $this->application->id,
            'agency_name' => $this->application->agency_name,
            'status' => $this->application->status,
            'status_label' => $this->application->getStatusLabel(),
            'title' => $config['greeting'] ?? 'Application Status Update',
            'message' => $this->getShortMessage(),
            'action_url' => $this->getApplicationUrl(),
            'rejection_reason' => $this->notificationType === 'rejected'
                ? $this->application->rejection_reason
                : null,
        ];
    }

    /**
     * Get a short message for the notification.
     *
     * @return string
     */
    protected function getShortMessage(): string
    {
        return match ($this->notificationType) {
            'submitted' => 'Your application has been submitted and is under review.',
            'documents_verified' => 'All your documents have been verified.',
            'documents_rejected' => 'Some documents need your attention.',
            'compliance_started' => 'Compliance review has started.',
            'compliance_approved' => 'Compliance checks have passed.',
            'approved' => 'Congratulations! Your agency has been approved.',
            'rejected' => 'Your application was not approved.',
            'reviewer_assigned' => 'A reviewer has been assigned to your application.',
            default => 'Your application status has been updated.',
        };
    }

    /**
     * Get rejected documents list.
     *
     * @return array
     */
    protected function getRejectedDocuments(): array
    {
        $rejected = [];

        if ($this->application->relationLoaded('documents')) {
            $rejectedDocs = $this->application->documents->where('status', 'rejected');
        } else {
            $rejectedDocs = $this->application->documents()->where('status', 'rejected')->get();
        }

        foreach ($rejectedDocs as $doc) {
            $rejected[] = [
                'type' => $doc->getDocumentTypeLabel(),
                'reason' => $doc->reviewer_notes,
            ];
        }

        return $rejected;
    }

    /**
     * Get the application status URL.
     *
     * @return string
     */
    protected function getApplicationUrl(): string
    {
        return route('agency.application.show', $this->application->id);
    }

    /**
     * Get the agency dashboard URL.
     *
     * @return string
     */
    protected function getDashboardUrl(): string
    {
        return route('agency.dashboard');
    }

    /**
     * Get the support URL.
     *
     * @return string
     */
    protected function getSupportUrl(): string
    {
        return route('contact');
    }
}
