<?php

namespace App\Services;

use App\Mail\TemplatedEmail;
use App\Models\EmailLog;
use App\Models\EmailPreference;
use App\Models\EmailTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * COM-003: Email Service
 *
 * Handles email sending, template rendering, logging, and tracking.
 */
class EmailService
{
    /**
     * Send a template-based email to a user.
     *
     * @param  array<string, mixed>  $variables
     */
    public function sendTemplateEmail(User $user, string $templateSlug, array $variables = []): ?EmailLog
    {
        $template = EmailTemplate::findActiveBySlug($templateSlug);

        if (! $template) {
            Log::warning("Email template not found: {$templateSlug}");

            return null;
        }

        // Check user preferences
        if (! $this->checkUserPreference($user, $template->category)) {
            Log::info("Email not sent due to user preferences: {$templateSlug} to {$user->email}");

            return null;
        }

        // Add default variables
        $variables = array_merge($this->getDefaultVariables($user), $variables);

        // Create log entry first (queued status)
        $log = $this->logEmail([
            'user_id' => $user->id,
            'to_email' => $user->email,
            'template_slug' => $templateSlug,
            'subject' => $template->render($variables)['subject'],
            'status' => EmailLog::STATUS_QUEUED,
            'metadata' => [
                'variables' => $variables,
                'template_category' => $template->category,
            ],
        ]);

        try {
            // Send the email
            $rendered = $template->render($variables);

            Mail::to($user->email)
                ->send(new TemplatedEmail(
                    $rendered['subject'],
                    $rendered['body_html'],
                    $rendered['body_text'],
                    $log->id
                ));

            // Update log to sent
            $log->markAsSent();

            return $log;
        } catch (\Exception $e) {
            Log::error("Failed to send email: {$templateSlug} to {$user->email}", [
                'error' => $e->getMessage(),
            ]);

            $log->markAsFailed($e->getMessage());

            return $log;
        }
    }

    /**
     * Send bulk emails using a template.
     *
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $variables
     * @return array<int, EmailLog>
     */
    public function sendBulkEmail(Collection $users, string $templateSlug, array $variables = []): array
    {
        $logs = [];

        foreach ($users as $user) {
            $log = $this->sendTemplateEmail($user, $templateSlug, $variables);
            if ($log) {
                $logs[] = $log;
            }
        }

        return $logs;
    }

    /**
     * Send a raw email (not template-based) to an email address.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function sendRawEmail(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?User $user = null,
        array $metadata = []
    ): ?EmailLog {
        // Create log entry
        $log = $this->logEmail([
            'user_id' => $user?->id,
            'to_email' => $toEmail,
            'template_slug' => null,
            'subject' => $subject,
            'status' => EmailLog::STATUS_QUEUED,
            'metadata' => $metadata,
        ]);

        try {
            Mail::to($toEmail)
                ->send(new TemplatedEmail(
                    $subject,
                    $bodyHtml,
                    $bodyText ?? strip_tags($bodyHtml),
                    $log->id
                ));

            $log->markAsSent();

            return $log;
        } catch (\Exception $e) {
            Log::error("Failed to send raw email to {$toEmail}", [
                'error' => $e->getMessage(),
            ]);

            $log->markAsFailed($e->getMessage());

            return $log;
        }
    }

    /**
     * Send a test email for template preview.
     */
    public function sendTestEmail(string $toEmail, EmailTemplate $template, array $variables = []): ?EmailLog
    {
        $log = $this->logEmail([
            'to_email' => $toEmail,
            'template_slug' => $template->slug,
            'subject' => '[TEST] '.$template->render($variables)['subject'],
            'status' => EmailLog::STATUS_QUEUED,
            'metadata' => [
                'is_test' => true,
                'variables' => $variables,
            ],
        ]);

        try {
            $rendered = $template->render($variables);

            Mail::to($toEmail)
                ->send(new TemplatedEmail(
                    '[TEST] '.$rendered['subject'],
                    $rendered['body_html'],
                    $rendered['body_text'],
                    $log->id
                ));

            $log->markAsSent();

            return $log;
        } catch (\Exception $e) {
            Log::error("Failed to send test email to {$toEmail}", [
                'error' => $e->getMessage(),
            ]);

            $log->markAsFailed($e->getMessage());

            return $log;
        }
    }

    /**
     * Render a template with variables (for preview).
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, body_html: string, body_text: string}
     */
    public function renderTemplate(EmailTemplate $template, array $variables = []): array
    {
        // Add sample data for preview if no variables provided
        if (empty($variables)) {
            $variables = $this->getSampleVariables($template);
        }

        return $template->render($variables);
    }

    /**
     * Log an email.
     *
     * @param  array<string, mixed>  $data
     */
    public function logEmail(array $data): EmailLog
    {
        return EmailLog::create($data);
    }

