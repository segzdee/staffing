<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\HealthDeclaration;
use App\Models\Shift;
use App\Models\VaccinationRecord;
use App\Services\HealthProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * SAF-005: COVID/Health Protocols - Health Declaration Controller
 *
 * Handles health declarations and vaccination records for workers.
 */
class HealthDeclarationController extends Controller
{
    protected HealthProtocolService $healthService;

    public function __construct(HealthProtocolService $healthService)
    {
        $this->middleware('auth');
        $this->healthService = $healthService;
    }

    /**
     * Display health declaration form for a shift.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, ?int $shiftId = null)
    {
        $this->authorizeWorker();

        $shift = null;
        $clearanceSummary = null;

        if ($shiftId) {
            $shift = Shift::findOrFail($shiftId);
            $clearanceSummary = $this->healthService->getHealthClearanceSummary(Auth::user(), $shift);
        }

        $recentDeclaration = $this->healthService->getRecentDeclaration(Auth::user());

        return view('worker.health.declaration', compact('shift', 'recentDeclaration', 'clearanceSummary'));
    }

    /**
     * Store a new health declaration.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'shift_id' => 'nullable|exists:shifts,id',
            'fever_free' => 'required|boolean',
            'no_symptoms' => 'required|boolean',
            'no_exposure' => 'required|boolean',
            'fit_for_work' => 'required|boolean',
            'confirm_accuracy' => 'required|accepted',
        ], [
            'confirm_accuracy.accepted' => 'You must confirm that the information provided is accurate.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $shift = $request->shift_id ? Shift::find($request->shift_id) : null;

        $declaration = $this->healthService->submitHealthDeclaration(
            Auth::user(),
            $shift,
            [
                'fever_free' => $request->boolean('fever_free'),
                'no_symptoms' => $request->boolean('no_symptoms'),
                'no_exposure' => $request->boolean('no_exposure'),
                'fit_for_work' => $request->boolean('fit_for_work'),
                'ip_address' => $request->ip(),
            ]
        );

        if ($declaration->isClearedToWork()) {
            return redirect()->route('worker.health.status')
                ->with('success', 'Health declaration submitted successfully. You are cleared to work.');
        }

        return redirect()->route('worker.health.status')
            ->with('warning', 'Health declaration submitted. Based on your responses, you may not be cleared to work certain shifts. Please consult with the business or your healthcare provider.');
    }

    /**
     * Display health status and history.
     *
     * @return \Illuminate\View\View
     */
    public function status()
    {
        $this->authorizeWorker();

        $user = Auth::user();
        $recentDeclaration = $this->healthService->getRecentDeclaration($user);
        $declarationHistory = HealthDeclaration::forUser($user->id)
            ->orderBy('declared_at', 'desc')
            ->take(10)
            ->get();
        $vaccinationRecords = $this->healthService->getVaccinationRecords($user);
        $expiringVaccinations = $this->healthService->getExpiringVaccinations($user);

        return view('worker.health.status', compact(
            'recentDeclaration',
            'declarationHistory',
            'vaccinationRecords',
            'expiringVaccinations'
        ));
    }

    /**
     * Display vaccination records management page.
     *
     * @return \Illuminate\View\View
     */
    public function vaccinations()
    {
        $this->authorizeWorker();

        $user = Auth::user();
        $vaccinationRecords = $this->healthService->getVaccinationRecords($user);
        $expiringVaccinations = $this->healthService->getExpiringVaccinations($user);
        $vaccineTypes = VaccinationRecord::VACCINE_TYPES;

        return view('worker.health.vaccinations', compact(
            'vaccinationRecords',
            'expiringVaccinations',
            'vaccineTypes'
        ));
    }

