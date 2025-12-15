<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Penalty Appeal Model
 *
 * Handles worker appeals against penalties issued to them.
 * Workers have 14 days from penalty issue date to submit an appeal.
 *
 * @property int $id
 * @property int $penalty_id
 * @property int $worker_id
 * @property int|null $reviewed_by_admin_id
 * @property string $appeal_reason
 * @property array|null $evidence_urls
 * @property string|null $additional_notes
 * @property string $status
 * @property string|null $admin_notes
 * @property string|null $decision_reason
 * @property float|null $adjusted_amount
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $deadline_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PenaltyAppeal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'penalty_id',
        'worker_id',
        'reviewed_by_admin_id',
        'appeal_reason',
        'evidence_urls',
        'additional_notes',
        'status',
        'admin_notes',
        'decision_reason',
        'adjusted_amount',
        'submitted_at',
        'reviewed_at',
        'deadline_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'evidence_urls' => 'array',
        'adjusted_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appeal) {
            if (!$appeal->submitted_at) {
                $appeal->submitted_at = now();
            }
            if (!$appeal->deadline_at && $appeal->penalty) {
                // Deadline is 14 days from penalty issue
                $appeal->deadline_at = $appeal->penalty->issued_at->addDays(14);
            }
        });

        // Update penalty status when appeal is created
        static::created(function ($appeal) {
            $appeal->penalty->markAsAppealed();
        });
    }

    /**
     * Get the penalty being appealed.
     */
    public function penalty()
    {
        return $this->belongsTo(WorkerPenalty::class, 'penalty_id');
    }

    /**
     * Get the worker who filed the appeal.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the admin who reviewed the appeal.
     */
    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    /**
     * Check if the appeal is still within the deadline.
     */
    public function isWithinDeadline()
    {
        return $this->deadline_at && $this->deadline_at->isFuture();
    }

    /**
     * Check if the appeal is pending review.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the appeal is under review.
     */
    public function isUnderReview()
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if the appeal was approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the appeal was rejected.
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark appeal as under review.
     */
    public function markAsUnderReview($adminId)
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by_admin_id' => $adminId,
        ]);
    }

    /**
     * Approve the appeal.
     */
    public function approve($decisionReason, $adjustedAmount = null, $adminId = null)
    {
        $this->update([
            'status' => 'approved',
            'decision_reason' => $decisionReason,
            'adjusted_amount' => $adjustedAmount,
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $adminId ?? $this->reviewed_by_admin_id,
        ]);

        // Update penalty status
        if ($adjustedAmount === null || $adjustedAmount == 0) {
            // Full waiver
            $this->penalty->waive();
        } else {
            // Partial reduction
            $this->penalty->update([
                'status' => 'appeal_approved',
                'penalty_amount' => $adjustedAmount,
            ]);
        }
    }

    /**
     * Reject the appeal.
     */
    public function reject($decisionReason, $adminId = null)
    {
        $this->update([
            'status' => 'rejected',
            'decision_reason' => $decisionReason,
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $adminId ?? $this->reviewed_by_admin_id,
        ]);

        // Update penalty status back to active
        $this->penalty->rejectAppeal();
    }

    /**
     * Add evidence URL to the appeal.
     */
    public function addEvidence($url)
    {
        $evidence = $this->evidence_urls ?? [];
        $evidence[] = $url;
        $this->update(['evidence_urls' => $evidence]);
    }

    /**
     * Scope for pending appeals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for appeals under review.
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope for approved appeals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected appeals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for appeals awaiting review (pending or under review).
     */
    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }

    /**
     * Scope for appeals within deadline.
     */
    public function scopeWithinDeadline($query)
    {
        return $query->where('deadline_at', '>', now());
    }

    /**
     * Scope for overdue appeals (past deadline, still pending).
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('deadline_at', '<', now());
    }
}
