<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ShiftTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('business');
    }

    /**
     * Display all shift templates for business
     */
    public function index()
    {
        $templates = ShiftTemplate::where('business_id', Auth::id())
            ->orderBy('template_name', 'asc')
            ->paginate(20);

        return view('templates.index', compact('templates'));
    }

    /**
     * Show form to create new template
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store new shift template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'title' => 'required|string|max:255',
            'shift_description' => 'required|string',
            'industry' => 'required|in:hospitality,healthcare,retail,events,warehouse,professional',
            'location_address' => 'required|string',
            'location_city' => 'required|string',
            'location_state' => 'required|string',
            'location_country' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1',
            'urgency_level' => 'sometimes|in:normal,urgent,critical',
            'requirements' => 'sometimes|array',
            'dress_code' => 'nullable|string',
            'parking_info' => 'nullable|string',
            'break_info' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'auto_renew' => 'sometimes|boolean',
            'recurrence_pattern' => 'required_if:auto_renew,true|in:daily,weekly,biweekly,monthly',
            'recurrence_days' => 'required_if:recurrence_pattern,weekly|array',
            'recurrence_start_date' => 'required_if:auto_renew,true|date',
            'recurrence_end_date' => 'required_if:auto_renew,true|date|after:recurrence_start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Calculate duration
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        $duration = $startTime->diffInHours($endTime, true);

        $template = ShiftTemplate::create([
            'business_id' => Auth::id(),
            'template_name' => $request->template_name,
            'description' => $request->description,
            'title' => $request->title,
            'shift_description' => $request->shift_description,
            'industry' => $request->industry,
            'location_address' => $request->location_address,
            'location_city' => $request->location_city,
            'location_state' => $request->location_state,
            'location_country' => $request->location_country,
            'location_lat' => $request->location_lat,
            'location_lng' => $request->location_lng,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_hours' => $duration,
            'base_rate' => $request->base_rate,
            'urgency_level' => $request->urgency_level ?? 'normal',
            'required_workers' => $request->required_workers,
            'requirements' => $request->requirements,
            'dress_code' => $request->dress_code,
            'parking_info' => $request->parking_info,
            'break_info' => $request->break_info,
            'special_instructions' => $request->special_instructions,
            'auto_renew' => $request->auto_renew ?? false,
            'recurrence_pattern' => $request->recurrence_pattern,
            'recurrence_days' => $request->recurrence_days,
            'recurrence_start_date' => $request->recurrence_start_date,
            'recurrence_end_date' => $request->recurrence_end_date,
        ]);

        // If auto-renew is enabled, create initial batch of shifts
        if ($template->auto_renew) {
            $shifts = $template->createBulkShifts();

            return redirect()->route('templates.show', $template->id)
                ->with('success', "Template created! {$shifts->count()} shifts automatically generated.");
        }

        return redirect()->route('templates.show', $template->id)
            ->with('success', 'Shift template created successfully!');
    }

    /**
     * Show template details
     */
    public function show($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        // Authorization check
        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only view your own templates.');
        }

        // Get shifts created from this template
        $shifts = $template->shifts()
            ->orderBy('shift_date', 'desc')
            ->paginate(10);

        return view('templates.show', compact('template', 'shifts'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only edit your own templates.');
        }

        return view('templates.edit', compact('template'));
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only edit your own templates.');
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template->update($request->only([
            'template_name',
            'description',
            'title',
            'shift_description',
            'base_rate',
            'required_workers',
            'urgency_level',
            'requirements',
            'dress_code',
            'parking_info',
            'break_info',
            'special_instructions',
            'auto_renew',
            'recurrence_pattern',
            'recurrence_days',
            'recurrence_start_date',
            'recurrence_end_date',
        ]));

        return redirect()->route('templates.show', $template->id)
            ->with('success', 'Template updated successfully!');
    }

    /**
     * Delete template
     */
    public function destroy($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only delete your own templates.');
        }

        // Check if template has future shifts
        $futureShifts = $template->shifts()
            ->where('shift_date', '>=', Carbon::today())
            ->whereIn('status', ['open', 'assigned'])
            ->count();

        if ($futureShifts > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete template with {$futureShifts} upcoming shifts. Cancel them first.");
        }

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted successfully!');
    }

    /**
     * Create a single shift from template
     */
    public function createShiftFromTemplate(Request $request, $id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only use your own templates.');
        }

        $validator = Validator::make($request->all(), [
            'shift_date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $shift = $template->createShift($request->shift_date);

        return redirect()->route('shift.show', $shift->id)
            ->with('success', 'Shift created from template!');
    }

    /**
     * Create bulk shifts from template
     */
    public function createBulkShifts(Request $request, $id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only use your own templates.');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $shifts = $template->createBulkShifts($request->start_date, $request->end_date);

        return redirect()->route('templates.show', $template->id)
            ->with('success', "{$shifts->count()} shifts created successfully!");
    }

    /**
     * Generate shifts for next period (for auto-renewal)
     */
    public function generateNextPeriod($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403);
        }

        if (!$template->auto_renew) {
            return redirect()->back()
                ->with('error', 'Auto-renewal is not enabled for this template.');
        }

        // Generate shifts for next 30 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30);

        $shifts = $template->createBulkShifts($startDate, $endDate);

        return redirect()->route('templates.show', $template->id)
            ->with('success', "{$shifts->count()} shifts generated for the next 30 days!");
    }

    /**
     * Duplicate a shift template
     */
    public function duplicate($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only duplicate your own templates.');
        }

        $newTemplate = $template->replicate();
        $newTemplate->template_name = $template->template_name . ' (Copy)';
        $newTemplate->created_at = now();
        $newTemplate->updated_at = now();
        $newTemplate->save();

        return redirect()->route('business.templates.index')
            ->with('success', 'Template duplicated successfully!');
    }

    /**
     * Activate a shift template
     */
    public function activate($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only activate your own templates.');
        }

        $template->update(['is_active' => true]);

        return redirect()->back()
            ->with('success', 'Template activated successfully!');
    }

    /**
     * Deactivate a shift template
     */
    public function deactivate($id)
    {
        $template = ShiftTemplate::findOrFail($id);

        if ($template->business_id !== Auth::id()) {
            abort(403, 'You can only deactivate your own templates.');
        }

        $template->update(['is_active' => false]);

        return redirect()->back()
            ->with('success', 'Template deactivated successfully!');
    }
}
