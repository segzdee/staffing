<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $agency_id
 * @property int $worker_id
 * @property numeric $commission_rate
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $added_at
 * @property \Illuminate\Support\Carbon|null $removed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftAssignment> $shiftAssignments
 * @property-read int|null $shift_assignments_count
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker forAgency($agencyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereAddedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereRemovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyWorker whereWorkerId($value)
 * @mixin \Eloquent
 */
class AgencyWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'worker_id',
        'commission_rate',
        'status',
        'notes',
        'added_at',
        'removed_at',
        // Payout tracking fields (AGY-003)
        'last_payout_transaction_id',
        'last_payout_at',
        'last_payout_amount',
        'total_commission_earned',
        'total_commission_paid',
        'pending_commission',
        'payout_count',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'added_at' => 'datetime',
        'removed_at' => 'datetime',
        // Payout tracking casts (AGY-003)
        'last_payout_at' => 'datetime',
        'last_payout_amount' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'total_commission_paid' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'payout_count' => 'integer',
    ];

    /**
     * Get the agency that manages this worker.
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the worker being managed.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get shift assignments for this agency-worker relationship.
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class, 'worker_id', 'worker_id')
            ->where('agency_id', $this->agency_id);
    }

    /**
     * Scope: Active agency-worker relationships
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: For specific agency
     */
    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Check if the worker is currently active with this agency
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Calculate total commission earned from this worker
     */
    public function totalCommissionEarned()
    {
        return ShiftPayment::whereIn('shift_assignment_id', function($query) {
            $query->select('id')
                ->from('shift_assignments')
                ->where('worker_id', $this->worker_id)
                ->where('agency_id', $this->agency_id);
        })
        ->where('status', 'released')
        ->sum('agency_commission');
    }

    /**
     * Calculate commission for a specific amount.
     *
     * Uses worker-specific commission rate if set, otherwise falls back to agency default.
     *
     * @param float $workerEarnings Worker's gross earnings in dollars
     * @return float Commission amount in dollars
     */
    public function calculateCommission($workerEarnings)
    {
        // Get commission rate (worker-specific or agency default)
        $commissionRate = $this->commission_rate;

        // If no worker-specific rate, get agency default
        if ($commissionRate == 0.00) {
            $agency = AgencyProfile::where('user_id', $this->agency_id)->first();
            $commissionRate = $agency ? $agency->commission_rate : 15.00; // Default 15%
        }

        // Enforce min/max boundaries (5-20%)
        $commissionRate = max(5.00, min(20.00, $commissionRate));

        // Calculate commission
        $commission = ($workerEarnings * $commissionRate) / 100;

        return round($commission, 2);
    }

    /**
     * Get the effective commission rate for this worker.
     *
     * @return float Commission rate percentage
     */
    public function getEffectiveCommissionRate()
    {
        if ($this->commission_rate > 0.00) {
            return $this->commission_rate;
        }

        $agency = AgencyProfile::where('user_id', $this->agency_id)->first();
        return $agency ? $agency->commission_rate : 15.00;
    }

    /**
     * Get pending commissions (from released payments not yet paid out).
     */
    public function pendingCommission()
    {
        return ShiftPayment::whereIn('shift_assignment_id', function($query) {
            $query->select('id')
                ->from('shift_assignments')
                ->where('worker_id', $this->worker_id)
                ->where('agency_id', $this->agency_id);
        })
        ->where('status', 'released')
        ->whereNull('payout_completed_at')
        ->sum('agency_commission');
    }

    /**
     * Get paid commissions (from completed payouts).
     */
    public function paidCommission()
    {
        return ShiftPayment::whereIn('shift_assignment_id', function($query) {
            $query->select('id')
                ->from('shift_assignments')
                ->where('worker_id', $this->worker_id)
                ->where('agency_id', $this->agency_id);
        })
        ->where('status', 'paid_out')
        ->whereNotNull('payout_completed_at')
        ->sum('agency_commission');
    }
}
