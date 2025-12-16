<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\AgencyInvitation;
use App\Models\AgencyWorker;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AGY-REG-004: Worker Agency Invitation Controller
 *
 * Handles worker-side agency invitation viewing, acceptance, and declining.
 */
class AgencyInvitationController extends Controller
{
    /**
     * Show invitation details.
     *
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $token)
    {
        $invitation = AgencyInvitation::with(['agency', 'agencyProfile'])
            ->byToken($token)
            ->first();

        if (!$invitation) {
            return redirect()->route('home')
                ->with('error', 'This invitation link is invalid.');
        }

        // Mark as viewed if not already
        if ($invitation->status === 'sent' || $invitation->status === 'pending') {
            $invitation->markAsViewed();
        }

        // Check if invitation is still valid
        if (!$invitation->isValid()) {
            $reason = $invitation->getInvalidReason();

            return view('worker.agency-invitation.invalid', [
                'invitation' => $invitation,
                'reason' => $reason,
            ]);
        }

        // Get agency details
        $agency = $invitation->agency;
        $agencyProfile = $agency->agencyProfile;

        // Get agency statistics
        $agencyStats = $this->getAgencyStats($agency);

        // Check if viewer is already authenticated
        $currentUser = Auth::user();
        $isAlreadyWorker = false;
        $isInAgency = false;

        if ($currentUser) {
            $isAlreadyWorker = $currentUser->isWorker();

            if ($isAlreadyWorker) {
                $isInAgency = AgencyWorker::where('agency_id', $agency->id)
                    ->where('worker_id', $currentUser->id)
                    ->where('status', 'active')
                    ->exists();
            }
        }

        return view('worker.agency-invitation.show', [
            'invitation' => $invitation,
            'agency' => $agency,
            'agencyProfile' => $agencyProfile,
            'agencyStats' => $agencyStats,
            'currentUser' => $currentUser,
            'isAlreadyWorker' => $isAlreadyWorker,
            'isInAgency' => $isInAgency,
        ]);
    }

    /**
     * Accept agency invitation.
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request, string $token)
    {
        $invitation = AgencyInvitation::byToken($token)->first();

        if (!$invitation) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation link is invalid.',
                ], 404);
            }
            return redirect()->route('home')
                ->with('error', 'This invitation link is invalid.');
        }

        if (!$invitation->isValid()) {
            $reason = $invitation->getInvalidReason();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $reason,
                ], 422);
            }
            return back()->with('error', $reason);
        }

        $currentUser = Auth::user();

        // If user is not logged in, redirect to registration with invitation token
        if (!$currentUser) {
            return redirect()->route('worker.register.agency-invite', ['token' => $token])
                ->with('info', 'Please create an account or log in to accept this invitation.');
        }

        // Check if user is a worker
        if (!$currentUser->isWorker()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only worker accounts can accept agency invitations.',
                ], 422);
            }
            return back()->with('error', 'Only worker accounts can accept agency invitations.');
        }

        // Check if already in agency
        $existingRelationship = AgencyWorker::where('agency_id', $invitation->agency_id)
            ->where('worker_id', $currentUser->id)
            ->first();

        if ($existingRelationship && $existingRelationship->status === 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a member of this agency.',
                ], 422);
            }
            return redirect()->route('worker.dashboard')
                ->with('info', 'You are already a member of this agency.');
        }

        DB::beginTransaction();

        try {
            // Accept the invitation
            $invitation->accept(
                $currentUser,
                $request->ip(),
                $request->userAgent()
            );

            // Create or reactivate agency-worker relationship
            if ($existingRelationship) {
                // Reactivate existing relationship
                $existingRelationship->update([
                    'status' => 'active',
                    'commission_rate' => $invitation->preset_commission_rate ?? $existingRelationship->commission_rate,
                    'added_at' => now(),
                    'removed_at' => null,
                ]);

                $agencyWorker = $existingRelationship;
            } else {
                // Create new relationship
                $agencyWorker = AgencyWorker::create([
                    'agency_id' => $invitation->agency_id,
                    'worker_id' => $currentUser->id,
                    'commission_rate' => $invitation->preset_commission_rate ?? 0,
                    'status' => 'active',
                    'notes' => 'Joined via invitation',
                    'added_at' => now(),
                ]);
            }

            // Apply preset skills if any
            if (!empty($invitation->preset_skills)) {
                $this->applyPresetSkills($currentUser, $invitation->preset_skills);
            }

            // Apply preset certifications if any
            if (!empty($invitation->preset_certifications)) {
                $this->applyPresetCertifications($currentUser, $invitation->preset_certifications);
            }

            DB::commit();

            Log::info('AgencyInvitation: Worker accepted invitation', [
                'invitation_id' => $invitation->id,
                'worker_id' => $currentUser->id,
                'agency_id' => $invitation->agency_id,
            ]);

            $agencyName = $invitation->agency->agencyProfile->agency_name ?? $invitation->agency->name;
            $message = "You have successfully joined {$agencyName}!";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'agency_worker' => $agencyWorker,
                ]);
            }

            return redirect()->route('worker.dashboard')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('AgencyInvitation: Failed to accept invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to accept invitation. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Failed to accept invitation. Please try again.');
        }
    }

    /**
     * Decline agency invitation.
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function decline(Request $request, string $token)
    {
        $invitation = AgencyInvitation::byToken($token)->first();

        if (!$invitation) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation link is invalid.',
                ], 404);
            }
            return redirect()->route('home')
                ->with('error', 'This invitation link is invalid.');
        }

        if (!$invitation->isValid()) {
            $reason = $invitation->getInvalidReason();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $reason,
                ], 422);
            }
            return back()->with('error', $reason);
        }

        // Mark invitation as declined (using cancelled status)
        $invitation->update([
            'status' => 'cancelled',
        ]);

        Log::info('AgencyInvitation: Worker declined invitation', [
            'invitation_id' => $invitation->id,
            'agency_id' => $invitation->agency_id,
        ]);

        $message = 'You have declined the invitation.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('home')
            ->with('info', $message);
    }

    /**
     * Get agency statistics for display.
     *
     * @param User $agency
     * @return array
     */
    protected function getAgencyStats(User $agency): array
    {
        $workerCount = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        $completedShifts = DB::table('shift_assignments')
            ->whereIn('worker_id', function ($query) use ($agency) {
                $query->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->where('status', 'completed')
            ->count();

        $avgRating = DB::table('ratings')
            ->whereIn('rated_id', function ($query) use ($agency) {
                $query->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->where('rater_type', 'business')
            ->avg('rating');

        return [
            'worker_count' => $workerCount,
            'completed_shifts' => $completedShifts,
            'avg_rating' => $avgRating ? round($avgRating, 1) : null,
        ];
    }

    /**
     * Apply preset skills to worker.
     *
     * @param User $worker
     * @param array $skillIds
     * @return void
     */
    protected function applyPresetSkills(User $worker, array $skillIds): void
    {
        foreach ($skillIds as $skillId) {
            // Check if already has skill
            $exists = $worker->skills()->where('skill_id', $skillId)->exists();

            if (!$exists) {
                $worker->skills()->attach($skillId, [
                    'proficiency_level' => 'intermediate',
                    'years_experience' => 0,
                    'verified' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Apply preset certifications to worker.
     *
     * @param User $worker
     * @param array $certificationIds
     * @return void
     */
    protected function applyPresetCertifications(User $worker, array $certificationIds): void
    {
        foreach ($certificationIds as $certId) {
            // Check if already has certification
            $exists = $worker->certifications()->where('certification_id', $certId)->exists();

            if (!$exists) {
                $worker->certifications()->attach($certId, [
                    'verified' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
