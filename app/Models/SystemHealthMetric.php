<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'value',
        'unit',
        'environment',
        'metadata',
        'is_healthy',
        'threshold_warning',
        'threshold_critical',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'threshold_warning' => 'decimal:4',
        'threshold_critical' => 'decimal:4',
        'metadata' => 'array',
        'is_healthy' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get incidents triggered by this metric.
     */
    public function triggeredIncidents()
    {
        return $this->hasMany(SystemIncident::class, 'triggered_by_metric_id');
    }

    /**
     * Check if value exceeds warning threshold.
     */
    public function exceedsWarningThreshold()
    {
        if (is_null($this->threshold_warning)) {
            return false;
        }

        return $this->value >= $this->threshold_warning;
    }

    /**
     * Check if value exceeds critical threshold.
     */
    public function exceedsCriticalThreshold()
    {
        if (is_null($this->threshold_critical)) {
            return false;
        }

        return $this->value >= $this->threshold_critical;
    }

    /**
     * Scope to get metrics by type.
     */
    public function scopeOfType($query, $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    /**
     * Scope to get unhealthy metrics.
     */
    public function scopeUnhealthy($query)
    {
        return $query->where('is_healthy', false);
    }

    /**
     * Scope to get metrics within time range.
     */
    public function scopeInTimeRange($query, $start, $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }
}
