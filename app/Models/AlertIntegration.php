<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AlertIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'display_name',
        'enabled',
        'config',
        'verified',
        'last_verified_at',
        'last_used_at',
        'total_alerts_sent',
        'failed_alerts',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'verified' => 'boolean',
        'config' => 'array',
        'last_verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Scope to get enabled integrations.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to get integration by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get a specific config value.
     */
    public function getConfigValue(string $key, $default = null)
    {
        $config = $this->config ?? [];
        return $config[$key] ?? $default;
    }

    /**
     * Set a specific config value.
     */
    public function setConfigValue(string $key, $value): self
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
        return $this;
    }

    /**
     * Get decrypted webhook URL for Slack.
     */
    public function getWebhookUrl(string $channel = 'default'): ?string
    {
        $webhooks = $this->getConfigValue('webhooks', []);
        return $webhooks[$channel] ?? $webhooks['default'] ?? null;
    }

    /**
     * Set webhook URL for Slack.
     */
    public function setWebhookUrl(string $url, string $channel = 'default'): self
    {
        $config = $this->config ?? [];
        $webhooks = $config['webhooks'] ?? [];
        $webhooks[$channel] = $url;
        $config['webhooks'] = $webhooks;
        $this->config = $config;
        return $this;
    }

    /**
     * Get PagerDuty integration key.
     */
    public function getIntegrationKey(): ?string
    {
        return $this->getConfigValue('integration_key');
    }

    /**
     * Set PagerDuty integration key.
     */
    public function setIntegrationKey(string $key): self
    {
        return $this->setConfigValue('integration_key', $key);
    }

    /**
     * Get routing key for severity.
     */
    public function getRoutingKey(string $severity = 'default'): ?string
    {
        $routingKeys = $this->getConfigValue('routing_keys', []);
        return $routingKeys[$severity] ?? $routingKeys['default'] ?? $this->getIntegrationKey();
    }

    /**
     * Set routing key for severity.
     */
    public function setRoutingKey(string $key, string $severity = 'default'): self
    {
        $config = $this->config ?? [];
        $routingKeys = $config['routing_keys'] ?? [];
        $routingKeys[$severity] = $key;
        $config['routing_keys'] = $routingKeys;
        $this->config = $config;
        return $this;
    }

    /**
     * Mark as used.
     */
    public function markAsUsed(bool $success = true): self
    {
        $updates = [
            'last_used_at' => now(),
        ];

        if ($success) {
            $updates['total_alerts_sent'] = $this->total_alerts_sent + 1;
        } else {
            $updates['failed_alerts'] = $this->failed_alerts + 1;
        }

        $this->update($updates);
        return $this;
    }

    /**
     * Mark as verified.
     */
    public function markAsVerified(): self
    {
        $this->update([
            'verified' => true,
            'last_verified_at' => now(),
        ]);
        return $this;
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRate(): float
    {
        $total = $this->total_alerts_sent + $this->failed_alerts;
        if ($total === 0) {
            return 100.0;
        }
        return round(($this->total_alerts_sent / $total) * 100, 2);
    }

    /**
     * Get default integrations for seeding.
     */
    public static function getDefaultIntegrations(): array
    {
        return [
            [
                'type' => 'slack',
                'display_name' => 'Slack',
                'enabled' => false,
                'config' => [
                    'webhooks' => [
                        'default' => '',
                        'critical' => '',
                        'warnings' => '',
                    ],
                    'default_channel' => '#monitoring',
                    'mention_on_critical' => '@channel',
                ],
            ],
            [
                'type' => 'pagerduty',
                'display_name' => 'PagerDuty',
                'enabled' => false,
                'config' => [
                    'integration_key' => '',
                    'routing_keys' => [
                        'default' => '',
                        'critical' => '',
                    ],
                    'api_url' => 'https://events.pagerduty.com/v2/enqueue',
                ],
            ],
            [
                'type' => 'email',
                'display_name' => 'Email',
                'enabled' => true,
                'config' => [
                    'recipients' => [],
                    'critical_recipients' => [],
                    'from_address' => '',
                    'from_name' => 'OvertimeStaff Alerts',
                ],
            ],
        ];
    }
}
