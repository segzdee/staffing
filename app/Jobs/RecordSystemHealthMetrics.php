<?php

namespace App\Jobs;

use App\Services\SystemHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RecordSystemHealthMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes

    /**
     * Execute the job.
     */
    public function handle(SystemHealthService $systemHealthService)
    {
        // Record shift fill rate
        $this->recordShiftFillRate($systemHealthService);

        // Record payment success rate
        $this->recordPaymentSuccessRate($systemHealthService);

        // Record queue depths
        $this->recordQueueDepths($systemHealthService);

        // Record infrastructure metrics
        $this->recordInfrastructureMetrics($systemHealthService);
    }

    /**
     * Record shift fill rate metric.
     */
    protected function recordShiftFillRate(SystemHealthService $systemHealthService)
    {
        $shiftMetrics = $systemHealthService->getShiftMetrics();
        // The service already records this metric internally
    }

    /**
     * Record payment success rate metric.
     */
    protected function recordPaymentSuccessRate(SystemHealthService $systemHealthService)
    {
        $paymentMetrics = $systemHealthService->getPaymentMetrics();
        // The service already records this metric internally
    }

    /**
     * Record queue depths.
     */
    protected function recordQueueDepths(SystemHealthService $systemHealthService)
    {
        try {
            $queues = ['default', 'notifications', 'payments', 'emails'];

            foreach ($queues as $queueName) {
                $size = Redis::llen("queues:{$queueName}");

                $systemHealthService->recordMetric('queue_depth', $size, 'jobs', [
                    'queue' => $queueName,
                ]);
            }
        } catch (\Exception $e) {
            // Log but don't fail the job
            \Log::warning("Failed to record queue depths: " . $e->getMessage());
        }
    }

    /**
     * Record infrastructure metrics.
     */
    protected function recordInfrastructureMetrics(SystemHealthService $systemHealthService)
    {
        // Database connections
        try {
            $dbConnections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $connections = $dbConnections[0]->Value ?? 0;

            $systemHealthService->recordMetric('database_connections', $connections, 'count');
        } catch (\Exception $e) {
            \Log::warning("Failed to record database connections: " . $e->getMessage());
        }

        // Redis connections
        try {
            $redisInfo = Redis::info();
            $connections = $redisInfo['connected_clients'] ?? 0;

            $systemHealthService->recordMetric('redis_connections', $connections, 'count');
        } catch (\Exception $e) {
            \Log::warning("Failed to record Redis connections: " . $e->getMessage());
        }

        // Disk usage
        try {
            $diskUsedPercentage = round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 2);

            $systemHealthService->recordMetric('disk_usage', $diskUsedPercentage, '%');
        } catch (\Exception $e) {
            \Log::warning("Failed to record disk usage: " . $e->getMessage());
        }

        // Memory usage
        try {
            $memoryUsed = memory_get_usage(true);
            $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

            $systemHealthService->recordMetric('memory_usage', $memoryUsedMB, 'MB');
        } catch (\Exception $e) {
            \Log::warning("Failed to record memory usage: " . $e->getMessage());
        }
    }
}
