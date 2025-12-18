<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-003: Email Log Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $to_email
 * @property string|null $template_slug
 * @property string $subject
 * @property string $status
 * @property string|null $message_id
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read EmailTemplate|null $template
 */
class EmailLog extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_OPENED = 'opened';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'to_email',
        'template_slug',
        'subject',
        'status',
        'message_id',
        'metadata',
        'sent_at',
        'opened_at',
        'clicked_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the user this email was sent to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this email.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_slug', 'slug');
    }

    /**
     * Scope to get only queued emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope to get only sent emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to get only delivered emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope to get only opened emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpened($query)
    {
        return $query->where('status', self::STATUS_OPENED);
    }

    /**
     * Scope to get only clicked emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClicked($query)
    {
        return $query->where('status', self::STATUS_CLICKED);
    }

    /**
     * Scope to get only bounced emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBounced($query)
    {
        return $query->where('status', self::STATUS_BOUNCED);
    }

    /**
     * Scope to get only failed emails.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter by template slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTemplate($query, string $templateSlug)
    {
        return $query->where('template_slug', $templateSlug);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Mark the email as sent.
     */
    public function markAsSent(?string $messageId = null): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'message_id' => $messageId,
        ]);

        return $this;
    }

    /**
     * Mark the email as delivered.
     */
    public function markAsDelivered(): self
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
        ]);

        return $this;
    }

    /**
     * Mark the email as opened.
     */
    public function markAsOpened(): self
    {
        if ($this->opened_at === null) {
            $this->update([
                'status' => self::STATUS_OPENED,
                'opened_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Mark the email as clicked.
     */
    public function markAsClicked(): self
    {
        $this->update([
            'status' => self::STATUS_CLICKED,
            'clicked_at' => now(),
        ]);

        // Also set opened_at if not already set
        if ($this->opened_at === null) {
            $this->update(['opened_at' => now()]);
        }

        return $this;
    }

    /**
     * Mark the email as bounced.
     */
    public function markAsBounced(?string $errorMessage = null): self
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Mark the email as failed.
     */
    public function markAsFailed(?string $errorMessage = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Find a log by message ID.
     */
    public static function findByMessageId(string $messageId): ?self
    {
        return static::where('message_id', $messageId)->first();
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_OPENED => 'Opened',
            self::STATUS_CLICKED => 'Clicked',
            self::STATUS_BOUNCED => 'Bounced',
            self::STATUS_FAILED => 'Failed',
        ];
    }
}
