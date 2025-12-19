<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * AdminAlertNotification
 *
 * Reusable notification for sending alerts to admin users.
 * Supports database notifications for in-app alerts and Slack logging.
 *
 * Usage:
 *   AdminAlertNotification::send(
 *       title: 'SLA Breaches Detected',
 *       message: 'There are 5 urgent shifts that have breached SLA.',
 *       severity: 'warning',
 *       context: ['breached_count' => 5],
 *       actionUrl: '/admin/urgent-shifts',
 *       actionLabel: 'View Urgent Shifts'
 *   );
 */
class AdminAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected string $title;

    protected string $message;

    protected string $severity;

    protected array $context;

    protected ?string $actionUrl;

    protected ?string $actionLabel;

    protected string $category;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        string $severity = self::SEVERITY_INFO,
        array $context = [],
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        string $category = 'system'
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->severity = $severity;
        $this->context = $context;
        $this->actionUrl = $actionUrl;
        $this->actionLabel = $actionLabel;
        $this->category = $category;

        $this->onQueue('notifications');
    }

    /**
     * Static helper to send notification to all admin users.
     */
    public static function send(
        string $title,
        string $message,
        string $severity = self::SEVERITY_INFO,
        array $context = [],
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        string $category = 'system'
    ): void {
        $notification = new static(
            $title,
            $message,
            $severity,
            $context,
            $actionUrl,
            $actionLabel,
            $category
        );

        // Get all admin users
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            Log::warning('AdminAlertNotification: No admin users found to notify', [
                'title' => $title,
                'severity' => $severity,
            ]);

            return;
        }

        // Send notification to each admin
        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        // Also log to Slack channel if configured
        static::logToSlack($title, $message, $severity, $context);
    }

    /**
     * Log alert to Slack if configured.
     */
    protected static function logToSlack(
        string $title,
        string $message,
        string $severity,
        array $context
    ): void {
        // Check if Slack logging is configured
        if (! config('logging.channels.slack.url')) {
            return;
        }

        $logLevel = match ($severity) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_WARNING => 'warning',
            default => 'info',
        };

        try {
            Log::channel('slack')->{$logLevel}("[{$severity}] {$title}: {$message}", $context);
        } catch (\Exception $e) {
            // Don't let Slack failures affect the notification
            Log::warning('Failed to send admin alert to Slack', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add mail for critical alerts
        if ($this->severity === self::SEVERITY_CRITICAL) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification (for critical alerts).
     */
    public function toMail(object $notifiable): MailMessage
    {
        $severityLabel = $this->getSeverityLabel();

        $mail = (new MailMessage)
            ->subject("[{$severityLabel}] {$this->title}")
            ->greeting("Admin Alert: {$this->title}")
            ->line($this->message);

        // Add context details
        if (! empty($this->context)) {
            $mail->line('');
            $mail->line('**Details:**');
            foreach ($this->context as $key => $value) {
                $formattedKey = ucwords(str_replace('_', ' ', $key));
                $formattedValue = is_array($value) ? json_encode($value) : $value;
                $mail->line("- {$formattedKey}: {$formattedValue}");
            }
        }

        if ($this->actionUrl) {
            $mail->action($this->actionLabel ?? 'View Details', url($this->actionUrl));
        }

        $mail->line('');
        $mail->line('This is an automated alert from the OvertimeStaff platform.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_alert',
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'context' => $this->context,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get severity label for display.
     */
    protected function getSeverityLabel(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'CRITICAL',
            self::SEVERITY_WARNING => 'WARNING',
            default => 'INFO',
        };
    }

    /**
     * Handle failed notification.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AdminAlertNotification failed', [
            'title' => $this->title,
            'severity' => $this->severity,
            'error' => $exception->getMessage(),
        ]);
    }
}
