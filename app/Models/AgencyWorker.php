<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'added_at' => 'datetime',
        'removed_at' => 'datetime',
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
}
