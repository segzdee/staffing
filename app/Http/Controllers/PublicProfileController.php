<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\WorkerPortfolioService;
use Illuminate\Http\Request;

/**
 * Public Profile Controller
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * Handles public-facing worker profiles (no authentication required).
 */
class PublicProfileController extends Controller
{
    /**
     * Portfolio service instance.
     */
    protected WorkerPortfolioService $portfolioService;

    /**
     * Create a new controller instance.
     */
    public function __construct(WorkerPortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    /**
     * Display a public worker profile.
     *
     * GET /profile/{username}
     */
    public function show(string $username)
    {
        // Find worker by public profile slug or username
        $profile = WorkerProfile::where('public_profile_slug', $username)
            ->where('public_profile_enabled', true)
            ->first();

        if (!$profile) {
            // Try finding by username
            $user = User::where('username', $username)
                ->where('user_type', 'worker')
                ->first();

            if ($user && $user->workerProfile && $user->workerProfile->public_profile_enabled) {
                $profile = $user->workerProfile;
            }
        }

        if (!$profile) {
            abort(404, 'Profile not found or is private.');
        }

        $worker = $profile->user;

        // Generate public profile data
        $profileData = $this->portfolioService->generatePublicProfile($worker);

        if (!$profileData['enabled']) {
            abort(404, 'Profile not found or is private.');
        }

        // Record profile view
        $viewer = auth()->user();
        $source = $this->determineViewSource();
        $this->portfolioService->recordProfileView($worker, $viewer, $source);

        // SEO meta tags
        $meta = $profileData['meta'];

        return view('worker.profile.public', [
            'profile' => $profileData,
            'meta' => $meta,
            'worker' => $worker,
            'isOwner' => auth()->id() === $worker->id,
        ]);
    }

    /**
     * Show portfolio item detail on public profile.
     *
     * GET /profile/{username}/portfolio/{itemId}
     */
    public function portfolioItem(string $username, int $itemId)
    {
        // Find worker by slug
        $profile = WorkerProfile::where('public_profile_slug', $username)
            ->where('public_profile_enabled', true)
            ->first();

        if (!$profile) {
            abort(404, 'Profile not found.');
        }

        $worker = $profile->user;

        // Find the portfolio item
        $item = $worker->portfolioItems()
            ->where('id', $itemId)
            ->where('is_visible', true)
            ->first();

        if (!$item) {
            abort(404, 'Portfolio item not found.');
        }

        // Record view
        $viewer = auth()->user();
        $this->portfolioService->recordProfileView($worker, $viewer, 'public_profile');

        // Get profile data for context
        $profileData = $this->portfolioService->generatePublicProfile($worker);

        return view('worker.profile.portfolio-item', [
            'item' => $item,
            'profile' => $profileData,
            'worker' => $worker,
        ]);
    }

    /**
     * Get featured workers for homepage showcase.
     *
     * GET /api/featured-workers
     */
    public function featuredWorkers(Request $request)
    {
        $limit = min($request->input('limit', 6), 20);

        // Get workers with active featured status
        $featuredWorkerIds = \App\Models\WorkerFeaturedStatus::active()
            ->orderByRaw("FIELD(tier, 'gold', 'silver', 'bronze')")
            ->pluck('worker_id');

        $featuredWorkers = User::whereIn('id', $featuredWorkerIds)
            ->with(['workerProfile'])
            ->get()
            ->filter(function ($worker) {
                return $worker->workerProfile
                    && $worker->workerProfile->public_profile_enabled;
            })
            ->take($limit)
            ->map(function ($worker) {
                $featuredStatus = \App\Models\WorkerFeaturedStatus::where('worker_id', $worker->id)
                    ->active()
                    ->first();

                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'slug' => $worker->workerProfile->public_profile_slug,
                    'avatar' => $worker->workerProfile->profile_photo_url,
                    'bio' => \Str::limit($worker->workerProfile->bio, 100),
                    'rating' => $worker->workerProfile->rating_average,
                    'shifts_completed' => $worker->workerProfile->total_shifts_completed,
                    'featured_tier' => $featuredStatus ? $featuredStatus->tier : null,
                    'featured_badge_color' => $featuredStatus ? $featuredStatus->badge_color : null,
                    'profile_url' => route('profile.public', $worker->workerProfile->public_profile_slug),
                ];
            })
            ->values();

        return response()->json([
            'workers' => $featuredWorkers,
        ]);
    }

