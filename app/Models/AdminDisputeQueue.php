<?php

namespace App\Models;

use App\Services\DisputeEscalationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AdminDisputeQueue Model
 *
 * Manages dispute lifecycle with automated escalation support.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * @property int $id
 * @property int $shift_payment_id
 * @property string $filed_by
 * @property int $worker_id
 * @property int $business_id
 * @property string $status
 * @property string $priority
 * @property int $escalation_level
 * @property string $dispute_reason
 * @property array<array-key, mixed>|null $evidence_urls
 * @property int|null $assigned_to_admin
 * @property int|null $previous_assigned_admin
 * @property string|null $resolution_notes
 * @property string|null $internal_notes
 * @property string|null $resolution_outcome
 * @property numeric|null $adjustment_amount
 * @property \Illuminate\Support\Carbon $filed_at
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property \Illuminate\Support\Carbon|null $sla_warning_sent_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignedAdmin
 * @property-read \App\Models\User|null $previousAdmin
 * @property-read \App\Models\User|null $business
 * @property-read \App\Models\ShiftPayment|null $shiftPayment
 * @property-read \App\Models\User|null $worker
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisputeMessage> $messages
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisputeEscalation> $escalations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentAdjustment> $adjustments
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue assignedTo($adminId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue urgent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminDisputeQueue escalated()
 * @mixin \Eloquent
 */
class AdminDisputeQueue extends Model
{
    use HasFactory;

    protected $table = 'admin_dispute_queue';

    protected $fillable = [
        'shift_payment_id',
        'filed_by',
        'worker_id',
        'business_id',
        'status',
        'priority',
        'escalation_level',
        'dispute_reason',
        'evidence_urls',
        'assigned_to_admin',
        'previous_assigned_admin',
        'resolution_notes',
        'internal_notes',
        'resolution_outcome',
        'adjustment_amount',
        'filed_at',
        'assigned_at',
        'escalated_at',
        'sla_warning_sent_at',
        'resolved_at',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'adjustment_amount' => 'decimal:2',
        'escalation_level' => 'integer',
        'filed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'escalated_at' => 'datetime',
        'sla_warning_sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_EVIDENCE_REVIEW = 'evidence_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    /**
     * Priority constants.
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Resolution outcome constants.
     */
    public const OUTCOME_WORKER_FAVOR = 'worker_favor';
    public const OUTCOME_BUSINESS_FAVOR = 'business_favor';
    public const OUTCOME_SPLIT = 'split';
    public const OUTCOME_NO_FAULT = 'no_fault';

    /**
     * Get the shift payment related to this dispute.
     */
    public function shiftPayment()
    {
        return $this->belongsTo(ShiftPayment::class);
    }

    /**
     * Get the worker involved in the dispute.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business involved in the dispute.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the admin assigned to this dispute.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to_admin');
    }

    /**
     * Get the previously assigned admin.
     */
    public function previousAdmin()
    {
        return $this->belongsTo(User::class, 'previous_assigned_admin');
    }

    /**
     * Get all messages for this dispute.
     */
    public function messages()
    {
        return $this->hasMany(DisputeMessage::class, 'dispute_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get public messages (visible to all parties).
     */
    public function publicMessages()
    {
        return $this->hasMany(DisputeMessage::class, 'dispute_id')
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get escalation history.
     */
    public function escalations()
    {
        return $this->hasMany(DisputeEscalation::class, 'dispute_id')->orderBy('escalated_at', 'desc');
    }

    /**
     * Get payment adjustments for this dispute.
     */
    public function adjustments()
    {
        return $this->hasMany(PaymentAdjustment::class, 'dispute_id');
    }

    /**
     * Scope: Pending disputes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Active (not resolved/closed) disputes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_INVESTIGATING,
            self::STATUS_EVIDENCE_REVIEW
        ]);
    }

    /**
     * Scope: Urgent priority
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Scope: High priority or above
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope: Escalated disputes
     */
    public function scopeEscalated($query)
    {
        return $query->whereNotNull('escalated_at');
    }

    /**
     * Scope: Assigned to admin
     */
    public function scopeAssignedTo($query, $adminId)
    {
        return $query->where('assigned_to_admin', $adminId);
    }

    /**
     * Scope: Unassigned disputes
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to_admin');
    }

    /**
     * Assign to admin
     */
    public function assignTo($adminId)
    {
        $this->update([
            'assigned_to_admin' => $adminId,
            'assigned_at' => now(),
            'status' => self::STATUS_INVESTIGATING,
        ]);

        // Create system message
        DisputeMessage::createSystemMessage(
            $this->id,
            "Dispute assigned to admin for investigation."
        );

        return $this;
    }

    /**
     * Update status
     */
    public function updateStatus(string $status, ?string $notes = null)
    {
        $this->update([
            'status' => $status,
        ]);

        if ($notes) {
            DisputeMessage::createSystemMessage(
                $this->id,
                "Status changed to: {$status}. {$notes}"
            );
        }

        return $this;
    }

    /**
     * Resolve dispute with automated adjustment
     */
    public function resolve($outcome, $adjustmentAmount = null, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_outcome' => $outcome,
            'adjustment_amount' => $adjustmentAmount,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        // Apply adjustment using service
        if ($adjustmentAmount && $adjustmentAmount > 0) {
            $escalationService = app(DisputeEscalationService::class);
            $escalationService->applyResolutionAdjustment(
                $this,
                $outcome,
                $adjustmentAmount,
                $notes
            );
        }

        // Create resolution message
        DisputeMessage::create([
            'dispute_id' => $this->id,
            'sender_type' => User::class,
            'sender_id' => auth()->id() ?? 0,
            'message' => "Dispute resolved: {$this->getOutcomeLabel($outcome)}. " . ($notes ?? ''),
            'message_type' => DisputeMessage::TYPE_RESOLUTION,
            'is_internal' => false,
        ]);

        return $this;
    }

    /**
     * Close dispute without resolution
     */
    public function close($notes = null)
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        DisputeMessage::createSystemMessage(
            $this->id,
            "Dispute closed. " . ($notes ?? '')
        );

        return $this;
    }

    /**
     * Add a message to the dispute thread.
     */
    public function addMessage(int $senderId, string $message, string $type = 'text', bool $internal = false, array $attachments = [])
    {
        return DisputeMessage::create([
            'dispute_id' => $this->id,
            'sender_type' => User::class,
            'sender_id' => $senderId,
            'message' => $message,
            'message_type' => $type,
            'is_internal' => $internal,
            'attachments' => $attachments ?: null,
        ]);
    }

    /**
     * Check if dispute is active (not resolved/closed).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_INVESTIGATING,
            self::STATUS_EVIDENCE_REVIEW
        ]);
    }

    /**
     * Check if dispute is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if dispute has been escalated.
     */
    public function isEscalated(): bool
    {
        return !is_null($this->escalated_at);
    }

    /**
     * Get SLA deadline.
     */
    public function getSLADeadline()
    {
        $service = app(DisputeEscalationService::class);
        return $service->getSLADeadline($this);
    }

    /**
     * Get remaining SLA hours.
     */
    public function getRemainingHours(): float
    {
        $service = app(DisputeEscalationService::class);
        return $service->getRemainingHours($this);
    }

    /**
     * Get SLA percentage elapsed.
     */
    public function getSLAPercentage(): float
    {
        $service = app(DisputeEscalationService::class);
        return $service->getSLAPercentage($this);
    }

    /**
     * Get human-readable outcome label.
     */
    public function getOutcomeLabel(?string $outcome = null): string
    {
        $outcome = $outcome ?? $this->resolution_outcome;

        return match ($outcome) {
            self::OUTCOME_WORKER_FAVOR => 'Resolved in Worker\'s Favor',
            self::OUTCOME_BUSINESS_FAVOR => 'Resolved in Business\'s Favor',
            self::OUTCOME_SPLIT => 'Split Resolution',
            self::OUTCOME_NO_FAULT => 'No Fault Found',
            default => 'Unknown',
        };
    }

    /**
     * Get priority badge class for UI.
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'badge-secondary',
            self::PRIORITY_MEDIUM => 'badge-info',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_INVESTIGATING => 'badge-info',
            self::STATUS_EVIDENCE_REVIEW => 'badge-primary',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_CLOSED => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
