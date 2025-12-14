<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationQueue extends Model
{
    use HasFactory;

    protected $table = 'verification_queue';

    protected $fillable = [
        'verifiable_id',
        'verifiable_type',
        'verification_type',
        'status',
        'documents',
        'admin_notes',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the verifiable model (worker, business, or agency).
     */
    public function verifiable()
    {
        return $this->morphTo();
    }

    /**
     * Get the admin who reviewed this verification.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: Pending verifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: In review
     */
    public function scopeInReview($query)
    {
        return $query->where('status', 'in_review');
    }

    /**
     * Approve verification
     */
    public function approve($adminId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Update the verifiable model's verification status
        if ($this->verification_type === 'identity' && method_exists($this->verifiable, 'markAsVerified')) {
            $this->verifiable->markAsVerified();
        }

        return $this;
    }

    /**
     * Reject verification
     */
    public function reject($adminId, $notes)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        return $this;
    }
}
