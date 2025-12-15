<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use Carbon\Carbon;

/**
 * VerificationQueue Model - ADM-001 Enhanced
 *
 * Handles verification requests for workers, businesses, and agencies
 * with SLA tracking, bulk operations, and priority scoring.
 *
 * @property int $id
 * @property string $verifiable_type
 * @property int $verifiable_id
 * @property string $verification_type
 * @property string $status
 * @property array<array-key, mixed>|null $documents
 * @property string|null $admin_notes
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon $submitted_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $sla_deadline
 * @property string $sla_status
 * @property \Illuminate\Support\Carbon|null $sla_warning_sent_at
 * @property \Illuminate\Support\Carbon|null $sla_breach_notified_at
 * @property int $priority_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $reviewer
 * @property-read Model|\Eloquent $verifiable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue inReview()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue atRisk()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue breached()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue onTrack()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VerificationQueue byPriority()
 * @mixin \Eloquent
 */
class VerificationQueue extends Model
{
    use HasFactory;

    protected $table = 'verification_queue';

    /**
     * SLA targets in hours by verification type
     */
    public const SLA_TARGETS = [
        'identity' => 48,              // Worker documents: 48 hours
        'background_check' => 48,      // Worker background: 48 hours
        'certification' => 48,         // Worker certifications: 48 hours
        'business_license' => 72,      // Business verification: 72 hours
        'agency' => 96,                // Agency verification: 96 hours
    ];

    /**
     * Default SLA target for unknown types (hours)
     */
    public const DEFAULT_SLA_HOURS = 72;

