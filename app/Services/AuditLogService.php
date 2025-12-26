<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AuditLogService
 *
 * SECURITY: Centralized audit logging for financial and sensitive operations.
 * Provides consistent audit trail for compliance and security monitoring.
 */
class AuditLogService
{
    /**
     * Log a financial operation.
     *
     * @param  string  $action  Action name (e.g., 'withdrawal_requested', 'payout_approved')
     * @param  int  $userId  User ID performing the action
     * @param  array  $data  Additional data to log
     * @param  string|null  $ipAddress  IP address (optional, defaults to request IP)
     */
    public function logFinancialOperation(string $action, int $userId, array $data = [], ?string $ipAddress = null): void
    {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'data' => $data,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Log to admin channel for financial operations
        Log::channel('admin')->warning('Financial operation', $logData);

        // Also log to database if audit_logs table exists
        if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
            try {
                DB::table('audit_logs')->insert([
                    'user_id' => $userId,
                    'action' => $action,
                    'model_type' => $data['model_type'] ?? null,
                    'model_id' => $data['model_id'] ?? null,
                    'data' => json_encode($data),
                    'ip_address' => $ipAddress ?? request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // If table doesn't exist or insert fails, just log to file
                Log::channel('admin')->error('Failed to write audit log to database', [
                    'error' => $e->getMessage(),
                    'log_data' => $logData,
                ]);
            }
        }
    }

    /**
     * Log a configuration change.
     *
     * @param  string  $key  Configuration key changed
     * @param  mixed  $oldValue  Previous value (will be masked if secret)
     * @param  mixed  $newValue  New value (will be masked if secret)
     * @param  int  $userId  Admin user ID
     */
    public function logConfigChange(string $key, $oldValue, $newValue, int $userId): void
    {
        $secretHelper = app(\App\Helpers\SecretMaskHelper::class);

        // Mask secret values
        if ($secretHelper->shouldMask($key)) {
            $oldValue = $secretHelper->mask($oldValue ?? '');
            $newValue = $secretHelper->mask($newValue ?? '');
        }

        $logData = [
            'user_id' => $userId,
            'action' => 'config_changed',
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('admin')->warning('Configuration changed', $logData);

        // Also log to database if audit_logs table exists
        if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
            try {
                DB::table('audit_logs')->insert([
                    'user_id' => $userId,
                    'action' => 'config_changed',
                    'model_type' => 'config',
                    'model_id' => null,
                    'data' => json_encode([
                        'key' => $key,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                    ]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::channel('admin')->error('Failed to write audit log to database', [
                    'error' => $e->getMessage(),
                    'log_data' => $logData,
                ]);
            }
        }
    }
}
