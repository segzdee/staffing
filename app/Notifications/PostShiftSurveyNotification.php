<?php

namespace App\Notifications;

use App\Models\Shift;
use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * QUA-003: PostShiftSurveyNotification
 *
 * Notification sent to workers after completing a shift to gather feedback.
 */
class PostShiftSurveyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Survey $survey;

    protected Shift $shift;

    /**
     * Create a new notification instance.
     */
    public function __construct(Survey $survey, Shift $shift)
    {
        $this->survey = $survey;
        $this->shift = $shift;
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
        $businessName = $this->shift->business->businessProfile->company_name
            ?? $this->shift->business->name
            ?? 'the business';

        $surveyUrl = route('feedback.surveys.post-shift', [
            'slug' => $this->survey->slug,
            'shiftId' => $this->shift->id,
        ]);

        return (new MailMessage)
            ->subject('How was your shift? Share your feedback')
            ->greeting("Hi {$notifiable->first_name}!")
            ->line("You recently completed a shift with {$businessName}.")
            ->line("We'd love to hear about your experience! Your feedback helps us improve the platform and helps businesses provide better working conditions.")
            ->line('**Shift Details:**')
            ->line("- Title: {$this->shift->title}")
            ->line("- Date: {$this->shift->shift_date->format('l, F j, Y')}")
            ->line("- Location: {$this->shift->location_city}")
            ->action('Share Your Feedback', $surveyUrl)
            ->line('This survey takes less than 2 minutes to complete.')
            ->line('Your responses are confidential and help us serve you better.')
            ->salutation('Thank you, The OvertimeStaff Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_shift_survey',
            'survey_id' => $this->survey->id,
            'survey_slug' => $this->survey->slug,
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'business_id' => $this->shift->business_id,
            'business_name' => $this->shift->business->businessProfile->company_name
                ?? $this->shift->business->name
                ?? 'Business',
            'message' => "Share your feedback about your shift: {$this->shift->title}",
            'action_url' => route('feedback.surveys.post-shift', [
                'slug' => $this->survey->slug,
                'shiftId' => $this->shift->id,
            ]),
        ];
    }

    /**
     * Get the shift associated with this notification.
     */
    public function getShift(): Shift
    {
        return $this->shift;
    }

    /**
     * Get the survey associated with this notification.
     */
    public function getSurvey(): Survey
    {
        return $this->survey;
    }
}
