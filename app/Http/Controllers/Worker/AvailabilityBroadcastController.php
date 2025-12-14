<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBroadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AvailabilityBroadcastController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('worker');
    }

    /**
     * Show worker's broadcast availability page
     */
    public function index()
    {
        $worker = Auth::user();

        // Get current active broadcast
        $activeBroadcast = AvailabilityBroadcast::where('worker_id', $worker->id)
            ->where('status', 'active')
            ->where('available_to', '>=', now())
            ->first();

        // Get broadcast history
        $broadcastHistory = AvailabilityBroadcast::where('worker_id', $worker->id)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();

        // Count responses received
        $totalResponses = AvailabilityBroadcast::where('worker_id', $worker->id)
            ->sum('responses_count');

        return view('worker.availability.index', compact(
            'activeBroadcast',
            'broadcastHistory',
            'totalResponses'
        ));
    }

    /**
     * Create a new availability broadcast
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'available_from' => 'required|date|after_or_equal:now',
            'available_to' => 'required|date|after:available_from',
            'industries' => 'required|array|min:1',
            'industries.*' => 'in:hospitality,healthcare,retail,events,warehouse,professional',
            'preferred_rate' => 'sometimes|numeric|min:0',
            'location_radius' => 'sometimes|integer|min:1|max:100',
            'message' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $worker = Auth::user();

        // Cancel any existing active broadcasts
        AvailabilityBroadcast::where('worker_id', $worker->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        // Create new broadcast
        $broadcast = AvailabilityBroadcast::create([
            'worker_id' => $worker->id,
            'available_from' => $request->available_from,
            'available_to' => $request->available_to,
            'industries' => $request->industries,
            'preferred_rate' => $request->preferred_rate,
            'location_radius' => $request->location_radius ?? 25,
            'message' => $request->message,
            'status' => 'active',
            'responses_count' => 0,
        ]);

        // TODO: Notify relevant businesses
        // event(new WorkerAvailabilityBroadcast($broadcast));

        return redirect()->route('worker.availability.index')
            ->with('success', 'Your availability has been broadcast! Businesses will be notified.');
    }

    /**
     * Cancel an active broadcast
     */
    public function cancel($id)
    {
        $broadcast = AvailabilityBroadcast::findOrFail($id);

        // Verify ownership
        if ($broadcast->worker_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $broadcast->update(['status' => 'cancelled']);

        return redirect()->route('worker.availability.index')
            ->with('success', 'Broadcast cancelled successfully.');
    }

    /**
     * Extend an existing broadcast
     */
    public function extend($id, Request $request)
    {
        $broadcast = AvailabilityBroadcast::findOrFail($id);

        // Verify ownership
        if ($broadcast->worker_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'extend_hours' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $newEndTime = Carbon::parse($broadcast->available_to)->addHours($request->extend_hours);
        $broadcast->update(['available_to' => $newEndTime]);

        return redirect()->route('worker.availability.index')
            ->with('success', "Broadcast extended by {$request->extend_hours} hours.");
    }
}
