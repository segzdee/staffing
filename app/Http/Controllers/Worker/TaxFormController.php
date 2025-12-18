<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxFormRequest;
use App\Models\TaxForm;
use App\Services\TaxJurisdictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GLO-002: Tax Form Controller for Workers
 *
 * Handles submission and management of tax forms (W-9, W-8BEN, etc.)
 */
class TaxFormController extends Controller
{
    public function __construct(
        protected TaxJurisdictionService $taxService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the tax forms dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        // Get required forms for this user
        $requiredForms = $this->taxService->getRequiredForms($user);

        // Get user's submitted forms
        $submittedForms = TaxForm::where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Get tax summary for current year
        $currentYear = now()->year;
        $taxSummary = $this->taxService->generateTaxSummary($user, $currentYear);

        return view('worker.tax.index', [
            'requiredForms' => $requiredForms,
            'submittedForms' => $submittedForms,
            'taxSummary' => $taxSummary,
            'formTypes' => TaxForm::getFormTypes(),
            'entityTypes' => TaxForm::getEntityTypes(),
        ]);
    }

    /**
     * Show form to submit a new tax form.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $formType = $request->query('type', TaxForm::TYPE_W9);

        // Check if user already has a valid form of this type
        $existingForm = TaxForm::where('user_id', $user->id)
            ->where('form_type', $formType)
            ->valid()
            ->first();

        if ($existingForm) {
            return redirect()
                ->route('worker.tax.show', $existingForm)
                ->with('warning', 'You already have a valid form of this type.');
        }

        // Get required forms to show which are needed
        $requiredForms = $this->taxService->getRequiredForms($user);

        return view('worker.tax.create', [
            'formType' => $formType,
            'formTypes' => TaxForm::getFormTypes(),
            'entityTypes' => TaxForm::getEntityTypes(),
            'requiredForms' => $requiredForms,
            'user' => $user,
        ]);
    }

    /**
     * Store a new tax form submission.
     */
    public function store(TaxFormRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Check for duplicate active form
        $existingActive = TaxForm::where('user_id', $user->id)
            ->where('form_type', $validated['form_type'])
            ->whereIn('status', [TaxForm::STATUS_PENDING, TaxForm::STATUS_VERIFIED])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existingActive) {
            return back()
                ->withInput()
                ->with('error', 'You already have an active or pending form of this type.');
        }

        // Handle document upload
        $documentUrl = null;
        if ($request->hasFile('document')) {
            $documentUrl = $request->file('document')->store(
                'tax-forms/'.$user->id,
                'private'
            );
        }

        // Build form data based on form type
        $formData = $this->buildFormData($validated);

        // Create the tax form
        $taxForm = TaxForm::create([
            'user_id' => $user->id,
            'form_type' => $validated['form_type'],
            'tax_id' => $validated['tax_id'] ?? null,
            'legal_name' => $validated['legal_name'],
            'business_name' => $validated['business_name'] ?? null,
            'address' => $validated['address'],
            'country_code' => $validated['country_code'],
            'entity_type' => $validated['entity_type'],
            'document_url' => $documentUrl,
            'status' => TaxForm::STATUS_PENDING,
            'submitted_at' => now(),
            'form_data' => $formData,
        ]);

        Log::info('Tax form submitted', [
            'user_id' => $user->id,
            'form_id' => $taxForm->id,
            'form_type' => $taxForm->form_type,
        ]);

