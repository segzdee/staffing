<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftApplication;
use App\Models\ShiftPayment;
use App\Models\AgencyWorker;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard stats for authenticated user
     */
    public function stats(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Cache key based on user ID and type
            $cacheKey = "dashboard_stats_{$user->id}_{$user->user_type}";
            
            // Cache for 30 seconds (matches polling interval)
            $stats = Cache::remember($cacheKey, 30, function() use ($user) {
                if ($user->isWorker()) {
                    return $this->getWorkerStats($user);
                } elseif ($user->isBusiness()) {
                    return $this->getBusinessStats($user);
                } elseif ($user->isAgency()) {
                    return $this->getAgencyStats($user);
                }
                
                return ['error' => 'Invalid user type'];
            });
            
            // If cached result is an array with error, return it
            if (is_array($stats) && isset($stats['error'])) {
                return response()->json($stats, 400);
            }
            
            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Dashboard Stats API Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch stats'], 500);
        }
    }

    /**
     * Get worker dashboard stats
     */
    protected function getWorkerStats($user)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Return array instead of response for caching

        // Optimized queries using joins
        $stats = [
            'shifts_today' => ShiftAssignment::where('worker_id', $user->id)
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->whereDate('shifts.shift_date', Carbon::today())
                ->whereIn('shift_assignments.status', ['assigned', 'checked_in', 'in_progress'])
                ->count(),
            
            'shifts_this_week' => ShiftAssignment::where('worker_id', $user->id)
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->whereBetween('shifts.shift_date', [$startOfWeek, $endOfWeek])
                ->count(),
            
            'pending_applications' => ShiftApplication::where('worker_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            
            'earnings_this_week' => ShiftPayment::where('worker_id', $user->id)
                ->where('status', 'paid_out')
                ->whereBetween('payout_completed_at', [$startOfWeek, $endOfWeek])
                ->sum('amount_net'),
            
            'earnings_this_month' => ShiftPayment::where('worker_id', $user->id)
                ->where('status', 'paid_out')
                ->whereMonth('payout_completed_at', Carbon::now()->month)
                ->sum('amount_net'),
            
            'total_completed' => ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            
            'rating' => $user->rating_as_worker ?? 0,
            'reliability_score' => $user->workerProfile->reliability_score ?? 0,
        ];

        return $stats;
    }

    /**
     * Get business dashboard stats
     */
    protected function getBusinessStats($user)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Return array instead of response for caching

        // Optimized queries using joins
        $stats = [
            'active_shifts' => Shift::where('business_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'in_progress'])
                ->where('shift_date', '>=', Carbon::today())
                ->count(),
            
            'pending_applications' => ShiftApplication::join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $user->id)
                ->where('shift_applications.status', 'pending')
                ->count(),
            
            'workers_today' => ShiftAssignment::join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $user->id)
                ->whereDate('shifts.shift_date', Carbon::today())
                ->count(),
            
            'cost_this_week' => ShiftPayment::where('business_id', $user->id)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('amount_gross'),
            
            'cost_this_month' => ShiftPayment::where('business_id', $user->id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount_gross'),
            
            'total_shifts_posted' => Shift::where('business_id', $user->id)->count(),
        ];

        return $stats;
    }

    /**
     * Get agency dashboard stats
     */
    protected function getAgencyStats($user)
    {
        // Return array instead of response for caching
        // Optimized query using joins instead of subqueries
        $stats = [
            'total_workers' => AgencyWorker::where('agency_id', $user->id)
                ->where('status', 'active')
                ->count(),
            
            'active_placements' => ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('agency_workers.agency_id', $user->id)
                ->where('agency_workers.status', 'active')
                ->whereIn('shift_assignments.status', ['assigned', 'in_progress'])
                ->where('shifts.shift_date', '>=', Carbon::today())
                ->distinct('shift_assignments.worker_id')
                ->count('shift_assignments.worker_id'),
            
            'revenue_this_month' => DB::table('shift_payments')
                ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                ->join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $user->id)
                ->whereMonth('shift_payments.payout_completed_at', Carbon::now()->month)
                ->where('shift_payments.status', 'released')
                ->sum('shift_payments.agency_commission'),
            
            'total_placements_month' => ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $user->id)
                ->whereMonth('shift_assignments.created_at', Carbon::now()->month)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get unread notifications count
     */
    public function notificationsCount(Request $request)
    {
        try {
            $user = Auth::user();
            $cacheKey = "notification_count_{$user->id}";
            
            // Cache for 15 seconds (shorter than stats since notifications are more time-sensitive)
            $count = Cache::remember($cacheKey, 15, function() use ($user) {
                return \App\Models\ShiftNotification::where('user_id', $user->id)
                    ->where('read', false)
                    ->count();
            });
            
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            \Log::error('Notifications Count API Error: ' . $e->getMessage());
            return response()->json(['count' => 0]);
        }
    }
}
