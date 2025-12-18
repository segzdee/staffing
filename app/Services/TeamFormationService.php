<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\TeamShiftRequest;
use App\Models\User;
use App\Models\WorkerRelationship;
use App\Models\WorkerTeam;
use App\Models\WorkerTeamMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * WKR-014: Team Formation Service
 *
 * Handles buddy system, preferred coworker matching, and team management
 * for workers on the OvertimeStaff platform.
 */
class TeamFormationService
{
    /**
     * Compatibility score weights.
     */
    private const WEIGHT_SHIFTS_TOGETHER = 0.30;

    private const WEIGHT_MUTUAL_RATINGS = 0.25;

    private const WEIGHT_SKILL_OVERLAP = 0.20;

    private const WEIGHT_RESPONSE_TIMES = 0.15;

    private const WEIGHT_RELIABILITY_MATCH = 0.10;

    /**
     * Maximum buddy/preferred relationships per worker.
     */
    private const MAX_BUDDIES = 5;

    private const MAX_PREFERRED = 20;

    // =========================================================================
    // BUDDY SYSTEM
    // =========================================================================

    /**
     * Send a buddy request to another worker.
     */
    public function addBuddy(User $worker, User $buddy): WorkerRelationship
    {
        // Validate both are workers
        $this->validateWorkerType($worker);
        $this->validateWorkerType($buddy);

        // Check if relationship already exists
        $existing = WorkerRelationship::where('worker_id', $worker->id)
            ->where('related_worker_id', $buddy->id)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->first();

        if ($existing) {
            throw new \Exception('A buddy relationship already exists with this worker.');
        }

        // Check buddy limit
        $currentBuddies = $this->getBuddies($worker)->count();
        if ($currentBuddies >= self::MAX_BUDDIES) {
            throw new \Exception('Maximum number of buddies reached ('.self::MAX_BUDDIES.').');
        }

        // Check if there's an incoming buddy request from this worker
        $incomingRequest = WorkerRelationship::where('worker_id', $buddy->id)
            ->where('related_worker_id', $worker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_PENDING)
            ->first();

        return DB::transaction(function () use ($worker, $buddy, $incomingRequest) {
            if ($incomingRequest) {
                // Auto-confirm mutual buddy relationship
                $incomingRequest->confirm();

                // Create the reciprocal relationship as active
                return WorkerRelationship::create([
                    'worker_id' => $worker->id,
                    'related_worker_id' => $buddy->id,
                    'relationship_type' => WorkerRelationship::TYPE_BUDDY,
                    'status' => WorkerRelationship::STATUS_ACTIVE,
                    'is_mutual' => true,
                    'confirmed_at' => now(),
                    'compatibility_score' => $this->calculateCompatibility($worker, $buddy),
                ]);
            }

            // Create pending buddy request
            return WorkerRelationship::create([
                'worker_id' => $worker->id,
                'related_worker_id' => $buddy->id,
                'relationship_type' => WorkerRelationship::TYPE_BUDDY,
                'status' => WorkerRelationship::STATUS_PENDING,
                'is_mutual' => false,
                'compatibility_score' => $this->calculateCompatibility($worker, $buddy),
            ]);
        });
    }

