<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Retention Policy Model
 *
 * Defines data retention policies and handles automatic data cleanup.
 *
 * @property int $id
 * @property string $data_type
 * @property string $model_class
 * @property int $retention_days
 * @property string $action
 * @property bool $is_active
 * @property string|null $description
 * @property array<array-key, mixed>|null $conditions
 * @property \Illuminate\Support\Carbon|null $last_executed_at
 * @property int $last_affected_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DataRetentionPolicy extends Model
{
    use HasFactory;

    // Retention Actions
    public const ACTION_DELETE = 'delete';

    public const ACTION_ANONYMIZE = 'anonymize';

    public const ACTION_ARCHIVE = 'archive';

    // Common Data Types
    public const DATA_MESSAGES = 'messages';

    public const DATA_SHIFTS = 'shifts';

    public const DATA_PAYMENTS = 'payments';

    public const DATA_LOGS = 'logs';

    public const DATA_NOTIFICATIONS = 'notifications';

    public const DATA_SESSION_DATA = 'session_data';

    public const DATA_AUDIT_LOGS = 'audit_logs';

    public const DATA_CONSENT_RECORDS = 'consent_records';

    protected $fillable = [
        'data_type',
        'model_class',
        'retention_days',
        'action',
        'is_active',
        'description',
        'conditions',
        'last_executed_at',
        'last_affected_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'last_executed_at' => 'datetime',
        'retention_days' => 'integer',
        'last_affected_count' => 'integer',
    ];

    /**
     * Get the cutoff date for this policy.
     */
    public function getCutoffDateAttribute(): \Carbon\Carbon
    {
        return now()->subDays($this->retention_days);
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_DELETE => 'Permanently Delete',
            self::ACTION_ANONYMIZE => 'Anonymize Data',
            self::ACTION_ARCHIVE => 'Archive Data',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get human-readable data type label.
     */
    public function getDataTypeLabelAttribute(): string
    {
        return match ($this->data_type) {
            self::DATA_MESSAGES => 'Chat Messages',
            self::DATA_SHIFTS => 'Completed Shifts',
            self::DATA_PAYMENTS => 'Payment Records',
            self::DATA_LOGS => 'System Logs',
            self::DATA_NOTIFICATIONS => 'Notifications',
            self::DATA_SESSION_DATA => 'Session Data',
            self::DATA_AUDIT_LOGS => 'Audit Logs',
            self::DATA_CONSENT_RECORDS => 'Consent Records',
            default => ucfirst(str_replace('_', ' ', $this->data_type)),
        };
    }

    /**
     * Execute the retention policy.
     *
     * @return int Number of affected records
     */
    public function execute(): int
    {
        if (! $this->is_active) {
            return 0;
        }

        $affectedCount = 0;

        try {
            DB::beginTransaction();

            $affectedCount = match ($this->action) {
                self::ACTION_DELETE => $this->executeDelete(),
                self::ACTION_ANONYMIZE => $this->executeAnonymize(),
                self::ACTION_ARCHIVE => $this->executeArchive(),
                default => 0,
            };

            $this->update([
                'last_executed_at' => now(),
                'last_affected_count' => $affectedCount,
            ]);

            DB::commit();

            Log::info('Data retention policy executed', [
                'policy_id' => $this->id,
                'data_type' => $this->data_type,
                'action' => $this->action,
                'affected_count' => $affectedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Data retention policy execution failed', [
                'policy_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $affectedCount;
    }

    /**
     * Execute delete action.
     */
    protected function executeDelete(): int
    {
        $modelClass = $this->model_class;

        if (! class_exists($modelClass)) {
            Log::warning('Model class not found for retention policy', [
                'model_class' => $modelClass,
            ]);

            return 0;
        }

        $query = $modelClass::query()
            ->where('created_at', '<', $this->cutoff_date);

        // Apply additional conditions
        $query = $this->applyConditions($query);

        return $query->delete();
    }

    /**
     * Execute anonymize action.
     */
    protected function executeAnonymize(): int
    {
        $modelClass = $this->model_class;

        if (! class_exists($modelClass)) {
            return 0;
        }

        $query = $modelClass::query()
            ->where('created_at', '<', $this->cutoff_date);

        $query = $this->applyConditions($query);

        $records = $query->get();
        $affectedCount = 0;

        foreach ($records as $record) {
            if ($this->anonymizeRecord($record)) {
                $affectedCount++;
            }
        }

        return $affectedCount;
    }

    /**
     * Execute archive action.
     */
    protected function executeArchive(): int
    {
        // Archive action requires a custom implementation based on storage strategy
        // This could involve moving to cold storage, compressing, etc.
        Log::info('Archive action triggered but requires custom implementation', [
            'policy_id' => $this->id,
            'data_type' => $this->data_type,
        ]);

        return 0;
    }

    /**
     * Apply additional conditions to the query.
     */
    protected function applyConditions($query)
    {
        if (empty($this->conditions)) {
            return $query;
        }

        foreach ($this->conditions as $condition) {
            if (isset($condition['column'], $condition['operator'], $condition['value'])) {
                $query->where(
                    $condition['column'],
                    $condition['operator'],
                    $condition['value']
                );
            }
        }

        return $query;
    }

    /**
     * Anonymize a single record.
     */
    protected function anonymizeRecord(Model $record): bool
    {
        $anonymizableFields = $this->getAnonymizableFields();

        if (empty($anonymizableFields)) {
            return false;
        }

        $updates = [];
        foreach ($anonymizableFields as $field => $replacement) {
            if (isset($record->{$field})) {
                $updates[$field] = $replacement;
            }
        }

        if (! empty($updates)) {
            $record->update($updates);

            return true;
        }

        return false;
    }

    /**
     * Get fields that should be anonymized based on model type.
     */
    protected function getAnonymizableFields(): array
    {
        return match ($this->data_type) {
            self::DATA_MESSAGES => [
                'content' => '[Message removed for privacy]',
            ],
            self::DATA_LOGS => [
                'ip_address' => '0.0.0.0',
                'user_agent' => '[Anonymized]',
            ],
            default => $this->conditions['anonymize_fields'] ?? [],
        };
    }

    /**
     * Scope for active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for policies by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get all available actions.
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_DELETE => 'Permanently Delete',
            self::ACTION_ANONYMIZE => 'Anonymize Data',
            self::ACTION_ARCHIVE => 'Archive to Cold Storage',
        ];
    }

    /**
     * Get default retention policies.
     */
    public static function getDefaultPolicies(): array
    {
        return [
            [
                'data_type' => self::DATA_MESSAGES,
                'model_class' => Message::class,
                'retention_days' => 730, // 2 years
                'action' => self::ACTION_ANONYMIZE,
                'description' => 'Anonymize chat messages after 2 years',
            ],
            [
                'data_type' => self::DATA_NOTIFICATIONS,
                'model_class' => Notifications::class,
                'retention_days' => 365, // 1 year
                'action' => self::ACTION_DELETE,
                'description' => 'Delete notifications after 1 year',
            ],
            [
                'data_type' => self::DATA_LOGS,
                'model_class' => 'App\Models\SystemLog',
                'retention_days' => 90, // 90 days
                'action' => self::ACTION_DELETE,
                'description' => 'Delete system logs after 90 days',
            ],
            [
                'data_type' => self::DATA_CONSENT_RECORDS,
                'model_class' => ConsentRecord::class,
                'retention_days' => 1825, // 5 years (legal requirement)
                'action' => self::ACTION_ARCHIVE,
                'description' => 'Archive consent records after 5 years (legal requirement)',
            ],
        ];
    }

    /**
     * Initialize default retention policies if none exist.
     */
    public static function initializeDefaults(): void
    {
        foreach (self::getDefaultPolicies() as $policy) {
            self::firstOrCreate(
                [
                    'data_type' => $policy['data_type'],
                    'model_class' => $policy['model_class'],
                ],
                array_merge($policy, ['is_active' => true])
            );
        }
    }

    /**
     * Get count of records that would be affected by this policy.
     */
    public function getAffectedCount(): int
    {
        $modelClass = $this->model_class;

        if (! class_exists($modelClass)) {
            return 0;
        }

        $query = $modelClass::query()
            ->where('created_at', '<', $this->cutoff_date);

        $query = $this->applyConditions($query);

        return $query->count();
    }

    /**
     * Preview what records would be affected without executing.
     */
    public function preview(int $limit = 100): array
    {
        $modelClass = $this->model_class;

        if (! class_exists($modelClass)) {
            return [];
        }

        $query = $modelClass::query()
            ->where('created_at', '<', $this->cutoff_date);

        $query = $this->applyConditions($query);

        return [
            'total_count' => $query->count(),
            'sample_records' => $query->limit($limit)->get()->toArray(),
        ];
    }
}