    /**
     * Process webhook from email provider (SendGrid/Mailgun).
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhook(string $provider, array $payload): bool
    {
        try {
            switch ($provider) {
                case 'sendgrid':
                    return $this->processSendGridWebhook($payload);
                case 'mailgun':
                    return $this->processMailgunWebhook($payload);
                default:
                    Log::warning("Unknown email provider webhook: {$provider}");

                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to process {$provider} webhook", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    /**
     * Process SendGrid webhook events.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function processSendGridWebhook(array $payload): bool
    {
        foreach ($payload as $event) {
            $messageId = $event['sg_message_id'] ?? null;

            if (! $messageId) {
                continue;
            }

            // Extract base message ID (remove suffix)
            $messageId = explode('.', $messageId)[0];

            $log = EmailLog::findByMessageId($messageId);

            if (! $log) {
                continue;
            }

            $eventType = $event['event'] ?? null;

            switch ($eventType) {
                case 'delivered':
                    $log->markAsDelivered();
                    break;
                case 'open':
                    $log->markAsOpened();
                    break;
                case 'click':
                    $log->markAsClicked();
                    break;
                case 'bounce':
                case 'dropped':
                    $log->markAsBounced($event['reason'] ?? null);
                    break;
            }
        }

        return true;
    }

    /**
     * Process Mailgun webhook events.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function processMailgunWebhook(array $payload): bool
    {
        $eventData = $payload['event-data'] ?? [];
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        if (! $messageId) {
            return false;
        }

        $log = EmailLog::findByMessageId($messageId);

        if (! $log) {
            return false;
        }

        $eventType = $eventData['event'] ?? null;

        switch ($eventType) {
            case 'delivered':
                $log->markAsDelivered();
                break;
            case 'opened':
                $log->markAsOpened();
                break;
            case 'clicked':
                $log->markAsClicked();
                break;
            case 'failed':
            case 'rejected':
                $reason = $eventData['delivery-status']['message'] ?? null;
                $log->markAsBounced($reason);
                break;
        }

        return true;
    }

    /**
     * Get email statistics for a date range.
     *
     * @return array<string, mixed>
     */
    public function getEmailStats(Carbon $startDate, Carbon $endDate): array
    {
        $query = EmailLog::query()->dateRange($startDate, $endDate);

        $total = $query->count();

        $byStatus = EmailLog::query()
            ->dateRange($startDate, $endDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byTemplate = EmailLog::query()
            ->dateRange($startDate, $endDate)
            ->whereNotNull('template_slug')
            ->selectRaw('template_slug, COUNT(*) as count')
            ->groupBy('template_slug')
            ->pluck('count', 'template_slug')
            ->toArray();

        $deliveryRate = $total > 0
            ? (($byStatus[EmailLog::STATUS_DELIVERED] ?? 0) +
               ($byStatus[EmailLog::STATUS_OPENED] ?? 0) +
               ($byStatus[EmailLog::STATUS_CLICKED] ?? 0)) / $total * 100
            : 0;

        $openRate = $total > 0
            ? (($byStatus[EmailLog::STATUS_OPENED] ?? 0) +
               ($byStatus[EmailLog::STATUS_CLICKED] ?? 0)) / $total * 100
            : 0;

        $clickRate = $total > 0
            ? ($byStatus[EmailLog::STATUS_CLICKED] ?? 0) / $total * 100
            : 0;

        $bounceRate = $total > 0
            ? ($byStatus[EmailLog::STATUS_BOUNCED] ?? 0) / $total * 100
            : 0;

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_template' => $byTemplate,
            'delivery_rate' => round($deliveryRate, 2),
            'open_rate' => round($openRate, 2),
            'click_rate' => round($clickRate, 2),
            'bounce_rate' => round($bounceRate, 2),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Check if user allows email based on their preferences.
     */
    public function checkUserPreference(User $user, string $category): bool
    {
        // Transactional emails are always allowed
        if ($category === EmailTemplate::CATEGORY_TRANSACTIONAL) {
            return true;
        }

        return $user->allowsEmailCategory($category);
    }

    /**
     * Get default variables for all emails.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultVariables(User $user): array
    {
        $preferences = $user->emailPreferences ?? EmailPreference::getOrCreateForUser($user);

        return [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_first_name' => $user->first_name,
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
            'unsubscribe_url' => route('email.unsubscribe', ['token' => $preferences->unsubscribe_token]),
            'preferences_url' => route('settings.email-preferences'),
        ];
    }

    /**
     * Get sample variables for template preview.
     *
     * @return array<string, mixed>
     */
    protected function getSampleVariables(EmailTemplate $template): array
    {
        $defaults = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'user_first_name' => 'John',
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
            'unsubscribe_url' => '#',
            'preferences_url' => '#',
        ];

        // Add template-specific sample variables
        $templateVariables = $template->variables ?? [];
        foreach ($templateVariables as $variable) {
            if (! isset($defaults[$variable])) {
                $defaults[$variable] = $this->getSampleValueForVariable($variable);
            }
        }

        return $defaults;
    }

    /**
     * Generate sample value for a variable.
     */
    protected function getSampleValueForVariable(string $variable): string
    {
        $samples = [
            'shift_title' => 'Evening Bartender Shift',
            'shift_date' => 'December 20, 2025',
            'shift_time' => '6:00 PM - 11:00 PM',
            'shift_location' => '123 Main Street, New York, NY',
            'business_name' => 'Sample Restaurant',
            'amount' => '$150.00',
            'payment_amount' => '$150.00',
            'hourly_rate' => '$25.00',
            'total_hours' => '5 hours',
            'worker_name' => 'Jane Smith',
            'rating' => '4.8',
            'verification_type' => 'ID Verification',
        ];

        return $samples[$variable] ?? '[Sample '.$variable.']';
    }

    /**
     * Get bounced emails for bounce management.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, EmailLog>
     */
    public function getBouncedEmails(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return EmailLog::bounced()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed emails for retry.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, EmailLog>
     */
    public function getFailedEmails(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return EmailLog::failed()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Retry sending a failed email.
     */
    public function retryEmail(EmailLog $log): ?EmailLog
    {
        if (! in_array($log->status, [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED])) {
            return null;
        }

        $user = $log->user;
        $templateSlug = $log->template_slug;
        $metadata = $log->metadata ?? [];

        if ($user && $templateSlug) {
            return $this->sendTemplateEmail($user, $templateSlug, $metadata['variables'] ?? []);
        }

        return null;
    }
}
