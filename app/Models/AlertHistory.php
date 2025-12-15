<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertHistory extends Model
{
    use HasFactory;

    protected $table = 'alert_history';

    protected $fillable = [
        'incident_id',
        'alert_configuration_id',
        'metric_name',
        'alert_type',
        'severity',
        'title',
        'message',
        'channel',
        'status',
        'error_message',
        'retry_count',
        'external_id',
        'dedup_key',
        'acknowledged_by_user_id',
        'acknowledged_at',
        'resolved',
        'resolved_at',
        'resolution_duration_minutes',
        'sent_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the incident associated with this alert.
     */
    public function incident()
    {
        return $this->belongsTo(SystemIncident::class, 'incident_id');
    }

    /**
     * Get the alert configuration.
     */
    public function alertConfiguration()
    {
        return $this->belongsTo(AlertConfiguration::class, 'alert_configuration_id');
    }

    /**
     * Get the user who acknowledged this alert.
     */
    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    /**
     * Scope to get pending alerts.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent alerts.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed alerts.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get alerts by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to get unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to get alerts within a time range.
     */
    public function scopeWithinHours($query, int $hours)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Mark alert as sent.
     */
    public function markAsSent(?string $externalId = null): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'external_id' => $externalId,
        ]);

        return $this;
    }

    /**
     * Mark alert as failed.
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);

        return $this;
    }

    /**
     * Mark alert as suppressed.
     */
    public function markAsSuppressed(string $reason): self
    {
        $this->update([
            'status' => 'suppressed',
            'error_message' => "Suppressed: {$reason}",
        ]);

        return $this;
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(int $userId): self
    {
        $this->update([
            'acknowledged_by_user_id' => $userId,
            'acknowledged_at' => now(),
        ]);

        return $this;
    }

    /**
     * Resolve the alert.
     */
    public function resolve(): self
    {
        $resolutionDuration = $this->created_at
            ? now()->diffInMinutes($this->created_at)
            : null;

        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution_duration_minutes' => $resolutionDuration,
        ]);

        return $this;
    }

    /**
     * Check if alert can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    /**
     * Get severity badge class for UI.
     */
    public function getSeverityBadgeClass(): string
    {
        return match ($this->severity) {
            'critical' => 'badge-danger',
            'warning' => 'badge-warning',
            'info' => 'badge-info',
            default => 'badge-secondary',
        };
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'sent' => 'badge-success',
            'failed' => 'badge-danger',
            'pending' => 'badge-warning',
            'suppressed' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }
}
