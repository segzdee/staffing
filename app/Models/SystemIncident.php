<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'severity',
        'status',
        'triggered_by_metric_id',
        'affected_service',
        'detected_at',
        'acknowledged_at',
        'resolved_at',
        'duration_minutes',
        'assigned_to_user_id',
        'affected_users',
        'affected_shifts',
        'failed_payments',
        'resolution_notes',
        'prevention_steps',
        'email_sent',
        'slack_sent',
        'last_notification_sent_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'duration_minutes' => 'integer',
        'affected_users' => 'integer',
        'affected_shifts' => 'integer',
        'failed_payments' => 'integer',
        'email_sent' => 'boolean',
        'slack_sent' => 'boolean',
        'last_notification_sent_at' => 'datetime',
    ];

    /**
     * Get the metric that triggered this incident.
     */
    public function triggeredByMetric()
    {
        return $this->belongsTo(SystemHealthMetric::class, 'triggered_by_metric_id');
    }

    /**
     * Get the user assigned to this incident.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Calculate and update the duration when resolved.
     */
    public function calculateDuration()
    {
        if ($this->resolved_at && $this->detected_at) {
            $this->duration_minutes = $this->detected_at->diffInMinutes($this->resolved_at);
            $this->save();
        }
    }

    /**
     * Mark incident as acknowledged.
     */
    public function acknowledge($userId = null)
    {
        $this->update([
            'status' => 'investigating',
            'acknowledged_at' => now(),
            'assigned_to_user_id' => $userId ?? $this->assigned_to_user_id,
        ]);
    }

    /**
     * Mark incident as resolved.
     */
    public function resolve($resolutionNotes = null, $preventionSteps = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
            'prevention_steps' => $preventionSteps,
        ]);

        $this->calculateDuration();

        // Send resolution notification via AlertingService
        try {
            $systemHealthService = app(\App\Services\SystemHealthService::class);
            $systemHealthService->notifyIncidentResolved($this);
        } catch (\Exception $e) {
            // Log but don't fail the resolution
            \Log::warning("Failed to send resolution notification for incident {$this->id}: " . $e->getMessage());
        }
    }

    /**
     * Scope to get open incidents.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating']);
    }

    /**
     * Scope to get critical incidents.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope to get incidents by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
