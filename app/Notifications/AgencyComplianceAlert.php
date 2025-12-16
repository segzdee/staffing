<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * AgencyComplianceAlert Notification
 *
 * Sent to agency when compliance issues are detected (expiring documents, score drop, etc).
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 */
class AgencyComplianceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyProfile $agency;
    protected string $alertType;
    protected array $alertData;

    /**
     * Alert types:
     * - document_expiring: A document is about to expire
     * - score_dropped: Compliance score dropped below threshold
     * - license_expired: Business license has expired
     * - insurance_expired: Insurance coverage has expired
     * - review_required: Manual review is required
     */
    public const ALERT_DOCUMENT_EXPIRING = 'document_expiring';
    public const ALERT_SCORE_DROPPED = 'score_dropped';
    public const ALERT_LICENSE_EXPIRED = 'license_expired';
    public const ALERT_INSURANCE_EXPIRED = 'insurance_expired';
    public const ALERT_REVIEW_REQUIRED = 'review_required';

    /**
     * Create a new notification instance.
     *
     * @param AgencyProfile $agency
     * @param string $alertType
     * @param array $alertData
     */
    public function __construct(AgencyProfile $agency, string $alertType, array $alertData = [])
    {
        $this->agency = $agency;
        $this->alertType = $alertType;
        $this->alertData = $alertData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        // Use all channels for urgent alerts
        $urgentTypes = [self::ALERT_LICENSE_EXPIRED, self::ALERT_INSURANCE_EXPIRED];

        if (in_array($this->alertType, $urgentTypes)) {
            return ['mail', 'database'];
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage;

        switch ($this->alertType) {
            case self::ALERT_DOCUMENT_EXPIRING:
                $documentType = $this->alertData['document_type'] ?? 'Document';
                $daysRemaining = $this->alertData['days_remaining'] ?? 0;

                $message
                    ->subject('Action Required: ' . ucfirst(str_replace('_', ' ', $documentType)) . ' Expiring Soon')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your **' . str_replace('_', ' ', $documentType) . '** for ' . $this->agency->agency_name . ' will expire in **' . $daysRemaining . ' days**.')
                    ->line('Please upload updated documentation to maintain your active status on OvertimeStaff.')
                    ->action('Update Documents', route('agency.profile.edit'))
                    ->line('Failure to update before expiration may result in account restrictions.');
                break;

            case self::ALERT_SCORE_DROPPED:
                $currentScore = $this->alertData['current_score'] ?? 0;
                $previousScore = $this->alertData['previous_score'] ?? 0;
                $threshold = $this->alertData['threshold'] ?? 60;

                $message
                    ->subject('Compliance Score Alert: ' . $this->agency->agency_name)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your compliance score for **' . $this->agency->agency_name . '** has dropped.')
                    ->line('- Previous Score: ' . $previousScore . '%')
                    ->line('- Current Score: ' . $currentScore . '%')
                    ->line('- Minimum Required: ' . $threshold . '%')
                    ->line('Please review your compliance requirements and take action to improve your score.')
                    ->action('View Compliance Status', route('agency.go-live.checklist'))
                    ->line('Maintaining a compliance score above ' . $threshold . '% is required to remain active on the platform.');
                break;

            case self::ALERT_LICENSE_EXPIRED:
                $message
                    ->subject('URGENT: Business License Expired - ' . $this->agency->agency_name)
                    ->greeting('Urgent Action Required!')
                    ->line('The business license for **' . $this->agency->agency_name . '** has expired.')
                    ->line('**Your account will be restricted until a valid license is provided.**')
                    ->line('Please upload your renewed business license immediately to avoid service interruption.')
                    ->action('Update License', route('agency.profile.edit'))
                    ->line('If you have questions about license requirements, please contact our support team.');
                break;

            case self::ALERT_INSURANCE_EXPIRED:
                $message
                    ->subject('URGENT: Insurance Coverage Expired - ' . $this->agency->agency_name)
                    ->greeting('Urgent Action Required!')
                    ->line('The insurance coverage for **' . $this->agency->agency_name . '** has expired.')
                    ->line('**Your account will be restricted until valid insurance documentation is provided.**')
                    ->line('Please upload your renewed insurance certificate immediately to avoid service interruption.')
                    ->action('Update Insurance', route('agency.profile.edit'))
                    ->line('Minimum coverage requirements: General Liability $1,000,000, Workers Compensation as required by law.');
                break;

            case self::ALERT_REVIEW_REQUIRED:
                $reason = $this->alertData['reason'] ?? 'routine review';

                $message
                    ->subject('Compliance Review Required: ' . $this->agency->agency_name)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('A compliance review is required for **' . $this->agency->agency_name . '**.')
                    ->line('**Reason:** ' . ucfirst($reason))
                    ->line('Please review your compliance status and address any outstanding items.')
                    ->action('View Compliance', route('agency.go-live.checklist'))
                    ->line('Our compliance team may reach out for additional information if needed.');
                break;

            default:
                $message
                    ->subject('Compliance Notice: ' . $this->agency->agency_name)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('This is a compliance notice for your agency **' . $this->agency->agency_name . '**.')
                    ->line('Please review your compliance status on the platform.')
                    ->action('View Compliance', route('agency.go-live.checklist'));
        }

        return $message->salutation('OvertimeStaff Compliance Team');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        $messages = [
            self::ALERT_DOCUMENT_EXPIRING => 'Document expiring in ' . ($this->alertData['days_remaining'] ?? 0) . ' days for ' . $this->agency->agency_name,
            self::ALERT_SCORE_DROPPED => 'Compliance score dropped to ' . ($this->alertData['current_score'] ?? 0) . '% for ' . $this->agency->agency_name,
            self::ALERT_LICENSE_EXPIRED => 'Business license expired for ' . $this->agency->agency_name,
            self::ALERT_INSURANCE_EXPIRED => 'Insurance coverage expired for ' . $this->agency->agency_name,
            self::ALERT_REVIEW_REQUIRED => 'Compliance review required for ' . $this->agency->agency_name,
        ];

        $urgentTypes = [self::ALERT_LICENSE_EXPIRED, self::ALERT_INSURANCE_EXPIRED];

        return [
            'type' => 'agency_compliance_alert',
            'alert_type' => $this->alertType,
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->agency_name,
            'message' => $messages[$this->alertType] ?? 'Compliance alert for ' . $this->agency->agency_name,
            'alert_data' => $this->alertData,
            'action_url' => route('agency.go-live.checklist'),
            'action_label' => 'View Compliance',
            'priority' => in_array($this->alertType, $urgentTypes) ? 'urgent' : 'high',
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
            'database' => 'default',
        ];
    }
}
