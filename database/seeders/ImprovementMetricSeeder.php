<?php

namespace Database\Seeders;

use App\Models\ImprovementMetric;
use Illuminate\Database\Seeder;

/**
 * QUA-005: Continuous Improvement System
 * Seeder for default improvement metrics.
 */
class ImprovementMetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $metrics = [
            [
                'metric_key' => 'shift_fill_rate',
                'name' => 'Shift Fill Rate',
                'description' => 'Percentage of shift positions that are successfully filled with workers.',
                'unit' => '%',
                'current_value' => 85.0,
                'target_value' => 95.0,
                'baseline_value' => 70.0,
            ],
            [
                'metric_key' => 'avg_response_time',
                'name' => 'Average Response Time',
                'description' => 'Average time in seconds from shift posting to first worker application.',
                'unit' => 'seconds',
                'current_value' => 1800.0, // 30 minutes
                'target_value' => 900.0,   // 15 minutes
                'baseline_value' => 3600.0, // 1 hour
            ],
            [
                'metric_key' => 'worker_satisfaction',
                'name' => 'Worker Satisfaction Score',
                'description' => 'Average satisfaction rating given by workers (1-5 scale).',
                'unit' => 'score',
                'current_value' => 4.2,
                'target_value' => 4.5,
                'baseline_value' => 3.5,
            ],
            [
                'metric_key' => 'business_retention',
                'name' => 'Business Retention Rate',
                'description' => 'Percentage of businesses that continue posting shifts month-over-month.',
                'unit' => '%',
                'current_value' => 78.0,
                'target_value' => 90.0,
                'baseline_value' => 60.0,
            ],
            [
                'metric_key' => 'dispute_resolution_time',
                'name' => 'Avg Dispute Resolution Time',
                'description' => 'Average time in hours to resolve worker/business disputes.',
                'unit' => 'hours',
                'current_value' => 48.0,
                'target_value' => 24.0,
                'baseline_value' => 72.0,
            ],
            [
                'metric_key' => 'platform_health_score',
                'name' => 'Platform Health Score',
                'description' => 'Overall platform health indicator (0-100 scale).',
                'unit' => 'score',
                'current_value' => 75.0,
                'target_value' => 90.0,
                'baseline_value' => 50.0,
            ],
            [
                'metric_key' => 'worker_retention',
                'name' => 'Worker Retention Rate',
                'description' => 'Percentage of workers who complete multiple shifts over time.',
                'unit' => '%',
                'current_value' => 65.0,
                'target_value' => 80.0,
                'baseline_value' => 45.0,
            ],
            [
                'metric_key' => 'avg_rating',
                'name' => 'Average Overall Rating',
                'description' => 'Combined average rating across all workers and businesses.',
                'unit' => 'score',
                'current_value' => 4.1,
                'target_value' => 4.5,
                'baseline_value' => 3.0,
            ],
            [
                'metric_key' => 'cancellation_rate',
                'name' => 'Cancellation Rate',
                'description' => 'Percentage of shifts cancelled after being posted.',
                'unit' => '%',
                'current_value' => 8.5,
                'target_value' => 5.0,
                'baseline_value' => 15.0,
            ],
            [
                'metric_key' => 'payment_success_rate',
                'name' => 'Payment Success Rate',
                'description' => 'Percentage of payments processed successfully on first attempt.',
                'unit' => '%',
                'current_value' => 97.5,
                'target_value' => 99.5,
                'baseline_value' => 95.0,
            ],
        ];

        foreach ($metrics as $metricData) {
            ImprovementMetric::updateOrCreate(
                ['metric_key' => $metricData['metric_key']],
                array_merge($metricData, [
                    'trend' => ImprovementMetric::TREND_STABLE,
                    'measured_at' => now(),
                    'history' => [],
                ])
            );
        }

        $this->command->info('Improvement metrics seeded successfully.');
    }
}
