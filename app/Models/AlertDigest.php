<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertDigest extends Model
{
    use HasFactory;

    protected $fillable = [
        'digest_key',
        'alert_count',
        'alert_ids',
        'metrics_summary',
        'status',
        'period_start',
        'period_end',
        'sent_at',
    ];

    protected $casts = [
        'alert_ids' => 'array',
        'metrics_summary' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Scope to get collecting digests.
     */
    public function scopeCollecting($query)
    {
        return $query->where('status', 'collecting');
    }

    /**
     * Scope to get digests ready to send (collecting for over 4 hours).
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'collecting')
            ->where('period_start', '<=', now()->subHours(4));
    }

    /**
     * Add an alert to this digest.
     */
    public function addAlert(AlertHistory $alert): self
    {
        $alertIds = $this->alert_ids ?? [];
        $alertIds[] = $alert->id;

        $summary = $this->metrics_summary ?? [];
        $metricName = $alert->metric_name;

        if (!isset($summary[$metricName])) {
            $summary[$metricName] = [
                'count' => 0,
                'severities' => [],
            ];
        }

        $summary[$metricName]['count']++;
        $summary[$metricName]['severities'][$alert->severity] =
            ($summary[$metricName]['severities'][$alert->severity] ?? 0) + 1;

        $this->update([
            'alert_ids' => $alertIds,
            'alert_count' => count($alertIds),
            'metrics_summary' => $summary,
        ]);

        return $this;
    }

    /**
     * Mark digest as sent.
     */
    public function markAsSent(): self
    {
        $this->update([
            'status' => 'sent',
            'period_end' => now(),
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Cancel the digest.
     */
    public function cancel(): self
    {
        $this->update([
            'status' => 'cancelled',
            'period_end' => now(),
        ]);

        return $this;
    }

    /**
     * Get alerts in this digest.
     */
    public function getAlerts()
    {
        $alertIds = $this->alert_ids ?? [];
        return AlertHistory::whereIn('id', $alertIds)->get();
    }

    /**
     * Generate digest summary text.
     */
    public function generateSummary(): string
    {
        $summary = $this->metrics_summary ?? [];
        $lines = [];

        $lines[] = "Alert Digest Summary ({$this->alert_count} alerts)";
        $lines[] = "Period: " . $this->period_start->format('M j, H:i') . " - " . now()->format('M j, H:i');
        $lines[] = "";

        foreach ($summary as $metricName => $data) {
            $severityStr = [];
            foreach ($data['severities'] as $severity => $count) {
                $severityStr[] = "{$count} {$severity}";
            }
            $lines[] = "- {$metricName}: {$data['count']} alerts (" . implode(', ', $severityStr) . ")";
        }

        return implode("\n", $lines);
    }

    /**
     * Get or create a digest for the current period.
     */
    public static function getOrCreateForPeriod(): self
    {
        $digestKey = 'digest_' . now()->format('Y-m-d_H');

        return static::firstOrCreate(
            ['digest_key' => $digestKey, 'status' => 'collecting'],
            [
                'alert_count' => 0,
                'alert_ids' => [],
                'metrics_summary' => [],
                'period_start' => now()->startOfHour(),
            ]
        );
    }
}
