<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSwap extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_assignment_id',
        'offering_worker_id',
        'receiving_worker_id',
        'reason',
        'status',
        'business_approval_required',
        'business_approved_at',
        'approved_by',
        'offered_at',
        'accepted_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'business_approval_required' => 'boolean',
        'business_approved_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }

    /**
     * Alias for assignment() - used in controllers for readability.
     */
    public function offeringAssignment()
    {
        return $this->assignment();
    }

    public function offeringWorker()
    {
        return $this->belongsTo(User::class, 'offering_worker_id');
    }

    public function receivingWorker()
    {
        return $this->belongsTo(User::class, 'receiving_worker_id');
    }

    /**
     * Alias for receivingWorker() - used in controllers for readability.
     * Maps to receiving_worker_id column in database.
     */
    public function acceptingWorker()
    {
        return $this->receivingWorker();
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the shift via the assignment relationship.
     * Convenience method for direct access to the shift.
     */
    public function shift()
    {
        return $this->hasOneThrough(
            Shift::class,
            ShiftAssignment::class,
            'id', // Foreign key on shift_assignments (primary key)
            'id', // Foreign key on shifts (primary key)
            'shift_assignment_id', // Local key on shift_swaps
            'shift_id' // Local key on shift_assignments
        );
    }

    /**
     * Alias for assignment() - provides requestingAssignment() for consistency
     * with audit specification terminology.
     */
    public function requestingAssignment()
    {
        return $this->assignment();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function approve($approverId = null)
    {
        $this->update([
            'status' => 'approved',
            'business_approved_at' => now(),
            'approved_by' => $approverId
        ]);
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
