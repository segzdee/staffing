<?php

namespace App\Services;

use App\Models\BusinessRoster;
use App\Models\RosterInvitation;
use App\Models\RosterMember;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\InvitationAcceptedNotification;
use App\Notifications\RemovedFromRosterNotification;
use App\Notifications\RosterInvitationNotification;
use Illuminate\Support\Facades\DB;

/**
 * BIZ-005: Roster Management Service
 *
 * Handles all roster-related operations for businesses managing their
 * preferred, regular, backup, and blacklisted workers.
 */
class RosterService
{
    /**
     * Create a new roster for a business.
     *
     * @param  array  $data  Contains: name, description, type, is_default
     */
    public function createRoster(User $business, array $data): BusinessRoster
    {
        // If this is set as default, unset other defaults of same type
        if (! empty($data['is_default'])) {
            BusinessRoster::where('business_id', $business->id)
                ->where('type', $data['type'])
                ->update(['is_default' => false]);
        }

        return BusinessRoster::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? BusinessRoster::TYPE_REGULAR,
            'is_default' => $data['is_default'] ?? false,
        ]);
    }

    /**
     * Update an existing roster.
     *
     * @param  array  $data  Contains: name, description, type, is_default
     */
    public function updateRoster(BusinessRoster $roster, array $data): BusinessRoster
    {
        // If this is set as default, unset other defaults of same type
        if (! empty($data['is_default']) && ! $roster->is_default) {
            BusinessRoster::where('business_id', $roster->business_id)
                ->where('type', $data['type'] ?? $roster->type)
                ->where('id', '!=', $roster->id)
                ->update(['is_default' => false]);
        }

        $roster->update([
            'name' => $data['name'] ?? $roster->name,
            'description' => $data['description'] ?? $roster->description,
            'type' => $data['type'] ?? $roster->type,
            'is_default' => $data['is_default'] ?? $roster->is_default,
        ]);

        return $roster->fresh();
    }

    /**
     * Delete a roster (and all its members).
     */
    public function deleteRoster(BusinessRoster $roster): bool
    {
        // Notify workers they've been removed
        $roster->members()->with('worker')->get()->each(function ($member) use ($roster) {
            if ($member->worker) {
                $member->worker->notify(new RemovedFromRosterNotification($roster, 'Roster was deleted'));
            }
        });

        return $roster->delete();
    }

    /**
     * Add a worker directly to a roster.
     *
     * @param  array  $data  Contains: status, notes, custom_rate, priority, preferred_positions, availability_preferences
     */
    public function addWorkerToRoster(BusinessRoster $roster, User $worker, array $data = []): RosterMember
    {
        // Check if already exists
        $existing = $roster->getMember($worker);
        if ($existing) {
            return $this->updateRosterMember($existing, $data);
        }

        return RosterMember::create([
            'roster_id' => $roster->id,
            'worker_id' => $worker->id,
            'status' => $data['status'] ?? RosterMember::STATUS_ACTIVE,
            'notes' => $data['notes'] ?? null,
            'custom_rate' => $data['custom_rate'] ?? null,
            'priority' => $data['priority'] ?? 0,
            'preferred_positions' => $data['preferred_positions'] ?? null,
            'availability_preferences' => $data['availability_preferences'] ?? null,
            'added_by' => $data['added_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Update a roster member's details.
     *
     * @param  array  $data  Contains: status, notes, custom_rate, priority, preferred_positions, availability_preferences
     */
    public function updateRosterMember(RosterMember $member, array $data): RosterMember
    {
        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $data['notes'];
        }
        if (array_key_exists('custom_rate', $data)) {
            $updateData['custom_rate'] = $data['custom_rate'];
        }
        if (isset($data['priority'])) {
            $updateData['priority'] = $data['priority'];
        }
        if (array_key_exists('preferred_positions', $data)) {
            $updateData['preferred_positions'] = $data['preferred_positions'];
        }
        if (array_key_exists('availability_preferences', $data)) {
            $updateData['availability_preferences'] = $data['availability_preferences'];
        }

        if (! empty($updateData)) {
            $member->update($updateData);
        }

        return $member->fresh();
    }

    /**
     * Remove a worker from a roster.
     */
    public function removeWorkerFromRoster(RosterMember $member, ?string $reason = null): bool
    {
        $roster = $member->roster;
        $worker = $member->worker;

        $deleted = $member->delete();

        // Notify the worker
        if ($deleted && $worker) {
            $worker->notify(new RemovedFromRosterNotification($roster, $reason));
        }

        return $deleted;
    }

    /**
     * Invite a worker to join a roster.
     */
    public function inviteWorkerToRoster(
        BusinessRoster $roster,
        User $worker,
        ?string $message = null,
        ?int $expiryDays = null
    ): RosterInvitation {
        // Cancel any existing pending invitations
        RosterInvitation::where('roster_id', $roster->id)
            ->where('worker_id', $worker->id)
            ->where('status', RosterInvitation::STATUS_PENDING)
            ->update(['status' => RosterInvitation::STATUS_EXPIRED]);

        $invitation = RosterInvitation::create([
            'roster_id' => $roster->id,
            'worker_id' => $worker->id,
            'invited_by' => auth()->id(),
            'status' => RosterInvitation::STATUS_PENDING,
            'message' => $message,
            'expires_at' => now()->addDays($expiryDays ?? RosterInvitation::DEFAULT_EXPIRY_DAYS),
        ]);

        // Notify the worker
        $worker->notify(new RosterInvitationNotification($invitation));

        return $invitation;
    }

    /**
     * Accept a roster invitation.
     */
    public function acceptInvitation(RosterInvitation $invitation): RosterMember
    {
        if (! $invitation->canRespond()) {
            throw new \Exception('This invitation cannot be accepted. It may have expired or already been responded to.');
        }

        return DB::transaction(function () use ($invitation) {
            // Accept the invitation
            $invitation->accept();

            // Add the worker to the roster
            $member = $this->addWorkerToRoster(
                $invitation->roster,
                $invitation->worker,
                [
                    'status' => RosterMember::STATUS_ACTIVE,
                    'added_by' => $invitation->invited_by,
                ]
            );

            // Notify the business
            $inviter = User::find($invitation->invited_by);
            if ($inviter) {
                $inviter->notify(new InvitationAcceptedNotification($invitation));
            }

            return $member;
        });
    }

    /**
     * Decline a roster invitation.
     */
    public function declineInvitation(RosterInvitation $invitation): RosterInvitation
    {
        if (! $invitation->canRespond()) {
            throw new \Exception('This invitation cannot be declined. It may have expired or already been responded to.');
        }

        $invitation->decline();

        return $invitation;
    }

    /**
     * Worker leaves a roster voluntarily.
     */
    public function leaveRoster(RosterMember $member): bool
    {
        return $member->delete();
    }

    /**
     * Get available roster workers for a shift.
     * Excludes blacklisted workers and prioritizes by roster type and priority.
     */
    public function getAvailableRosterWorkers(User $business, Shift $shift): \Illuminate\Support\Collection
    {
        // Get all blacklisted worker IDs
        $blacklistedIds = RosterMember::whereHas('roster', function ($q) use ($business) {
            $q->where('business_id', $business->id)
                ->where('type', BusinessRoster::TYPE_BLACKLIST);
        })
            ->where('status', RosterMember::STATUS_ACTIVE)
            ->pluck('worker_id')
            ->toArray();

        // Get all roster members (non-blacklist), excluding blacklisted workers
        $members = RosterMember::with(['roster', 'worker.workerProfile', 'worker.skills'])
            ->whereHas('roster', function ($q) use ($business) {
                $q->where('business_id', $business->id)
                    ->where('type', '!=', BusinessRoster::TYPE_BLACKLIST);
            })
            ->where('status', RosterMember::STATUS_ACTIVE)
            ->whereNotIn('worker_id', $blacklistedIds)
            ->get();

        // Filter by availability for this shift
        $availableMembers = $members->filter(function ($member) use ($shift) {
            // Check if worker is available based on their preferences
            return $member->isAvailableFor($shift);
        });

        // Sort by roster type priority and member priority
        return $availableMembers->sortBy([
            // Preferred first, then regular, then backup
            fn ($a) => match ($a->roster->type) {
                BusinessRoster::TYPE_PREFERRED => 0,
                BusinessRoster::TYPE_REGULAR => 1,
                BusinessRoster::TYPE_BACKUP => 2,
                default => 3,
            },
            // Then by individual priority (higher first)
            fn ($a, $b) => $b->priority <=> $a->priority,
            // Then by total shifts (more experienced first)
            fn ($a, $b) => $b->total_shifts <=> $a->total_shifts,
        ])->values();
    }

    /**
     * Prioritize roster workers for shift matching.
     * Returns worker IDs with their priority scores.
     *
     * @return array<int, array{worker_id: int, priority_score: int, roster_type: string, custom_rate: float|null}>
     */
    public function prioritizeRosterForShift(Shift $shift): array
    {
        $business = $shift->business;
        if (! $business) {
            return [];
        }

        $availableMembers = $this->getAvailableRosterWorkers($business, $shift);

        return $availableMembers->map(function ($member, $index) {
            // Priority score: base score based on roster type + individual priority + position bonus
            $baseScore = match ($member->roster->type) {
                BusinessRoster::TYPE_PREFERRED => 100,
                BusinessRoster::TYPE_REGULAR => 50,
                BusinessRoster::TYPE_BACKUP => 25,
                default => 0,
            };

            $priorityScore = $baseScore + ($member->priority * 5) + (10 - min($index, 10));

            return [
                'worker_id' => $member->worker_id,
                'priority_score' => $priorityScore,
                'roster_type' => $member->roster->type,
                'custom_rate' => $member->custom_rate,
                'total_shifts' => $member->total_shifts,
                'last_worked_at' => $member->last_worked_at?->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Update worker stats after completing a shift.
     */
    public function updateWorkerStats(RosterMember $member, Shift $shift): RosterMember
    {
        return $member->recordShiftWorked($shift);
    }

    /**
     * Bulk update worker stats for all roster members who worked a shift.
     */
    public function updateStatsForShift(Shift $shift): int
    {
        $business = $shift->business;
        if (! $business) {
            return 0;
        }

        // Get all workers who completed the shift
        $completedWorkerIds = $shift->assignments()
            ->where('status', 'completed')
            ->pluck('worker_id')
            ->toArray();

        if (empty($completedWorkerIds)) {
            return 0;
        }

        // Update stats for any roster members who worked
        $members = RosterMember::whereHas('roster', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })
            ->whereIn('worker_id', $completedWorkerIds)
            ->get();

        $updated = 0;
        foreach ($members as $member) {
            $this->updateWorkerStats($member, $shift);
            $updated++;
        }

        return $updated;
    }

    /**
     * Expire old invitations (to be run by scheduler).
     */
    public function expireOldInvitations(): int
    {
        return RosterInvitation::needingExpiration()
            ->update(['status' => RosterInvitation::STATUS_EXPIRED]);
    }

    /**
     * Check if a worker is blacklisted by a business.
     */
    public function isWorkerBlacklisted(User $business, User $worker): bool
    {
        return RosterMember::whereHas('roster', function ($q) use ($business) {
            $q->where('business_id', $business->id)
                ->where('type', BusinessRoster::TYPE_BLACKLIST);
        })
            ->where('worker_id', $worker->id)
            ->where('status', RosterMember::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Blacklist a worker for a business.
     */
    public function blacklistWorker(User $business, User $worker, ?string $reason = null): RosterMember
    {
        // Get or create blacklist roster
        $blacklist = BusinessRoster::firstOrCreate(
            [
                'business_id' => $business->id,
                'type' => BusinessRoster::TYPE_BLACKLIST,
                'is_default' => true,
            ],
            [
                'name' => 'Blacklist',
                'description' => 'Workers blocked from shifts',
            ]
        );

        // Remove from any other rosters first
        RosterMember::whereHas('roster', function ($q) use ($business) {
            $q->where('business_id', $business->id)
                ->where('type', '!=', BusinessRoster::TYPE_BLACKLIST);
        })
            ->where('worker_id', $worker->id)
            ->delete();

        // Add to blacklist
        return $this->addWorkerToRoster($blacklist, $worker, [
            'status' => RosterMember::STATUS_ACTIVE,
            'notes' => $reason,
        ]);
    }

    /**
     * Remove a worker from blacklist.
     */
    public function unblacklistWorker(User $business, User $worker): bool
    {
        return RosterMember::whereHas('roster', function ($q) use ($business) {
            $q->where('business_id', $business->id)
                ->where('type', BusinessRoster::TYPE_BLACKLIST);
        })
            ->where('worker_id', $worker->id)
            ->delete() > 0;
    }

    /**
     * Get roster statistics for a business.
     */
    public function getRosterStats(User $business): array
    {
        $rosters = BusinessRoster::where('business_id', $business->id)
            ->withCount(['members', 'activeMembers', 'pendingInvitations'])
            ->get();

        $stats = [
            'total_rosters' => $rosters->count(),
            'by_type' => [],
            'total_workers' => 0,
            'total_active_workers' => 0,
            'pending_invitations' => 0,
        ];

        foreach (BusinessRoster::getTypes() as $type => $label) {
            $typeRosters = $rosters->where('type', $type);
            $stats['by_type'][$type] = [
                'label' => $label,
                'roster_count' => $typeRosters->count(),
                'member_count' => $typeRosters->sum('members_count'),
                'active_count' => $typeRosters->sum('active_members_count'),
            ];
            $stats['total_workers'] += $typeRosters->sum('members_count');
            $stats['total_active_workers'] += $typeRosters->sum('active_members_count');
            $stats['pending_invitations'] += $typeRosters->sum('pending_invitations_count');
        }

        return $stats;
    }

    /**
     * Search workers for adding to roster.
     */
    public function searchWorkersForRoster(User $business, string $search, ?string $excludeRosterType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });

        // Exclude workers already in rosters of the specified type
        if ($excludeRosterType) {
            $existingWorkerIds = RosterMember::whereHas('roster', function ($q) use ($business, $excludeRosterType) {
                $q->where('business_id', $business->id)
                    ->where('type', $excludeRosterType);
            })->pluck('worker_id')->toArray();

            $query->whereNotIn('id', $existingWorkerIds);
        }

        return $query->with('workerProfile')
            ->limit(20)
            ->get();
    }

    /**
     * Bulk add workers to a roster.
     *
     * @param  array  $workerIds  Array of worker IDs
     * @param  array  $defaultData  Default data for all members
     * @return array{added: int, skipped: int, errors: array}
     */
    public function bulkAddWorkers(BusinessRoster $roster, array $workerIds, array $defaultData = []): array
    {
        $result = ['added' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($workerIds as $workerId) {
            try {
                $worker = User::find($workerId);
                if (! $worker || $worker->user_type !== 'worker') {
                    $result['errors'][] = "Worker ID {$workerId} not found or invalid";
                    $result['skipped']++;

                    continue;
                }

                if ($roster->hasWorker($worker)) {
                    $result['skipped']++;

                    continue;
                }

                $this->addWorkerToRoster($roster, $worker, $defaultData);
                $result['added']++;
            } catch (\Exception $e) {
                $result['errors'][] = "Error adding worker {$workerId}: ".$e->getMessage();
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * Bulk invite workers to a roster.
     *
     * @param  array  $workerIds  Array of worker IDs
     * @return array{invited: int, skipped: int, errors: array}
     */
    public function bulkInviteWorkers(BusinessRoster $roster, array $workerIds, ?string $message = null): array
    {
        $result = ['invited' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($workerIds as $workerId) {
            try {
                $worker = User::find($workerId);
                if (! $worker || $worker->user_type !== 'worker') {
                    $result['errors'][] = "Worker ID {$workerId} not found or invalid";
                    $result['skipped']++;

                    continue;
                }

                if ($roster->hasWorker($worker)) {
                    $result['skipped']++;

                    continue;
                }

                if ($roster->hasPendingInvitation($worker)) {
                    $result['skipped']++;

                    continue;
                }

                $this->inviteWorkerToRoster($roster, $worker, $message);
                $result['invited']++;
            } catch (\Exception $e) {
                $result['errors'][] = "Error inviting worker {$workerId}: ".$e->getMessage();
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * Move a worker from one roster to another.
     */
    public function moveWorkerToRoster(RosterMember $member, BusinessRoster $newRoster): RosterMember
    {
        if ($member->roster_id === $newRoster->id) {
            return $member;
        }

        return DB::transaction(function () use ($member, $newRoster) {
            $workerData = [
                'status' => $member->status,
                'notes' => $member->notes,
                'custom_rate' => $member->custom_rate,
                'priority' => $member->priority,
                'preferred_positions' => $member->preferred_positions,
                'availability_preferences' => $member->availability_preferences,
                'added_by' => $member->added_by,
            ];

            // Delete from old roster
            $member->delete();

            // Add to new roster
            return $this->addWorkerToRoster($newRoster, $member->worker, $workerData);
        });
    }

    /**
     * Get workers from a previous shift for quick re-booking.
     */
    public function getWorkersFromPreviousShift(Shift $shift): \Illuminate\Support\Collection
    {
        $completedWorkerIds = $shift->assignments()
            ->whereIn('status', ['completed', 'verified'])
            ->pluck('worker_id')
            ->toArray();

        return User::whereIn('id', $completedWorkerIds)
            ->where('status', 'active')
            ->with('workerProfile')
            ->get();
    }
}
