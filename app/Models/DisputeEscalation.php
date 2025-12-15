<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DisputeEscalation Model
 *
 * Tracks escalation history for disputes.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * @property int $id
 * @property int $dispute_id
 * @property int $escalation_level
 * @property string $escalation_reason
 * @property int|null $escalated_from_admin_id
 * @property int|null $escalated_to_admin_id
 * @property float|null $sla_hours_at_escalation
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $escalated_at
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DisputeEscalation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dispute_escalations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dispute_id',
        'escalation_level',
        'escalation_reason',
        'escalated_from_admin_id',
        'escalated_to_admin_id',
        'sla_hours_at_escalation',
        'notes',
        'escalated_at',
        'acknowledged_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'escalated_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'sla_hours_at_escalation' => 'float',
    ];

    /**
     * Escalation level names.
     */
    public const LEVELS = [
        1 => 'Senior Admin',
        2 => 'Supervisor',
        3 => 'Manager',
    ];

    /**
     * Get the dispute this escalation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dispute()
    {
        return $this->belongsTo(AdminDisputeQueue::class, 'dispute_id');
    }

    /**
     * Get the admin this was escalated from.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function escalatedFromAdmin()
    {
        return $this->belongsTo(User::class, 'escalated_from_admin_id');
    }

    /**
     * Get the admin this was escalated to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function escalatedToAdmin()
    {
        return $this->belongsTo(User::class, 'escalated_to_admin_id');
    }

    /**
     * Get escalation level name.
     *
     * @return string
     */
    public function getLevelName(): string
    {
        return self::LEVELS[$this->escalation_level] ?? 'Unknown';
    }

    /**
     * Check if escalation has been acknowledged.
     *
     * @return bool
     */
    public function isAcknowledged(): bool
    {
        return !is_null($this->acknowledged_at);
    }

    /**
     * Acknowledge this escalation.
     *
     * @return bool
     */
    public function acknowledge(): bool
    {
        if ($this->acknowledged_at) {
            return false;
        }

        return $this->update([
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Scope: Unacknowledged escalations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Scope: Escalations of a specific level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfLevel($query, int $level)
    {
        return $query->where('escalation_level', $level);
    }
}