        return redirect()
            ->route('worker.tax.show', $taxForm)
            ->with('success', 'Your tax form has been submitted and is pending review.');
    }

    /**
     * Display a specific tax form.
     */
    public function show(TaxForm $taxForm)
    {
        $this->authorize('view', $taxForm);

        return view('worker.tax.show', [
            'taxForm' => $taxForm,
        ]);
    }

    /**
     * Show form to edit a rejected tax form.
     */
    public function edit(TaxForm $taxForm)
    {
        $this->authorize('update', $taxForm);

        // Only allow editing rejected forms
        if ($taxForm->status !== TaxForm::STATUS_REJECTED) {
            return redirect()
                ->route('worker.tax.show', $taxForm)
                ->with('error', 'Only rejected forms can be edited.');
        }

        return view('worker.tax.edit', [
            'taxForm' => $taxForm,
            'formTypes' => TaxForm::getFormTypes(),
            'entityTypes' => TaxForm::getEntityTypes(),
        ]);
    }

    /**
     * Update a rejected tax form.
     */
    public function update(TaxFormRequest $request, TaxForm $taxForm)
    {
        $this->authorize('update', $taxForm);

        // Only allow updating rejected forms
        if ($taxForm->status !== TaxForm::STATUS_REJECTED) {
            return back()->with('error', 'Only rejected forms can be updated.');
        }

        $validated = $request->validated();

        // Handle new document upload
        $documentUrl = $taxForm->document_url;
        if ($request->hasFile('document')) {
            // Delete old document
            if ($documentUrl) {
                Storage::disk('private')->delete($documentUrl);
            }

            $documentUrl = $request->file('document')->store(
                'tax-forms/'.auth()->id(),
                'private'
            );
        }

        // Build form data
        $formData = $this->buildFormData($validated);

        // Update the form
        $taxForm->update([
            'tax_id' => $validated['tax_id'] ?? null,
            'legal_name' => $validated['legal_name'],
            'business_name' => $validated['business_name'] ?? null,
            'address' => $validated['address'],
            'country_code' => $validated['country_code'],
            'entity_type' => $validated['entity_type'],
            'document_url' => $documentUrl,
            'status' => TaxForm::STATUS_PENDING,
            'submitted_at' => now(),
            'rejection_reason' => null,
            'verified_at' => null,
            'verified_by' => null,
            'form_data' => $formData,
        ]);

        Log::info('Tax form resubmitted', [
            'user_id' => auth()->id(),
            'form_id' => $taxForm->id,
            'form_type' => $taxForm->form_type,
        ]);

        return redirect()
            ->route('worker.tax.show', $taxForm)
            ->with('success', 'Your tax form has been resubmitted for review.');
    }

    /**
     * Delete a tax form (only pending/rejected).
     */
    public function destroy(TaxForm $taxForm)
    {
        $this->authorize('delete', $taxForm);

        // Only allow deleting pending or rejected forms
        if (! in_array($taxForm->status, [TaxForm::STATUS_PENDING, TaxForm::STATUS_REJECTED])) {
            return back()->with('error', 'Cannot delete verified or expired forms.');
        }

        // Delete associated document
        if ($taxForm->document_url) {
            Storage::disk('private')->delete($taxForm->document_url);
        }

        $taxForm->delete();

        Log::info('Tax form deleted', [
            'user_id' => auth()->id(),
            'form_id' => $taxForm->id,
            'form_type' => $taxForm->form_type,
        ]);

        return redirect()
            ->route('worker.tax.index')
            ->with('success', 'Tax form has been deleted.');
    }

    /**
     * Download the tax form document.
     */
    public function download(TaxForm $taxForm)
    {
        $this->authorize('view', $taxForm);

        if (! $taxForm->document_url) {
            return back()->with('error', 'No document available for this form.');
        }

        if (! Storage::disk('private')->exists($taxForm->document_url)) {
            return back()->with('error', 'Document file not found.');
        }

        return Storage::disk('private')->download(
            $taxForm->document_url,
            $taxForm->form_type_name.'_'.auth()->user()->name.'.pdf'
        );
    }

    /**
     * Get tax estimate for a worker.
     */
    public function estimate(Request $request)
    {
        $request->validate([
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'country_code' => ['required', 'string', 'size:2'],
            'state_code' => ['nullable', 'string', 'max:10'],
        ]);

        $user = auth()->user();
        $estimate = $this->taxService->estimateTax(
            $user,
            $request->input('gross_amount'),
            strtoupper($request->input('country_code')),
            $request->input('state_code')
        );

        if ($request->wantsJson()) {
            return response()->json($estimate);
        }

        return view('worker.tax.estimate', [
            'estimate' => $estimate,
            'grossAmount' => $request->input('gross_amount'),
        ]);
    }

    /**
     * Get annual tax summary.
     */
    public function summary(Request $request)
    {
        $year = $request->query('year', now()->year);
        $user = auth()->user();

        $summary = $this->taxService->generateTaxSummary($user, (int) $year);

        if ($request->wantsJson()) {
            return response()->json($summary);
        }

        return view('worker.tax.summary', [
            'summary' => $summary,
            'year' => $year,
            'availableYears' => range(now()->year, now()->year - 5),
        ]);
    }

    /**
     * Build form-specific data array.
     */
    protected function buildFormData(array $validated): array
    {
        $formData = [];

        switch ($validated['form_type']) {
            case TaxForm::TYPE_W8BEN:
                $formData = [
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'foreign_tax_id' => $validated['foreign_tax_id'] ?? null,
                    'treaty_country' => $validated['treaty_country'] ?? null,
                    'treaty_article' => $validated['treaty_article'] ?? null,
                    'treaty_rate' => $validated['treaty_rate'] ?? null,
                ];
                break;

            case TaxForm::TYPE_W8BENE:
                $formData = [
                    'entity_name' => $validated['entity_name'] ?? null,
                    'foreign_tax_id' => $validated['foreign_tax_id'] ?? null,
                    'chapter3_status' => $validated['chapter3_status'] ?? null,
                    'fatca_status' => $validated['fatca_status'] ?? null,
                ];
                break;

            case TaxForm::TYPE_P45:
            case TaxForm::TYPE_P60:
                $formData = [
                    'employer_paye_reference' => $validated['employer_paye_reference'] ?? null,
                    'tax_code' => $validated['tax_code'] ?? null,
                ];
                break;

            case TaxForm::TYPE_SELF_ASSESSMENT:
                $formData = [
                    'utr' => $validated['utr'] ?? null,
                    'is_self_employed' => $validated['is_self_employed'] ?? true,
                ];
                break;

            case TaxForm::TYPE_TAX_DECLARATION:
                $formData = [
                    'declaration_text' => $validated['declaration_text'] ?? null,
                ];
                break;
        }

        return $formData;
    }
}
