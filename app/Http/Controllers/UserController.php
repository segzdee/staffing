<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Rating;

class UserController extends Controller
{
    /**
     * Display user profile by username
     */
    public function profile($username)
    {
        // Find user by username
        $user = User::where('username', $username)
            ->orWhere('username', '@' . $username)
            ->first();

        if (!$user) {
            abort(404, 'User not found');
        }

        // Get user type specific data
        $data = $this->getUserTypeData($user);

        // Get ratings
        $ratings = Rating::where('rated_user_id', $user->id)
            ->with('ratedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $averageRating = Rating::where('rated_user_id', $user->id)->avg('rating');

        // Check if viewing own profile
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;

        return view('users.profile', compact(
            'user',
            'data',
            'ratings',
            'averageRating',
            'isOwnProfile'
        ));
    }

    /**
     * Get user type specific data
     */
    private function getUserTypeData($user)
    {
        $data = [];

        switch ($user->user_type) {
            case 'worker':
                $data['profile'] = $user->workerProfile;
                $data['skills'] = $user->skills;
                $data['certifications'] = $user->certifications()->where('status', 'approved')->get();
                $data['badges'] = $user->badges;
                $data['shifts_completed'] = ShiftAssignment::where('worker_id', $user->id)
                    ->where('status', 'completed')
                    ->count();
                $data['total_hours'] = ShiftAssignment::where('worker_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('hours_worked');
                $data['industries_worked'] = ShiftAssignment::where('worker_id', $user->id)
                    ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                    ->select('shifts.industry')
                    ->distinct()
                    ->pluck('industry');
                break;

            case 'business':
                $data['profile'] = $user->businessProfile;
                $data['shifts_posted'] = Shift::where('business_id', $user->id)->count();
                $data['active_shifts'] = Shift::where('business_id', $user->id)
                    ->whereIn('status', ['open', 'filled', 'in_progress'])
                    ->count();
                $data['completed_shifts'] = Shift::where('business_id', $user->id)
                    ->where('status', 'completed')
                    ->count();
                $data['industries'] = Shift::where('business_id', $user->id)
                    ->select('industry')
                    ->distinct()
                    ->pluck('industry');
                break;

            case 'agency':
                $data['profile'] = $user->agencyProfile;
                $data['workers_managed'] = $user->agencyProfile->workers_count ?? 0;
                $data['active_workers'] = $user->agencyProfile->active_workers_count ?? 0;
                $data['shifts_filled'] = $user->agencyProfile->shifts_filled_count ?? 0;
                break;

            default:
                $data['profile'] = null;
                break;
        }

        return $data;
    }

    /**
     * Display user's public shifts (for businesses)
     */
    public function shifts($username)
    {
        $user = User::where('username', $username)
            ->orWhere('username', '@' . $username)
            ->first();

        if (!$user || $user->user_type !== 'business') {
            abort(404);
        }

        $shifts = Shift::where('business_id', $user->id)
            ->where('status', 'open')
            ->orderBy('shift_date', 'asc')
            ->paginate(20);

        return view('users.shifts', compact('user', 'shifts'));
    }

    /**
     * Display user's reviews
     */
    public function reviews($username)
    {
        $user = User::where('username', $username)
            ->orWhere('username', '@' . $username)
            ->first();

        if (!$user) {
            abort(404);
        }

        $ratings = Rating::where('rated_user_id', $user->id)
            ->with(['ratedBy', 'shift'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $averageRating = Rating::where('rated_user_id', $user->id)->avg('rating');
        $totalRatings = Rating::where('rated_user_id', $user->id)->count();

        // Rating distribution
        $ratingDistribution = [
            5 => Rating::where('rated_user_id', $user->id)->where('rating', 5)->count(),
            4 => Rating::where('rated_user_id', $user->id)->where('rating', 4)->count(),
            3 => Rating::where('rated_user_id', $user->id)->where('rating', 3)->count(),
            2 => Rating::where('rated_user_id', $user->id)->where('rating', 2)->count(),
            1 => Rating::where('rated_user_id', $user->id)->where('rating', 1)->count(),
        ];

        return view('users.reviews', compact(
            'user',
            'ratings',
            'averageRating',
            'totalRatings',
            'ratingDistribution'
        ));
    }
}
