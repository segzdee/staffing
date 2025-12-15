<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StripeConnectRequiredNotification
 *
 * Sent when an agency needs to complete Stripe Connect onboarding
 * or provide additional information to Stripe.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 */
class StripeConnectRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyProfile $agency;
    protected array $requirements;

    /**
     * Create a new notification instance.
     */
    public function __construct(AgencyProfile $agency, array $requirements = [])
    {
        $this->agency = $agency;
        $this->requirements = $requirements;
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
        $mail = (new MailMessage)
            ->subject('Action Required: Complete Your Stripe Payout Setup')
            ->greeting('Hello ' . $notifiable->name . ',');

        if (empty($this->requirements)) {
            $mail->line('To receive commission payouts from OvertimeStaff, please complete your Stripe Connect setup.')
                ->line('This secure process connects your bank account so we can automatically send your earnings.');
        } else {
            $mail->line('Stripe needs additional information to process your commission payouts.')
                ->line('Please provide the following:');

            foreach ($this->requirements as $requirement) {
                $mail->line('- ' . $this->formatRequirement($requirement));
            }
        }

        $mail->line('')
            ->line('Pending Commission: $' . number_format($this->agency->pending_commission, 2))
            ->action('Complete Setup', route('agency.stripe.onboarding'))
            ->line('This process typically takes just a few minutes.')
            ->line('Once complete, you\'ll receive automatic weekly payouts for your commissions.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stripe_connect_required',
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->agency_name,
            'requirements' => $this->requirements,
            'pending_commission' => $this->agency->pending_commission,
            'message' => empty($this->requirements)
                ? 'Complete your Stripe Connect setup to receive commission payouts.'
                : 'Stripe needs additional information to process your payouts.',
            'action_url' => route('agency.stripe.onboarding'),
        ];
    }

    /**
     * Format a Stripe requirement code to human-readable text.
     */
    protected function formatRequirement(string $requirement): string
    {
        $requirementMap = [
            'individual.first_name' => 'First name',
            'individual.last_name' => 'Last name',
            'individual.dob.day' => 'Date of birth (day)',
            'individual.dob.month' => 'Date of birth (month)',
            'individual.dob.year' => 'Date of birth (year)',
            'individual.address.line1' => 'Street address',
            'individual.address.city' => 'City',
            'individual.address.state' => 'State/Province',
            'individual.address.postal_code' => 'Postal code',
            'individual.address.country' => 'Country',
            'individual.ssn_last_4' => 'Last 4 digits of SSN',
            'individual.id_number' => 'Government ID number',
            'individual.verification.document' => 'Identity document',
            'individual.verification.additional_document' => 'Additional identity document',
            'business_profile.url' => 'Business website',
            'business_profile.mcc' => 'Business category',
            'business_profile.product_description' => 'Business description',
            'company.name' => 'Company name',
            'company.tax_id' => 'Company tax ID',
            'company.address.line1' => 'Company address',
            'company.address.city' => 'Company city',
            'company.address.state' => 'Company state',
            'company.address.postal_code' => 'Company postal code',
            'company.verification.document' => 'Company verification document',
            'external_account' => 'Bank account information',
            'tos_acceptance' => 'Terms of service acceptance',
        ];

        return $requirementMap[$requirement] ?? str_replace(['_', '.'], ' ', $requirement);
    }
}
