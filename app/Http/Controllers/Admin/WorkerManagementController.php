<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerBadge;
use App\Models\Skill;
use App\Models\Certification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkerManagementController extends Controller
{
    /**
     * Display all workers with filters
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::where('user_type', 'worker')
            ->with(['workerProfile', 'skills', 'certifications']);

        // Verification status filter
        if ($request->has('verification') && $request->verification != '') {
            if ($request->verification === 'verified') {
                $query->where('is_verified_worker', true);
            } elseif ($request->verification === 'unverified') {
                $query->where('is_verified_worker', false);
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

        // Rating filter
        if ($request->has('min_rating') && $request->min_rating != '') {
            $query->whereHas('workerProfile', function($q) use ($request) {
                $q->where('average_rating', '>=', $request->min_rating);
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

        $workers = $query->orderBy('id', 'desc')->paginate(30);

        // Statistics
        $stats = [
            'total_workers' => User::where('user_type', 'worker')->count(),
            'verified_workers' => User::where('user_type', 'worker')->where('is_verified_worker', true)->count(),
            'active_workers' => User::where('user_type', 'worker')->where('status', 'active')->count(),
            'suspended_workers' => User::where('user_type', 'worker')->where('status', 'suspended')->count(),
        ];

        return view('admin.workers.index', compact('workers', 'stats'));
    }

    /**
     * Display worker details
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $worker = User::where('user_type', 'worker')
            ->with([
                'workerProfile',
                'skills',
                'certifications',
                'badges',
                'completedShifts',
                'ratings'
            ])
            ->findOrFail($id);

        // Calculate statistics
        $stats = [
            'total_shifts_completed' => DB::table('shift_assignments')
                ->where('worker_id', $id)
                ->where('status', 'completed')
                ->count(),

            'total_earnings' => DB::table('shift_payments')
                ->where('worker_id', $id)
                ->where('payout_status', 'completed')
                ->sum('worker_amount'),

            'average_rating' => DB::table('ratings')
                ->where('worker_id', $id)
                ->avg('rating'),

            'no_show_count' => DB::table('shift_assignments')
                ->where('worker_id', $id)
                ->where('status', 'no_show')
                ->count(),

            'on_time_percentage' => $this->calculateOnTimePercentage($id),

            'shifts_this_month' => DB::table('shift_assignments')
                ->where('worker_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),

            'total_badges' => DB::table('worker_badges')
                ->where('worker_id', $id)
                ->count(),
        ];

        // Recent activity
        $recentShifts = DB::table('shift_assignments')
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->where('shift_assignments.worker_id', $id)
            ->select('shifts.*', 'shift_assignments.status as assignment_status')
            ->orderBy('shift_assignments.created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.workers.show', compact('worker', 'stats', 'recentShifts'));
    }

    /**
     * Manually verify worker
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyWorker($id)
    {
        $worker = User::findOrFail($id);

        if ($worker->user_type !== 'worker') {
            return back()->withErrors(['error' => 'User is not a worker.']);
        }

        $worker->is_verified_worker = true;
        $worker->verified_at = Carbon::now();
        $worker->verified_by_admin_id = auth()->id();
        $worker->save();

        // Update verification request if exists
        if ($worker->verificationRequest) {
            $worker->verificationRequest->update([
                'status' => 'approved',
                'approved_at' => Carbon::now()
            ]);
        }

        // Notify worker
        // $worker->notify(new WorkerVerifiedNotification());

        \Session::flash('success', 'Worker has been verified successfully.');

        return redirect()->route('admin.workers.show', $id);
    }

    /**
     * Remove worker verification
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unverifyWorker($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $worker = User::findOrFail($id);
        $worker->is_verified_worker = false;
        $worker->verified_at = null;
        $worker->unverification_reason = $request->reason;
        $worker->save();

        \Session::flash('success', 'Worker verification has been removed.');

        return redirect()->route('admin.workers.show', $id);
    }

    /**
     * Assign badge to worker
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignBadge($id, Request $request)
    {
        $request->validate([
            'badge_type' => 'required|in:reliable,top_performer,rising_star,veteran,specialist,safety_champion,attendance_ace',
            'badge_level' => 'required|in:bronze,silver,gold',
            'reason' => 'nullable|string|max:500'
        ]);

        $worker = User::findOrFail($id);

        // Check if badge already exists
        $existingBadge = WorkerBadge::where('worker_id', $id)
            ->where('badge_type', $request->badge_type)
            ->first();

        if ($existingBadge) {
            // Update level
            $existingBadge->badge_level = $request->badge_level;
            $existingBadge->updated_at = Carbon::now();
            $existingBadge->save();
        } else {
            // Create new badge
            WorkerBadge::create([
                'worker_id' => $id,
                'badge_type' => $request->badge_type,
                'badge_level' => $request->badge_level,
                'earned_at' => Carbon::now(),
                'assigned_by_admin_id' => auth()->id(),
                'manual_reason' => $request->reason
            ]);
        }

        \Session::flash('success', 'Badge assigned successfully.');

        return redirect()->route('admin.workers.show', $id);
    }

    /**
     * Suspend worker account
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

        $worker = User::findOrFail($id);

        $worker->status = 'suspended';
        $worker->suspended_at = Carbon::now();
        $worker->suspension_reason = $request->reason;
        $worker->suspended_by_admin_id = auth()->id();

        if ($request->duration_days) {
            $worker->suspension_ends_at = Carbon::now()->addDays($request->duration_days);
        }

        $worker->save();

        // Cancel all future shift assignments
        DB::table('shift_assignments')
            ->where('worker_id', $id)
            ->where('status', 'assigned')
            ->update(['status' => 'cancelled_by_admin']);

        // Notify worker
        // $worker->notify(new AccountSuspendedNotification($worker));

        \Session::flash('success', 'Worker account has been suspended.');

        return redirect()->route('admin.workers.show', $id);
    }

    /**
     * Unsuspend worker account
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspend($id)
    {
        $worker = User::findOrFail($id);

        $worker->status = 'active';
        $worker->suspended_at = null;
        $worker->suspension_reason = null;
        $worker->suspension_ends_at = null;
        $worker->unsuspended_at = Carbon::now();
        $worker->unsuspended_by_admin_id = auth()->id();
        $worker->save();

        \Session::flash('success', 'Worker account has been unsuspended.');

        return redirect()->route('admin.workers.show', $id);
    }

    /**
     * View worker skills
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function viewSkills($id)
    {
        $worker = User::with(['skills' => function($query) {
            $query->withPivot('proficiency_level', 'years_experience', 'verified');
        }])->findOrFail($id);

        $allSkills = Skill::orderBy('name')->get();

        return view('admin.workers.skills', compact('worker', 'allSkills'));
    }

    /**
     * View worker certifications
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function viewCertifications($id)
    {
        $worker = User::with(['certifications' => function($query) {
            $query->withPivot('issued_date', 'expiry_date', 'verified', 'document_path');
        }])->findOrFail($id);

        return view('admin.workers.certifications', compact('worker'));
    }

    /**
     * Approve worker certification
     *
     * @param int $workerId
     * @param int $certificationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveCertification($workerId, $certificationId)
    {
        DB::table('worker_certifications')
            ->where('worker_id', $workerId)
            ->where('certification_id', $certificationId)
            ->update([
                'verified' => true,
                'verified_at' => Carbon::now(),
                'verified_by_admin_id' => auth()->id()
            ]);

        \Session::flash('success', 'Certification approved.');

        return redirect()->route('admin.workers.certifications', $workerId);
    }

    /**
     * Calculate on-time percentage
     *
     * @param int $workerId
     * @return float
     */
    private function calculateOnTimePercentage($workerId)
    {
        $totalShifts = DB::table('shift_assignments')
            ->where('worker_id', $workerId)
            ->where('status', 'completed')
            ->count();

        if ($totalShifts == 0) {
            return 0;
        }

        $onTimeShifts = DB::table('shift_assignments')
            ->where('worker_id', $workerId)
            ->where('status', 'completed')
            ->whereRaw('checked_in_at <= shift_start_time')
            ->count();

        return round(($onTimeShifts / $totalShifts) * 100, 1);
    }
}
