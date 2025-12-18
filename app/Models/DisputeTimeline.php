<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DisputeTimeline Model
 *
 * FIN-010: Tracks all actions and events in a dispute's lifecycle.
 *
 * @property int $id
 * @property int $dispute_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $description
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Dispute $dispute
 * @property-read \App\Models\User|null $user
 */
class DisputeTimeline extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dispute_timeline';

    /**
     * Action constants.
     */
    public const ACTION_OPENED = 'opened';

    public const ACTION_BUSINESS_RESPONDED = 'business_responded';

    public const ACTION_WORKER_EVIDENCE = 'worker_evidence_submitted';

    public const ACTION_BUSINESS_EVIDENCE = 'business_evidence_submitted';

    public const ACTION_ASSIGNED = 'assigned';

    public const ACTION_STATUS_CHANGED = 'status_changed';

    public const ACTION_ESCALATED = 'escalated';

    public const ACTION_RESOLVED = 'resolved';

    public const ACTION_CLOSED = 'closed';

    public const ACTION_DEADLINE_SET = 'deadline_set';

    public const ACTION_DEADLINE_EXTENDED = 'deadline_extended';

    public const ACTION_ADMIN_NOTE = 'admin_note';

    public const ACTION_WITHDRAWN = 'withdrawn';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dispute_id',
        'user_id',
        'action',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Action labels for display.
     *
     * @var array<string, string>
     */
    public static array $actionLabels = [
        self::ACTION_OPENED => 'Dispute Opened',
        self::ACTION_BUSINESS_RESPONDED => 'Business Responded',
        self::ACTION_WORKER_EVIDENCE => 'Worker Submitted Evidence',
        self::ACTION_BUSINESS_EVIDENCE => 'Business Submitted Evidence',
        self::ACTION_ASSIGNED => 'Mediator Assigned',
        self::ACTION_STATUS_CHANGED => 'Status Changed',
        self::ACTION_ESCALATED => 'Dispute Escalated',
        self::ACTION_RESOLVED => 'Dispute Resolved',
        self::ACTION_CLOSED => 'Dispute Closed',
        self::ACTION_DEADLINE_SET => 'Evidence Deadline Set',
        self::ACTION_DEADLINE_EXTENDED => 'Deadline Extended',
        self::ACTION_ADMIN_NOTE => 'Admin Note Added',
        self::ACTION_WITHDRAWN => 'Dispute Withdrawn',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the dispute this timeline entry belongs to.
     */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return self::$actionLabels[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get the actor name for display.
     */
    public function getActorNameAttribute(): string
    {
        if (! $this->user) {
            return 'System';
        }

        // Check if user is admin
        if ($this->user->role === 'admin') {
            return 'Admin: '.$this->user->name;
        }

        // Check user's relationship to the dispute
        if ($this->dispute) {
            if ($this->user_id === $this->dispute->worker_id) {
                return 'Worker: '.$this->user->name;
            }

            if ($this->user_id === $this->dispute->business_id) {
                return 'Business: '.$this->user->name;
            }
        }

        return $this->user->name;
    }

    /**
     * Get icon class for this action type.
     */
    public function getIconClassAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_OPENED => 'fas fa-plus-circle text-primary',
            self::ACTION_BUSINESS_RESPONDED => 'fas fa-reply text-info',
            self::ACTION_WORKER_EVIDENCE,
            self::ACTION_BUSINESS_EVIDENCE => 'fas fa-file-upload text-secondary',
            self::ACTION_ASSIGNED => 'fas fa-user-tie text-info',
            self::ACTION_STATUS_CHANGED => 'fas fa-exchange-alt text-warning',
            self::ACTION_ESCALATED => 'fas fa-arrow-up text-danger',
            self::ACTION_RESOLVED => 'fas fa-check-circle text-success',
            self::ACTION_CLOSED => 'fas fa-times-circle text-dark',
            self::ACTION_DEADLINE_SET,
            self::ACTION_DEADLINE_EXTENDED => 'fas fa-clock text-warning',
            self::ACTION_ADMIN_NOTE => 'fas fa-sticky-note text-secondary',
            self::ACTION_WITHDRAWN => 'fas fa-undo text-muted',
            default => 'fas fa-circle text-muted',
        };
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Create a system-generated timeline entry.
     */
    public static function createSystemEntry(
        int $disputeId,
        string $action,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'dispute_id' => $disputeId,
            'user_id' => null,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
