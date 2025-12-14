<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyClient;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgencyClientController extends Controller
{
    /**
     * Constructor with auth middleware.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:agency']);
    }

    /**
     * Display a listing of the agency's clients.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $clients = AgencyClient::where('agency_id', Auth::id())
            ->with('shifts')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('agency.clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('agency.clients.create');
    }

    /**
     * Store a newly created client.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'default_markup_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $validated['agency_id'] = Auth::id();

        $client = AgencyClient::create($validated);

        return redirect()
            ->route('agency.clients.show', $client)
            ->with('success', 'Client created successfully');
    }

    /**
     * Display the specified client.
     *
     * @param AgencyClient $client
     * @return \Illuminate\View\View
     */
    public function show(AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $client->load('shifts');

        return view('agency.clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     *
     * @param AgencyClient $client
     * @return \Illuminate\View\View
     */
    public function edit(AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('agency.clients.edit', compact('client'));
    }

    /**
     * Update the specified client.
     *
     * @param Request $request
     * @param AgencyClient $client
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'default_markup_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $client->update($validated);

        return redirect()
            ->route('agency.clients.show', $client)
            ->with('success', 'Client updated successfully');
    }

    /**
     * Remove the specified client.
     *
     * @param AgencyClient $client
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $client->delete();

        return redirect()
            ->route('agency.clients.index')
            ->with('success', 'Client deleted successfully');
    }

    /**
     * Show form to post a shift for this client.
     *
     * @param AgencyClient $client
     * @return \Illuminate\View\View
     */
    public function postShiftFor(AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('agency.clients.post-shift', compact('client'));
    }

    /**
     * Store a shift for this client.
     *
     * @param Request $request
     * @param AgencyClient $client
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeShift(Request $request, AgencyClient $client)
    {
        // Ensure agency owns this client
        if ($client->agency_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'role_type' => 'required|string|max:100',
            'description' => 'required|string',
            'industry' => 'required|string|max:100',
            'location_address' => 'required|string|max:255',
            'location_city' => 'required|string|max:100',
            'location_state' => 'required|string|max:100',
            'location_country' => 'required|string|max:100',
            'shift_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1',
            'instant_claim_enabled' => 'nullable|boolean',
        ]);

        // Calculate duration
        $start = \Carbon\Carbon::parse($validated['shift_date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['shift_date'] . ' ' . $validated['end_time']);
        $duration = $start->diffInHours($end, true);

        // Create shift
        $shift = Shift::create([
            'business_id' => $client->agency_id, // Agency acts as business
            'agency_client_id' => $client->id,
            'posted_by_agency_id' => Auth::id(),
            'title' => $validated['title'],
            'role_type' => $validated['role_type'],
            'description' => $validated['description'],
            'industry' => $validated['industry'],
            'location_address' => $validated['location_address'],
            'location_city' => $validated['location_city'],
            'location_state' => $validated['location_state'],
            'location_country' => $validated['location_country'],
            'shift_date' => $validated['shift_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_hours' => $duration,
            'base_rate' => $validated['base_rate'],
            'final_rate' => $validated['base_rate'],
            'required_workers' => $validated['required_workers'],
            'status' => 'open',
            'in_market' => true,
            'market_posted_at' => now(),
            'instant_claim_enabled' => $validated['instant_claim_enabled'] ?? false,
        ]);

        // Calculate costs
        $shift->calculateCosts();

        return redirect()
            ->route('agency.clients.show', $client)
            ->with('success', 'Shift posted successfully');
    }
}