    /**
     * Store a new vaccination record.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeVaccination(Request $request)
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'vaccine_type' => 'required|string|max:100',
            'vaccination_date' => 'required|date|before_or_equal:today',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'provider_name' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:vaccination_date',
            'is_booster' => 'nullable|boolean',
            'dose_number' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $documentUrl = null;
        if ($request->hasFile('document')) {
            $documentUrl = $request->file('document')->store('vaccination-documents', 'private');
        }

        $this->healthService->addVaccinationRecord(Auth::user(), [
            'vaccine_type' => $request->vaccine_type,
            'vaccination_date' => $request->vaccination_date,
            'document_url' => $documentUrl,
            'provider_name' => $request->provider_name,
            'lot_number' => $request->lot_number,
            'expiry_date' => $request->expiry_date,
            'is_booster' => $request->boolean('is_booster'),
            'dose_number' => $request->dose_number,
        ]);

        return redirect()->route('worker.health.vaccinations')
            ->with('success', 'Vaccination record added successfully. It will be verified by our team.');
    }

    /**
     * Delete a vaccination record.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyVaccination(int $id)
    {
        $this->authorizeWorker();

        $record = VaccinationRecord::where('user_id', Auth::id())
            ->findOrFail($id);

        // Don't allow deletion of verified records
        if ($record->isVerified()) {
            return redirect()->back()
                ->with('error', 'Verified vaccination records cannot be deleted. Please contact support if you need to make changes.');
        }

        $record->delete();

        return redirect()->route('worker.health.vaccinations')
            ->with('success', 'Vaccination record deleted successfully.');
    }

    /**
     * Check health clearance for a specific shift (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkClearance(Request $request, int $shiftId)
    {
        $this->authorizeWorker();

        $shift = Shift::findOrFail($shiftId);
        $user = Auth::user();

        $summary = $this->healthService->getHealthClearanceSummary($user, $shift);

        return response()->json([
            'success' => true,
            'cleared' => $summary['cleared'],
            'summary' => $summary,
        ]);
    }

    /**
     * API endpoint to submit health declaration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiSubmit(Request $request)
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'shift_id' => 'nullable|exists:shifts,id',
            'fever_free' => 'required|boolean',
            'no_symptoms' => 'required|boolean',
            'no_exposure' => 'required|boolean',
            'fit_for_work' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $shift = $request->shift_id ? Shift::find($request->shift_id) : null;

        $declaration = $this->healthService->submitHealthDeclaration(
            Auth::user(),
            $shift,
            [
                'fever_free' => $request->boolean('fever_free'),
                'no_symptoms' => $request->boolean('no_symptoms'),
                'no_exposure' => $request->boolean('no_exposure'),
                'fit_for_work' => $request->boolean('fit_for_work'),
                'ip_address' => $request->ip(),
            ]
        );

        return response()->json([
            'success' => true,
            'cleared' => $declaration->isClearedToWork(),
            'declaration' => [
                'id' => $declaration->id,
                'declared_at' => $declaration->declared_at->toIso8601String(),
                'valid_until' => $declaration->declared_at->addHours(24)->toIso8601String(),
            ],
            'concerns' => $declaration->getHealthConcerns(),
        ]);
    }

    /**
     * Report an outbreak exposure for a shift.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reportExposure(Request $request, int $shiftId)
    {
        $this->authorizeWorker();

        $shift = Shift::findOrFail($shiftId);

        // Verify the user was assigned to this shift
        $assignment = $shift->assignments()
            ->where('worker_id', Auth::id())
            ->whereIn('status', ['assigned', 'checked_in', 'checked_out', 'completed'])
            ->first();

        if (! $assignment) {
            return redirect()->back()
                ->with('error', 'You can only report exposure for shifts you were assigned to.');
        }

        $validator = Validator::make($request->all(), [
            'exposure_details' => 'nullable|string|max:1000',
            'confirm_report' => 'required|accepted',
        ], [
            'confirm_report.accepted' => 'You must confirm that you want to report this exposure.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->healthService->recordOutbreakExposure(
            $shift,
            Auth::user(),
            $request->exposure_details
        );

        return redirect()->route('worker.health.status')
            ->with('success', 'Exposure report submitted successfully. All affected workers have been notified.');
    }

    /**
     * Get PPE requirements information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ppeTypes()
    {
        return response()->json([
            'success' => true,
            'ppe_types' => HealthProtocolService::PPE_TYPES,
        ]);
    }

    /**
     * Authorize that the current user is a worker.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeWorker(): void
    {
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access this page.');
        }
    }
}
