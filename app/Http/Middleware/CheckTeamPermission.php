<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TeamMember;

/**
 * BIZ-003: Check Team Permission Middleware
 *
 * Verifies that the current user has the required permission to perform an action
 * within a business context. Supports both business owners and team members.
 *
 * Usage:
 * Route::get('/shifts/create', [ShiftController::class, 'create'])
 *     ->middleware('team.permission:can_post_shifts');
 *
 * Multiple permissions (user needs at least one):
 * Route::put('/shifts/{id}', [ShiftController::class, 'update'])
 *     ->middleware('team.permission:can_edit_shifts,can_post_shifts');
 */
class CheckTeamPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permissions  Comma-separated list of permissions (OR logic)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permissions)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();
        $businessId = $this->getBusinessIdFromRequest($request);

        if (!$businessId) {
            abort(403, 'Business context required.');
        }

        // Parse permissions (comma-separated)
        $requiredPermissions = array_map('trim', explode(',', $permissions));

        // Check if user is the business owner
        if ($this->isBusinessOwner($user, $businessId)) {
            // Business owners have all permissions
            return $next($request);
        }

        // Check if user is a team member with required permissions
        $teamMember = TeamMember::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$teamMember) {
            abort(403, 'You are not a member of this business team.');
        }

        // Check if team member has at least one of the required permissions
        $hasPermission = false;
        foreach ($requiredPermissions as $permission) {
            if ($teamMember->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            $permissionNames = implode(' or ', array_map(function($p) {
                return str_replace('can_', '', $p);
            }, $requiredPermissions));

            abort(403, "You don't have permission to {$permissionNames}.");
        }

        // Store team member in request for later use
        $request->merge(['team_member' => $teamMember]);

        // Update last active timestamp
        $teamMember->updateLastActive();

        return $next($request);
    }

    /**
     * Get business ID from the current request.
     * Tries multiple sources: route parameter, query parameter, form data.
     */
    protected function getBusinessIdFromRequest(Request $request): ?int
    {
        // Try route parameter
        $businessId = $request->route('business_id') ?? $request->route('business');

        // Try query parameter
        if (!$businessId) {
            $businessId = $request->query('business_id');
        }

        // Try form data
        if (!$businessId) {
            $businessId = $request->input('business_id');
        }

        // Try to get from shift if editing/viewing shift
        if (!$businessId && $request->route('shift')) {
            $shift = $request->route('shift');
            if (is_object($shift)) {
                $businessId = $shift->business_id;
            } elseif (is_numeric($shift)) {
                $shift = \App\Models\Shift::find($shift);
                $businessId = $shift?->business_id;
            }
        }

        // Try to get from authenticated user if they're a business
        if (!$businessId && Auth::user()->user_type === 'business') {
            $businessId = Auth::id();
        }

        return $businessId ? (int) $businessId : null;
    }

    /**
     * Check if user is the business owner.
     */
    protected function isBusinessOwner($user, int $businessId): bool
    {
        return $user->id === $businessId && $user->user_type === 'business';
    }
}
