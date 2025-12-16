<?php

namespace App\Notifications;

use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Email verification notification with code and link.
 */
class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected VerificationCode $verificationCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationCode $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $code = $this->verificationCode->code;
        $token = $this->verificationCode->token;
        $expiresAt = $this->verificationCode->expires_at;

        $verificationUrl = route('worker.verify.email.link', ['token' => $token]);

        return (new MailMessage)
            ->subject('Verify Your Email Address - OvertimeStaff')
            ->greeting('Verify Your Email')
            ->line('Thank you for registering with OvertimeStaff. Please use the code below to verify your email address:')
            ->line("**Your verification code is: {$code}**")
            ->line('Or click the button below to verify automatically:')
            ->action('Verify Email', $verificationUrl)
            ->line("This code will expire in {$this->getExpiryText($expiresAt)}.")
            ->line('If you did not create an account, no further action is required.')
            ->salutation("Best regards,\nThe OvertimeStaff Team");
    }

    /**
     * Get human-readable expiry text.
     */
    protected function getExpiryText($expiresAt): string
    {
        $minutes = now()->diffInMinutes($expiresAt);

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = now()->diffInHours($expiresAt);
        return "{$hours} hour" . ($hours > 1 ? 's' : '');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'email_verification',
            'title' => 'Email Verification Required',
            'message' => 'Please verify your email address to access all features.',
            'code' => $this->verificationCode->code,
            'expires_at' => $this->verificationCode->expires_at->toIso8601String(),
        ];
    }
}