    /**
     * Remove a buddy relationship.
     */
    public function removeBuddy(User $worker, User $buddy): void
    {
        DB::transaction(function () use ($worker, $buddy) {
            // Remove the relationship from worker's side
            WorkerRelationship::where('worker_id', $worker->id)
                ->where('related_worker_id', $buddy->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->update(['status' => WorkerRelationship::STATUS_REMOVED]);

            // Update the reverse relationship if it exists
            $reverseRelation = WorkerRelationship::where('worker_id', $buddy->id)
                ->where('related_worker_id', $worker->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->first();

            if ($reverseRelation) {
                $reverseRelation->is_mutual = false;
                $reverseRelation->save();
            }
        });
    }

    /**
     * Confirm a mutual buddy relationship (from pending request).
     */
    public function confirmMutualBuddy(WorkerRelationship $relationship): void
    {
        if (! $relationship->isPending()) {
            throw new \Exception('Relationship is not pending confirmation.');
        }

        if ($relationship->relationship_type !== WorkerRelationship::TYPE_BUDDY) {
            throw new \Exception('Only buddy relationships require confirmation.');
        }

        DB::transaction(function () use ($relationship) {
            // Confirm the incoming request
            $relationship->confirm();

            // Check if the accepting worker already has a relationship
            $reciprocal = WorkerRelationship::where('worker_id', $relationship->related_worker_id)
                ->where('related_worker_id', $relationship->worker_id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->first();

            if (! $reciprocal) {
                // Create reciprocal relationship
                WorkerRelationship::create([
                    'worker_id' => $relationship->related_worker_id,
                    'related_worker_id' => $relationship->worker_id,
                    'relationship_type' => WorkerRelationship::TYPE_BUDDY,
                    'status' => WorkerRelationship::STATUS_ACTIVE,
                    'is_mutual' => true,
                    'confirmed_at' => now(),
                    'compatibility_score' => $relationship->compatibility_score,
                ]);
            } else {
                $reciprocal->status = WorkerRelationship::STATUS_ACTIVE;
                $reciprocal->is_mutual = true;
                $reciprocal->confirmed_at = now();
                $reciprocal->save();
            }
        });
    }

    /**
     * Decline a buddy request.
     */
    public function declineBuddyRequest(WorkerRelationship $relationship): void
    {
        if (! $relationship->isPending()) {
            throw new \Exception('Relationship is not pending.');
        }

        $relationship->decline();
    }

    // =========================================================================
    // PREFERRED/AVOIDED COWORKERS
    // =========================================================================

    /**
     * Add a preferred coworker (one-way, no confirmation needed).
     */
    public function addPreferredCoworker(User $worker, User $coworker): WorkerRelationship
    {
        $this->validateWorkerType($worker);
        $this->validateWorkerType($coworker);

        // Check limit
        $currentPreferred = $this->getPreferredCoworkers($worker)->count();
        if ($currentPreferred >= self::MAX_PREFERRED) {
            throw new \Exception('Maximum number of preferred coworkers reached.');
        }

        // Check if already exists
        $existing = WorkerRelationship::where('worker_id', $worker->id)
            ->where('related_worker_id', $coworker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_PREFERRED)
            ->first();

        if ($existing) {
            if ($existing->status === WorkerRelationship::STATUS_REMOVED) {
                $existing->status = WorkerRelationship::STATUS_ACTIVE;
                $existing->save();

                return $existing;
            }
            throw new \Exception('Already marked as preferred coworker.');
        }

        // Remove from avoided if present
        $this->removeAvoidedCoworker($worker, $coworker);

        return WorkerRelationship::create([
            'worker_id' => $worker->id,
            'related_worker_id' => $coworker->id,
            'relationship_type' => WorkerRelationship::TYPE_PREFERRED,
            'status' => WorkerRelationship::STATUS_ACTIVE,
            'compatibility_score' => $this->calculateCompatibility($worker, $coworker),
        ]);
    }

    /**
     * Remove a preferred coworker.
     */
    public function removePreferredCoworker(User $worker, User $coworker): void
    {
        WorkerRelationship::where('worker_id', $worker->id)
            ->where('related_worker_id', $coworker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_PREFERRED)
            ->update(['status' => WorkerRelationship::STATUS_REMOVED]);
    }

    /**
     * Mark a worker as avoided.
     */
    public function avoidCoworker(User $worker, User $coworker): WorkerRelationship
    {
        $this->validateWorkerType($worker);
        $this->validateWorkerType($coworker);

        // Check if already exists
        $existing = WorkerRelationship::where('worker_id', $worker->id)
            ->where('related_worker_id', $coworker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->first();

        if ($existing) {
            if ($existing->status === WorkerRelationship::STATUS_REMOVED) {
                $existing->status = WorkerRelationship::STATUS_ACTIVE;
                $existing->save();

                return $existing;
            }
            throw new \Exception('Already marked as avoided.');
        }

        // Remove from preferred if present
        $this->removePreferredCoworker($worker, $coworker);

        // Remove buddy relationship if exists
        $this->removeBuddy($worker, $coworker);

        return WorkerRelationship::create([
            'worker_id' => $worker->id,
            'related_worker_id' => $coworker->id,
            'relationship_type' => WorkerRelationship::TYPE_AVOIDED,
            'status' => WorkerRelationship::STATUS_ACTIVE,
        ]);
    }

    /**
     * Remove avoided status.
     */
    public function removeAvoidedCoworker(User $worker, User $coworker): void
    {
        WorkerRelationship::where('worker_id', $worker->id)
            ->where('related_worker_id', $coworker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->update(['status' => WorkerRelationship::STATUS_REMOVED]);
    }

    // =========================================================================
    // COMPATIBILITY SCORING
    // =========================================================================

    /**
     * Calculate compatibility score between two workers (0-100).
     */
    public function calculateCompatibility(User $worker1, User $worker2): float
    {
        $score = 0.0;

        // 1. Shifts worked together (30%)
        $shiftsTogether = $this->getShiftCountTogether($worker1, $worker2);
        $shiftScore = min(100, $shiftsTogether * 10); // 10 shifts = max score
        $score += $shiftScore * self::WEIGHT_SHIFTS_TOGETHER;

        // 2. Mutual ratings (25%)
        $mutualRatingScore = $this->calculateMutualRatingScore($worker1, $worker2);
        $score += $mutualRatingScore * self::WEIGHT_MUTUAL_RATINGS;

        // 3. Skill overlap (20%)
        $skillOverlap = $this->calculateSkillOverlap($worker1, $worker2);
        $score += $skillOverlap * self::WEIGHT_SKILL_OVERLAP;

        // 4. Response time similarity (15%)
        $responseTimeScore = $this->calculateResponseTimeScore($worker1, $worker2);
        $score += $responseTimeScore * self::WEIGHT_RESPONSE_TIMES;

        // 5. Reliability score match (10%)
        $reliabilityMatch = $this->calculateReliabilityMatch($worker1, $worker2);
        $score += $reliabilityMatch * self::WEIGHT_RELIABILITY_MATCH;

        return round($score, 2);
    }

    /**
     * Get number of shifts two workers have worked together.
     */
    private function getShiftCountTogether(User $worker1, User $worker2): int
    {
        return ShiftAssignment::where('worker_id', $worker1->id)
            ->where('status', 'completed')
            ->whereIn('shift_id', function ($query) use ($worker2) {
                $query->select('shift_id')
                    ->from('shift_assignments')
                    ->where('worker_id', $worker2->id)
                    ->where('status', 'completed');
            })
            ->count();
    }

    /**
     * Calculate mutual rating score.
     */
    private function calculateMutualRatingScore(User $worker1, User $worker2): float
    {
        // Get ratings they've given each other
        $rating1to2 = DB::table('ratings')
            ->where('rater_id', $worker1->id)
            ->where('rated_id', $worker2->id)
            ->avg('rating');

        $rating2to1 = DB::table('ratings')
            ->where('rater_id', $worker2->id)
            ->where('rated_id', $worker1->id)
            ->avg('rating');

        if (! $rating1to2 && ! $rating2to1) {
            return 50; // Neutral if no ratings
        }

        $avgRating = ($rating1to2 ?? $rating2to1 ?? 0 + $rating2to1 ?? $rating1to2 ?? 0) / 2;

        return ($avgRating / 5) * 100;
    }

    /**
     * Calculate skill overlap percentage.
     */
    private function calculateSkillOverlap(User $worker1, User $worker2): float
    {
        $skills1 = $worker1->skills()->pluck('skill_id')->toArray();
        $skills2 = $worker2->skills()->pluck('skill_id')->toArray();

        if (empty($skills1) || empty($skills2)) {
            return 50; // Neutral if no skills
        }

        $intersection = count(array_intersect($skills1, $skills2));
        $union = count(array_unique(array_merge($skills1, $skills2)));

        return ($intersection / $union) * 100;
    }

    /**
     * Calculate response time similarity score.
     */
    private function calculateResponseTimeScore(User $worker1, User $worker2): float
    {
        // Get average response times from shift applications
        $avgTime1 = DB::table('shift_applications')
            ->where('worker_id', $worker1->id)
            ->whereNotNull('applied_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, applied_at)) as avg_time')
            ->value('avg_time') ?? 60;

        $avgTime2 = DB::table('shift_applications')
            ->where('worker_id', $worker2->id)
            ->whereNotNull('applied_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, applied_at)) as avg_time')
            ->value('avg_time') ?? 60;

        // Calculate similarity (smaller difference = higher score)
        $diff = abs($avgTime1 - $avgTime2);
        $maxDiff = 120; // 2 hours max difference

        return max(0, 100 - ($diff / $maxDiff * 100));
    }

    /**
     * Calculate reliability score match.
     */
    private function calculateReliabilityMatch(User $worker1, User $worker2): float
    {
        $reliability1 = $worker1->reliability_score ?? 70;
        $reliability2 = $worker2->reliability_score ?? 70;

        $diff = abs($reliability1 - $reliability2);

        // Smaller difference = higher compatibility
        return max(0, 100 - $diff);
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    /**
     * Get all buddies for a worker.
     */
    public function getBuddies(User $worker): Collection
    {
        return User::whereIn('id', function ($query) use ($worker) {
            $query->select('related_worker_id')
                ->from('worker_relationships')
                ->where('worker_id', $worker->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->where('status', WorkerRelationship::STATUS_ACTIVE);
        })->get();
    }

    /**
     * Get pending buddy requests for a worker (incoming).
     */
    public function getPendingBuddyRequests(User $worker): Collection
    {
        return WorkerRelationship::with('worker')
            ->where('related_worker_id', $worker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_PENDING)
            ->get();
    }

    /**
     * Get outgoing buddy requests for a worker.
     */
    public function getSentBuddyRequests(User $worker): Collection
    {
        return WorkerRelationship::with('relatedWorker')
            ->where('worker_id', $worker->id)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_PENDING)
            ->get();
    }

    /**
     * Get preferred coworkers for a worker.
     */
    public function getPreferredCoworkers(User $worker): Collection
    {
        return User::whereIn('id', function ($query) use ($worker) {
            $query->select('related_worker_id')
                ->from('worker_relationships')
                ->where('worker_id', $worker->id)
                ->where('relationship_type', WorkerRelationship::TYPE_PREFERRED)
                ->where('status', WorkerRelationship::STATUS_ACTIVE);
        })->get();
    }

    /**
     * Get avoided workers for a worker.
     */
    public function getAvoidedWorkers(User $worker): Collection
    {
        return User::whereIn('id', function ($query) use ($worker) {
            $query->select('related_worker_id')
                ->from('worker_relationships')
                ->where('worker_id', $worker->id)
                ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
                ->where('status', WorkerRelationship::STATUS_ACTIVE);
        })->get();
    }

    /**
     * Suggest potential buddies based on shift history.
     */
    public function suggestBuddies(User $worker, int $limit = 10): Collection
    {
        // Get existing buddy IDs to exclude
        $existingBuddyIds = $this->getBuddies($worker)->pluck('id')->toArray();
        $avoidedIds = $this->getAvoidedWorkers($worker)->pluck('id')->toArray();
        $excludeIds = array_merge($existingBuddyIds, $avoidedIds, [$worker->id]);

        // Find workers who have worked the same shifts
        $suggestedWorkerIds = ShiftAssignment::where('worker_id', '!=', $worker->id)
            ->whereNotIn('worker_id', $excludeIds)
            ->whereIn('shift_id', function ($query) use ($worker) {
                $query->select('shift_id')
                    ->from('shift_assignments')
                    ->where('worker_id', $worker->id)
                    ->where('status', 'completed');
            })
            ->where('status', 'completed')
            ->select('worker_id', DB::raw('COUNT(*) as shift_count'))
            ->groupBy('worker_id')
            ->orderByDesc('shift_count')
            ->limit($limit * 2) // Get more to filter
            ->pluck('worker_id')
            ->toArray();

        if (empty($suggestedWorkerIds)) {
            return collect([]);
        }

        // Get users and calculate compatibility
        $suggestions = User::whereIn('id', $suggestedWorkerIds)
            ->where('user_type', 'worker')
            ->where('status', 'active')
            ->get()
            ->map(function ($suggestedWorker) use ($worker) {
                $suggestedWorker->compatibility_score = $this->calculateCompatibility($worker, $suggestedWorker);

                return $suggestedWorker;
            })
            ->sortByDesc('compatibility_score')
            ->take($limit);

        return $suggestions;
    }

    // =========================================================================
    // TEAM MANAGEMENT
    // =========================================================================

    /**
     * Create a new worker team.
     */
    public function createTeam(User $leader, string $name, array $memberIds = [], array $options = []): WorkerTeam
    {
        $this->validateWorkerType($leader);

        return DB::transaction(function () use ($leader, $name, $memberIds, $options) {
            // Create the team
            $team = WorkerTeam::create([
                'name' => $name,
                'created_by' => $leader->id,
                'description' => $options['description'] ?? null,
                'max_members' => $options['max_members'] ?? 10,
                'is_public' => $options['is_public'] ?? false,
                'requires_approval' => $options['requires_approval'] ?? true,
                'specializations' => $options['specializations'] ?? null,
                'preferred_industries' => $options['preferred_industries'] ?? null,
                'min_reliability_score' => $options['min_reliability_score'] ?? null,
                'member_count' => 1, // Start with leader
            ]);

            // Add leader as first member
            WorkerTeamMember::create([
                'team_id' => $team->id,
                'user_id' => $leader->id,
                'role' => WorkerTeamMember::ROLE_LEADER,
                'status' => WorkerTeamMember::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            // Invite additional members
            foreach ($memberIds as $memberId) {
                if ($memberId !== $leader->id) {
                    $this->inviteToTeam($team, User::find($memberId), $leader);
                }
            }

            return $team->fresh();
        });
    }

    /**
     * Invite a user to join a team.
     */
    public function inviteToTeam(WorkerTeam $team, User $user, ?User $invitedBy = null): WorkerTeamMember
    {
        $this->validateWorkerType($user);

        if (! $team->canAcceptMembers()) {
            throw new \Exception('Team cannot accept new members.');
        }

        if ($team->hasMember($user)) {
            throw new \Exception('User is already a member of this team.');
        }

        // Check existing pending invitation
        $existing = WorkerTeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', WorkerTeamMember::STATUS_PENDING)
            ->first();

        if ($existing) {
            throw new \Exception('An invitation is already pending for this user.');
        }

        // Check reliability requirement
        if (! $team->meetsReliabilityRequirement($user)) {
            throw new \Exception('User does not meet the team reliability requirements.');
        }

        return WorkerTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => WorkerTeamMember::ROLE_MEMBER,
            'status' => WorkerTeamMember::STATUS_PENDING,
            'invited_by' => $invitedBy?->id,
            'invited_at' => now(),
        ]);
    }

    /**
     * Accept a team invitation.
     */
    public function acceptTeamInvitation(WorkerTeamMember $membership): bool
    {
        if (! $membership->isPending()) {
            throw new \Exception('This invitation is no longer pending.');
        }

        $team = $membership->team;

        if (! $team->canAcceptMembers()) {
            throw new \Exception('Team is full.');
        }

        return $membership->accept();
    }

    /**
     * Leave a team.
     */
    public function leaveTeam(WorkerTeam $team, User $user): void
    {
        $membership = WorkerTeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', WorkerTeamMember::STATUS_ACTIVE)
            ->first();

        if (! $membership) {
            throw new \Exception('User is not an active member of this team.');
        }

        // Check if last leader
        if ($membership->isLeader()) {
            $otherLeaders = WorkerTeamMember::where('team_id', $team->id)
                ->where('user_id', '!=', $user->id)
                ->where('role', WorkerTeamMember::ROLE_LEADER)
                ->where('status', WorkerTeamMember::STATUS_ACTIVE)
                ->count();

            if ($otherLeaders === 0) {
                // Promote another member or deactivate team
                $nextMember = WorkerTeamMember::where('team_id', $team->id)
                    ->where('user_id', '!=', $user->id)
                    ->where('status', WorkerTeamMember::STATUS_ACTIVE)
                    ->orderBy('joined_at')
                    ->first();

                if ($nextMember) {
                    $nextMember->promoteToLeader();
                } else {
                    $team->deactivate();
                }
            }
        }

        $membership->remove('Left voluntarily');
    }

    /**
     * Remove a member from team (by leader).
     */
    public function removeFromTeam(WorkerTeam $team, User $member, User $removedBy, ?string $reason = null): void
    {
        if (! $team->hasLeader($removedBy)) {
            throw new \Exception('Only team leaders can remove members.');
        }

        $membership = WorkerTeamMember::where('team_id', $team->id)
            ->where('user_id', $member->id)
            ->where('status', WorkerTeamMember::STATUS_ACTIVE)
            ->first();

        if (! $membership) {
            throw new \Exception('User is not an active member of this team.');
        }

        $membership->remove($reason ?? 'Removed by team leader');
    }

    // =========================================================================
    // TEAM SHIFT APPLICATIONS
    // =========================================================================

    /**
     * Apply to a shift as a team.
     */
    public function applyAsTeam(WorkerTeam $team, Shift $shift, User $requestedBy, int $membersNeeded = 0): TeamShiftRequest
    {
        if (! $team->hasLeader($requestedBy)) {
            throw new \Exception('Only team leaders can submit team applications.');
        }

        // Check if team already applied
        $existing = TeamShiftRequest::where('team_id', $team->id)
            ->where('shift_id', $shift->id)
            ->whereIn('status', [
                TeamShiftRequest::STATUS_PENDING,
                TeamShiftRequest::STATUS_PARTIAL,
                TeamShiftRequest::STATUS_APPROVED,
            ])
            ->first();

        if ($existing) {
            throw new \Exception('Team has already applied to this shift.');
        }

        // Determine how many members are needed
        if ($membersNeeded <= 0) {
            $membersNeeded = min($team->member_count, $shift->required_workers - $shift->filled_workers);
        }

        // Calculate priority score (team has worked with this business before, etc.)
        $priorityScore = $this->calculateTeamPriority($team, $shift);

        return TeamShiftRequest::create([
            'team_id' => $team->id,
            'shift_id' => $shift->id,
            'requested_by' => $requestedBy->id,
            'status' => TeamShiftRequest::STATUS_PENDING,
            'members_needed' => $membersNeeded,
            'members_confirmed' => 1, // Leader is auto-confirmed
            'confirmed_members' => [$requestedBy->id],
            'priority_score' => $priorityScore,
            'confirmation_deadline' => now()->addHours(24),
        ]);
    }

    /**
     * Confirm participation in a team shift application.
     */
    public function confirmTeamShiftParticipation(TeamShiftRequest $request, User $member): bool
    {
        if (! $request->team->hasMember($member)) {
            throw new \Exception('User is not a member of this team.');
        }

        return $request->confirmMember($member->id);
    }

    /**
     * Assign a team to a shift (business approves the team application).
     */
    public function assignTeamToShift(WorkerTeam $team, Shift $shift, User $approvedBy): Collection
    {
        $request = TeamShiftRequest::where('team_id', $team->id)
            ->where('shift_id', $shift->id)
            ->whereIn('status', [TeamShiftRequest::STATUS_PENDING, TeamShiftRequest::STATUS_PARTIAL])
            ->first();

        if (! $request) {
            throw new \Exception('No pending team application found for this shift.');
        }

        return DB::transaction(function () use ($request, $shift, $approvedBy) {
            // Approve the request
            $request->approve($approvedBy);

            $assignments = collect();

            // Create shift assignments for confirmed members
            foreach ($request->confirmed_members ?? [] as $memberId) {
                $assignment = ShiftAssignment::create([
                    'shift_id' => $shift->id,
                    'worker_id' => $memberId,
                    'assigned_by' => $approvedBy->id,
                    'status' => 'assigned',
                ]);

                $assignments->push($assignment);
            }

            // Update shift filled count
            $shift->filled_workers += count($request->confirmed_members ?? []);
            $shift->save();

            return $assignments;
        });
    }

    /**
     * Calculate team priority score for a shift.
     */
    private function calculateTeamPriority(WorkerTeam $team, Shift $shift): int
    {
        $score = 0;

        // Team has worked for this business before
        $previousShifts = ShiftAssignment::whereIn('worker_id', function ($query) use ($team) {
            $query->select('user_id')
                ->from('worker_team_members')
                ->where('team_id', $team->id)
                ->where('status', 'active');
        })
            ->whereIn('shift_id', function ($query) use ($shift) {
                $query->select('id')
                    ->from('shifts')
                    ->where('business_id', $shift->business_id);
            })
            ->where('status', 'completed')
            ->count();

        $score += min(20, $previousShifts * 2);

        // Team average rating
        if ($team->average_rating) {
            $score += (int) (($team->average_rating / 5) * 15);
        }

        // Team completion count
        $score += min(15, $team->total_shifts_completed);

        return $score;
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Validate that a user is a worker.
     */
    private function validateWorkerType(User $user): void
    {
        if (! $user->isWorker()) {
            throw new \Exception('User must be a worker to use team formation features.');
        }
    }

    /**
     * Update shift count for worker relationships after shift completion.
     */
    public function updateShiftRelationships(Shift $shift): void
    {
        $workerIds = ShiftAssignment::where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->pluck('worker_id')
            ->toArray();

        if (count($workerIds) < 2) {
            return;
        }

        // Update shift count for all pairs
        foreach ($workerIds as $i => $workerId1) {
            foreach (array_slice($workerIds, $i + 1) as $workerId2) {
                // Update or create relationship records
                WorkerRelationship::where(function ($q) use ($workerId1, $workerId2) {
                    $q->where('worker_id', $workerId1)
                        ->where('related_worker_id', $workerId2);
                })->orWhere(function ($q) use ($workerId1, $workerId2) {
                    $q->where('worker_id', $workerId2)
                        ->where('related_worker_id', $workerId1);
                })->increment('shifts_together');
            }
        }
    }

    /**
     * Get teams for a worker.
     */
    public function getWorkerTeams(User $worker): Collection
    {
        return WorkerTeam::whereHas('memberships', function ($query) use ($worker) {
            $query->where('user_id', $worker->id)
                ->where('status', WorkerTeamMember::STATUS_ACTIVE);
        })->with(['creator', 'activeMembers'])->get();
    }

    /**
     * Get team invitations for a worker.
     */
    public function getTeamInvitations(User $worker): Collection
    {
        return WorkerTeamMember::with(['team', 'inviter'])
            ->where('user_id', $worker->id)
            ->where('status', WorkerTeamMember::STATUS_PENDING)
            ->get();
    }

    /**
     * Check if two workers should avoid being on the same shift.
     */
    public function shouldAvoidPairing(User $worker1, User $worker2): bool
    {
        return WorkerRelationship::where(function ($q) use ($worker1, $worker2) {
            $q->where('worker_id', $worker1->id)
                ->where('related_worker_id', $worker2->id);
        })->orWhere(function ($q) use ($worker1, $worker2) {
            $q->where('worker_id', $worker2->id)
                ->where('related_worker_id', $worker1->id);
        })
            ->where('relationship_type', WorkerRelationship::TYPE_AVOIDED)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Check if two workers are buddies.
     */
    public function areBuddies(User $worker1, User $worker2): bool
    {
        return WorkerRelationship::where('worker_id', $worker1->id)
            ->where('related_worker_id', $worker2->id)
            ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
            ->where('status', WorkerRelationship::STATUS_ACTIVE)
            ->where('is_mutual', true)
            ->exists();
    }
}
