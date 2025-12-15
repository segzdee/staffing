<?php

namespace App\Services;

use App\Models\SystemHealthMetric;
use App\Models\SystemIncident;
use App\Models\AlertConfiguration;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    protected ?AlertingService $alertingService = null;

    /**
     * Get or create the AlertingService instance.
     */
    protected function getAlertingService(): AlertingService
    {
        if ($this->alertingService === null) {
            $this->alertingService = app(AlertingService::class);
        }
        return $this->alertingService;
    }

    /**
     * Record a health metric.
     */
    public function recordMetric($metricType, $value, $unit = null, $metadata = [])
    {
        $thresholds = $this->getThresholds($metricType);

        $isHealthy = true;
        if (isset($thresholds['critical']) && $value >= $thresholds['critical']) {
            $isHealthy = false;
        }

        $metric = SystemHealthMetric::create([
            'metric_type' => $metricType,
            'value' => $value,
            'unit' => $unit,
            'environment' => config('app.env'),
            'metadata' => $metadata,
            'is_healthy' => $isHealthy,
            'threshold_warning' => $thresholds['warning'] ?? null,
            'threshold_critical' => $thresholds['critical'] ?? null,
            'recorded_at' => now(),
        ]);

        // Check if we need to create an incident
        if (!$isHealthy) {
            $this->checkAndCreateIncident($metric);
        }

        return $metric;
    }

    /**
     * Get current system health dashboard data.
     */
    public function getDashboardData()
    {
        return [
            'api_performance' => $this->getApiPerformance(),
            'shift_metrics' => $this->getShiftMetrics(),
            'payment_metrics' => $this->getPaymentMetrics(),
            'user_activity' => $this->getUserActivity(),
            'queue_status' => $this->getQueueStatus(),
            'infrastructure' => $this->getInfrastructureMetrics(),
            'recent_incidents' => $this->getRecentIncidents(),
            'overall_health' => $this->calculateOverallHealth(),
        ];
    }

    /**
     * Get API performance metrics.
     */
    public function getApiPerformance()
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();

        $metrics = SystemHealthMetric::where('metric_type', 'api_response_time')
            ->whereBetween('recorded_at', [$lastHour, $now])
            ->orderBy('recorded_at')
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'p50' => null,
                'p95' => null,
                'p99' => null,
                'average' => null,
                'sample_count' => 0,
            ];
        }

        $values = $metrics->pluck('value')->sort()->values();
        $count = $values->count();

        return [
            'p50' => $this->percentile($values, 50),
            'p95' => $this->percentile($values, 95),
            'p99' => $this->percentile($values, 99),
            'average' => round($values->average(), 2),
            'sample_count' => $count,
            'unit' => 'ms',
        ];
    }

    /**
     * Get shift fill rate for last 24 hours.
     */
    public function getShiftMetrics()
    {
        $last24Hours = Carbon::now()->subHours(24);

        $totalShifts = Shift::where('created_at', '>=', $last24Hours)->count();
        $filledShifts = Shift::where('created_at', '>=', $last24Hours)
            ->where('status', 'filled')
            ->count();

        $fillRate = $totalShifts > 0 ? round(($filledShifts / $totalShifts) * 100, 2) : 0;

        // Record metric
        $this->recordMetric('shift_fill_rate', $fillRate, '%');

        return [
            'total_shifts' => $totalShifts,
            'filled_shifts' => $filledShifts,
            'fill_rate' => $fillRate,
            'pending_shifts' => Shift::where('status', 'open')->count(),
            'active_shifts' => Shift::where('status', 'in_progress')->count(),
        ];
    }

    /**
     * Get payment success rate.
     */
    public function getPaymentMetrics()
    {
        $last24Hours = Carbon::now()->subHours(24);

        $totalPayments = ShiftPayment::where('created_at', '>=', $last24Hours)->count();
        $successfulPayments = ShiftPayment::where('created_at', '>=', $last24Hours)
            ->whereIn('status', ['completed', 'paid'])
            ->count();
        $failedPayments = ShiftPayment::where('created_at', '>=', $last24Hours)
            ->where('status', 'failed')
            ->count();

        $successRate = $totalPayments > 0
            ? round(($successfulPayments / $totalPayments) * 100, 2)
            : 100;

        // Record metric
        $this->recordMetric('payment_success_rate', $successRate, '%');

        return [
            'total_payments' => $totalPayments,
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => $successRate,
            'pending_payments' => ShiftPayment::where('status', 'pending')->count(),
        ];
    }

    /**
     * Get active user metrics.
     */
    public function getUserActivity()
    {
        $last15Minutes = Carbon::now()->subMinutes(15);
        $lastHour = Carbon::now()->subHour();
        $last24Hours = Carbon::now()->subHours(24);

        // This would typically use a last_seen_at field
        // For now, we'll use a simplified version
        $activeUsers15min = Cache::get('active_users_15min', 0);
        $activeUsersHour = Cache::get('active_users_hour', 0);
        $activeUsers24h = Cache::get('active_users_24h', 0);

        // Record metric
        $this->recordMetric('active_users', $activeUsers15min, 'count', [
            'period' => '15_minutes',
        ]);

        return [
            'active_15_minutes' => $activeUsers15min,
            'active_1_hour' => $activeUsersHour,
            'active_24_hours' => $activeUsers24h,
            'total_users' => User::count(),
            'new_signups_today' => User::whereDate('created_at', Carbon::today())->count(),
        ];
    }

    /**
     * Get queue status.
     */
    public function getQueueStatus()
    {
        try {
            // Get queue depths for different queues
            $queues = ['default', 'notifications', 'payments', 'emails'];
            $queueData = [];

            foreach ($queues as $queueName) {
                $size = Redis::llen("queues:{$queueName}");
                $queueData[$queueName] = $size;

                // Record metric for default queue
                if ($queueName === 'default') {
                    $this->recordMetric('queue_depth', $size, 'jobs', [
                        'queue' => $queueName,
                    ]);
                }
            }

            return [
                'queues' => $queueData,
                'total_jobs' => array_sum($queueData),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            return [
                'queues' => [],
                'total_jobs' => 0,
                'failed_jobs' => 0,
                'error' => 'Unable to fetch queue status',
            ];
        }
    }

    /**
     * Get infrastructure metrics.
     */
    public function getInfrastructureMetrics()
    {
        $metrics = [];

        // Database connections
        try {
            $dbConnections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $metrics['database_connections'] = $dbConnections[0]->Value ?? 0;
        } catch (\Exception $e) {
            $metrics['database_connections'] = 'unavailable';
        }

        // Redis connections
        try {
            $redisInfo = Redis::info();
            $metrics['redis_connections'] = $redisInfo['connected_clients'] ?? 0;
            $metrics['redis_memory_used'] = $redisInfo['used_memory_human'] ?? 'unavailable';
        } catch (\Exception $e) {
            $metrics['redis_connections'] = 'unavailable';
            $metrics['redis_memory_used'] = 'unavailable';
        }

        // Disk usage
        $metrics['disk_usage'] = [
            'free' => disk_free_space('/'),
            'total' => disk_total_space('/'),
            'used_percentage' => round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 2),
        ];

        // Memory usage
        $metrics['memory_usage'] = [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];

        return $metrics;
    }

    /**
     * Get recent incidents.
     */
    public function getRecentIncidents($limit = 10)
    {
        return SystemIncident::with(['triggeredByMetric', 'assignedTo'])
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate overall system health score.
     */
    public function calculateOverallHealth()
    {
        $lastHour = Carbon::now()->subHour();

        $unhealthyMetrics = SystemHealthMetric::where('is_healthy', false)
            ->where('recorded_at', '>=', $lastHour)
            ->count();

        $totalMetrics = SystemHealthMetric::where('recorded_at', '>=', $lastHour)->count();

        $healthScore = $totalMetrics > 0
            ? round((($totalMetrics - $unhealthyMetrics) / $totalMetrics) * 100, 2)
            : 100;

        $openIncidents = SystemIncident::open()->count();
        $criticalIncidents = SystemIncident::open()->critical()->count();

        $status = 'healthy';
        if ($criticalIncidents > 0) {
            $status = 'critical';
        } elseif ($openIncidents > 0) {
            $status = 'degraded';
        } elseif ($healthScore < 90) {
            $status = 'warning';
        }

        return [
            'score' => $healthScore,
            'status' => $status,
            'open_incidents' => $openIncidents,
            'critical_incidents' => $criticalIncidents,
            'unhealthy_metrics_count' => $unhealthyMetrics,
        ];
    }

    /**
     * Check and create incident if needed.
     */
    protected function checkAndCreateIncident(SystemHealthMetric $metric)
    {
        // Check if there's already an open incident for this metric type
        $existingIncident = SystemIncident::where('affected_service', $metric->metric_type)
            ->where('status', 'open')
            ->first();

        if ($existingIncident) {
            return; // Don't create duplicate incidents
        }

        // Determine severity
        $severity = $metric->exceedsCriticalThreshold() ? 'critical' : 'high';

        // Create incident
        $incident = SystemIncident::create([
            'title' => $this->generateIncidentTitle($metric),
            'description' => $this->generateIncidentDescription($metric),
            'severity' => $severity,
            'status' => 'open',
            'triggered_by_metric_id' => $metric->id,
            'affected_service' => $metric->metric_type,
            'detected_at' => now(),
        ]);

        // Send notifications if critical
        if ($severity === 'critical') {
            $this->notifyIncident($incident);
        }

        return $incident;
    }

    /**
     * Generate incident title.
     */
    protected function generateIncidentTitle(SystemHealthMetric $metric)
    {
        $titles = [
            'api_response_time' => 'High API Response Time Detected',
            'shift_fill_rate' => 'Low Shift Fill Rate Detected',
            'payment_success_rate' => 'Payment Success Rate Below Threshold',
            'queue_depth' => 'High Queue Depth Detected',
            'error_rate' => 'Elevated Error Rate Detected',
        ];

        return $titles[$metric->metric_type] ?? "System Health Issue: {$metric->metric_type}";
    }

    /**
     * Generate incident description.
     */
    protected function generateIncidentDescription(SystemHealthMetric $metric)
    {
        return sprintf(
            'Metric %s exceeded threshold with value %.2f%s (warning: %.2f, critical: %.2f)',
            $metric->metric_type,
            $metric->value,
            $metric->unit ?? '',
            $metric->threshold_warning ?? 0,
            $metric->threshold_critical ?? 0
        );
    }

    /**
     * Notify about incident using AlertingService.
     */
    protected function notifyIncident(SystemIncident $incident)
    {
        try {
            // Use AlertingService to send external alerts (Slack, PagerDuty, Email)
            $this->getAlertingService()->sendAlert($incident);

            $incident->update([
                'email_sent' => true,
                'slack_sent' => true,
                'last_notification_sent_at' => now(),
            ]);

            Log::info("Alert sent for incident {$incident->id}", [
                'incident_id' => $incident->id,
                'severity' => $incident->severity,
                'affected_service' => $incident->affected_service,
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break monitoring
            Log::error("Failed to send alert for incident {$incident->id}: " . $e->getMessage(), [
                'incident_id' => $incident->id,
                'exception' => $e,
            ]);

            // Still update the incident to prevent repeated attempts
            $incident->update([
                'last_notification_sent_at' => now(),
            ]);
        }
    }

    /**
     * Send resolution notification when incident is resolved.
     */
    public function notifyIncidentResolved(SystemIncident $incident)
    {
        try {
            $this->getAlertingService()->sendResolutionNotification($incident);

            Log::info("Resolution notification sent for incident {$incident->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send resolution notification for incident {$incident->id}: " . $e->getMessage());
        }
    }

    /**
     * Get metric thresholds.
     * First checks AlertConfiguration table, then falls back to defaults.
     */
    protected function getThresholds($metricType)
    {
        // First, check if there's a custom configuration in the database
        try {
            $config = AlertConfiguration::where('metric_name', $metricType)
                ->where('enabled', true)
                ->first();

            if ($config) {
                return [
                    'warning' => $config->warning_threshold,
                    'critical' => $config->critical_threshold,
                ];
            }
        } catch (\Exception $e) {
            // Database might not be available, fall back to defaults
            Log::debug("Could not fetch AlertConfiguration for {$metricType}: " . $e->getMessage());
        }

        // Default thresholds
        $thresholds = [
            'api_response_time' => ['warning' => 500, 'critical' => 1000], // ms
            'shift_fill_rate' => ['warning' => 70, 'critical' => 50], // percentage
            'payment_success_rate' => ['warning' => 95, 'critical' => 90], // percentage (inverse - lower is bad)
            'queue_depth' => ['warning' => 1000, 'critical' => 5000], // job count
            'error_rate' => ['warning' => 5, 'critical' => 10], // percentage
            'health_score' => ['warning' => 70, 'critical' => 50], // percentage
            'database_connections' => ['warning' => 100, 'critical' => 150], // count
            'disk_usage' => ['warning' => 80, 'critical' => 90], // percentage
        ];

        return $thresholds[$metricType] ?? [];
    }

    /**
     * Calculate percentile from array of values.
     */
    protected function percentile($values, $percentile)
    {
        $index = ($percentile / 100) * ($values->count() - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        if ($lower === $upper) {
            return round($values[$lower], 2);
        }

        return round($values[$lower] * (1 - $weight) + $values[$upper] * $weight, 2);
    }

    /**
     * Track API response time.
     */
    public function trackApiResponseTime($endpoint, $responseTime)
    {
        return $this->recordMetric('api_response_time', $responseTime, 'ms', [
            'endpoint' => $endpoint,
        ]);
    }
}
