<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBroadcast;
use App\Models\Shift;
use App\Models\ShiftInvitation;
use App\Models\User;
use App\Services\ShiftMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvailableWorkersController extends Controller
{
    protected $matchingService;

    public function __construct(ShiftMatchingService $matchingService)
    {
        $this->middleware('auth');
        $this->middleware('business');
        $this->matchingService = $matchingService;
    }

    /**
     * Show currently available workers broadcasting their availability
     * WKR-010: Enhanced with featured worker boost and portfolio data
     */
    public function index(Request $request)
    {
        $query = AvailabilityBroadcast::with([
            'worker.workerProfile',
            'worker.skills',
            'worker.certifications',
            'worker.portfolioItems' => function ($q) {
                $q->where('is_visible', true)
                    ->where(function ($query) {
                        $query->where('is_featured', true)
                            ->orWhere('display_order', 0);
                    })
                    ->limit(1);
            },
            'worker.activeFeaturedStatus',
        ])
            ->where('status', 'active')
            ->where('available_from', '<=', now())
            ->where('available_to', '>=', now());

        // Filter by industry
        if ($request->has('industry') && $request->industry != 'all') {
            $query->whereRaw('JSON_CONTAINS(industries, ?)', [json_encode($request->industry)]);
        }

        // Filter by location (if business has location set)
        $business = Auth::user();
        if ($request->has('max_distance') && $business->location_lat && $business->location_lng) {
            $maxDistance = $request->max_distance;

            // Get all broadcasts and filter by distance
            $broadcasts = $query->get()->filter(function ($broadcast) use ($business, $maxDistance) {
                $workerProfile = $broadcast->worker->workerProfile;
                if (! $workerProfile || ! $workerProfile->location_lat || ! $workerProfile->location_lng) {
                    return false;
                }

                $distance = $this->matchingService->calculateDistance(
                    $business->location_lat,
                    $business->location_lng,
                    $workerProfile->location_lat,
                    $workerProfile->location_lng
                );

                return $distance <= $maxDistance;
            });
        } else {
            $broadcasts = $query->get();
        }

        // Sort by match score if specific shift provided, with featured worker boost
        // WKR-010: Featured workers get priority in search results
        if ($request->has('shift_id')) {
            $shift = Shift::find($request->shift_id);
            if ($shift && $shift->business_id === Auth::id()) {
                $broadcasts = $broadcasts->map(function ($broadcast) use ($shift) {
                    $baseScore = $this->matchingService->calculateWorkerShiftMatch(
                        $broadcast->worker,
                        $shift
                    );

                    // Apply featured boost
                    $featuredBoost = 1.0;
                    if ($broadcast->worker->activeFeaturedStatus) {
                        $featuredBoost = $broadcast->worker->activeFeaturedStatus->search_boost ?? 1.0;
                    }

                    $broadcast->match_score = $baseScore * $featuredBoost;
                    $broadcast->is_featured = $broadcast->worker->activeFeaturedStatus !== null;
                    $broadcast->featured_tier = $broadcast->worker->activeFeaturedStatus?->tier;

                    // Get portfolio thumbnail
                    $broadcast->portfolio_thumbnail = $broadcast->worker->portfolioItems->first()?->thumbnail_url;

                    return $broadcast;
                })->sortByDesc('match_score');
            }
        } else {
            // Even without shift matching, boost featured workers to the top
            $broadcasts = $broadcasts->map(function ($broadcast) {
                $broadcast->is_featured = $broadcast->worker->activeFeaturedStatus !== null;
                $broadcast->featured_tier = $broadcast->worker->activeFeaturedStatus?->tier;
                $broadcast->portfolio_thumbnail = $broadcast->worker->portfolioItems->first()?->thumbnail_url;

                // Featured score for sorting
                $broadcast->featured_score = match ($broadcast->featured_tier) {
                    'gold' => 3,
                    'silver' => 2,
                    'bronze' => 1,
                    default => 0,
                };

                return $broadcast;
            })->sortByDesc('featured_score');
        }

        // Paginate manually
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $total = $broadcasts->count();
        $broadcasts = $broadcasts->slice($offset, $perPage);

        // Get business's open shifts for quick invitation
        $openShifts = Shift::where('business_id', Auth::id())
            ->open()
            ->upcoming()
            ->orderBy('shift_date', 'asc')
            ->get();

        return view('business.available_workers.index', compact(
            'broadcasts',
            'openShifts',
            'total',
            'currentPage',
            'perPage'
        ));
    }

    /**
     * Invite a worker to apply for a specific shift
     */
    public function inviteWorker(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'message' => 'sometimes|string|max:500',
        ]);

        $shift = Shift::findOrFail($request->shift_id);

        // Verify ownership
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only invite workers to your own shifts');
        }

        // Check if already invited
        $existingInvitation = ShiftInvitation::where('shift_id', $shift->id)
            ->where('worker_id', $request->worker_id)
            ->first();

        if ($existingInvitation) {
            return redirect()->back()
                ->with('error', 'You have already invited this worker to this shift.');
        }

        // Create invitation
        $invitation = ShiftInvitation::create([
            'shift_id' => $shift->id,
            'worker_id' => $request->worker_id,
            'business_id' => Auth::id(),
            'message' => $request->message,
            'status' => 'pending',
        ]);

        // Increment responses count on broadcast
        $broadcast = AvailabilityBroadcast::where('worker_id', $request->worker_id)
            ->where('status', 'active')
            ->first();

        if ($broadcast) {
            $broadcast->increment('responses_count');
        }

        // Send notification to worker about invitation
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyShiftInvitation($invitation);

        return redirect()->back()
            ->with('success', 'Invitation sent successfully! The worker will be notified.');
    }

    /**
     * Send bulk invitations to multiple workers
     */
    public function bulkInvite(Request $request)
    {
        $request->validate([
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'message' => 'sometimes|string|max:500',
        ]);

        $shift = Shift::findOrFail($request->shift_id);

        // Verify ownership
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only invite workers to your own shifts');
        }

        $invitedCount = 0;
        $skippedCount = 0;

        foreach ($request->worker_ids as $workerId) {
            // Check if already invited
            $existingInvitation = ShiftInvitation::where('shift_id', $shift->id)
                ->where('worker_id', $workerId)
                ->first();

            if ($existingInvitation) {
                $skippedCount++;

                continue;
            }

            // Create invitation
            ShiftInvitation::create([
                'shift_id' => $shift->id,
                'worker_id' => $workerId,
                'business_id' => Auth::id(),
                'message' => $request->message,
                'status' => 'pending',
            ]);

            // Increment responses count
            $broadcast = AvailabilityBroadcast::where('worker_id', $workerId)
                ->where('status', 'active')
                ->first();

            if ($broadcast) {
                $broadcast->increment('responses_count');
            }

            $invitedCount++;
        }

        $message = "Invited {$invitedCount} worker(s) successfully!";
        if ($skippedCount > 0) {
            $message .= " (Skipped {$skippedCount} already invited)";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Find workers actively looking for work matching a shift
     */
    public function matchForShift($shiftId)
    {
        $shift = Shift::findOrFail($shiftId);

        // Verify ownership
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Get currently available workers broadcasting availability
        $availableWorkers = $this->matchingService->findAvailableWorkers(
            $shift->industry,
            null,
            $shift->shift_date
        );

        // Calculate match score for each
        $rankedWorkers = collect($availableWorkers)->map(function ($worker) use ($shift) {
            $user = User::find($worker->id);
            if ($user) {
                $matchScore = $this->matchingService->calculateWorkerShiftMatch($user, $shift);
                $worker->match_score = $matchScore;
            }

            return $worker;
        })->sortByDesc('match_score');

        return view('business.available_workers.match', compact('shift', 'rankedWorkers'));
    }
}
