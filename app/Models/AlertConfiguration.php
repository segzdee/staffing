<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_name',
        'display_name',
        'description',
        'warning_threshold',
        'critical_threshold',
        'comparison',
        'severity',
        'slack_channel',
        'pagerduty_routing_key',
        'enabled',
        'slack_enabled',
        'pagerduty_enabled',
        'email_enabled',
        'cooldown_minutes',
        'escalation_delay_minutes',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'additional_settings',
    ];

    protected $casts = [
        'warning_threshold' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'enabled' => 'boolean',
        'slack_enabled' => 'boolean',
        'pagerduty_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'additional_settings' => 'array',
    ];

    /**
     * Scope to get only enabled configurations.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to get configurations for a specific metric.
     */
    public function scopeForMetric($query, string $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Get alert history for this configuration.
     */
    public function alertHistory()
    {
        return $this->hasMany(AlertHistory::class, 'alert_configuration_id');
    }

    /**
     * Check if value exceeds warning threshold.
     */
    public function exceedsWarningThreshold($value): bool
    {
        if ($this->warning_threshold === null) {
            return false;
        }

        return $this->compareValue($value, $this->warning_threshold);
    }

    /**
     * Check if value exceeds critical threshold.
     */
    public function exceedsCriticalThreshold($value): bool
    {
        if ($this->critical_threshold === null) {
            return false;
        }

        return $this->compareValue($value, $this->critical_threshold);
    }

    /**
     * Compare value against threshold based on comparison type.
     */
    protected function compareValue($value, $threshold): bool
    {
        return match ($this->comparison) {
            'greater_than' => $value > $threshold,
            'less_than' => $value < $threshold,
            'equals' => $value == $threshold,
            default => false,
        };
    }

    /**
     * Check if currently in quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now();
        $start = $now->copy()->setTimeFromTimeString($this->quiet_hours_start);
        $end = $now->copy()->setTimeFromTimeString($this->quiet_hours_end);

        // Handle overnight quiet hours (e.g., 22:00 - 08:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Get the Slack channel to use for this alert.
     */
    public function getSlackChannel(): ?string
    {
        // Use specific channel if configured, otherwise return null to use default
        return $this->slack_channel ?: null;
    }

    /**
     * Get the PagerDuty routing key to use for this alert.
     */
    public function getPagerDutyRoutingKey(): ?string
    {
        return $this->pagerduty_routing_key ?: null;
    }

    /**
     * Get default alert configurations for seeding.
     */
    public static function getDefaultConfigurations(): array
    {
        return [
            [
                'metric_name' => 'api_response_time',
                'display_name' => 'API Response Time (P99)',
                'description' => 'Monitors API response time percentiles',
                'warning_threshold' => 1000,
                'critical_threshold' => 3000,
                'comparison' => 'greater_than',
                'severity' => 'critical',
                'cooldown_minutes' => 30,
            ],
            [
                'metric_name' => 'payment_success_rate',
                'display_name' => 'Payment Success Rate',
                'description' => 'Monitors payment processing success rate',
                'warning_threshold' => 98,
                'critical_threshold' => 95,
                'comparison' => 'less_than',
                'severity' => 'critical',
                'cooldown_minutes' => 15,
            ],
            [
                'metric_name' => 'queue_depth',
                'display_name' => 'Queue Depth',
                'description' => 'Monitors job queue depth',
                'warning_threshold' => 1000,
                'critical_threshold' => 5000,
                'comparison' => 'greater_than',
                'severity' => 'warning',
                'cooldown_minutes' => 60,
            ],
            [
                'metric_name' => 'shift_fill_rate',
                'display_name' => 'Shift Fill Rate',
                'description' => 'Monitors shift fill rate percentage',
                'warning_threshold' => 70,
                'critical_threshold' => 50,
                'comparison' => 'less_than',
                'severity' => 'warning',
                'cooldown_minutes' => 240,
            ],
            [
                'metric_name' => 'error_rate',
                'display_name' => 'Application Error Rate',
                'description' => 'Monitors application error rate percentage',
                'warning_threshold' => 5,
                'critical_threshold' => 10,
                'comparison' => 'greater_than',
                'severity' => 'critical',
                'cooldown_minutes' => 30,
            ],
            [
                'metric_name' => 'health_score',
                'display_name' => 'Overall Health Score',
                'description' => 'Monitors overall system health score',
                'warning_threshold' => 70,
                'critical_threshold' => 50,
                'comparison' => 'less_than',
                'severity' => 'critical',
                'cooldown_minutes' => 30,
            ],
            [
                'metric_name' => 'database_connections',
                'display_name' => 'Database Connections',
                'description' => 'Monitors active database connections',
                'warning_threshold' => 100,
                'critical_threshold' => 150,
                'comparison' => 'greater_than',
                'severity' => 'warning',
                'cooldown_minutes' => 60,
            ],
            [
                'metric_name' => 'disk_usage',
                'display_name' => 'Disk Usage',
                'description' => 'Monitors disk space usage percentage',
                'warning_threshold' => 80,
                'critical_threshold' => 90,
                'comparison' => 'greater_than',
                'severity' => 'warning',
                'cooldown_minutes' => 240,
            ],
        ];
    }
}
