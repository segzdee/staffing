<?php

namespace Database\Seeders;

use App\Models\DataRetentionPolicy;
use Illuminate\Database\Seeder;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Retention Policy Seeder
 *
 * Seeds default data retention policies for GDPR compliance.
 */
class DataRetentionPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = [
            // Messages - Anonymize after 2 years (keep for historical reference but remove PII)
            [
                'data_type' => 'messages',
                'model_class' => 'App\Models\Message',
                'retention_days' => 730, // 2 years
                'action' => 'anonymize',
                'is_active' => true,
                'description' => 'Anonymize chat messages after 2 years to comply with data minimization principles while preserving communication records.',
            ],
            // Notifications - Delete after 1 year
            [
                'data_type' => 'notifications',
                'model_class' => 'App\Models\Notifications',
                'retention_days' => 365, // 1 year
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete notification records after 1 year. Users typically do not need access to old notifications.',
            ],
            // Shift Applications (rejected) - Delete after 6 months
            [
                'data_type' => 'shift_applications_rejected',
                'model_class' => 'App\Models\ShiftApplication',
                'retention_days' => 180, // 6 months
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete rejected shift applications after 6 months. No longer needed for operational purposes.',
                'conditions' => [
                    ['column' => 'status', 'operator' => '=', 'value' => 'rejected'],
                ],
            ],
            // Consent Records - Archive after 5 years (legal requirement)
            [
                'data_type' => 'consent_records',
                'model_class' => 'App\Models\ConsentRecord',
                'retention_days' => 1825, // 5 years
                'action' => 'archive',
                'is_active' => true,
                'description' => 'Archive consent records after 5 years. Legal requirement to maintain proof of consent.',
            ],
            // Worker Availability Broadcasts - Delete after 30 days
            [
                'data_type' => 'availability_broadcasts',
                'model_class' => 'App\Models\AvailabilityBroadcast',
                'retention_days' => 30,
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete expired availability broadcasts after 30 days. No longer relevant for matching.',
            ],
            // Session Data (anonymous consents) - Delete after 90 days
            [
                'data_type' => 'anonymous_consents',
                'model_class' => 'App\Models\ConsentRecord',
                'retention_days' => 90,
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete anonymous session consent records after 90 days.',
                'conditions' => [
                    ['column' => 'user_id', 'operator' => 'IS', 'value' => null],
                ],
            ],
            // Reliability Score History - Anonymize after 3 years
            [
                'data_type' => 'reliability_history',
                'model_class' => 'App\Models\ReliabilityScoreHistory',
                'retention_days' => 1095, // 3 years
                'action' => 'anonymize',
                'is_active' => true,
                'description' => 'Anonymize reliability score history after 3 years. Keep for analytics but remove PII.',
            ],
            // Verification Queue (completed) - Delete after 2 years
            [
                'data_type' => 'verification_queue',
                'model_class' => 'App\Models\VerificationQueue',
                'retention_days' => 730, // 2 years
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete completed verification queue entries after 2 years.',
                'conditions' => [
                    ['column' => 'status', 'operator' => '!=', 'value' => 'pending'],
                ],
            ],
            // Profile Views - Delete after 1 year
            [
                'data_type' => 'profile_views',
                'model_class' => 'App\Models\WorkerProfileView',
                'retention_days' => 365,
                'action' => 'delete',
                'is_active' => true,
                'description' => 'Delete profile view analytics data after 1 year.',
            ],
            // Audit Logs / System Logs - Archive after 2 years
            [
                'data_type' => 'system_logs',
                'model_class' => 'App\Models\SystemSettingAudit',
                'retention_days' => 730, // 2 years
                'action' => 'archive',
                'is_active' => true,
                'description' => 'Archive system audit logs after 2 years for compliance records.',
            ],
        ];

        foreach ($policies as $policy) {
            DataRetentionPolicy::updateOrCreate(
                [
                    'data_type' => $policy['data_type'],
                    'model_class' => $policy['model_class'],
                ],
                $policy
            );
        }

        $this->command->info('Data retention policies seeded successfully.');
    }
}
