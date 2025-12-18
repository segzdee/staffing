<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\Team\AddBuddyRequest;
use App\Http\Requests\Worker\Team\AddCoworkerPreferenceRequest;
use App\Models\User;
use App\Models\WorkerRelationship;
use App\Services\TeamFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WKR-014: Team Formation - Worker Relationship Controller
 *
 * Manages buddy system and coworker preferences for workers.
 */
class WorkerRelationshipController extends Controller
{
    public function __construct(
        protected TeamFormationService $teamFormationService
    ) {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display relationships dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $buddies = $this->teamFormationService->getBuddies($user);
        $pendingRequests = $this->teamFormationService->getPendingBuddyRequests($user);
        $sentRequests = $this->teamFormationService->getSentBuddyRequests($user);
        $preferredCoworkers = $this->teamFormationService->getPreferredCoworkers($user);
        $avoidedWorkers = $this->teamFormationService->getAvoidedWorkers($user);
        $suggestedBuddies = $this->teamFormationService->suggestBuddies($user, 5);

        return view('worker.relationships.index', [
            'buddies' => $buddies,
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
            'preferredCoworkers' => $preferredCoworkers,
            'avoidedWorkers' => $avoidedWorkers,
            'suggestedBuddies' => $suggestedBuddies,
        ]);
    }

    /**
     * Send a buddy request.
     */
    public function addBuddy(AddBuddyRequest $request): JsonResponse
    {
        try {
            $buddy = User::findOrFail($request->buddy_id);

            // Verify buddy is a worker
            if (! $buddy->isWorker()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a worker.',
                ], 422);
            }

            $relationship = $this->teamFormationService->addBuddy(
                $request->user(),
                $buddy
            );

            $message = $relationship->is_mutual
                ? 'Buddy added! You are now buddies with '.$buddy->name.'.'
                : 'Buddy request sent to '.$buddy->name.'.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'relationship' => [
                    'id' => $relationship->id,
                    'status' => $relationship->status,
                    'is_mutual' => $relationship->is_mutual,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove a buddy relationship.
     */
    public function removeBuddy(Request $request, int $buddyId): JsonResponse
    {
        try {
            $buddy = User::findOrFail($buddyId);

            $this->teamFormationService->removeBuddy($request->user(), $buddy);

            return response()->json([
                'success' => true,
                'message' => 'Buddy removed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept a pending buddy request.
     */
    public function acceptBuddyRequest(Request $request, int $relationshipId): JsonResponse
    {
        try {
            $relationship = WorkerRelationship::where('id', $relationshipId)
                ->where('related_worker_id', $request->user()->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->where('status', WorkerRelationship::STATUS_PENDING)
                ->firstOrFail();

            $this->teamFormationService->confirmMutualBuddy($relationship);

            return response()->json([
                'success' => true,
                'message' => 'Buddy request accepted! You are now buddies with '.$relationship->worker->name.'.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Decline a pending buddy request.
     */
    public function declineBuddyRequest(Request $request, int $relationshipId): JsonResponse
    {
        try {
            $relationship = WorkerRelationship::where('id', $relationshipId)
                ->where('related_worker_id', $request->user()->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->where('status', WorkerRelationship::STATUS_PENDING)
                ->firstOrFail();

            $this->teamFormationService->declineBuddyRequest($relationship);

            return response()->json([
                'success' => true,
                'message' => 'Buddy request declined.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a sent buddy request.
     */
    public function cancelBuddyRequest(Request $request, int $relationshipId): JsonResponse
    {
        try {
            $relationship = WorkerRelationship::where('id', $relationshipId)
                ->where('worker_id', $request->user()->id)
                ->where('relationship_type', WorkerRelationship::TYPE_BUDDY)
                ->where('status', WorkerRelationship::STATUS_PENDING)
                ->firstOrFail();

            $relationship->remove();

            return response()->json([
                'success' => true,
                'message' => 'Buddy request cancelled.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add a coworker preference (preferred or avoided).
     */
    public function addCoworkerPreference(AddCoworkerPreferenceRequest $request): JsonResponse
    {
        try {
            $coworker = User::findOrFail($request->coworker_id);

            if (! $coworker->isWorker()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a worker.',
                ], 422);
            }

            $preferenceType = $request->preference_type;

            if ($preferenceType === WorkerRelationship::TYPE_PREFERRED) {
                $relationship = $this->teamFormationService->addPreferredCoworker(
                    $request->user(),
                    $coworker
                );
                $message = $coworker->name.' added to your preferred coworkers.';
            } else {
                $relationship = $this->teamFormationService->avoidCoworker(
                    $request->user(),
                    $coworker
                );
                $message = $coworker->name.' added to your avoided list.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'relationship' => [
                    'id' => $relationship->id,
                    'type' => $relationship->relationship_type,
                    'status' => $relationship->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove a coworker preference.
     */
    public function removeCoworkerPreference(Request $request, int $coworkerId, string $type): JsonResponse
    {
        try {
            $coworker = User::findOrFail($coworkerId);

            if ($type === 'preferred') {
                $this->teamFormationService->removePreferredCoworker($request->user(), $coworker);
                $message = $coworker->name.' removed from your preferred coworkers.';
            } elseif ($type === 'avoided') {
                $this->teamFormationService->removeAvoidedCoworker($request->user(), $coworker);
                $message = $coworker->name.' removed from your avoided list.';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid preference type.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get suggested buddies based on shift history.
     */
    public function suggestBuddies(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $suggestions = $this->teamFormationService->suggestBuddies($request->user(), $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'avatar' => $worker->avatar,
                    'compatibility_score' => $worker->compatibility_score,
                    'rating' => $worker->rating_as_worker,
                    'reliability_score' => $worker->reliability_score,
                ];
            }),
        ]);
    }

    /**
     * Search workers to add as buddy/preference.
     */
    public function searchWorkers(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'workers' => [],
            ]);
        }

        $currentUserId = $request->user()->id;

        $workers = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->where('id', '!=', $currentUserId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'avatar', 'rating_as_worker', 'reliability_score']);

        return response()->json([
            'success' => true,
            'workers' => $workers->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'avatar' => $worker->avatar,
                    'rating' => $worker->rating_as_worker,
                    'reliability_score' => $worker->reliability_score,
                ];
            }),
        ]);
    }

    /**
     * Get compatibility score between current user and another worker.
     */
    public function getCompatibility(Request $request, int $workerId): JsonResponse
    {
        try {
            $worker = User::findOrFail($workerId);

            if (! $worker->isWorker()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a worker.',
                ], 422);
            }

            $score = $this->teamFormationService->calculateCompatibility(
                $request->user(),
                $worker
            );

            return response()->json([
                'success' => true,
                'compatibility_score' => $score,
                'worker' => [
                    'id' => $worker->id,
                    'name' => $worker->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
