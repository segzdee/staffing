<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessManagementController extends Controller
{
    /**
     * Display all businesses with filters
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::where('user_type', 'business')
            ->with(['businessProfile', 'postedShifts']);

        // Verification status filter
        if ($request->has('verification') && $request->verification != '') {
            if ($request->verification === 'verified') {
                $query->where('is_verified_business', true);
            } elseif ($request->verification === 'unverified') {
                $query->where('is_verified_business', false);
            } elseif ($request->verification === 'pending') {
                $query->whereHas('verificationRequest', function($q) {
                    $q->where('status', 'pending');
                });
            }
        }

        // Status filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Industry filter
        if ($request->has('industry') && $request->industry != '') {
            $query->whereHas('businessProfile', function($q) use ($request) {
                $q->where('industry', $request->industry);
            });
        }

        // Search by name or email
        if ($request->has('q') && strlen($request->q) > 2) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('username', 'LIKE', '%' . $request->q . '%');
            });
        }

        $businesses = $query->orderBy('id', 'desc')->paginate(30);

        // Statistics
        $stats = [
            'total_businesses' => User::where('user_type', 'business')->count(),
            'verified_businesses' => User::where('user_type', 'business')->where('is_verified_business', true)->count(),
            'active_businesses' => User::where('user_type', 'business')->where('status', 'active')->count(),
            'suspended_businesses' => User::where('user_type', 'business')->where('status', 'suspended')->count(),
        ];

        return view('admin.businesses.index', compact('businesses', 'stats'));
    }

    /**
     * Display business details
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $business = User::where('user_type', 'business')
            ->with([
                'businessProfile',
                'postedShifts',
                'ratings'
            ])
            ->findOrFail($id);

        // Calculate statistics
        $stats = [
            'total_shifts_posted' => DB::table('shifts')
                ->where('business_id', $id)
                ->count(),

            'total_spent' => DB::table('shift_payments')
                ->join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $id)
                ->sum('shift_payments.amount'),

            'average_rating' => DB::table('ratings')
                ->where('business_id', $id)
                ->avg('rating'),

            'fill_rate' => $this->calculateFillRate($id),

            'cancellation_rate' => $this->calculateCancellationRate($id),

            'avg_shift_cost' => DB::table('shift_payments')
                ->join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $id)
                ->avg('shift_payments.amount'),

            'shifts_this_month' => DB::table('shifts')
                ->where('business_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),

            'active_shifts' => DB::table('shifts')
                ->where('business_id', $id)
                ->whereIn('status', ['open', 'filled'])
                ->count(),
        ];

        // Recent shifts
        $recentShifts = DB::table('shifts')
            ->where('business_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.businesses.show', compact('business', 'stats', 'recentShifts'));
    }

    /**
     * Manually verify business
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyBusiness($id)
    {
        $business = User::findOrFail($id);

        if ($business->user_type !== 'business') {
            return back()->withErrors(['error' => 'User is not a business.']);
        }

        $business->is_verified_business = true;
        $business->verified_at = Carbon::now();
        $business->verified_by_admin_id = auth()->id();
        $business->save();

        // Update verification request if exists
        if ($business->verificationRequest) {
            $business->verificationRequest->update([
                'status' => 'approved',
                'approved_at' => Carbon::now()
            ]);
        }

        // Notify business
        // $business->notify(new BusinessVerifiedNotification());

        \Session::flash('success', 'Business has been verified successfully.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Remove business verification
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unverifyBusiness($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $business = User::findOrFail($id);
        $business->is_verified_business = false;
        $business->verified_at = null;
        $business->unverification_reason = $request->reason;
        $business->save();

        \Session::flash('success', 'Business verification has been removed.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Approve business license document
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveLicense($id)
    {
        $business = User::with('businessProfile')->findOrFail($id);

        if (!$business->businessProfile) {
            return back()->withErrors(['error' => 'Business profile not found.']);
        }

        $business->businessProfile->update([
            'license_verified' => true,
            'license_verified_at' => Carbon::now(),
            'license_verified_by_admin_id' => auth()->id()
        ]);

        \Session::flash('success', 'Business license approved.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Set spending limit for business
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setSpendingLimit($id, Request $request)
    {
        $request->validate([
            'monthly_limit' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500'
        ]);

        $business = User::with('businessProfile')->findOrFail($id);

        $business->businessProfile->update([
            'monthly_spending_limit' => $request->monthly_limit,
            'spending_limit_set_at' => Carbon::now(),
            'spending_limit_set_by_admin_id' => auth()->id(),
            'spending_limit_reason' => $request->reason
        ]);

        \Session::flash('success', 'Spending limit updated successfully.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Suspend business account
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'duration_days' => 'nullable|integer|min:1|max:365'
        ]);

        $business = User::findOrFail($id);

        $business->status = 'suspended';
        $business->suspended_at = Carbon::now();
        $business->suspension_reason = $request->reason;
        $business->suspended_by_admin_id = auth()->id();

        if ($request->duration_days) {
            $business->suspension_ends_at = Carbon::now()->addDays($request->duration_days);
        }

        $business->save();

        // Cancel all open shifts
        DB::table('shifts')
            ->where('business_id', $id)
            ->where('status', 'open')
            ->update(['status' => 'cancelled', 'cancellation_reason' => 'Business account suspended']);

        // Notify business
        // $business->notify(new AccountSuspendedNotification($business));

        \Session::flash('success', 'Business account has been suspended.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Unsuspend business account
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspend($id)
    {
        $business = User::findOrFail($id);

        $business->status = 'active';
        $business->suspended_at = null;
        $business->suspension_reason = null;
        $business->suspension_ends_at = null;
        $business->unsuspended_at = Carbon::now();
        $business->unsuspended_by_admin_id = auth()->id();
        $business->save();

        \Session::flash('success', 'Business account has been unsuspended.');

        return redirect()->route('admin.businesses.show', $id);
    }

    /**
     * Calculate fill rate for business
     *
     * @param int $businessId
     * @return float
     */
    private function calculateFillRate($businessId)
    {
        $totalShifts = DB::table('shifts')
            ->where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($totalShifts == 0) {
            return 0;
        }

        $filledShifts = DB::table('shifts')
            ->where('business_id', $businessId)
            ->where('status', 'filled')
            ->count();

        return round(($filledShifts / $totalShifts) * 100, 1);
    }

    /**
     * Calculate cancellation rate for business
     *
     * @param int $businessId
     * @return float
     */
    private function calculateCancellationRate($businessId)
    {
        $totalShifts = DB::table('shifts')
            ->where('business_id', $businessId)
            ->count();

        if ($totalShifts == 0) {
            return 0;
        }

        $cancelledShifts = DB::table('shifts')
            ->where('business_id', $businessId)
            ->where('status', 'cancelled')
            ->count();

        return round(($cancelledShifts / $totalShifts) * 100, 1);
    }

    /**
     * View business payment history
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function paymentHistory($id)
    {
        $business = User::findOrFail($id);

        $payments = DB::table('shift_payments')
            ->join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $id)
            ->select('shift_payments.*', 'shifts.title as shift_title')
            ->orderBy('shift_payments.created_at', 'desc')
            ->paginate(30);

        $totalSpent = DB::table('shift_payments')
            ->join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $id)
            ->sum('shift_payments.amount');

        return view('admin.businesses.payments', compact('business', 'payments', 'totalSpent'));
    }
}
