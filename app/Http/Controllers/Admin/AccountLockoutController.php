<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountLockedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin controller for managing account lockouts.
 *
 * Provides functionality to:
 * - View all locked accounts
 * - Manually lock user accounts
 * - Unlock user accounts
 * - View lockout history and statistics
 */
class AccountLockoutController extends Controller
{
    /**
     * Display list of locked accounts and lockout statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (!$user->is_dev_account && !$user->hasPermission('users')) {
            return view('admin.unauthorized');
        }

        // Get filter parameters
        $filter = $request->get('filter', 'locked');
        $search = $request->get('search');

        // Build query
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply filter
        switch ($filter) {
            case 'locked':
                $query->locked();
                break;
            case 'at_risk':
                // Users with 3+ failed attempts but not yet locked
                $query->where('failed_login_attempts', '>=', 3)
                    ->where(function ($q) {
                        $q->whereNull('locked_until')
                            ->orWhere('locked_until', '<', now());
                    });
                break;
            case 'recently_unlocked':
                // Users who were locked in the past 24 hours but are now unlocked
                $query->whereNotNull('locked_at')
                    ->where('locked_at', '>=', now()->subDay())
                    ->where(function ($q) {
                        $q->whereNull('locked_until')
                            ->orWhere('locked_until', '<', now());
                    });
                break;
            case 'all':
            default:
                // All users with any lockout history
                $query->where(function ($q) {
                    $q->where('failed_login_attempts', '>', 0)
                        ->orWhereNotNull('locked_until')
                        ->orWhereNotNull('locked_at');
                });
                break;
        }

        $users = $query->orderBy('locked_at', 'desc')
            ->orderBy('failed_login_attempts', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Statistics
        $stats = [
            'currently_locked' => User::locked()->count(),
            'at_risk' => User::where('failed_login_attempts', '>=', 3)
                ->where(function ($q) {
                    $q->whereNull('locked_until')
                        ->orWhere('locked_until', '<', now());
                })->count(),
            'locked_today' => User::whereDate('locked_at', today())->count(),
            'locked_this_week' => User::whereBetween('locked_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
        ];

        return view('admin.account-lockouts.index', compact('users', 'stats', 'filter', 'search'));
    }

    /**
     * Unlock a user account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlock(Request $request, $id)
    {
        $admin = auth()->user();

        // Permission check
        if (!$admin->is_dev_account && !$admin->hasPermission('users')) {
            return redirect()->back()->with('error', 'You do not have permission to unlock accounts.');
        }

        $user = User::findOrFail($id);

        // Check if account is actually locked
        if (!$user->isLocked() && $user->failed_login_attempts == 0) {
            return redirect()->back()->with('warning', 'This account is not locked.');
        }

        // Log the unlock action
        Log::channel('security')->info('Account unlocked by admin', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'unlocked_user_id' => $user->id,
            'unlocked_user_email' => $user->email,
            'was_locked_until' => $user->locked_until?->toIso8601String(),
            'lock_reason' => $user->lock_reason,
            'failed_attempts' => $user->failed_login_attempts,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // Unlock the account
        $user->unlock();

        return redirect()->back()->with('success', "Account for {$user->email} has been unlocked successfully.");
    }

    /**
     * Manually lock a user account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function lock(Request $request, $id)
    {
        $admin = auth()->user();

        // Permission check
        if (!$admin->is_dev_account && !$admin->hasPermission('users')) {
            return redirect()->back()->with('error', 'You do not have permission to lock accounts.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1|max:43200', // Max 30 days in minutes
        ]);

        $user = User::findOrFail($id);

        // Cannot lock admin accounts
        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Admin accounts cannot be locked.');
        }

        // Cannot lock your own account
        if ($user->id === $admin->id) {
            return redirect()->back()->with('error', 'You cannot lock your own account.');
        }

        $reason = $request->input('reason');
        $durationMinutes = $request->input('duration'); // null = indefinite

        // Log the lock action
        Log::channel('security')->warning('Account locked by admin', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'locked_user_id' => $user->id,
            'locked_user_email' => $user->email,
            'reason' => $reason,
            'duration_minutes' => $durationMinutes ?? 'indefinite',
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // Lock the account
        $user->lockByAdmin($admin->id, $reason, $durationMinutes);

        // Send notification to user
        try {
            $lockedUntil = $durationMinutes
                ? Carbon::now()->addMinutes($durationMinutes)
                : Carbon::now()->addYears(100); // Use far future date for indefinite locks

            $user->notify(new AccountLockedNotification(
                $lockedUntil,
                $reason,
                $durationMinutes ?? 0,
                'Admin Action',
                null,
                true // Is admin lock
            ));
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to send account locked notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        $durationText = $durationMinutes
            ? ($durationMinutes >= 60 ? round($durationMinutes / 60) . ' hours' : $durationMinutes . ' minutes')
            : 'indefinitely';

        return redirect()->back()->with('success', "Account for {$user->email} has been locked {$durationText}.");
    }

    /**
     * Bulk unlock multiple accounts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUnlock(Request $request)
    {
        $admin = auth()->user();

        // Permission check
        if (!$admin->is_dev_account && !$admin->hasPermission('users')) {
            return redirect()->back()->with('error', 'You do not have permission to unlock accounts.');
        }

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->input('user_ids');
        $unlockedCount = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && ($user->isLocked() || $user->failed_login_attempts > 0)) {
                // Log each unlock
                Log::channel('security')->info('Account bulk-unlocked by admin', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'unlocked_user_id' => $user->id,
                    'unlocked_user_email' => $user->email,
                    'ip' => $request->ip(),
                    'timestamp' => now()->toISOString(),
                ]);

                $user->unlock();
                $unlockedCount++;
            }
        }

        return redirect()->back()->with('success', "{$unlockedCount} account(s) have been unlocked.");
    }

    /**
     * Reset failed login attempts without unlocking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetAttempts(Request $request, $id)
    {
        $admin = auth()->user();

        // Permission check
        if (!$admin->is_dev_account && !$admin->hasPermission('users')) {
            return redirect()->back()->with('error', 'You do not have permission to reset login attempts.');
        }

        $user = User::findOrFail($id);

        // Log the reset action
        Log::channel('security')->info('Failed login attempts reset by admin', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_attempts' => $user->failed_login_attempts,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        $user->resetFailedLoginAttempts();

        return redirect()->back()->with('success', "Failed login attempts for {$user->email} have been reset.");
    }

    /**
     * Get lockout details for a specific user (AJAX).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function details($id)
    {
        $admin = auth()->user();

        // Permission check
        if (!$admin->is_dev_account && !$admin->hasPermission('users')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'is_locked' => $user->isLocked(),
            'locked_until' => $user->locked_until?->toIso8601String(),
            'locked_until_formatted' => $user->locked_until?->format('M j, Y g:i A'),
            'lock_reason' => $user->lock_reason,
            'locked_at' => $user->locked_at?->toIso8601String(),
            'locked_at_formatted' => $user->locked_at?->format('M j, Y g:i A'),
            'locked_by_admin' => $user->wasLockedByAdmin(),
            'locked_by_admin_name' => $user->lockedByAdmin?->name,
            'failed_login_attempts' => $user->failed_login_attempts,
            'last_failed_login_at' => $user->last_failed_login_at?->toIso8601String(),
            'last_failed_login_at_formatted' => $user->last_failed_login_at?->format('M j, Y g:i A'),
            'minutes_remaining' => $user->lockoutMinutesRemaining(),
            'remaining_attempts' => $user->remainingLoginAttempts(),
        ]);
    }
}
