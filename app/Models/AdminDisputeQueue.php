<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'dispute_reason',
        'evidence_urls',
        'assigned_to_admin',
        'resolution_notes',
        'resolution_outcome',
        'adjustment_amount',
        'filed_at',
        'assigned_at',
        'resolved_at',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'adjustment_amount' => 'decimal:2',
        'filed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

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
     * Scope: Pending disputes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Urgent priority
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope: Assigned to admin
     */
    public function scopeAssignedTo($query, $adminId)
    {
        return $query->where('assigned_to_admin', $adminId);
    }

    /**
     * Assign to admin
     */
    public function assignTo($adminId)
    {
        $this->update([
            'assigned_to_admin' => $adminId,
            'assigned_at' => now(),
            'status' => 'investigating',
        ]);

        return $this;
    }

    /**
     * Resolve dispute
     */
    public function resolve($outcome, $adjustmentAmount = null, $notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolution_outcome' => $outcome,
            'adjustment_amount' => $adjustmentAmount,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        // Apply adjustment to shift payment if needed
        if ($adjustmentAmount && $this->shiftPayment) {
            $this->shiftPayment->update([
                'dispute_adjustment_amount' => $adjustmentAmount,
                'dispute_status' => 'resolved',
            ]);
        }

        return $this;
    }

    /**
     * Close dispute without resolution
     */
    public function close($notes = null)
    {
        $this->update([
            'status' => 'closed',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return $this;
    }
}
