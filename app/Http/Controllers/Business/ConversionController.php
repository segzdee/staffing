<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\WorkerConversion;
use App\Services\ConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for worker direct hire conversions.
 * BIZ-010: Direct Hire & Conversion
 */
class ConversionController extends Controller
{
    protected $conversionService;

    public function __construct(ConversionService $conversionService)
    {
        $this->middleware('auth');
        $this->middleware('user_type:business');
        $this->conversionService = $conversionService;
    }

    /**
     * Show conversion dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $business = auth()->user();
        $dashboard = $this->conversionService->getBusinessDashboard($business->id);

        return view('business.conversions.index', [
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Show eligible workers for conversion.
     *
     * @return \Illuminate\View\View
     */
    public function eligibleWorkers()
    {
        $business = auth()->user();
        $eligibleWorkers = $this->conversionService->getEligibleWorkers($business->id);

        return view('business.conversions.eligible-workers', [
            'eligibleWorkers' => $eligibleWorkers,
        ]);
    }

    /**
     * Show conversion details for a specific worker.
     *
     * @param int $workerId
     * @return \Illuminate\View\View
     */
    public function showWorker($workerId)
    {
        $business = auth()->user();
        $eligibility = $this->conversionService->getConversionEligibility($workerId, $business->id);

        return view('business.conversions.worker-details', [
            'workerId' => $workerId,
            'eligibility' => $eligibility,
        ]);
    }

    /**
     * Initiate hire intent.
     *
     * @param Request $request
     * @param int $workerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function initiateHireIntent(Request $request, $workerId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $business = auth()->user();
            $conversion = $this->conversionService->initiateHireIntent(
                $workerId,
                $business->id,
                $request->only('notes')
            );

            return redirect()
                ->route('business.conversions.show', $conversion->id)
                ->with('success', 'Hire intent has been sent to the worker.');
        } catch (\Exception $e) {
            Log::error('Failed to initiate hire intent', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show conversion details.
     *
     * @param int $conversionId
     * @return \Illuminate\View\View
     */
    public function show($conversionId)
    {
        $conversion = WorkerConversion::with(['worker', 'business'])
            ->findOrFail($conversionId);

        // Authorize business owns this conversion
        if ($conversion->business_id !== auth()->id()) {
            abort(403, 'Unauthorized access to conversion.');
        }

        return view('business.conversions.show', [
            'conversion' => $conversion,
        ]);
    }

    /**
     * Process payment for conversion.
     *
     * @param Request $request
     * @param int $conversionId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request, $conversionId)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            $conversion = WorkerConversion::findOrFail($conversionId);

            // Authorize
            if ($conversion->business_id !== auth()->id()) {
                abort(403);
            }

            // Process payment
            $conversion = $this->conversionService->processPayment(
                $conversion,
                $request->only('payment_method', 'transaction_id')
            );

            // Complete conversion
            $conversion = $this->conversionService->completeConversion($conversion);

            return redirect()
                ->route('business.conversions.show', $conversion->id)
                ->with('success', 'Payment processed and conversion completed successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to process conversion payment', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel conversion request.
     *
     * @param int $conversionId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($conversionId)
    {
        try {
            $conversion = WorkerConversion::findOrFail($conversionId);

            // Authorize
            if ($conversion->business_id !== auth()->id()) {
                abort(403);
            }

            $conversion->update([
                'status' => 'cancelled',
                'is_active' => false,
            ]);

            return redirect()
                ->route('business.conversions.index')
                ->with('success', 'Conversion request cancelled.');
        } catch (\Exception $e) {
            Log::error('Failed to cancel conversion', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel conversion.');
        }
    }
}
