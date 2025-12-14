<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Services\ShiftMatchingService;
use App\Services\ShiftPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AgentController extends Controller
{
    protected $matchingService;
    protected $paymentService;

    public function __construct(ShiftMatchingService $matchingService, ShiftPaymentService $paymentService)
    {
        $this->middleware('api.agent');
        $this->matchingService = $matchingService;
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new shift.
     * POST /api/agent/shifts
     */
    public function createShift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'industry' => 'required|in:hospitality,healthcare,retail,events,warehouse,professional',
            'location_address' => 'required|string',
            'location_city' => 'required|string',
            'location_state' => 'required|string',
            'location_country' => 'required|string',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'shift_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1|max:100',
            'urgency_level' => 'sometimes|in:normal,urgent,critical',
            'requirements' => 'sometimes|array',
            'dress_code' => 'sometimes|string|max:255',
            'parking_info' => 'sometimes|string',
            'break_info' => 'sometimes|string',
            'special_instructions' => 'sometimes|string',
            'business_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify business_id is actually a business
        $business = User::find($request->business_id);
        if (!$business || !$business->isBusiness()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid business_id. User must be a business account.'
            ], 400);
        }

        // Calculate duration
        $startTime = Carbon::parse($request->shift_date . ' ' . $request->start_time);
        $endTime = Carbon::parse($request->shift_date . ' ' . $request->end_time);
        $duration = $startTime->diffInHours($endTime, true);

        // Calculate dynamic rate
        $dynamicRate = $this->matchingService->calculateDynamicRate([
            'base_rate' => $request->base_rate,
            'shift_date' => $request->shift_date,
            'start_time' => $request->start_time,
            'industry' => $request->industry,
            'urgency_level' => $request->urgency_level ?? 'normal',
        ]);

        // Create shift
        $shift = Shift::create([
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
            'industry' => $request->industry,
            'location_address' => $request->location_address,
            'location_city' => $request->location_city,
            'location_state' => $request->location_state,
            'location_country' => $request->location_country,
            'location_lat' => $request->location_lat,
            'location_lng' => $request->location_lng,
            'shift_date' => $request->shift_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_hours' => $duration,
            'base_rate' => $request->base_rate,
            'dynamic_rate' => $dynamicRate,
            'final_rate' => $dynamicRate,
            'urgency_level' => $request->urgency_level ?? 'normal',
            'status' => 'open',
            'required_workers' => $request->required_workers,
            'filled_workers' => 0,
            'requirements' => $request->requirements,
            'dress_code' => $request->dress_code,
            'parking_info' => $request->parking_info,
            'break_info' => $request->break_info,
            'special_instructions' => $request->special_instructions,
            'posted_by_agent' => true,
            'agent_id' => $request->agent->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'shift' => $shift
        ], 201);
    }

    /**
     * Get shift details.
     * GET /api/agent/shifts/{id}
     */
    public function getShift($id)
    {
        $shift = Shift::with([
            'business',
            'applications.worker.workerProfile',
            'assignments.worker.workerProfile'
        ])->find($id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'error' => 'Shift not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'shift' => $shift
        ]);
    }

    /**
     * Update a shift.
     * PUT /api/agent/shifts/{id}
     */
    public function updateShift(Request $request, $id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'error' => 'Shift not found'
            ], 404);
        }

        // Can't update shift that's already in progress or completed
        if (in_array($shift->status, ['in_progress', 'completed'])) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot update a shift that is in progress or completed'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'shift_date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'base_rate' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Recalculate if time/date changed
        if ($request->has('shift_date') || $request->has('start_time') || $request->has('end_time')) {
            $startTime = Carbon::parse(
                ($request->shift_date ?? $shift->shift_date) . ' ' .
                ($request->start_time ?? $shift->start_time)
            );
            $endTime = Carbon::parse(
                ($request->shift_date ?? $shift->shift_date) . ' ' .
                ($request->end_time ?? $shift->end_time)
            );
            $duration = $startTime->diffInHours($endTime, true);

            $dynamicRate = $this->matchingService->calculateDynamicRate([
                'base_rate' => $request->base_rate ?? $shift->base_rate,
                'shift_date' => $request->shift_date ?? $shift->shift_date,
                'start_time' => $request->start_time ?? $shift->start_time,
                'industry' => $shift->industry,
                'urgency_level' => $shift->urgency_level,
            ]);

            $shift->update([
                'duration_hours' => $duration,
                'dynamic_rate' => $dynamicRate,
                'final_rate' => $dynamicRate,
            ]);
        }

        $shift->update($request->only([
            'title', 'description', 'shift_date', 'start_time', 'end_time', 'base_rate'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully',
            'shift' => $shift->fresh()
        ]);
    }

    /**
     * Cancel a shift.
     * DELETE /api/agent/shifts/{id}
     */
    public function cancelShift($id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'error' => 'Shift not found'
            ], 404);
        }

        // Can't cancel completed shifts
        if ($shift->status === 'completed') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel a completed shift'
            ], 400);
        }

        // Cancel assignments
        if ($shift->assignments()->count() > 0) {
            $shift->assignments()->update(['status' => 'cancelled']);
        }

        $shift->update(['status' => 'cancelled']);
        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift cancelled successfully'
        ]);
    }

    /**
     * Search for workers.
     * GET /api/agent/workers/search
     */
    public function searchWorkers(Request $request)
    {
        $query = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->with(['workerProfile', 'skills', 'certifications']);

        // Filter by industry experience
        if ($request->has('industry')) {
            $query->whereHas('workerProfile', function($q) use ($request) {
                $q->whereJsonContains('industries_experience', $request->industry);
            });
        }

        // Filter by skills
        if ($request->has('skills')) {
            $skills = is_array($request->skills) ? $request->skills : [$request->skills];
            $query->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('skill_name', $skills);
            });
        }

        // Filter by location (city)
        if ($request->has('city')) {
            $query->whereHas('workerProfile', function($q) use ($request) {
                $q->where('location_city', 'LIKE', '%' . $request->city . '%');
            });
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating_as_worker', '>=', $request->min_rating);
        }

        // Filter by verification status
        if ($request->has('verified') && $request->verified) {
            $query->where('is_verified_worker', true);
        }

        $limit = $request->get('limit', 20);
        $workers = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'workers' => $workers
        ]);
    }

    /**
     * Invite a worker to a shift.
     * POST /api/agent/workers/invite
     */
    public function inviteWorker(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|exists:shifts,id',
            'worker_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shift = Shift::find($request->shift_id);
        $worker = User::find($request->worker_id);

        // Verify worker is a worker
        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'error' => 'User is not a worker'
            ], 400);
        }

        // Check if invitation already exists
        $existingInvitation = $shift->invitations()
            ->where('worker_id', $worker->id)
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'success' => false,
                'error' => 'Worker has already been invited to this shift'
            ], 400);
        }

        // Create invitation
        $invitation = $shift->invitations()->create([
            'worker_id' => $worker->id,
            'invited_by' => $shift->business_id,
            'message' => $request->message,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker invited successfully',
            'invitation' => $invitation
        ], 201);
    }

    /**
     * Get applications for a shift.
     * GET /api/agent/applications
     */
    public function getApplications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|exists:shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shift = Shift::with([
            'applications.worker.workerProfile',
            'applications.worker.skills',
            'applications.worker.certifications'
        ])->find($request->shift_id);

        // Calculate match scores
        foreach ($shift->applications as $application) {
            $application->match_score = $this->matchingService
                ->calculateWorkerShiftMatch($application->worker, $shift);
        }

        $sortedApplications = $shift->applications->sortByDesc('match_score')->values();

        return response()->json([
            'success' => true,
            'applications' => $sortedApplications
        ]);
    }

    /**
     * Accept an application (assign worker).
     * POST /api/agent/applications/{id}/accept
     */
    public function acceptApplication($applicationId)
    {
        $application = ShiftApplication::with('shift')->find($applicationId);

        if (!$application) {
            return response()->json([
                'success' => false,
                'error' => 'Application not found'
            ], 404);
        }

        $shift = $application->shift;

        // Check if shift is already full
        if ($shift->isFull()) {
            return response()->json([
                'success' => false,
                'error' => 'Shift is already fully staffed'
            ], 400);
        }

        // Create assignment
        $assignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $application->worker_id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'payment_status' => 'pending',
        ]);

        // Update application
        $application->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Increment filled workers
        $shift->increment('filled_workers');

        // Update shift status if now full
        if ($shift->fresh()->isFull()) {
            $shift->update([
                'status' => 'assigned',
                'filled_at' => now(),
            ]);
        }

        // Hold payment in escrow
        $escrowResult = $this->paymentService->holdInEscrow($assignment);

        if (!$escrowResult) {
            return response()->json([
                'success' => false,
                'error' => 'Payment processing failed. Please ensure business has valid payment method.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Application accepted and worker assigned',
            'assignment' => $assignment
        ], 201);
    }

    /**
     * Match workers to a shift using AI algorithm.
     * POST /api/agent/match/workers
     */
    public function matchWorkers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|exists:shifts,id',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shift = Shift::find($request->shift_id);
        $limit = $request->get('limit', 10);

        $matchedWorkers = $this->matchingService
            ->matchWorkersForShift($shift)
            ->take($limit);

        return response()->json([
            'success' => true,
            'matched_workers' => $matchedWorkers->values(),
            'algorithm' => [
                'skills_weight' => 40,
                'location_weight' => 25,
                'availability_weight' => 20,
                'experience_weight' => 10,
                'rating_weight' => 5,
            ]
        ]);
    }

    /**
     * Get agent statistics.
     * GET /api/agent/stats
     */
    public function getStats(Request $request)
    {
        $agent = $request->agent;

        $stats = [
            'total_shifts_posted' => Shift::where('agent_id', $agent->id)->count(),
            'open_shifts' => Shift::where('agent_id', $agent->id)->where('status', 'open')->count(),
            'completed_shifts' => Shift::where('agent_id', $agent->id)->where('status', 'completed')->count(),
            'total_workers_assigned' => ShiftAssignment::whereHas('shift', function($q) use ($agent) {
                $q->where('agent_id', $agent->id);
            })->count(),
            'average_fill_time' => $this->calculateAverageFillTime($agent),
            'api_calls_today' => $this->getApiCallsToday($agent),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    protected function calculateAverageFillTime($agent)
    {
        $filledShifts = Shift::where('agent_id', $agent->id)
            ->whereNotNull('filled_at')
            ->get();

        if ($filledShifts->count() === 0) {
            return 0;
        }

        $totalMinutes = $filledShifts->sum(function($shift) {
            return Carbon::parse($shift->created_at)->diffInMinutes($shift->filled_at);
        });

        $count = $filledShifts->count();
        return $count > 0 ? round($totalMinutes / $count / 60, 1) : 0;
    }

    protected function getApiCallsToday($agent)
    {
        // This would need to be tracked in a separate table or logs
        // For now, return placeholder
        return 0;
    }
}