    /**
     * Search public worker profiles.
     *
     * GET /workers
     */
    public function searchWorkers(Request $request)
    {
        $query = $request->input('q');
        $skills = $request->input('skills', []);
        $location = $request->input('location');
        $page = $request->input('page', 1);
        $perPage = min($request->input('per_page', 12), 50);

        $workersQuery = User::where('user_type', 'worker')
            ->whereHas('workerProfile', function ($q) {
                $q->where('public_profile_enabled', true);
            })
            ->with(['workerProfile']);

        // Apply search filter
        if ($query) {
            $workersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhereHas('workerProfile', function ($q) use ($query) {
                        $q->where('bio', 'like', "%{$query}%");
                    });
            });
        }

        // Apply skills filter
        if (!empty($skills)) {
            $workersQuery->whereHas('skills', function ($q) use ($skills) {
                $q->whereIn('skills.id', $skills);
            });
        }

        // Apply location filter
        if ($location) {
            $workersQuery->whereHas('workerProfile', function ($q) use ($location) {
                $q->where('location_city', 'like', "%{$location}%")
                    ->orWhere('location_state', 'like', "%{$location}%")
                    ->orWhere('city', 'like', "%{$location}%");
            });
        }

        // Add featured boost to sorting
        $workersQuery->leftJoin('worker_featured_statuses', function ($join) {
            $join->on('users.id', '=', 'worker_featured_statuses.worker_id')
                ->where('worker_featured_statuses.status', '=', 'active')
                ->where('worker_featured_statuses.start_date', '<=', now())
                ->where('worker_featured_statuses.end_date', '>=', now());
        })
        ->select('users.*')
        ->selectRaw('CASE
            WHEN worker_featured_statuses.tier = "gold" THEN 3
            WHEN worker_featured_statuses.tier = "silver" THEN 2
            WHEN worker_featured_statuses.tier = "bronze" THEN 1
            ELSE 0
        END as featured_score')
        ->orderByDesc('featured_score')
        ->orderByDesc('users.created_at');

        $workers = $workersQuery->paginate($perPage);

        // Transform for display
        $workers->getCollection()->transform(function ($worker) {
            $featuredStatus = \App\Models\WorkerFeaturedStatus::where('worker_id', $worker->id)
                ->active()
                ->first();

            return [
                'id' => $worker->id,
                'name' => $worker->name,
                'slug' => $worker->workerProfile->public_profile_slug,
                'avatar' => $worker->workerProfile->profile_photo_url,
                'bio' => \Str::limit($worker->workerProfile->bio, 150),
                'rating' => $worker->workerProfile->rating_average,
                'shifts_completed' => $worker->workerProfile->total_shifts_completed,
                'years_experience' => $worker->workerProfile->years_experience,
                'location' => $this->formatLocation($worker->workerProfile),
                'skills' => $worker->skills->take(5)->pluck('name'),
                'verified' => $worker->workerProfile->identity_verified,
                'featured_tier' => $featuredStatus ? $featuredStatus->tier : null,
                'featured_badge_color' => $featuredStatus ? $featuredStatus->badge_color : null,
                'profile_url' => route('profile.public', $worker->workerProfile->public_profile_slug),
            ];
        });

        if ($request->wantsJson()) {
            return response()->json($workers);
        }

        return view('worker.profile.search', [
            'workers' => $workers,
            'query' => $query,
            'skills' => $skills,
            'location' => $location,
        ]);
    }

    /**
     * Determine the source of the profile view.
     */
    protected function determineViewSource(): string
    {
        $referrer = request()->header('referer');

        if (!$referrer) {
            return 'direct';
        }

        if (str_contains($referrer, '/search') || str_contains($referrer, 'q=')) {
            return 'search';
        }

        if (str_contains($referrer, '/shifts/') || str_contains($referrer, 'application')) {
            return 'shift_application';
        }

        if (str_contains($referrer, 'ref=') || str_contains($referrer, 'referral')) {
            return 'referral';
        }

        return 'other';
    }

    /**
     * Format location for display.
     */
    protected function formatLocation($profile): ?string
    {
        $parts = array_filter([
            $profile->city ?? $profile->location_city,
            $profile->state ?? $profile->location_state,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