    /**
     * SLA warning threshold (percentage of SLA elapsed)
     */
    public const SLA_WARNING_THRESHOLD = 80;

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
        'sla_deadline',
        'sla_status',
        'sla_warning_sent_at',
        'sla_breach_notified_at',
        'priority_score',
    ];

    protected $casts = [
        'documents' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_warning_sent_at' => 'datetime',
        'sla_breach_notified_at' => 'datetime',
    ];

    /**
     * Boot method to set SLA deadline on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set submitted_at if not already set
            if (!$model->submitted_at) {
                $model->submitted_at = now();
            }

            // Calculate and set SLA deadline
            $model->calculateSLADeadline();
        });
    }

    /**
     * Calculate SLA deadline based on verification type
     */
    public function calculateSLADeadline(): self
    {
        $slaHours = self::SLA_TARGETS[$this->verification_type] ?? self::DEFAULT_SLA_HOURS;
        $submittedAt = $this->submitted_at ?? now();

        $this->sla_deadline = $submittedAt->copy()->addHours($slaHours);
        $this->updatePriorityScore();

        return $this;
    }

    /**
     * Update priority score based on remaining SLA time
     * Higher score = more urgent
     */
    public function updatePriorityScore(): self
    {
        if (!$this->sla_deadline) {
            $this->priority_score = 0;
            return $this;
        }

        $now = now();

        if ($now->greaterThanOrEqualTo($this->sla_deadline)) {
            // Breached - highest priority
            $this->priority_score = 1000;
        } else {
            // Calculate hours remaining
            $hoursRemaining = $now->diffInHours($this->sla_deadline, false);
            // Lower remaining hours = higher priority (inverse relationship)
            $this->priority_score = max(0, 100 - $hoursRemaining);
        }

        return $this;
    }

    /**
     * Update SLA status based on current time
     */
    public function updateSLAStatus(): self
    {
        if (!$this->sla_deadline || !in_array($this->status, ['pending', 'in_review'])) {
            return $this;
        }

        $now = now();
        $submittedAt = $this->submitted_at;
        $slaDeadline = $this->sla_deadline;

        // Calculate total SLA duration and elapsed time
        $totalDuration = $submittedAt->diffInMinutes($slaDeadline);
        $elapsed = $submittedAt->diffInMinutes($now);
        $percentElapsed = $totalDuration > 0 ? ($elapsed / $totalDuration) * 100 : 0;

        if ($now->greaterThanOrEqualTo($slaDeadline)) {
            $this->sla_status = 'breached';
        } elseif ($percentElapsed >= self::SLA_WARNING_THRESHOLD) {
            $this->sla_status = 'at_risk';
        } else {
            $this->sla_status = 'on_track';
        }

        $this->updatePriorityScore();

        return $this;
    }

    /**
     * Get the SLA target hours for this verification type
     */
    public function getSLATargetHours(): int
    {
        return self::SLA_TARGETS[$this->verification_type] ?? self::DEFAULT_SLA_HOURS;
    }

    /**
     * Get remaining time until SLA deadline as a human-readable string
     */
    public function getSLARemainingTimeAttribute(): ?string
    {
        if (!$this->sla_deadline) {
            return null;
        }

        $now = now();

        if ($now->greaterThanOrEqualTo($this->sla_deadline)) {
            $overdue = $now->diffForHumans($this->sla_deadline, ['parts' => 2, 'syntax' => Carbon::DIFF_ABSOLUTE]);
            return "Overdue by {$overdue}";
        }

        return $this->sla_deadline->diffForHumans($now, ['parts' => 2, 'syntax' => Carbon::DIFF_ABSOLUTE]) . ' remaining';
    }

    /**
     * Get remaining hours until SLA deadline (negative if breached)
     */
    public function getSLARemainingHoursAttribute(): ?float
    {
        if (!$this->sla_deadline) {
            return null;
        }

        return now()->diffInHours($this->sla_deadline, false);
    }

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

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

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
     * Scope: Actionable (pending or in review)
     */
    public function scopeActionable($query)
    {
        return $query->whereIn('status', ['pending', 'in_review']);
    }

    /**
     * Scope: On track SLA status
     */
    public function scopeOnTrack($query)
    {
        return $query->where('sla_status', 'on_track');
    }

    /**
     * Scope: At risk SLA status
     */
    public function scopeAtRisk($query)
    {
        return $query->where('sla_status', 'at_risk');
    }

    /**
     * Scope: Breached SLA status
     */
    public function scopeBreached($query)
    {
        return $query->where('sla_status', 'breached');
    }

    /**
     * Scope: Order by priority (SLA deadline ascending, breached first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority_score')
                     ->orderBy('sla_deadline');
    }

    /**
     * Scope: Filter by SLA status
     */
    public function scopeBySLAStatus($query, string $status)
    {
        return $query->where('sla_status', $status);
    }

    /**
     * Scope: Filter by verification type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('verification_type', $type);
    }

    /**
     * Scope: Needs SLA warning notification (at risk and not yet notified)
     */
    public function scopeNeedsSLAWarning($query)
    {
        return $query->actionable()
                     ->where('sla_status', 'at_risk')
                     ->whereNull('sla_warning_sent_at');
    }

    /**
     * Scope: Needs SLA breach notification (breached and not yet notified)
     */
    public function scopeNeedsSLABreachNotification($query)
    {
        return $query->actionable()
                     ->where('sla_status', 'breached')
                     ->whereNull('sla_breach_notified_at');
    }

    // =========================================================================
    // INDIVIDUAL OPERATIONS
    // =========================================================================

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

        // Send notification to user
        $this->sendApprovalNotification();

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

        // Send notification to user
        $this->sendRejectionNotification();

        return $this;
    }

    /**
     * Send approval notification to the user
     */
    protected function sendApprovalNotification(): void
    {
        try {
            $user = $this->getAssociatedUser();
            if ($user) {
                $user->notify(new VerificationApprovedNotification($this));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send verification approval notification', [
                'verification_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send rejection notification to the user
     */
    protected function sendRejectionNotification(): void
    {
        try {
            $user = $this->getAssociatedUser();
            if ($user) {
                $user->notify(new VerificationRejectedNotification($this));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send verification rejection notification', [
                'verification_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the associated user for this verification request
     */
    public function getAssociatedUser(): ?User
    {
        $verifiable = $this->verifiable;

        if (!$verifiable) {
            return null;
        }

        // If verifiable is a User model directly
        if ($verifiable instanceof User) {
            return $verifiable;
        }

        // If verifiable has a user relationship (profile models)
        if (method_exists($verifiable, 'user')) {
            return $verifiable->user;
        }

        // If verifiable has a user_id attribute
        if (isset($verifiable->user_id)) {
            return User::find($verifiable->user_id);
        }

        return null;
    }

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    /**
     * Bulk approve multiple verification requests
     *
     * @param array $ids Array of verification queue IDs to approve
     * @param int $adminId ID of the admin performing the action
     * @param string|null $notes Optional notes to apply to all
     * @return array Summary of results ['success' => int, 'failed' => int, 'errors' => array]
     */
    public static function bulkApprove(array $ids, int $adminId, ?string $notes = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'approved_ids' => [],
        ];

        // Validate batch size limit
        if (count($ids) > 50) {
            $ids = array_slice($ids, 0, 50);
            $results['errors'][] = 'Batch size limited to 50 items. Only first 50 processed.';
        }

        // Get all valid pending/in_review verifications
        $verifications = self::whereIn('id', $ids)
            ->whereIn('status', ['pending', 'in_review'])
            ->with('verifiable')
            ->get();

        // Track IDs that weren't found or are in wrong status
        $foundIds = $verifications->pluck('id')->toArray();
        $notFoundIds = array_diff($ids, $foundIds);

        foreach ($notFoundIds as $id) {
            $results['errors'][] = "ID {$id}: Not found or already processed";
            $results['failed']++;
        }

        // Process each verification in a transaction
        DB::beginTransaction();
        try {
            $usersToNotify = collect();

            foreach ($verifications as $verification) {
                try {
                    $verification->update([
                        'status' => 'approved',
                        'reviewed_by' => $adminId,
                        'reviewed_at' => now(),
                        'admin_notes' => $notes,
                    ]);

                    // Update verifiable model's verification status
                    if ($verification->verification_type === 'identity' &&
                        $verification->verifiable &&
                        method_exists($verification->verifiable, 'markAsVerified')) {
                        $verification->verifiable->markAsVerified();
                    }

                    // Collect user for batch notification
                    $user = $verification->getAssociatedUser();
                    if ($user) {
                        $usersToNotify->push([
                            'user' => $user,
                            'verification' => $verification,
                        ]);
                    }

                    $results['success']++;
                    $results['approved_ids'][] = $verification->id;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "ID {$verification->id}: " . $e->getMessage();
                    Log::error('Bulk approve individual error', [
                        'verification_id' => $verification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Send notifications after successful commit (batch for efficiency)
            self::sendBulkApprovalNotifications($usersToNotify);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['failed'] = count($ids);
            $results['success'] = 0;
            $results['approved_ids'] = [];
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
            Log::error('Bulk approve transaction failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Bulk reject multiple verification requests
     *
     * @param array $ids Array of verification queue IDs to reject
     * @param int $adminId ID of the admin performing the action
     * @param string $notes Rejection reason (required for rejections)
     * @return array Summary of results ['success' => int, 'failed' => int, 'errors' => array]
     */
    public static function bulkReject(array $ids, int $adminId, string $notes): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'rejected_ids' => [],
        ];

        // Validate batch size limit
        if (count($ids) > 50) {
            $ids = array_slice($ids, 0, 50);
            $results['errors'][] = 'Batch size limited to 50 items. Only first 50 processed.';
        }

        // Get all valid pending/in_review verifications
        $verifications = self::whereIn('id', $ids)
            ->whereIn('status', ['pending', 'in_review'])
            ->with('verifiable')
            ->get();

        // Track IDs that weren't found or are in wrong status
        $foundIds = $verifications->pluck('id')->toArray();
        $notFoundIds = array_diff($ids, $foundIds);

        foreach ($notFoundIds as $id) {
            $results['errors'][] = "ID {$id}: Not found or already processed";
            $results['failed']++;
        }

        // Process each verification in a transaction
        DB::beginTransaction();
        try {
            $usersToNotify = collect();

            foreach ($verifications as $verification) {
                try {
                    $verification->update([
                        'status' => 'rejected',
                        'reviewed_by' => $adminId,
                        'reviewed_at' => now(),
                        'admin_notes' => $notes,
                    ]);

                    // Collect user for batch notification
                    $user = $verification->getAssociatedUser();
                    if ($user) {
                        $usersToNotify->push([
                            'user' => $user,
                            'verification' => $verification,
                        ]);
                    }

                    $results['success']++;
                    $results['rejected_ids'][] = $verification->id;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "ID {$verification->id}: " . $e->getMessage();
                    Log::error('Bulk reject individual error', [
                        'verification_id' => $verification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Send notifications after successful commit
            self::sendBulkRejectionNotifications($usersToNotify, $notes);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['failed'] = count($ids);
            $results['success'] = 0;
            $results['rejected_ids'] = [];
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
            Log::error('Bulk reject transaction failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Send bulk approval notifications efficiently
     */
    protected static function sendBulkApprovalNotifications($usersToNotify): void
    {
        foreach ($usersToNotify as $item) {
            try {
                $item['user']->notify(new VerificationApprovedNotification($item['verification']));
            } catch (\Exception $e) {
                Log::warning('Failed to send bulk approval notification', [
                    'user_id' => $item['user']->id,
                    'verification_id' => $item['verification']->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send bulk rejection notifications efficiently
     */
    protected static function sendBulkRejectionNotifications($usersToNotify, string $reason): void
    {
        foreach ($usersToNotify as $item) {
            try {
                $item['user']->notify(new VerificationRejectedNotification($item['verification']));
            } catch (\Exception $e) {
                Log::warning('Failed to send bulk rejection notification', [
                    'user_id' => $item['user']->id,
                    'verification_id' => $item['verification']->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // =========================================================================
    // SLA STATISTICS
    // =========================================================================

    /**
     * Get SLA compliance statistics
     */
    public static function getSLAStatistics(): array
    {
        $actionable = self::actionable();

        $total = (clone $actionable)->count();
        $onTrack = (clone $actionable)->where('sla_status', 'on_track')->count();
        $atRisk = (clone $actionable)->where('sla_status', 'at_risk')->count();
        $breached = (clone $actionable)->where('sla_status', 'breached')->count();

        // Calculate compliance percentage (on_track / total)
        $compliancePercentage = $total > 0 ? round(($onTrack / $total) * 100, 1) : 100;

        // Get recently completed stats for historical compliance
        $completedLast30Days = self::whereIn('status', ['approved', 'rejected'])
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->get();

        $completedOnTime = $completedLast30Days->filter(function ($item) {
            return $item->reviewed_at && $item->sla_deadline &&
                   $item->reviewed_at->lessThanOrEqualTo($item->sla_deadline);
        })->count();

        $historicalCompliancePercentage = $completedLast30Days->count() > 0
            ? round(($completedOnTime / $completedLast30Days->count()) * 100, 1)
            : 100;

        // Breakdown by verification type
        $byType = self::actionable()
            ->selectRaw('verification_type, sla_status, COUNT(*) as count')
            ->groupBy('verification_type', 'sla_status')
            ->get()
            ->groupBy('verification_type')
            ->map(function ($items) {
                return $items->pluck('count', 'sla_status')->toArray();
            })
            ->toArray();

        return [
            'total_pending' => $total,
            'on_track' => $onTrack,
            'at_risk' => $atRisk,
            'breached' => $breached,
            'current_compliance_percentage' => $compliancePercentage,
            'historical_compliance_percentage' => $historicalCompliancePercentage,
            'by_type' => $byType,
        ];
    }

    /**
     * Get average processing time by verification type (last 30 days)
     */
    public static function getAverageProcessingTimes(): array
    {
        return self::whereIn('status', ['approved', 'rejected'])
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->whereNotNull('submitted_at')
            ->whereNotNull('reviewed_at')
            ->selectRaw('verification_type, AVG(TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at)) as avg_hours')
            ->groupBy('verification_type')
            ->pluck('avg_hours', 'verification_type')
            ->map(fn($hours) => round($hours, 1))
            ->toArray();
    }
}
