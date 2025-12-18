<?php

namespace App\Services;

use App\Models\CommunicationTemplate;
use App\Models\Shift;
use App\Models\TemplateSend;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Money\Money;

/**
 * BIZ-010: Communication Template Service
 *
 * Handles template management, rendering, and sending for business-to-worker communications.
 */
class CommunicationTemplateService
{
    protected ?NotificationService $notificationService = null;

    protected ?SmsService $smsService = null;

    /**
     * Create a new template for a business.
     */
    public function createTemplate(User $business, array $data): CommunicationTemplate
    {
        $data['business_id'] = $business->id;

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Set default variables if not provided
        if (empty($data['variables']) && ! empty($data['type'])) {
            $data['variables'] = CommunicationTemplate::getDefaultVariablesForType($data['type']);
        }

        return CommunicationTemplate::create($data);
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(CommunicationTemplate $template, array $data): CommunicationTemplate
    {
        // Prevent editing system templates
        if ($template->is_system) {
            throw new \InvalidArgumentException('System templates cannot be edited.');
        }

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $template->name) {
            $data['slug'] = Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (CommunicationTemplate::where('business_id', $template->business_id)
                ->where('slug', $data['slug'])
                ->where('id', '!=', $template->id)
                ->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter++;
            }
        }

        $template->update($data);

        return $template->fresh();
    }

    /**
     * Render a template with the provided variables.
     */
    public function renderTemplate(CommunicationTemplate $template, array $variables): array
    {
        $body = $this->replaceVariables($template->body, $variables);
        $subject = $template->subject
            ? $this->replaceVariables($template->subject, $variables)
            : null;

        return [
            'subject' => $subject,
            'body' => $body,
            'plain_text' => strip_tags($body),
        ];
    }

