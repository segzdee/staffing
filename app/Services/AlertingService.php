<?php

namespace App\Services;

use App\Models\AlertConfiguration;
use App\Models\AlertHistory;
use App\Models\AlertIntegration;
use App\Models\AlertDigest;
use App\Models\SystemIncident;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class AlertingService
{
    protected const MAX_RETRIES = 3;
    protected const PAGERDUTY_API_URL = 'https://events.pagerduty.com/v2/enqueue';

    /**
     * Send an alert for an incident.
     */
    public function sendAlert(SystemIncident $incident): void
    {
        if (!$this->isAlertingEnabled()) {
            Log::info('Alerting is disabled globally');
            return;
        }

        // Determine if we should send this alert
        if (!$this->shouldSendAlert($incident)) {
            Log::info("Alert suppressed for incident {$incident->id}");
            return;
        }

        // Get alert configuration for this metric
        $config = $this->getAlertConfiguration($incident->affected_service);

        try {
            // Determine alert routing based on severity and health score
            $alertRouting = $this->determineAlertRouting($incident, $config);

            foreach ($alertRouting['channels'] as $channel) {
                $this->sendToChannel($channel, $incident, $config);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send alert for incident {$incident->id}: " . $e->getMessage(), [
                'incident_id' => $incident->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Send Slack alert with rich formatting.
     */
    public function sendSlackAlert(string $channel, string $message, string $severity, ?SystemIncident $incident = null): bool
    {
        $integration = AlertIntegration::ofType('slack')->enabled()->first();

        if (!$integration) {
            Log::warning('Slack integration not configured or disabled');
            return false;
        }

        // Get webhook URL for the channel type
        $channelType = $this->getSlackChannelType($severity);
        $webhookUrl = $integration->getWebhookUrl($channelType) ?: $integration->getWebhookUrl('default');

        if (empty($webhookUrl)) {
            Log::warning('No Slack webhook URL configured for channel: ' . $channelType);
            return false;
        }

        // Create alert history record
        $alertHistory = AlertHistory::create([
            'incident_id' => $incident?->id,
            'metric_name' => $incident?->affected_service ?? 'manual',
            'alert_type' => 'slack',
            'severity' => $severity,
            'title' => $incident?->title ?? 'System Alert',
            'message' => $message,
            'channel' => $channel,
            'status' => 'pending',
            'dedup_key' => $this->generateDedupKey($incident),
        ]);

        try {
            $payload = $this->formatSlackPayload($message, $severity, $incident);

            $response = Http::timeout(10)
                ->retry(self::MAX_RETRIES, 1000)
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                $alertHistory->markAsSent();
                $integration->markAsUsed(true);
                return true;
            }

            $alertHistory->markAsFailed($response->body());
            $integration->markAsUsed(false);
            return false;

        } catch (\Exception $e) {
            Log::error('Slack alert failed: ' . $e->getMessage());
            $alertHistory->markAsFailed($e->getMessage());
            $integration->markAsUsed(false);
            return false;
        }
    }

    /**
     * Send PagerDuty alert.
     */
    public function sendPagerDutyAlert(string $severity, array $details, ?SystemIncident $incident = null): bool
    {
        $integration = AlertIntegration::ofType('pagerduty')->enabled()->first();

        if (!$integration) {
            Log::warning('PagerDuty integration not configured or disabled');
            return false;
        }

        // Only send to PagerDuty for critical and high severity
        if (!in_array($severity, ['critical', 'high'])) {
            return false;
        }

        $routingKey = $integration->getRoutingKey($severity) ?: $integration->getIntegrationKey();

        if (empty($routingKey)) {
            Log::warning('No PagerDuty routing key configured for severity: ' . $severity);
            return false;
        }

        // Create alert history record
        $alertHistory = AlertHistory::create([
            'incident_id' => $incident?->id,
            'metric_name' => $incident?->affected_service ?? ($details['source'] ?? 'manual'),
            'alert_type' => 'pagerduty',
            'severity' => $severity,
            'title' => $details['summary'] ?? 'System Alert',
            'message' => json_encode($details),
            'status' => 'pending',
            'dedup_key' => $this->generateDedupKey($incident),
        ]);

        try {
            $payload = $this->formatPagerDutyPayload($severity, $details, $routingKey, $incident);

            $response = Http::timeout(10)
                ->retry(self::MAX_RETRIES, 1000)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::PAGERDUTY_API_URL, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $dedupKey = $responseData['dedup_key'] ?? null;
                $alertHistory->markAsSent($dedupKey);
                $integration->markAsUsed(true);

                // Store PagerDuty dedup key for resolution
                if ($incident && $dedupKey) {
                    Cache::put("pagerduty_incident_{$incident->id}", $dedupKey, now()->addDays(7));
                }

                return true;
            }

            $alertHistory->markAsFailed($response->body());
            $integration->markAsUsed(false);
            return false;

        } catch (\Exception $e) {
            Log::error('PagerDuty alert failed: ' . $e->getMessage());
            $alertHistory->markAsFailed($e->getMessage());
            $integration->markAsUsed(false);
            return false;
        }
    }

    /**
     * Auto-resolve PagerDuty incident when system incident is resolved.
     */
    public function resolvePagerDutyIncident(SystemIncident $incident): bool
    {
        $integration = AlertIntegration::ofType('pagerduty')->enabled()->first();

        if (!$integration) {
            return false;
        }

        $dedupKey = Cache::get("pagerduty_incident_{$incident->id}");

        if (!$dedupKey) {
            Log::info("No PagerDuty dedup key found for incident {$incident->id}");
            return false;
        }

        $routingKey = $integration->getIntegrationKey();

        try {
            $payload = [
                'routing_key' => $routingKey,
                'dedup_key' => $dedupKey,
                'event_action' => 'resolve',
            ];

            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::PAGERDUTY_API_URL, $payload);

            if ($response->successful()) {
                Cache::forget("pagerduty_incident_{$incident->id}");

                // Mark alert as resolved
                AlertHistory::where('incident_id', $incident->id)
                    ->where('alert_type', 'pagerduty')
                    ->unresolved()
                    ->each(function ($alert) {
                        $alert->resolve();
                    });

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('PagerDuty resolution failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send resolution notification.
     */
    public function sendResolutionNotification(SystemIncident $incident): void
    {
        if (!$this->isAlertingEnabled()) {
            return;
        }

        $resolutionTime = $incident->duration_minutes ??
            ($incident->detected_at && $incident->resolved_at
                ? $incident->detected_at->diffInMinutes($incident->resolved_at)
                : null);

        $message = $this->formatResolutionMessage($incident, $resolutionTime);

        // Send Slack resolution
        $this->sendSlackResolution($incident, $message);

        // Resolve PagerDuty incident
        $this->resolvePagerDutyIncident($incident);
    }

    /**
     * Check if alert should be sent (avoid alert fatigue).
     */
    public function shouldSendAlert(SystemIncident $incident): bool
    {
        // Check if alerting is enabled for this metric
        $config = $this->getAlertConfiguration($incident->affected_service);

        if (!$config || !$config->enabled) {
            return false;
        }

        // Check quiet hours for non-critical alerts
        if ($incident->severity !== 'critical' && $config->isInQuietHours()) {
            Log::info("Alert suppressed due to quiet hours for incident {$incident->id}");
            return false;
        }

        // Check cooldown - don't resend same alert within cooldown period
        $cooldownMinutes = $config->cooldown_minutes ?? 60;
        $recentAlert = AlertHistory::where('metric_name', $incident->affected_service)
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->exists();

        if ($recentAlert) {
            Log::info("Alert suppressed due to cooldown for incident {$incident->id}");

            // Add to digest instead
            $this->addToDigest($incident);

            return false;
        }

        // Check for exponential backoff on repeated alerts
        if ($this->isInBackoffPeriod($incident->affected_service)) {
            Log::info("Alert in backoff period for metric {$incident->affected_service}");
            $this->addToDigest($incident);
            return false;
        }

        return true;
    }

    /**
     * Group similar incidents for digest.
     */
    public function groupSimilarIncidents(): array
    {
        $last4Hours = now()->subHours(4);

        $incidents = SystemIncident::where('detected_at', '>=', $last4Hours)
            ->whereIn('status', ['open', 'investigating'])
            ->get()
            ->groupBy('affected_service');

        return $incidents->map(function ($group, $service) {
            return [
                'service' => $service,
                'count' => $group->count(),
                'severities' => $group->pluck('severity')->unique()->values(),
                'latest' => $group->sortByDesc('detected_at')->first(),
            ];
        })->toArray();
    }

    /**
     * Format alert message with rich details.
     */
    public function formatAlertMessage(SystemIncident $incident): string
    {
        $metric = $incident->triggeredByMetric;

        $message = "**{$incident->title}**\n\n";
        $message .= "**Severity:** {$incident->severity}\n";
        $message .= "**Service:** {$incident->affected_service}\n";
        $message .= "**Detected:** " . $incident->detected_at->format('M j, Y g:i A') . "\n";

        if ($metric) {
            $message .= "**Current Value:** {$metric->value} {$metric->unit}\n";
            if ($metric->threshold_warning) {
                $message .= "**Warning Threshold:** {$metric->threshold_warning} {$metric->unit}\n";
            }
            if ($metric->threshold_critical) {
                $message .= "**Critical Threshold:** {$metric->threshold_critical} {$metric->unit}\n";
            }
        }

        if ($incident->description) {
            $message .= "\n**Details:**\n{$incident->description}\n";
        }

        $message .= "\n**Incident ID:** {$incident->id}";

        return $message;
    }

    /**
     * Format Slack payload with rich blocks.
     */
    protected function formatSlackPayload(string $message, string $severity, ?SystemIncident $incident): array
    {
        $severityEmoji = match ($severity) {
            'critical' => ':red_circle:',
            'warning' => ':large_yellow_circle:',
            'info' => ':large_blue_circle:',
            default => ':white_circle:',
        };

        $severityColor = match ($severity) {
            'critical' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
            default => '#6c757d',
        };

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "{$severityEmoji} " . ($incident?->title ?? 'System Alert'),
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ],
        ];

        if ($incident) {
            $metric = $incident->triggeredByMetric;

            $fields = [
                [
                    'type' => 'mrkdwn',
                    'text' => "*Severity:*\n" . ucfirst($incident->severity),
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Service:*\n" . $incident->affected_service,
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Detected:*\n" . $incident->detected_at->format('M j, g:i A'),
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Incident ID:*\n#" . $incident->id,
                ],
            ];

            if ($metric) {
                $fields[] = [
                    'type' => 'mrkdwn',
                    'text' => "*Current Value:*\n{$metric->value} {$metric->unit}",
                ];
                if ($metric->threshold_critical) {
                    $fields[] = [
                        'type' => 'mrkdwn',
                        'text' => "*Threshold:*\n{$metric->threshold_critical} {$metric->unit}",
                    ];
                }
            }

            $blocks[] = [
                'type' => 'section',
                'fields' => $fields,
            ];

            // Add action buttons
            $dashboardUrl = config('app.url') . '/panel/admin/system-health/incidents/' . $incident->id;

            $blocks[] = [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'View Incident',
                            'emoji' => true,
                        ],
                        'url' => $dashboardUrl,
                        'style' => 'primary',
                    ],
                    [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'View Dashboard',
                            'emoji' => true,
                        ],
                        'url' => config('app.url') . '/panel/admin/system-health',
                    ],
                ],
            ];

            $blocks[] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => ':clock1: ' . now()->format('M j, Y g:i:s A T'),
                    ],
                ],
            ];
        }

        return [
            'blocks' => $blocks,
            'attachments' => [
                [
                    'color' => $severityColor,
                    'fallback' => $incident?->title ?? 'System Alert',
                ],
            ],
        ];
    }

    /**
     * Format PagerDuty Events API v2 payload.
     */
    protected function formatPagerDutyPayload(string $severity, array $details, string $routingKey, ?SystemIncident $incident): array
    {
        $pdSeverity = match ($severity) {
            'critical' => 'critical',
            'high' => 'error',
            'warning' => 'warning',
            default => 'info',
        };

        $payload = [
            'routing_key' => $routingKey,
            'event_action' => 'trigger',
            'dedup_key' => $incident ? "overtimestaff_incident_{$incident->id}" : uniqid('alert_'),
            'payload' => [
                'summary' => $details['summary'] ?? ($incident?->title ?? 'System Alert'),
                'severity' => $pdSeverity,
                'source' => $details['source'] ?? 'OvertimeStaff',
                'component' => $incident?->affected_service ?? ($details['component'] ?? 'system'),
                'group' => $details['group'] ?? 'infrastructure',
                'class' => $details['class'] ?? 'monitoring',
                'custom_details' => array_merge($details, [
                    'incident_id' => $incident?->id,
                    'detected_at' => $incident?->detected_at?->toIso8601String(),
                    'dashboard_url' => $incident
                        ? config('app.url') . '/panel/admin/system-health/incidents/' . $incident->id
                        : config('app.url') . '/panel/admin/system-health',
                ]),
            ],
            'links' => [
                [
                    'href' => config('app.url') . '/panel/admin/system-health',
                    'text' => 'System Health Dashboard',
                ],
            ],
            'images' => [],
        ];

        if ($incident) {
            $payload['links'][] = [
                'href' => config('app.url') . '/panel/admin/system-health/incidents/' . $incident->id,
                'text' => 'View Incident #' . $incident->id,
            ];
        }

        return $payload;
    }

    /**
     * Send to a specific channel type.
     */
    protected function sendToChannel(string $channel, SystemIncident $incident, ?AlertConfiguration $config): void
    {
        $message = $this->formatAlertMessage($incident);
        $severity = $incident->severity;

        try {
            match ($channel) {
                'slack' => $this->sendSlackAlert(
                    $config?->getSlackChannel() ?? $this->getDefaultSlackChannel($severity),
                    $message,
                    $severity,
                    $incident
                ),
                'pagerduty' => $this->sendPagerDutyAlert(
                    $severity,
                    [
                        'summary' => $incident->title,
                        'source' => 'OvertimeStaff',
                        'component' => $incident->affected_service,
                    ],
                    $incident
                ),
                'email' => $this->sendEmailAlert($incident, $config),
                default => Log::warning("Unknown alert channel: {$channel}"),
            };
        } catch (\Exception $e) {
            Log::error("Failed to send to {$channel}: " . $e->getMessage());
            // Don't throw - continue with other channels
        }
    }

    /**
     * Send email alert.
     */
    protected function sendEmailAlert(SystemIncident $incident, ?AlertConfiguration $config): void
    {
        $integration = AlertIntegration::ofType('email')->enabled()->first();

        if (!$integration) {
            return;
        }

        $recipients = $incident->severity === 'critical'
            ? ($integration->getConfigValue('critical_recipients') ?: $integration->getConfigValue('recipients'))
            : $integration->getConfigValue('recipients');

        if (empty($recipients)) {
            Log::warning('No email recipients configured');
            return;
        }

        // Create alert history record
        AlertHistory::create([
            'incident_id' => $incident->id,
            'metric_name' => $incident->affected_service,
            'alert_type' => 'email',
            'severity' => $incident->severity,
            'title' => $incident->title,
            'message' => $this->formatAlertMessage($incident),
            'channel' => implode(', ', $recipients),
            'status' => 'pending',
            'dedup_key' => $this->generateDedupKey($incident),
        ]);

        // Send via Laravel notification (queued)
        Notification::route('mail', $recipients)
            ->notify(new SystemAlertNotification($incident));
    }

    /**
     * Determine alert routing based on severity and configuration.
     */
    protected function determineAlertRouting(SystemIncident $incident, ?AlertConfiguration $config): array
    {
        $channels = [];

        // Default routing based on severity
        if ($incident->severity === 'critical') {
            $channels = ['slack', 'pagerduty', 'email'];
        } elseif ($incident->severity === 'high') {
            $channels = ['slack', 'email'];
        } elseif ($incident->severity === 'warning') {
            $channels = ['slack', 'email'];
        } else {
            $channels = ['slack'];
        }

        // Override with config if available
        if ($config) {
            $channels = [];
            if ($config->slack_enabled) {
                $channels[] = 'slack';
            }
            if ($config->pagerduty_enabled) {
                $channels[] = 'pagerduty';
            }
            if ($config->email_enabled) {
                $channels[] = 'email';
            }
        }

        // Special routing rules based on metric types
        $metricName = $incident->affected_service;

        // API response time P99 > 3000ms: Critical
        if ($metricName === 'api_response_time') {
            $metric = $incident->triggeredByMetric;
            if ($metric && $metric->value > 3000) {
                $channels = ['slack', 'pagerduty', 'email'];
            }
        }

        // Payment success rate < 95%: Critical
        if ($metricName === 'payment_success_rate') {
            $metric = $incident->triggeredByMetric;
            if ($metric && $metric->value < 95) {
                $channels = ['slack', 'pagerduty', 'email'];
            }
        }

        // Queue depth > 1000: Warning
        if ($metricName === 'queue_depth') {
            $metric = $incident->triggeredByMetric;
            if ($metric && $metric->value > 1000 && $metric->value <= 5000) {
                $channels = array_diff($channels, ['pagerduty']);
            }
        }

        return ['channels' => array_unique($channels)];
    }

    /**
     * Get default Slack channel based on severity.
     */
    protected function getDefaultSlackChannel(string $severity): string
    {
        return match ($severity) {
            'critical' => '#incidents',
            'warning', 'high' => '#monitoring',
            default => '#alerts',
        };
    }

    /**
     * Get Slack channel type based on severity.
     */
    protected function getSlackChannelType(string $severity): string
    {
        return match ($severity) {
            'critical' => 'critical',
            'warning', 'high' => 'warnings',
            default => 'default',
        };
    }

    /**
     * Generate deduplication key for an incident.
     */
    protected function generateDedupKey(?SystemIncident $incident): ?string
    {
        if (!$incident) {
            return null;
        }

        return md5($incident->affected_service . '_' . $incident->severity . '_' . $incident->detected_at->format('Y-m-d-H'));
    }

    /**
     * Check if metric is in backoff period.
     */
    protected function isInBackoffPeriod(string $metricName): bool
    {
        $cacheKey = "alert_backoff_{$metricName}";
        $backoffData = Cache::get($cacheKey);

        if (!$backoffData) {
            return false;
        }

        return $backoffData['until'] > now()->timestamp;
    }

    /**
     * Set backoff period for a metric.
     */
    protected function setBackoff(string $metricName): void
    {
        $cacheKey = "alert_backoff_{$metricName}";
        $backoffData = Cache::get($cacheKey);

        // Exponential backoff: 5min, 15min, 30min, 60min, max 120min
        $currentCount = $backoffData['count'] ?? 0;
        $backoffMinutes = min(5 * pow(2, $currentCount), 120);

        Cache::put($cacheKey, [
            'count' => $currentCount + 1,
            'until' => now()->addMinutes($backoffMinutes)->timestamp,
        ], now()->addHours(24));
    }

    /**
     * Add incident to digest for later summary.
     */
    protected function addToDigest(SystemIncident $incident): void
    {
        $digest = AlertDigest::getOrCreateForPeriod();

        $alertHistory = AlertHistory::create([
            'incident_id' => $incident->id,
            'metric_name' => $incident->affected_service,
            'alert_type' => 'digest',
            'severity' => $incident->severity,
            'title' => $incident->title,
            'message' => $this->formatAlertMessage($incident),
            'status' => 'suppressed',
            'dedup_key' => $this->generateDedupKey($incident),
        ]);

        $digest->addAlert($alertHistory);
    }

    /**
     * Send digest summary.
     */
    public function sendDigestSummary(): void
    {
        $digests = AlertDigest::readyToSend()->get();

        foreach ($digests as $digest) {
            if ($digest->alert_count === 0) {
                $digest->cancel();
                continue;
            }

            $message = $digest->generateSummary();

            // Send to Slack
            $this->sendSlackAlert(
                '#monitoring',
                $message,
                'info'
            );

            $digest->markAsSent();
        }
    }

    /**
     * Send Slack resolution message.
     */
    protected function sendSlackResolution(SystemIncident $incident, string $message): void
    {
        $integration = AlertIntegration::ofType('slack')->enabled()->first();

        if (!$integration) {
            return;
        }

        $webhookUrl = $integration->getWebhookUrl('default');

        if (empty($webhookUrl)) {
            return;
        }

        $payload = [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':white_check_mark: Incident Resolved',
                        'emoji' => true,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ],
                ],
            ],
            'attachments' => [
                [
                    'color' => '#28a745',
                ],
            ],
        ];

        try {
            Http::timeout(10)->post($webhookUrl, $payload);
        } catch (\Exception $e) {
            Log::error('Slack resolution notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Format resolution message.
     */
    protected function formatResolutionMessage(SystemIncident $incident, ?int $resolutionTime): string
    {
        $message = "*{$incident->title}* has been resolved.\n\n";
        $message .= "*Service:* {$incident->affected_service}\n";

        if ($resolutionTime !== null) {
            if ($resolutionTime < 60) {
                $message .= "*Resolution Time:* {$resolutionTime} minutes\n";
            } else {
                $hours = floor($resolutionTime / 60);
                $minutes = $resolutionTime % 60;
                $message .= "*Resolution Time:* {$hours}h {$minutes}m\n";
            }
        }

        if ($incident->resolution_notes) {
            $message .= "\n*Resolution Notes:*\n{$incident->resolution_notes}\n";
        }

        $message .= "\n*Incident ID:* #{$incident->id}";

        return $message;
    }

    /**
     * Check if alerting is enabled globally.
     */
    protected function isAlertingEnabled(): bool
    {
        return config('alerting.enabled', env('ALERTS_ENABLED', true));
    }

    /**
     * Get alert configuration for a metric.
     */
    protected function getAlertConfiguration(string $metricName): ?AlertConfiguration
    {
        return AlertConfiguration::enabled()
            ->forMetric($metricName)
            ->first();
    }

    /**
     * Test Slack integration.
     */
    public function testSlackIntegration(): array
    {
        $integration = AlertIntegration::ofType('slack')->first();

        if (!$integration) {
            return ['success' => false, 'message' => 'Slack integration not configured'];
        }

        $webhookUrl = $integration->getWebhookUrl('default');

        if (empty($webhookUrl)) {
            return ['success' => false, 'message' => 'No webhook URL configured'];
        }

        try {
            $payload = [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => ':white_check_mark: Test Alert - Connection Successful',
                            'emoji' => true,
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'This is a test alert from OvertimeStaff. Your Slack integration is working correctly!',
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => ':clock1: ' . now()->format('M j, Y g:i:s A T'),
                            ],
                        ],
                    ],
                ],
                'attachments' => [
                    [
                        'color' => '#28a745',
                    ],
                ],
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                $integration->markAsVerified();
                return ['success' => true, 'message' => 'Test alert sent successfully'];
            }

            return ['success' => false, 'message' => 'Failed: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Test PagerDuty integration.
     */
    public function testPagerDutyIntegration(): array
    {
        $integration = AlertIntegration::ofType('pagerduty')->first();

        if (!$integration) {
            return ['success' => false, 'message' => 'PagerDuty integration not configured'];
        }

        $routingKey = $integration->getIntegrationKey();

        if (empty($routingKey)) {
            return ['success' => false, 'message' => 'No integration key configured'];
        }

        try {
            $payload = [
                'routing_key' => $routingKey,
                'event_action' => 'trigger',
                'dedup_key' => 'overtimestaff_test_' . time(),
                'payload' => [
                    'summary' => 'Test Alert - OvertimeStaff Connection Successful',
                    'severity' => 'info',
                    'source' => 'OvertimeStaff',
                    'component' => 'alerting_system',
                    'group' => 'test',
                    'class' => 'test',
                    'custom_details' => [
                        'test' => true,
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
            ];

            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::PAGERDUTY_API_URL, $payload);

            if ($response->successful()) {
                // Immediately resolve the test alert
                $resolvePayload = [
                    'routing_key' => $routingKey,
                    'dedup_key' => 'overtimestaff_test_' . time(),
                    'event_action' => 'resolve',
                ];

                Http::timeout(10)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post(self::PAGERDUTY_API_URL, $resolvePayload);

                $integration->markAsVerified();
                return ['success' => true, 'message' => 'Test alert sent and resolved successfully'];
            }

            return ['success' => false, 'message' => 'Failed: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get alert statistics.
     */
    public function getAlertStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_alerts' => AlertHistory::where('created_at', '>=', $startDate)->count(),
            'sent_alerts' => AlertHistory::sent()->where('created_at', '>=', $startDate)->count(),
            'failed_alerts' => AlertHistory::failed()->where('created_at', '>=', $startDate)->count(),
            'suppressed_alerts' => AlertHistory::where('status', 'suppressed')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'by_type' => AlertHistory::where('created_at', '>=', $startDate)
                ->select('alert_type', \DB::raw('count(*) as count'))
                ->groupBy('alert_type')
                ->pluck('count', 'alert_type'),
            'by_severity' => AlertHistory::where('created_at', '>=', $startDate)
                ->select('severity', \DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'average_resolution_time' => AlertHistory::where('resolved', true)
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('resolution_duration_minutes')
                ->avg('resolution_duration_minutes'),
        ];
    }
}
