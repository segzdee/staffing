<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Webhook Event Model
 *
 * PRIORITY-0: Tracks webhook events for idempotency
 *
 * @property int $id
 * @property string $provider
 * @property string $event_id
 * @property string $event_type
 * @property string $payload
 * @property string $status
 * @property string|null $processing_result
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'webhook_events';

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'payload',
        'status',
        'processing_result',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'string',
        'processing_result' => 'string',
        'retry_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PROCESSED = 'processed';

    const STATUS_FAILED = 'failed';

    /**
     * Check if event is already processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Check if event can be retried.
     */
    public function canRetry(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED])
            && $this->retry_count < 5; // Max 5 retries
    }

    /**
     * Get payload as array.
     */
    public function getPayloadArray(): array
    {
        return json_decode($this->payload, true) ?? [];
    }

    /**
     * Get processing result as array.
     */
    public function getProcessingResultArray(): ?array
    {
        if (! $this->processing_result) {
            return null;
        }

        return json_decode($this->processing_result, true);
    }
}
