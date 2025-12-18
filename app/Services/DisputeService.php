<?php

namespace App\Services;

use App\Models\AdminDisputeQueue;
use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Notifications\DisputeOpenedNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Notifications\DisputeResponseNotification;
use App\Notifications\EvidenceDeadlineNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * DisputeService
 *
 * FIN-010: Dispute Resolution System
 *
 * Handles all dispute-related business logic including opening disputes,
 * submitting responses, managing evidence, resolution, and escalation.
 */
class DisputeService
{
    /**
     * Open a new dispute.
     *
     * @throws \Exception
     */
    public function openDispute(Shift $shift, User $worker, array $data): Dispute
    {
        // Validate minimum amount
        $minAmount = config('disputes.min_amount', 5.00);
        if ($data['disputed_amount'] < $minAmount) {
            throw new \Exception("Disputed amount must be at least \${$minAmount}");
        }

        // Check if worker has reached max active disputes
        $maxActive = config('disputes.max_active_per_user', 5);
        $activeCount = Dispute::forWorker($worker->id)->active()->count();
        if ($activeCount >= $maxActive) {
            throw new \Exception("You have reached the maximum number of active disputes ({$maxActive})");
        }

        // Check for existing active dispute on this shift by same worker
        $existingDispute = Dispute::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->active()
            ->first();

        if ($existingDispute) {
            throw new \Exception('You already have an active dispute for this shift');
        }

        // Check cooldown period
        $cooldownHours = config('disputes.cooldown_hours', 24);
        $recentDispute = Dispute::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->where('created_at', '>=', now()->subHours($cooldownHours))
            ->first();

        if ($recentDispute) {
            throw new \Exception("You must wait {$cooldownHours} hours before opening another dispute for this shift");
        }

        return DB::transaction(function () use ($shift, $worker, $data) {
            // Calculate evidence deadline
            $evidenceDeadlineDays = config('disputes.evidence_deadline_days', 5);
            $evidenceDeadline = now()->addDays($evidenceDeadlineDays);

            // Create the dispute
            $dispute = Dispute::create([
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'business_id' => $shift->business_id,
                'type' => $data['type'],
                'status' => Dispute::STATUS_OPEN,
                'disputed_amount' => $data['disputed_amount'],
                'worker_description' => $data['worker_description'],
                'evidence_deadline' => $evidenceDeadline,
            ]);

            // Add timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_OPENED,
                $worker->id,
                "Dispute opened for {$data['disputed_amount']} - Type: ".$dispute->type_label,
                ['amount' => $data['disputed_amount'], 'type' => $data['type']]
            );

            // Add deadline timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_DEADLINE_SET,
                null,
                "Evidence deadline set for {$evidenceDeadline->format('M d, Y H:i')}",
                ['deadline' => $evidenceDeadline->toDateTimeString()]
            );

            // Mark shift as having disputes
            $shift->update(['has_disputes' => true]);

            // Create admin queue entry if amount exceeds threshold
            $autoAssignThreshold = config('disputes.auto_assign_threshold', 500.00);
            if ($data['disputed_amount'] >= $autoAssignThreshold) {
                $this->createAdminQueueEntry($dispute, 'high');
            }

            // Notify business
            try {
                $business = $shift->business;
                if ($business) {
                    $business->notify(new DisputeOpenedNotification($dispute));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send dispute opened notification', [
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Dispute opened', [
                'dispute_id' => $dispute->id,
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'amount' => $data['disputed_amount'],
            ]);

            return $dispute;
        });
    }

    /**
     * Submit business response to a dispute.
     *
     * @throws \Exception
     */
    public function submitBusinessResponse(Dispute $dispute, array $data): Dispute
    {
        if (! $dispute->isActive()) {
            throw new \Exception('Cannot respond to a resolved or closed dispute');
        }

        if ($dispute->business_response) {
            throw new \Exception('Business has already responded to this dispute');
        }

        return DB::transaction(function () use ($dispute, $data) {
            $dispute->update([
                'business_response' => $data['response'],
                'status' => Dispute::STATUS_UNDER_REVIEW,
            ]);

            // Add timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_BUSINESS_RESPONDED,
                $dispute->business_id,
                'Business submitted response',
                ['response_length' => strlen($data['response'])]
            );

            // Status change entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_STATUS_CHANGED,
                null,
                'Status changed to Under Review',
                ['old_status' => Dispute::STATUS_OPEN, 'new_status' => Dispute::STATUS_UNDER_REVIEW]
            );

            // Notify worker
            try {
                $dispute->worker->notify(new DisputeResponseNotification($dispute));
            } catch (\Exception $e) {
                Log::error('Failed to send dispute response notification', [
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Business responded to dispute', [
                'dispute_id' => $dispute->id,
                'business_id' => $dispute->business_id,
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Submit evidence for a dispute.
     *
     * @throws \Exception
     */
    public function submitEvidence(Dispute $dispute, User $user, array $files): Dispute
    {
        if (! $dispute->isActive()) {
            throw new \Exception('Cannot submit evidence to a resolved or closed dispute');
        }

        // Check deadline
        if ($dispute->evidence_deadline && now()->isAfter($dispute->evidence_deadline)) {
            throw new \Exception('Evidence submission deadline has passed');
        }

        // Determine if worker or business
        $isWorker = $user->id === $dispute->worker_id;
        $isBusiness = $user->id === $dispute->business_id;

        if (! $isWorker && ! $isBusiness) {
            throw new \Exception('You are not a party to this dispute');
        }

        // Check max files
        $maxFiles = config('disputes.max_evidence_files', 10);
        $existingEvidence = $isWorker ? $dispute->evidence_worker : $dispute->evidence_business;
        $existingCount = $existingEvidence ? count($existingEvidence) : 0;

        if ($existingCount + count($files) > $maxFiles) {
            throw new \Exception("Maximum {$maxFiles} evidence files allowed per party");
        }

        return DB::transaction(function () use ($dispute, $user, $files, $isWorker) {
            $uploadedFiles = [];
            $maxSize = config('disputes.max_evidence_file_size_mb', 10) * 1024 * 1024;
            $allowedTypes = config('disputes.allowed_evidence_types', []);

            foreach ($files as $file) {
                // Validate file size
                if ($file instanceof UploadedFile && $file->getSize() > $maxSize) {
                    throw new \Exception("File {$file->getClientOriginalName()} exceeds maximum size limit");
                }

                // Validate file type
                if ($file instanceof UploadedFile) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (! empty($allowedTypes) && ! in_array($extension, $allowedTypes)) {
                        throw new \Exception("File type .{$extension} is not allowed");
                    }
                }

                // Store file
                $path = $file->store("disputes/{$dispute->id}/evidence", 'public');
                $uploadedFiles[] = [
                    'name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'mime' => $file instanceof UploadedFile ? $file->getMimeType() : null,
                    'size' => $file instanceof UploadedFile ? $file->getSize() : null,
                    'uploaded_at' => now()->toDateTimeString(),
                    'uploaded_by' => $user->id,
                ];
            }

            // Merge with existing evidence
            $evidenceField = $isWorker ? 'evidence_worker' : 'evidence_business';
            $existingEvidence = $dispute->{$evidenceField} ?? [];
            $allEvidence = array_merge($existingEvidence, $uploadedFiles);

            $dispute->update([
                $evidenceField => $allEvidence,
            ]);

            // Update status if both parties have submitted evidence
            if ($dispute->evidence_worker && $dispute->evidence_business) {
                $dispute->update(['status' => Dispute::STATUS_AWAITING_EVIDENCE]);
            }

            // Add timeline entry
            $action = $isWorker ? DisputeTimeline::ACTION_WORKER_EVIDENCE : DisputeTimeline::ACTION_BUSINESS_EVIDENCE;
            $dispute->addTimelineEntry(
                $action,
                $user->id,
                count($uploadedFiles).' file(s) submitted as evidence',
                ['files' => array_column($uploadedFiles, 'name')]
            );

            Log::info('Evidence submitted for dispute', [
                'dispute_id' => $dispute->id,
                'user_id' => $user->id,
                'file_count' => count($uploadedFiles),
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Assign a mediator to the dispute.
     *
     * @throws \Exception
     */
    public function assignMediator(Dispute $dispute, User $admin): Dispute
    {
        if (! $dispute->isActive()) {
            throw new \Exception('Cannot assign mediator to a resolved or closed dispute');
        }

        if ($admin->role !== 'admin') {
            throw new \Exception('Only administrators can be assigned as mediators');
        }

        return DB::transaction(function () use ($dispute, $admin) {
            $previousAssignee = $dispute->assigned_to;

            $dispute->update([
                'assigned_to' => $admin->id,
                'status' => Dispute::STATUS_MEDIATION,
            ]);

            // Add timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_ASSIGNED,
                auth()->id(),
                "Mediator assigned: {$admin->name}",
                ['assigned_to' => $admin->id, 'previous_assignee' => $previousAssignee]
            );

            // Status change entry
            if ($dispute->getOriginal('status') !== Dispute::STATUS_MEDIATION) {
                $dispute->addTimelineEntry(
                    DisputeTimeline::ACTION_STATUS_CHANGED,
                    null,
                    'Status changed to In Mediation',
                    ['old_status' => $dispute->getOriginal('status'), 'new_status' => Dispute::STATUS_MEDIATION]
                );
            }

            // Update or create admin queue entry
            if ($dispute->admin_queue_id) {
                AdminDisputeQueue::where('id', $dispute->admin_queue_id)
                    ->update([
                        'assigned_to_admin' => $admin->id,
                        'assigned_at' => now(),
                        'status' => AdminDisputeQueue::STATUS_INVESTIGATING,
                    ]);
            } else {
                $this->createAdminQueueEntry($dispute, 'medium', $admin->id);
            }

            Log::info('Mediator assigned to dispute', [
                'dispute_id' => $dispute->id,
                'admin_id' => $admin->id,
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Resolve a dispute.
     *
     * @throws \Exception
     */
    public function resolveDispute(Dispute $dispute, string $resolution, float $amount, string $notes): Dispute
    {
        if (! $dispute->isActive()) {
            throw new \Exception('Dispute is already resolved or closed');
        }

        $validResolutions = [
            Dispute::RESOLUTION_WORKER_FAVOR,
            Dispute::RESOLUTION_BUSINESS_FAVOR,
            Dispute::RESOLUTION_SPLIT,
            Dispute::RESOLUTION_WITHDRAWN,
            Dispute::RESOLUTION_EXPIRED,
        ];

        if (! in_array($resolution, $validResolutions)) {
            throw new \Exception('Invalid resolution type');
        }

        return DB::transaction(function () use ($dispute, $resolution, $amount, $notes) {
            $dispute->update([
                'status' => Dispute::STATUS_RESOLVED,
                'resolution' => $resolution,
                'resolution_amount' => $amount,
                'resolution_notes' => $notes,
                'resolved_at' => now(),
            ]);

            // Add timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_RESOLVED,
                auth()->id(),
                "Dispute resolved: {$dispute->resolution_label} - Amount: \${$amount}",
                [
                    'resolution' => $resolution,
                    'amount' => $amount,
                    'disputed_amount' => $dispute->disputed_amount,
                ]
            );

            // Update admin queue if linked
            if ($dispute->admin_queue_id) {
                AdminDisputeQueue::where('id', $dispute->admin_queue_id)
                    ->update([
                        'status' => AdminDisputeQueue::STATUS_RESOLVED,
                        'resolution_outcome' => $this->mapResolutionToAdminQueue($resolution),
                        'adjustment_amount' => $amount,
                        'resolution_notes' => $notes,
                        'resolved_at' => now(),
                    ]);
            }

            // Process resolution payment
            $this->processResolutionPayment($dispute);

            // Notify both parties
            try {
                $dispute->worker->notify(new DisputeResolvedNotification($dispute, 'worker'));
                $dispute->business->notify(new DisputeResolvedNotification($dispute, 'business'));
            } catch (\Exception $e) {
                Log::error('Failed to send dispute resolution notifications', [
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Dispute resolved', [
                'dispute_id' => $dispute->id,
                'resolution' => $resolution,
                'amount' => $amount,
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Escalate a dispute.
     *
     * @throws \Exception
     */
    public function escalateDispute(Dispute $dispute): Dispute
    {
        if (! $dispute->canBeEscalated()) {
            throw new \Exception('This dispute cannot be escalated');
        }

        return DB::transaction(function () use ($dispute) {
            $dispute->update([
                'status' => Dispute::STATUS_ESCALATED,
            ]);

            // Add timeline entry
            $dispute->addTimelineEntry(
                DisputeTimeline::ACTION_ESCALATED,
                auth()->id(),
                'Dispute escalated for urgent review',
                ['previous_status' => $dispute->getOriginal('status')]
            );

            // Create or update admin queue entry with higher priority
            if ($dispute->admin_queue_id) {
                $adminQueue = AdminDisputeQueue::find($dispute->admin_queue_id);
                if ($adminQueue) {
                    $escalationService = app(DisputeEscalationService::class);
                    $escalationService->escalateDispute($adminQueue, 'Manual escalation from dispute system');
                }
            } else {
                $this->createAdminQueueEntry($dispute, 'urgent');
            }

            Log::info('Dispute escalated', [
                'dispute_id' => $dispute->id,
            ]);

            return $dispute->fresh();
        });
    }

    /**
     * Auto-close stale disputes.
     *
     * @return int Number of disputes closed
     */
    public function autoCloseStaleDisputes(): int
    {
        $autoCloseDays = config('disputes.auto_close_days', 30);

        $staleDisputes = Dispute::stale($autoCloseDays)->get();
        $closedCount = 0;

        foreach ($staleDisputes as $dispute) {
            try {
                DB::transaction(function () use ($dispute) {
                    $dispute->update([
                        'status' => Dispute::STATUS_CLOSED,
                        'resolution' => Dispute::RESOLUTION_EXPIRED,
                        'resolution_notes' => 'Automatically closed due to inactivity',
                        'resolved_at' => now(),
                    ]);

                    $dispute->addTimelineEntry(
                        DisputeTimeline::ACTION_CLOSED,
                        null,
                        'Dispute automatically closed due to inactivity',
                        ['auto_closed' => true, 'days_inactive' => config('disputes.auto_close_days')]
                    );
                });

                $closedCount++;

                Log::info('Stale dispute auto-closed', [
                    'dispute_id' => $dispute->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to auto-close stale dispute', [
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $closedCount;
    }

    /**
     * Calculate resolution split based on evidence and circumstances.
     */
    public function calculateResolutionSplit(Dispute $dispute): array
    {
        $disputedAmount = $dispute->disputed_amount;
        $defaultSplitPercent = config('disputes.split_default_percentage', 50);

        // Default 50/50 split
        $workerAmount = round($disputedAmount * ($defaultSplitPercent / 100), 2);
        $businessAmount = round($disputedAmount * ((100 - $defaultSplitPercent) / 100), 2);

        // Factors that might adjust the split
        $factors = [];

        // Evidence factor
        $hasWorkerEvidence = ! empty($dispute->evidence_worker);
        $hasBusinessEvidence = ! empty($dispute->evidence_business);

        if ($hasWorkerEvidence && ! $hasBusinessEvidence) {
            // Worker provided evidence, business didn't - favor worker
            $workerAmount = round($disputedAmount * 0.70, 2);
            $businessAmount = round($disputedAmount * 0.30, 2);
            $factors[] = 'Worker provided evidence, business did not';
        } elseif ($hasBusinessEvidence && ! $hasWorkerEvidence) {
            // Business provided evidence, worker didn't - favor business
            $workerAmount = round($disputedAmount * 0.30, 2);
            $businessAmount = round($disputedAmount * 0.70, 2);
            $factors[] = 'Business provided evidence, worker did not';
        }

        // Response timing factor
        if ($dispute->business_response === null && $dispute->created_at->diffInDays(now()) > config('disputes.business_response_days', 3)) {
            // Business never responded within deadline - favor worker
            $workerAmount = round($disputedAmount * 0.75, 2);
            $businessAmount = round($disputedAmount * 0.25, 2);
            $factors[] = 'Business did not respond within deadline';
        }

        return [
            'disputed_amount' => $disputedAmount,
            'worker_amount' => $workerAmount,
            'business_amount' => $businessAmount,
            'worker_percentage' => round(($workerAmount / $disputedAmount) * 100, 1),
            'business_percentage' => round(($businessAmount / $disputedAmount) * 100, 1),
            'factors' => $factors,
            'recommendation' => $workerAmount > $businessAmount ? 'worker_favor' : ($businessAmount > $workerAmount ? 'business_favor' : 'split'),
        ];
    }

    /**
     * Send evidence deadline reminders.
     *
     * @return int Number of reminders sent
     */
    public function sendEvidenceDeadlineReminders(): int
    {
        $reminderHours = config('disputes.notifications.deadline_reminder_hours', [48, 24, 12]);
        $sentCount = 0;

        foreach ($reminderHours as $hours) {
            $disputes = Dispute::active()
                ->whereNotNull('evidence_deadline')
                ->whereBetween('evidence_deadline', [
                    now()->addHours($hours - 1),
                    now()->addHours($hours + 1),
                ])
                ->get();

            foreach ($disputes as $dispute) {
                try {
                    // Notify worker if no evidence submitted
                    if (empty($dispute->evidence_worker)) {
                        $dispute->worker->notify(new EvidenceDeadlineNotification($dispute, $hours, 'worker'));
                        $sentCount++;
                    }

                    // Notify business if no evidence submitted
                    if (empty($dispute->evidence_business)) {
                        $dispute->business->notify(new EvidenceDeadlineNotification($dispute, $hours, 'business'));
                        $sentCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send deadline reminder', [
                        'dispute_id' => $dispute->id,
                        'hours' => $hours,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $sentCount;
    }

    /**
     * Get dispute statistics.
     *
     * @param  int|null  $userId  Optional user ID to filter stats
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = Dispute::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('worker_id', $userId)
                    ->orWhere('business_id', $userId);
            });
        }

        $disputes = $query->get();

        return [
            'total' => $disputes->count(),
            'active' => $disputes->whereIn('status', [
                Dispute::STATUS_OPEN,
                Dispute::STATUS_UNDER_REVIEW,
                Dispute::STATUS_AWAITING_EVIDENCE,
                Dispute::STATUS_MEDIATION,
                Dispute::STATUS_ESCALATED,
            ])->count(),
            'resolved' => $disputes->where('status', Dispute::STATUS_RESOLVED)->count(),
            'closed' => $disputes->where('status', Dispute::STATUS_CLOSED)->count(),
            'escalated' => $disputes->where('status', Dispute::STATUS_ESCALATED)->count(),
            'by_type' => $disputes->groupBy('type')->map->count(),
            'by_resolution' => $disputes->whereNotNull('resolution')->groupBy('resolution')->map->count(),
            'total_disputed_amount' => $disputes->sum('disputed_amount'),
            'total_resolved_amount' => $disputes->whereNotNull('resolution_amount')->sum('resolution_amount'),
            'average_resolution_days' => $this->calculateAverageResolutionDays($disputes),
            'worker_favor_rate' => $this->calculateFavorRate($disputes, 'worker'),
            'business_favor_rate' => $this->calculateFavorRate($disputes, 'business'),
        ];
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Create an admin queue entry for the dispute.
     */
    private function createAdminQueueEntry(Dispute $dispute, string $priority = 'medium', ?int $assignedTo = null): void
    {
        // Find the shift payment for this dispute
        $shiftPayment = ShiftPayment::where('shift_id', $dispute->shift_id)
            ->where('worker_id', $dispute->worker_id)
            ->first();

        $adminQueue = AdminDisputeQueue::create([
            'shift_payment_id' => $shiftPayment?->id ?? 0,
            'filed_by' => 'worker',
            'worker_id' => $dispute->worker_id,
            'business_id' => $dispute->business_id,
            'status' => $assignedTo ? AdminDisputeQueue::STATUS_INVESTIGATING : AdminDisputeQueue::STATUS_PENDING,
            'priority' => $priority,
            'dispute_reason' => $dispute->worker_description,
            'assigned_to_admin' => $assignedTo,
            'assigned_at' => $assignedTo ? now() : null,
            'filed_at' => $dispute->created_at,
        ]);

        $dispute->update(['admin_queue_id' => $adminQueue->id]);
    }

    /**
     * Process resolution payment.
     */
    private function processResolutionPayment(Dispute $dispute): void
    {
        if (! $dispute->resolution_amount || $dispute->resolution_amount <= 0) {
            return;
        }

        // This would integrate with the payment service
        // For now, log the action
        Log::info('Resolution payment to be processed', [
            'dispute_id' => $dispute->id,
            'resolution' => $dispute->resolution,
            'amount' => $dispute->resolution_amount,
            'worker_id' => $dispute->worker_id,
            'business_id' => $dispute->business_id,
        ]);

        // TODO: Integrate with ShiftPaymentService or SettlementService to process actual payment
    }

    /**
     * Map dispute resolution to admin queue resolution outcome.
     */
    private function mapResolutionToAdminQueue(string $resolution): string
    {
        return match ($resolution) {
            Dispute::RESOLUTION_WORKER_FAVOR => 'worker_favor',
            Dispute::RESOLUTION_BUSINESS_FAVOR => 'business_favor',
            Dispute::RESOLUTION_SPLIT => 'split',
            default => 'no_fault',
        };
    }

    /**
     * Calculate average resolution time in days.
     */
    private function calculateAverageResolutionDays($disputes): float
    {
        $resolvedDisputes = $disputes->whereNotNull('resolved_at');

        if ($resolvedDisputes->isEmpty()) {
            return 0;
        }

        $totalDays = $resolvedDisputes->sum(function ($dispute) {
            return $dispute->created_at->diffInDays($dispute->resolved_at);
        });

        return round($totalDays / $resolvedDisputes->count(), 1);
    }

    /**
     * Calculate favor rate for a party.
     */
    private function calculateFavorRate($disputes, string $party): float
    {
        $resolvedDisputes = $disputes->whereNotNull('resolution');

        if ($resolvedDisputes->isEmpty()) {
            return 0;
        }

        $favorableOutcomes = $resolvedDisputes->filter(function ($dispute) use ($party) {
            if ($party === 'worker') {
                return in_array($dispute->resolution, [
                    Dispute::RESOLUTION_WORKER_FAVOR,
                    Dispute::RESOLUTION_SPLIT,
                ]);
            }

            return in_array($dispute->resolution, [
                Dispute::RESOLUTION_BUSINESS_FAVOR,
                Dispute::RESOLUTION_SPLIT,
            ]);
        });

        return round(($favorableOutcomes->count() / $resolvedDisputes->count()) * 100, 1);
    }
}
