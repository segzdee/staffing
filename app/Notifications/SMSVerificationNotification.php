<?php

namespace App\Notifications;

use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * SMS verification notification (for logging/tracking purposes).
 * Actual SMS is sent via VerificationService.
 */
class SMSVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected VerificationCode $verificationCode;
    protected string $phone;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationCode $verificationCode, string $phone)
    {
        $this->verificationCode = $verificationCode;
        $this->phone = $phone;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // SMS is sent directly via VerificationService
        // This notification is for database logging only
        return ['database'];
    }

    /**
     * Get the SMS message content.
     */
    public function getSMSMessage(): string
    {
        $code = $this->verificationCode->code;
        return "Your OvertimeStaff verification code is: {$code}. This code expires in 10 minutes.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sms_verification',
            'title' => 'Phone Verification Sent',
            'message' => 'A verification code was sent to your phone.',
            'phone' => $this->maskPhone($this->phone),
            'expires_at' => $this->verificationCode->expires_at->toIso8601String(),
        ];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Mask phone number for security.
     */
    protected function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }
}