    /**
     * Replace template variables with actual values.
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Support both {{variable}} and {{ variable }} syntax
            $content = preg_replace(
                '/\{\{\s*'.preg_quote($key, '/').'\s*\}\}/',
                $value ?? '',
                $content
            );
        }

        // Remove any unreplaced variables
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);

        return $content;
    }

    /**
     * Send a template to a single recipient.
     */
    public function sendTemplate(
        CommunicationTemplate $template,
        User $sender,
        User $recipient,
        array $variables = [],
        ?Shift $shift = null
    ): TemplateSend {
        // Render the template
        $rendered = $this->renderTemplate($template, $variables);

        // Determine which channels to use
        $channels = $this->resolveChannels($template->channel);

        // Create send record
        $templateSend = TemplateSend::create([
            'template_id' => $template->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'shift_id' => $shift?->id,
            'channel' => $template->channel,
            'subject' => $rendered['subject'],
            'rendered_content' => $rendered['body'],
            'status' => TemplateSend::STATUS_PENDING,
        ]);

        // Attempt to send via each channel
        $success = false;
        $errorMessages = [];

        foreach ($channels as $channel) {
            try {
                $this->sendViaChannel($channel, $recipient, $rendered, $shift);
                $success = true;
            } catch (\Exception $e) {
                $errorMessages[] = "{$channel}: {$e->getMessage()}";
                Log::error("Template send failed via {$channel}", [
                    'template_id' => $template->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update send status
        if ($success) {
            $templateSend->markAsSent();
            $template->incrementUsage();
        } else {
            $templateSend->markAsFailed(implode('; ', $errorMessages));
        }

        return $templateSend;
    }

    /**
     * Send a template to multiple recipients.
     */
    public function sendBulkTemplate(
        CommunicationTemplate $template,
        User $sender,
        Collection $recipients,
        array $baseVariables = [],
        ?Shift $shift = null
    ): array {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'sends' => [],
        ];

        foreach ($recipients as $recipient) {
            // Build recipient-specific variables
            $variables = array_merge($baseVariables, $this->buildRecipientVariables($recipient));

            try {
                $send = $this->sendTemplate($template, $sender, $recipient, $variables, $shift);
                $results['sends'][] = $send;

                if ($send->isSuccessful()) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Bulk template send failed', [
                    'template_id' => $template->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Build variables from a recipient user.
     */
    public function buildRecipientVariables(User $recipient): array
    {
        return [
            'worker_name' => $recipient->name,
            'worker_first_name' => $recipient->first_name,
            'worker_email' => $recipient->email,
        ];
    }

    /**
     * Build variables from a business user.
     */
    public function buildBusinessVariables(User $business): array
    {
        $profile = $business->businessProfile;

        return [
            'business_name' => $profile?->company_name ?? $business->name,
            'business_contact' => $profile?->contact_name ?? $business->name,
            'business_phone' => $profile?->phone ?? '',
        ];
    }

    /**
     * Build variables from a shift.
     */
    public function buildShiftVariables(Shift $shift): array
    {
        // Handle Money object for hourly rate
        $rate = $shift->final_rate ?? $shift->base_rate;
        $hourlyRate = $this->formatMoney($rate);

        return [
            'shift_date' => $shift->shift_date?->format('l, F j, Y'),
            'shift_start_time' => $shift->start_time?->format('g:i A'),
            'shift_end_time' => $shift->end_time?->format('g:i A'),
            'shift_duration' => $shift->duration_hours,
            'position_name' => $shift->title,
            'hourly_rate' => $hourlyRate,
            'venue_name' => $shift->venue?->name ?? $shift->location_address,
            'venue_address' => $shift->location_address,
            'venue_city' => $shift->location_city,
            'dress_code' => $shift->dress_code ?? 'Please check with the venue',
            'parking_info' => $shift->parking_info ?? 'Please check with the venue',
            'special_instructions' => $shift->special_instructions ?? 'None',
            'break_info' => $shift->break_info ?? 'As per venue policy',
        ];
    }

    /**
     * Format a Money object or numeric value to currency string.
     */
    protected function formatMoney($value): string
    {
        if ($value instanceof Money) {
            // Money stores amounts in smallest unit (cents), so divide by 100
            return '$'.number_format((float) $value->getAmount() / 100, 2);
        }

        if (is_numeric($value)) {
            return '$'.number_format((float) $value, 2);
        }

        return '$0.00';
    }

    /**
     * Build all variables for a complete send context.
     */
    public function buildAllVariables(User $recipient, User $business, ?Shift $shift = null): array
    {
        $variables = array_merge(
            $this->buildRecipientVariables($recipient),
            $this->buildBusinessVariables($business)
        );

        if ($shift) {
            $variables = array_merge($variables, $this->buildShiftVariables($shift));
        }

        return $variables;
    }

    /**
     * Get available variables for a specific template type.
     */
    public function getAvailableVariables(string $type): array
    {
        $defaultVars = CommunicationTemplate::getDefaultVariablesForType($type);
        $allVars = CommunicationTemplate::getAvailableVariables();

        $result = [];
        foreach ($allVars as $category => $variables) {
            $categoryVars = [];
            foreach ($variables as $key => $description) {
                if (in_array($key, $defaultVars)) {
                    $categoryVars[$key] = $description;
                }
            }
            if (! empty($categoryVars)) {
                $result[$category] = $categoryVars;
            }
        }

        return $result;
    }

    /**
     * Duplicate a template.
     */
    public function duplicateTemplate(CommunicationTemplate $template, ?string $newName = null): CommunicationTemplate
    {
        return $template->duplicate($newName);
    }

    /**
     * Get template analytics for a business.
     */
    public function getTemplateAnalytics(User $business): array
    {
        $templates = CommunicationTemplate::forBusiness($business->id)->get();

        $analytics = [
            'total_templates' => $templates->count(),
            'active_templates' => $templates->where('is_active', true)->count(),
            'by_type' => [],
            'by_channel' => [],
            'total_sends' => 0,
            'success_rate' => 0,
            'most_used' => null,
            'recent_sends' => [],
        ];

        // Count by type
        foreach (CommunicationTemplate::getTypes() as $type => $label) {
            $analytics['by_type'][$type] = [
                'label' => $label,
                'count' => $templates->where('type', $type)->count(),
            ];
        }

        // Count by channel
        foreach (CommunicationTemplate::getChannels() as $channel => $label) {
            $analytics['by_channel'][$channel] = [
                'label' => $label,
                'count' => $templates->where('channel', $channel)->count(),
            ];
        }

        // Get send statistics
        $templateIds = $templates->pluck('id');
        $sends = TemplateSend::whereIn('template_id', $templateIds);

        $analytics['total_sends'] = $sends->count();

        $successfulSends = TemplateSend::whereIn('template_id', $templateIds)
            ->whereIn('status', [TemplateSend::STATUS_SENT, TemplateSend::STATUS_DELIVERED])
            ->count();

        if ($analytics['total_sends'] > 0) {
            $analytics['success_rate'] = round(($successfulSends / $analytics['total_sends']) * 100, 1);
        }

        // Most used template
        $mostUsed = $templates->sortByDesc('usage_count')->first();
        if ($mostUsed && $mostUsed->usage_count > 0) {
            $analytics['most_used'] = [
                'id' => $mostUsed->id,
                'name' => $mostUsed->name,
                'usage_count' => $mostUsed->usage_count,
            ];
        }

        // Recent sends
        $analytics['recent_sends'] = TemplateSend::whereIn('template_id', $templateIds)
            ->with(['template', 'recipient'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($send) => [
                'id' => $send->id,
                'template_name' => $send->template->name,
                'recipient_name' => $send->recipient->name,
                'status' => $send->status,
                'sent_at' => $send->sent_at?->diffForHumans(),
            ]);

        return $analytics;
    }

    /**
     * Get send history for a business.
     */
    public function getSendHistory(User $business, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $templateIds = CommunicationTemplate::forBusiness($business->id)->pluck('id');

        $query = TemplateSend::whereIn('template_id', $templateIds)
            ->with(['template', 'recipient', 'shift']);

        // Apply filters
        if (! empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['recipient_id'])) {
            $query->where('recipient_id', $filters['recipient_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get default templates for a business (creates if not exist).
     */
    public function ensureDefaultTemplates(User $business): void
    {
        $defaults = $this->getDefaultTemplateDefinitions();

        foreach ($defaults as $definition) {
            $exists = CommunicationTemplate::forBusiness($business->id)
                ->ofType($definition['type'])
                ->where('is_default', true)
                ->exists();

            if (! $exists) {
                $this->createTemplate($business, array_merge($definition, [
                    'is_default' => true,
                ]));
            }
        }
    }

    /**
     * Get default template definitions.
     */
    public function getDefaultTemplateDefinitions(): array
    {
        return [
            [
                'type' => CommunicationTemplate::TYPE_SHIFT_INSTRUCTION,
                'name' => 'Basic Shift Instructions',
                'channel' => CommunicationTemplate::CHANNEL_ALL,
                'subject' => 'Shift Instructions for {{shift_date}}',
                'body' => 'Hi {{worker_name}},

Thank you for accepting the shift at {{venue_name}} on {{shift_date}}.

**Shift Details:**
- Date: {{shift_date}}
- Time: {{shift_start_time}} - {{shift_end_time}}
- Location: {{venue_address}}
- Position: {{position_name}}
- Rate: {{hourly_rate}}/hour

**Important Information:**
- Dress Code: {{dress_code}}
- Parking: {{parking_info}}
- Special Instructions: {{special_instructions}}

Please arrive 10-15 minutes early. If you have any questions, please contact us.

Best regards,
{{business_name}}',
            ],
            [
                'type' => CommunicationTemplate::TYPE_WELCOME,
                'name' => 'Welcome Message',
                'channel' => CommunicationTemplate::CHANNEL_ALL,
                'subject' => 'Welcome to {{business_name}}!',
                'body' => 'Hi {{worker_name}},

Welcome to {{business_name}}! We are excited to have you join our team of workers.

If you have any questions, feel free to reach out to us at any time.

Best regards,
{{business_name}}
{{business_phone}}',
            ],
            [
                'type' => CommunicationTemplate::TYPE_REMINDER,
                'name' => 'Shift Reminder',
                'channel' => CommunicationTemplate::CHANNEL_ALL,
                'subject' => 'Reminder: Shift Tomorrow at {{venue_name}}',
                'body' => 'Hi {{worker_name}},

This is a friendly reminder about your upcoming shift:

**Shift Details:**
- Date: {{shift_date}}
- Time: {{shift_start_time}}
- Location: {{venue_name}}, {{venue_address}}

Please remember to arrive 10-15 minutes early.

See you soon!
{{business_name}}',
            ],
            [
                'type' => CommunicationTemplate::TYPE_THANK_YOU,
                'name' => 'Thank You Message',
                'channel' => CommunicationTemplate::CHANNEL_EMAIL,
                'subject' => 'Thank You for Working With Us',
                'body' => 'Hi {{worker_name}},

Thank you for working with us on {{shift_date}}. We really appreciate your hard work and dedication.

We hope to work with you again soon!

Best regards,
{{business_name}}',
            ],
            [
                'type' => CommunicationTemplate::TYPE_FEEDBACK_REQUEST,
                'name' => 'Feedback Request',
                'channel' => CommunicationTemplate::CHANNEL_EMAIL,
                'subject' => 'How Was Your Shift at {{venue_name}}?',
                'body' => 'Hi {{worker_name}},

We hope you had a great experience working at {{venue_name}} on {{shift_date}}.

Your feedback is important to us! Please take a moment to rate your experience and let us know how we can improve.

Thank you for being part of our team!

Best regards,
{{business_name}}',
            ],
        ];
    }

    /**
     * Resolve channels from template channel setting.
     */
    protected function resolveChannels(string $channel): array
    {
        if ($channel === CommunicationTemplate::CHANNEL_ALL) {
            return [
                CommunicationTemplate::CHANNEL_IN_APP,
                CommunicationTemplate::CHANNEL_EMAIL,
            ];
        }

        return [$channel];
    }

    /**
     * Send message via specific channel.
     */
    protected function sendViaChannel(string $channel, User $recipient, array $rendered, ?Shift $shift = null): void
    {
        switch ($channel) {
            case CommunicationTemplate::CHANNEL_EMAIL:
                $this->sendEmail($recipient, $rendered);
                break;

            case CommunicationTemplate::CHANNEL_SMS:
                $this->sendSms($recipient, $rendered);
                break;

            case CommunicationTemplate::CHANNEL_IN_APP:
                $this->sendInApp($recipient, $rendered, $shift);
                break;

            default:
                throw new \InvalidArgumentException("Unknown channel: {$channel}");
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(User $recipient, array $rendered): void
    {
        \Illuminate\Support\Facades\Mail::send(
            'emails.template_message',
            ['content' => $rendered['body'], 'subject' => $rendered['subject']],
            function ($message) use ($recipient, $rendered) {
                $message->to($recipient->email, $recipient->name);
                $message->subject($rendered['subject'] ?? 'Message from OvertimeStaff');
            }
        );
    }

    /**
     * Send SMS notification.
     */
    protected function sendSms(User $recipient, array $rendered): void
    {
        if ($this->smsService === null) {
            $this->smsService = app(SmsService::class);
        }

        $phone = $recipient->workerProfile?->phone ?? null;
        if ($phone) {
            $this->smsService->send($phone, $rendered['plain_text']);
        }
    }

    /**
     * Send in-app notification.
     */
    protected function sendInApp(User $recipient, array $rendered, ?Shift $shift = null): void
    {
        if ($this->notificationService === null) {
            $this->notificationService = app(NotificationService::class);
        }

        $this->notificationService->send(
            $recipient,
            'template_message',
            $rendered['subject'] ?? 'New Message',
            $rendered['plain_text'],
            [
                'shift_id' => $shift?->id,
                'type' => 'template_message',
            ],
            ['push']
        );
    }

    /**
     * Preview a template with sample data.
     */
    public function previewTemplate(CommunicationTemplate $template): array
    {
        $sampleVariables = [
            'worker_name' => 'John Smith',
            'worker_first_name' => 'John',
            'worker_email' => 'john.smith@example.com',
            'business_name' => 'Sample Business',
            'business_contact' => 'Jane Doe',
            'business_phone' => '+1 (555) 123-4567',
            'shift_date' => 'Monday, January 15, 2025',
            'shift_start_time' => '9:00 AM',
            'shift_end_time' => '5:00 PM',
            'shift_duration' => '8',
            'position_name' => 'Event Staff',
            'hourly_rate' => '$25.00',
            'venue_name' => 'Grand Convention Center',
            'venue_address' => '123 Main Street, Suite 100',
            'venue_city' => 'New York',
            'dress_code' => 'Business casual - black pants, white shirt',
            'parking_info' => 'Free parking available in Lot B',
            'special_instructions' => 'Please enter through the staff entrance on the east side',
            'break_info' => '30-minute lunch break after 4 hours',
        ];

        return $this->renderTemplate($template, $sampleVariables);
    }
}
