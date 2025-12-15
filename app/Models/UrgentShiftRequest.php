<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * UrgentShiftRequest Model
 *
 * Tracks urgent shift fill requests routed to agencies with SLA monitoring.
 *
 * @property int $id
 * @property int $shift_id
 * @property int $business_id
 * @property string $urgency_reason
 * @property numeric $fill_percentage
 * @property int $hours_until_shift
 * @property \Illuminate\Support\Carbon $shift_start_time
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon|null $first_agency_notified_at
 * @property \Illuminate\Support\Carbon|null $sla_deadline
 * @property bool $sla_met
 * @property bool $sla_breached
 * @property string $status
 * @property int $agencies_notified
 * @property int $agencies_responded
 * @property array|null $notified_agency_ids
 * @property int|null $accepted_by_agency_id
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property int|null $response_time_minutes
 * @property bool $escalated
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property string|null $escalation_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string|null $resolution_type
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UrgentShiftRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'business_id',
        'urgency_reason',
        'fill_percentage',
        'hours_until_shift',
        'shift_start_time',
        'detected_at',
        'first_agency_notified_at',
        'sla_deadline',
        'sla_met',
        'sla_breached',
        'status',
        'agencies_notified',
        'agencies_responded',
        'notified_agency_ids',
        'accepted_by_agency_id',
        'accepted_at',
        'response_time_minutes',
        'escalated',
        'escalated_at',
        'escalation_notes',
        'resolved_at',
        'resolution_type',
        'resolution_notes',
    ];

    protected $casts = [
        'fill_percentage' => 'decimal:2',
        'hours_until_shift' => 'integer',
        'shift_start_time' => 'datetime',
        'detected_at' => 'datetime',
        'first_agency_notified_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_met' => 'boolean',
        'sla_breached' => 'boolean',
        'agencies_notified' => 'integer',
        'agencies_responded' => 'integer',
        'notified_agency_ids' => 'array',
        'accepted_at' => 'datetime',
        'response_time_minutes' => 'integer',
        'escalated' => 'boolean',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the shift this request is for.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the business that posted the shift.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the agency that accepted the request.
     */
    public function acceptedByAgency()
    {
        return $this->belongsTo(User::class, 'accepted_by_agency_id');
    }

    /**
     * Scope: Active requests (pending or routed, not expired).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'routed'])
            ->where('sla_deadline', '>', now());
    }

    /**
     * Scope: Requests approaching SLA breach.
     */
    public function scopeApproachingSLABreach($query, $minutesBuffer = 5)
    {
        return $query->whereIn('status', ['pending', 'routed'])
            ->where('sla_breached', false)
            ->where('sla_deadline', '<=', now()->addMinutes($minutesBuffer));
    }

    /**
     * Scope: Unresolved requests.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at')
            ->whereIn('status', ['pending', 'routed']);
    }

    /**
     * Check if SLA is breached.
     */
    public function isSLABreached()
    {
        return $this->sla_deadline && now()->isAfter($this->sla_deadline) && !$this->sla_met;
    }

    /**
     * Mark as routed to agencies.
     */
    public function markAsRouted($agencyIds)
    {
        $this->update([
            'status' => 'routed',
            'first_agency_notified_at' => $this->first_agency_notified_at ?? now(),
            'sla_deadline' => $this->sla_deadline ?? now()->addMinutes(30),
            'agencies_notified' => count($agencyIds),
            'notified_agency_ids' => $agencyIds,
        ]);
    }

    /**
     * Record agency acceptance.
     */
    public function markAsAccepted($agencyId)
    {
        $responseTime = $this->first_agency_notified_at
            ? now()->diffInMinutes($this->first_agency_notified_at)
            : null;

        $this->update([
            'status' => 'accepted',
            'accepted_by_agency_id' => $agencyId,
            'accepted_at' => now(),
            'response_time_minutes' => $responseTime,
            'sla_met' => $responseTime && $responseTime <= 30,
            'agencies_responded' => $this->agencies_responded + 1,
        ]);
    }

    /**
     * Mark as filled (shift fully staffed).
     */
    public function markAsFilled($notes = null)
    {
        $this->update([
            'status' => 'filled',
            'resolved_at' => now(),
            'resolution_type' => 'filled',
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Mark as failed (no agencies responded).
     */
    public function markAsFailed($notes = null)
    {
        $this->update([
            'status' => 'failed',
            'resolved_at' => now(),
            'resolution_type' => 'failed',
            'resolution_notes' => $notes,
            'sla_breached' => true,
        ]);
    }

    /**
     * Mark as expired (shift time passed).
     */
    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired',
            'resolved_at' => now(),
            'resolution_type' => 'expired',
        ]);
    }

    /**
     * Escalate the request.
     */
    public function escalate($notes = null)
    {
        $this->update([
            'escalated' => true,
            'escalated_at' => now(),
            'escalation_notes' => $notes,
        ]);
    }

    /**
     * Check SLA and update breach status.
     */
    public function checkSLA()
    {
        if ($this->isSLABreached()) {
            $this->update([
                'sla_breached' => true,
            ]);

            // Auto-escalate if not already escalated
            if (!$this->escalated) {
                $this->escalate('Automatic escalation due to SLA breach');
            }
        }
    }
}
