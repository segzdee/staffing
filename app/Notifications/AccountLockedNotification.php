<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

/**
 * Notification sent to users when their account is locked due to failed login attempts.
 *
 * This notification is triggered when:
 * - User exceeds the maximum number of failed login attempts (5)
 * - Admin manually locks an account
 *
 * The notification includes:
 * - Reason for the lockout
 * - Duration of the lockout
 * - When the account will be automatically unlocked
 * - Instructions for immediate unlock (password reset)
 * - Contact information for support
 */
class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Carbon $lockedUntil;
    protected string $reason;
    protected int $durationMinutes;
    protected string $ipAddress;
    protected ?string $userAgent;
    protected bool $isAdminLock;

    /**
     * Create a new notification instance.
     *
     * @param Carbon $lockedUntil When the lock expires
     * @param string $reason Reason for the lockout
     * @param int $durationMinutes Duration of the lock in minutes
     * @param string $ipAddress IP address of the failed login attempts
     * @param string|null $userAgent Browser/device info
     * @param bool $isAdminLock Whether this was an admin-initiated lock
     */
    public function __construct(
        Carbon $lockedUntil,
        string $reason,
        int $durationMinutes,
        string $ipAddress = 'Unknown',
        ?string $userAgent = null,
        bool $isAdminLock = false
    ) {
        $this->lockedUntil = $lockedUntil;
        $this->reason = $reason;
        $this->durationMinutes = $durationMinutes;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->isAdminLock = $isAdminLock;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $lockedUntilFormatted = $this->lockedUntil->format('F j, Y \a\t g:i A T');
        $appName = config('app.name', 'OvertimeStaff');

        $mail = (new MailMessage)
            ->error()
            ->subject('Account Temporarily Locked - Security Alert')
            ->greeting("Hello {$notifiable->name},");

        if ($this->isAdminLock) {
            $mail->line("Your {$appName} account has been temporarily locked by an administrator.");
        } else {
            $mail->line("Your {$appName} account has been temporarily locked due to multiple failed login attempts.");
        }

        $mail->line("**Reason:** {$this->reason}")
            ->line("**Lock Duration:** {$this->durationMinutes} minutes")
            ->line("**Account Unlocks:** {$lockedUntilFormatted}");

        if (!$this->isAdminLock) {
            $mail->line('')
                ->line('**Security Details:**')
                ->line("- IP Address: {$this->ipAddress}");

            if ($this->userAgent) {
                $mail->line("- Browser/Device: {$this->userAgent}");
            }

            $mail->line('')
                ->line('**What does this mean?**')
                ->line('Someone attempted to log into your account multiple times with incorrect credentials. To protect your account, we have temporarily locked it.');
        }

        $mail->line('')
            ->line('**What you can do:**')
            ->line('1. **Wait** - Your account will automatically unlock at the time shown above')
            ->line('2. **Reset Password** - If you need immediate access, reset your password')
            ->line('3. **Contact Support** - If you did not attempt these logins, please contact our security team');

        // Add password reset button
        $mail->action('Reset Your Password', url('/password/reset'));

        $mail->line('')
            ->line('**Did you make these login attempts?**')
            ->line('If you did, no further action is needed. Your account will unlock automatically.')
            ->line('')
            ->line('**Suspect unauthorized access?**')
            ->line('If you did not make these login attempts, we recommend:')
            ->line('- Resetting your password immediately')
            ->line('- Enabling two-factor authentication')
            ->line('- Reviewing your recent account activity')
            ->line('')
            ->line('If you have any questions or concerns, please contact our support team.')
            ->salutation('Stay secure,<br>The ' . $appName . ' Security Team');

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'account_locked',
            'title' => 'Account Temporarily Locked',
            'message' => $this->isAdminLock
                ? "Your account has been locked by an administrator. {$this->reason}"
                : "Your account has been locked for {$this->durationMinutes} minutes due to multiple failed login attempts.",
            'reason' => $this->reason,
            'duration_minutes' => $this->durationMinutes,
            'locked_until' => $this->lockedUntil->toIso8601String(),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'is_admin_lock' => $this->isAdminLock,
            'action_url' => url('/password/reset'),
            'action_text' => 'Reset Password',
            'priority' => 'high',
            'icon' => 'lock',
            'color' => 'red',
        ];
    }
}
