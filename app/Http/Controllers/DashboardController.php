<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Main dashboard - routes to appropriate dashboard based on user type
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'worker') {
            return $this->workerDashboard();
        } elseif ($user->user_type === 'business') {
            return $this->businessDashboard();
        } elseif ($user->user_type === 'agency') {
            return $this->agencyDashboard();
        } elseif ($user->user_type === 'admin' || $user->role === 'admin') {
            return $this->adminDashboard();
        }

        // Default fallback
        return view('dashboard.welcome');
    }

    /**
     * Worker Dashboard
     */
    public function workerDashboard()
    {
        $user = Auth::user();

        // Basic stats
        $stats = [
            'active_applications' => ShiftApplication::where('worker_id', $user->id)->where('status', 'pending')->count(),
            'completed_assignments' => ShiftAssignment::where('worker_id', $user->id)->where('status', 'completed')->count(),
            'upcoming_shifts' => ShiftAssignment::where('worker_id', $user->id)->where('status', 'active')->whereHas('shift', function ($q) {
                $q->where('shift_date', '>=', now());
            })->count(),
        ];

        // Recent applications
        $recentApplications = ShiftApplication::with('shift.business')
            ->where('worker_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('worker.dashboard', compact('stats', 'recentApplications'));
    }

    /**
     * Worker Assignments
     */
    public function workerAssignments()
    {
        $user = Auth::user();

        $assignments = ShiftAssignment::with('shift.business')
            ->where('worker_id', $user->id)
            ->latest()
            ->paginate(10); // Use pagination for list view

        return view('worker.assignments', compact('assignments'));
    }

    /**
     * Business Dashboard
     */
    public function businessDashboard()
    {
        $user = Auth::user();

        // Basic stats
        $stats = [
            'active_shifts' => Shift::where('business_id', $user->id)->where('status', 'active')->count(),
            'total_applications' => ShiftApplication::whereHas('shift', function ($q) use ($user) {
                $q->where('business_id', $user->id);
            })->count(),
            'pending_applications' => ShiftApplication::whereHas('shift', function ($q) use ($user) {
                $q->where('business_id', $user->id);
            })->where('status', 'pending')->count(),
        ];

        // Recent shifts
        $recentShifts = Shift::where('business_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('business.dashboard', compact('stats', 'recentShifts'));
    }

    /**
     * Business Available Workers
     */
    public function availableWorkers()
    {
        $user = Auth::user();
        $workers = []; // Placeholder
        return view('business.available-workers', compact('workers'));
    }

    /**
     * Agency Dashboard
     */
    public function agencyDashboard()
    {
        $user = Auth::user();

        // Basic stats
        $stats = [
            'total_workers' => 0, // Placeholder
            'active_placements' => 0, // Placeholder
            'commission_earned' => 0, // Placeholder
        ];

        return view('agency.dashboard', compact('stats'));
    }



    /**
     * User Profile
     */
    public function profile()
    {
        return view('profile.show', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Admin Dashboard
     */
    public function adminDashboard()
    {
        // Basic stats
        $stats = [
            'total_users' => User::count(),
            'active_shifts' => Shift::where('status', 'active')->count(),
            'total_applications' => ShiftApplication::count(),
        ];

        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }



}