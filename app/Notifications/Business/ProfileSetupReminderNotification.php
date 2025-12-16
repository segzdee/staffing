<?php

namespace App\Notifications\Business;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Profile Setup Reminder Notification
 * BIZ-REG-003: Reminds businesses to complete their profile
 */
class ProfileSetupReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessProfile $businessProfile;
    protected float $completionPercentage;
    protected array $missingFields;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile, float $completionPercentage, array $missingFields)
    {
        $this->businessProfile = $businessProfile;
        $this->completionPercentage = $completionPercentage;
        $this->missingFields = $missingFields;
    }

    /**
     * Get the notification's delivery channels.
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
        $businessName = $this->businessProfile->business_name;
        $percentRemaining = 100 - $this->completionPercentage;

        $message = (new MailMessage)
            ->subject("Complete Your Profile - {$percentRemaining}% to Go!")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your {$businessName} profile is {$this->completionPercentage}% complete.")
            ->line("Complete your profile to start posting shifts and finding qualified workers.");

        // Add list of missing required fields
        $requiredMissing = array_filter($this->missingFields, fn($f) => $f['required'] ?? false);
        if (!empty($requiredMissing)) {
            $message->line("**Required items to complete:**");
            foreach (array_slice($requiredMissing, 0, 5) as $field) {
                $fieldLabel = $this->getFieldLabel($field['field']);
                $message->line("- {$fieldLabel}");
            }
        }

        return $message
            ->action('Complete Your Profile', route('business.profile.complete'))
            ->line("Profiles need to be at least 80% complete before you can post shifts.")
            ->salutation("Best regards,\nThe OvertimeStaff Team");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'profile_setup_reminder',
            'title' => 'Complete Your Business Profile',
            'message' => "Your profile is {$this->completionPercentage}% complete. Complete it to start posting shifts.",
            'business_profile_id' => $this->businessProfile->id,
            'completion_percentage' => $this->completionPercentage,
            'missing_fields_count' => count($this->missingFields),
            'action_url' => route('business.profile.complete'),
            'action_text' => 'Complete Profile',
        ];
    }

    /**
     * Get human-readable field label
     */
    protected function getFieldLabel(string $field): string
    {
        $labels = [
            'business_name' => 'Business Name',
            'legal_business_name' => 'Legal Business Name',
            'trading_name' => 'Trading Name / DBA',
            'business_category' => 'Business Type',
            'industry' => 'Industry',
            'company_size' => 'Company Size',
            'description' => 'Business Description',
            'website' => 'Website',
            'phone' => 'Phone Number',
            'work_email_verified' => 'Email Verification',
            'default_currency' => 'Default Currency',
            'default_timezone' => 'Timezone',
            'logo_url' => 'Company Logo',
            'ein_tax_id' => 'Tax ID',
            'primary_contact' => 'Primary Contact',
            'billing_contact' => 'Billing Contact',
            'registered_address' => 'Registered Address',
            'billing_address' => 'Billing Address',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }
}
