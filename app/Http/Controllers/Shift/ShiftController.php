<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftMatchingService;
use App\Services\ComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ShiftController extends Controller
{
    protected $matchingService;
    protected $complianceService;

    public function __construct(ShiftMatchingService $matchingService, ComplianceService $complianceService)
    {
        $this->middleware('auth');
        $this->matchingService = $matchingService;
        $this->complianceService = $complianceService;
    }

    /**
     * Display a listing of available shifts.
     * Workers can browse and search for shifts.
     */
    public function index(Request $request)
    {
        $query = Shift::with(['business', 'assignments'])
            ->open()
            ->upcoming();

        // Filter by industry
        if ($request->has('industry') && $request->industry != 'all') {
            $query->where('industry', $request->industry);
        }

        // Filter by location (city)
        if ($request->has('city')) {
            $query->where('location_city', 'LIKE', '%' . $request->city . '%');
        }

        // Filter by date
        if ($request->has('date')) {
            $query->where('shift_date', $request->date);
        }

        // Filter by rate
        if ($request->has('min_rate')) {
            $query->where('final_rate', '>=', $request->min_rate);
        }

        // Filter by urgency
        if ($request->has('urgent') && $request->urgent) {
            $query->where('urgency_level', 'urgent')
                ->orWhere('urgency_level', 'critical');
        }

        // Search by keywords
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'rate':
                $query->orderBy('final_rate', 'desc');
                break;
            case 'urgent':
                $query->orderBy('urgency_level', 'desc')
                    ->orderBy('shift_date', 'asc');
                break;
            case 'date':
            default:
                $query->orderBy('shift_date', 'asc')
                    ->orderBy('start_time', 'asc');
                break;
        }

        $shifts = $query->paginate(20);

        // If user is a worker, get recommended shifts
        $recommendedShifts = [];
        if (Auth::user()->isWorker()) {
            $recommendedShifts = $this->matchingService
                ->matchShiftsForWorker(Auth::user())
                ->take(5);
        }

        return view('shifts.index', compact('shifts', 'recommendedShifts'));
    }

    /**
     * Display a specific shift.
     */
    public function show($id)
    {
        $shift = Shift::with([
            'business.businessProfile',
            'venue',
            'applications' => function ($query) {
                $query->where('worker_id', Auth::id());
            },
            'assignments',
            'attachments'
        ])->findOrFail($id);

        // Check if worker has already applied
        $hasApplied = false;
        $application = null;
        if (Auth::user()->isWorker()) {
            $application = $shift->applications->first();
            $hasApplied = $application !== null;
        }

        // Calculate match score if worker
        $matchScore = null;
        if (Auth::user()->isWorker()) {
            $matchScore = $this->matchingService
                ->calculateWorkerShiftMatch(Auth::user(), $shift);
        }

        return view('shifts.show', compact('shift', 'hasApplied', 'application', 'matchScore'));
    }

    /**
     * Show the form for creating a new shift.
     * Only accessible by businesses and agencies.
     */
    public function create()
    {
        // Check authorization
        if (!Auth::user()->isBusiness() && !Auth::user()->isAgency()) {
            abort(403, 'Only businesses and agencies can post shifts.');
        }

        // Get venues for the business (if business user)
        $venues = collect();
        if (Auth::user()->isBusiness() && Auth::user()->businessProfile) {
            $venues = \App\Models\Venue::forBusiness(Auth::user()->businessProfile->id)
                ->active()
                ->orderBy('name')
                ->get();
        }

        return view('shifts.create', compact('venues'));
    }

    /**
     * Store a newly created shift.
     */
    /**
     * Store a newly created shift.
     */
    public function store(\App\Http\Requests\StoreShiftRequest $request)
    {
        // Calculate duration
        $startTime = Carbon::parse($request->shift_date . ' ' . $request->start_time);
        $endTime = Carbon::parse($request->shift_date . ' ' . $request->end_time);
        $duration = $startTime->diffInHours($endTime, true);

        // Detect shift timing characteristics for surge pricing
        $shiftDateTime = Carbon::parse($request->shift_date . ' ' . $request->start_time);
        $isWeekend = $shiftDateTime->isWeekend();
        $isNightShift = $shiftDateTime->hour >= 22 || $shiftDateTime->hour < 6;
        $isPublicHoliday = app(\App\Services\ShiftPricingService::class)->isPublicHoliday($shiftDateTime);

        // ===== GLO-001: Jurisdiction Compliance Validation =====
        $tempShift = new Shift([
            'business_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'industry' => $request->industry,
            'location_country' => $request->location_country,
            'location_state' => $request->location_state,
            'base_rate' => $request->base_rate,
            'role_type' => $request->role_type ?? 'general',
            'shift_date' => $request->shift_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_hours' => $duration
        ]);

        // Validate compliance
        $complianceResult = $this->complianceService->validateShiftCreation($tempShift);

        if (!$complianceResult['compliant']) {
            return redirect()->back()
                ->withErrors(['compliance' => $complianceResult['violations']])
                ->withInput()
                ->with('compliance_warnings', $complianceResult['warnings'] ?? []);
        }

        // If venue_id is provided, validate it belongs to the business
        $venueId = null;
        if ($request->venue_id) {
            if (Auth::user()->isBusiness() && Auth::user()->businessProfile) {
                $venue = \App\Models\Venue::forBusiness(Auth::user()->businessProfile->id)
                    ->find($request->venue_id);
                if ($venue) {
                    $venueId = $venue->id;
                }
            }
        }

        // Create shift with business logic fields
        $shift = Shift::create([
            'business_id' => Auth::id(),
            'venue_id' => $venueId,
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
            'urgency_level' => $request->urgency_level ?? 'normal',
            'status' => 'open',
            'required_workers' => $request->required_workers,
            'filled_workers' => 0,
            'requirements' => $request->requirements,
            'dress_code' => $request->dress_code,
            'parking_info' => $request->parking_info,
            'break_info' => $request->break_info,
            'special_instructions' => $request->special_instructions,
            'posted_by_agent' => false,
            'agent_id' => null,

            // SL-001: Business logic fields
            'role_type' => $request->role_type,
            'required_skills' => $request->required_skills,
            'required_certifications' => $request->required_certifications,
            'platform_fee_rate' => config('overtimestaff.financial.platform_fee_rate'),
            'vat_rate' => config('overtimestaff.financial.vat_rate'),
            'contingency_buffer_rate' => config('overtimestaff.financial.contingency_buffer_rate'),

            // SL-008: Surge pricing flags
            'is_weekend' => $isWeekend,
            'is_night_shift' => $isNightShift,
            'is_public_holiday' => $isPublicHoliday,

            // SL-005: Clock-in verification defaults
            'geofence_radius' => $request->geofence_radius ?? config('overtimestaff.operations.default_geofence_radius'),
            'early_clockin_minutes' => config('overtimestaff.operations.early_clockin_minutes'),
            'late_grace_minutes' => config('overtimestaff.operations.late_grace_minutes'),
        ]);

        // Calculate all costs including surge pricing (SL-001 + SL-008)
        $shift->calculateCosts();

        // Display cost breakdown to business
        $costBreakdown = [
            'base_worker_pay' => $shift->base_worker_pay,
            'surge_multiplier' => $shift->surge_multiplier,
            'platform_fee' => $shift->platform_fee_amount,
            'vat' => $shift->vat_amount,
            'total_cost' => $shift->total_business_cost,
            'escrow_amount' => $shift->escrow_amount,
        ];

        // TODO: Trigger notification to matching workers
        // event(new ShiftPosted($shift));

        return redirect()->route('shifts.show', $shift->id)
            ->with('success', 'Shift posted successfully! Workers will be notified.')
            ->with('cost_breakdown', $costBreakdown);
    }

    /**
     * Show the form for editing a shift.
     */
    public function edit($id)
    {
        $shift = Shift::findOrFail($id);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only edit your own shifts.');
        }

        // Can't edit shift that's already in progress or completed
        if (in_array($shift->status, ['in_progress', 'completed'])) {
            return redirect()->back()
                ->with('error', 'Cannot edit a shift that is in progress or completed.');
        }

        return view('shifts.edit', compact('shift'));
    }

    /**
     * Update a shift.
     */
    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only edit your own shifts.');
        }

        // Can't edit shift that's already in progress or completed
        if (in_array($shift->status, ['in_progress', 'completed'])) {
            return redirect()->back()
                ->with('error', 'Cannot edit a shift that is in progress or completed.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'shift_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Recalculate duration and dynamic rate
        $startTime = Carbon::parse($request->shift_date . ' ' . $request->start_time);
        $endTime = Carbon::parse($request->shift_date . ' ' . $request->end_time);
        $duration = $startTime->diffInHours($endTime, true);

        $dynamicRate = $this->matchingService->calculateDynamicRate([
            'base_rate' => $request->base_rate,
            'shift_date' => $request->shift_date,
            'industry' => $shift->industry,
            'urgency_level' => $shift->urgency_level,
        ]);

        $shift->update([
            'title' => $request->title,
            'description' => $request->description,
            'shift_date' => $request->shift_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_hours' => $duration,
            'base_rate' => $request->base_rate,
            'dynamic_rate' => $dynamicRate,
            'final_rate' => $dynamicRate,
        ]);

        return redirect()->route('shifts.show', $shift->id)
            ->with('success', 'Shift updated successfully.');
    }

    /**
     * Cancel/delete a shift.
     */
    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);

        // Check authorization
        if ($shift->business_id !== Auth::id()) {
            abort(403, 'You can only delete your own shifts.');
        }

        // Can't delete completed shifts
        if ($shift->status === 'completed') {
            return redirect()->back()
                ->with('error', 'Cannot delete a completed shift.');
        }

        // If shift has assignments, cancel them
        if ($shift->assignments()->count() > 0) {
            // TODO: Notify assigned workers
            // TODO: Handle payment refunds if applicable
            $shift->assignments()->update(['status' => 'cancelled']);
        }

        $shift->update(['status' => 'cancelled']);
        $shift->delete(); // Soft delete

        return redirect()->route('business.shifts.index')
            ->with('success', 'Shift cancelled successfully.');
    }

    /**
     * Duplicate a shift for reposting.
     */
    public function duplicate($id)
    {
        $originalShift = Shift::findOrFail($id);

        // Check authorization
        if ($originalShift->business_id !== Auth::id()) {
            abort(403, 'You can only duplicate your own shifts.');
        }

        $newShift = $originalShift->replicate();
        $newShift->status = 'draft';
        $newShift->filled_workers = 0;
        $newShift->shift_date = null; // User must set new date
        $newShift->save();

        return redirect()->route('shifts.edit', $newShift->id)
            ->with('success', 'Shift duplicated. Please set the date and review details.');
    }

    /**
     * Get nearby shifts for workers based on location.
     */
    public function nearby(Request $request)
    {
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $radius = $request->get('radius', 25); // Default 25 miles

        if (!$lat || !$lng) {
            return response()->json(['error' => 'Location required'], 400);
        }

        $shifts = Shift::open()
            ->upcoming()
            ->nearby($lat, $lng, $radius)
            ->limit(20)
            ->get();

        return response()->json($shifts);
    }

    /**
     * Get recommended shifts for a worker.
     */
    public function recommended(Request $request)
    {
        if (!Auth::user()->isWorker()) {
            abort(403, 'Only workers can view recommendations.');
        }

        // Get matched shifts collection from service
        $matchedShifts = $this->matchingService->matchShiftsForWorker(Auth::user());

        // Manual pagination since we need to sort by match_score first
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        // Slice the collection for current page
        $paginatedItems = $matchedShifts->slice($offset, $perPage)->values();

        // Create a LengthAwarePaginator instance
        $shifts = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $matchedShifts->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('shifts.recommended', compact('shifts'));
    }

    // Protected helper methods removed in favor of ShiftPricingService and Config

}
