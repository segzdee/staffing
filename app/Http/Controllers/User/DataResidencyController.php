<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataRegion;
use App\Services\DataResidencyService;
use Illuminate\Http\Request;

/**
 * GLO-010: User Data Residency Controller
 *
 * Allows users to view and update their data region preferences.
 */
class DataResidencyController extends Controller
{
    public function __construct(
        protected DataResidencyService $residencyService
    ) {}

    /**
     * Show the user's data residency settings.
     */
    public function index()
    {
        $user = auth()->user();
        $residency = $user->dataResidency;
        $regions = DataRegion::active()->orderBy('name')->get();

        // Check if user selection is allowed
        $allowSelection = config('data_residency.allow_user_selection', true);

        return view('settings.data-residency', [
            'user' => $user,
            'residency' => $residency,
            'regions' => $regions,
            'allowSelection' => $allowSelection,
        ]);
    }

    /**
     * Update the user's data region.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Check if user selection is allowed
        if (! config('data_residency.allow_user_selection', true)) {
            return redirect()->back()
                ->with('error', 'User selection of data region is not allowed.');
        }

        $validated = $request->validate([
            'data_region_id' => 'required|exists:data_regions,id',
            'consent' => 'required|accepted',
        ], [
            'consent.accepted' => 'You must consent to data storage in the selected region.',
        ]);

        $region = DataRegion::findOrFail($validated['data_region_id']);

        // Check if this is actually a change
        $currentResidency = $user->dataResidency;

        if ($currentResidency && $currentResidency->data_region_id == $region->id) {
            // Just update consent if same region
            $currentResidency->recordConsent();
            $currentResidency->markAsUserSelected();

            return redirect()->back()
                ->with('success', 'Your data residency consent has been updated.');
        }

        // Migrate data to new region if changing
        if ($currentResidency) {
            try {
                $this->residencyService->migrateUserData(
                    $user,
                    $region,
                    'User consent' // Legal basis
                );

                $user->dataResidency->markAsUserSelected();
                $user->dataResidency->recordConsent();

                return redirect()->back()
                    ->with('success', 'Your data is being migrated to the '.$region->name.' region. You will be notified when complete.');

            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Unable to migrate your data. Please try again or contact support.');
            }
        } else {
            // First time assignment
            $this->residencyService->assignDataRegion(
                $user,
                $region,
                true, // User selected
                true  // Record consent
            );

            return redirect()->back()
                ->with('success', 'Your data region has been set to '.$region->name.'.');
        }
    }

    /**
     * Record consent for the current region.
     */
    public function consent(Request $request)
    {
        $user = auth()->user();
        $residency = $user->dataResidency;

        if (! $residency) {
            return redirect()->route('settings.data-residency')
                ->with('error', 'No data region assigned. Please select a region first.');
        }

        $residency->recordConsent();

        return redirect()->back()
            ->with('success', 'Your consent has been recorded.');
    }

    /**
     * Get the user's data residency report (for AJAX requests).
     */
    public function report()
    {
        $user = auth()->user();
        $report = $this->residencyService->generateDataResidencyReport($user);

        return response()->json($report);
    }
}
