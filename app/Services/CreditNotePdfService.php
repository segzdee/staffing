<?php

namespace App\Services;

use App\Models\Refund;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CreditNotePdfService
{
    /**
     * Generate a credit note PDF for a refund.
     */
    public function generate(Refund $refund): string
    {
        // Ensure credit note number exists
        if (! $refund->credit_note_number) {
            $refund->credit_note_number = Refund::generateCreditNoteNumber();
            $refund->save();
        }

        // Load related data
        $refund->load(['business.businessProfile', 'shift', 'shiftPayment']);

        // Prepare data for the PDF
        $data = $this->prepareData($refund);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.credit-note', $data);
        $pdf->setPaper('a4', 'portrait');

        // Define storage path
        $filename = "credit-notes/{$refund->credit_note_number}.pdf";

        // Store the PDF
        Storage::disk('local')->put($filename, $pdf->output());

        // Update refund record
        $refund->update([
            'credit_note_pdf_path' => $filename,
            'credit_note_generated_at' => now(),
        ]);

        return $filename;
    }

    /**
     * Download a credit note PDF.
     */
    public function download(Refund $refund)
    {
        if (! $refund->credit_note_pdf_path) {
            $this->generate($refund);
            $refund->refresh();
        }

        $path = Storage::disk('local')->path($refund->credit_note_pdf_path);

        if (! file_exists($path)) {
            $this->generate($refund);
            $refund->refresh();
            $path = Storage::disk('local')->path($refund->credit_note_pdf_path);
        }

        return response()->download($path, "{$refund->credit_note_number}.pdf");
    }

    /**
     * Stream a credit note PDF.
     */
    public function stream(Refund $refund)
    {
        $refund->load(['business.businessProfile', 'shift', 'shiftPayment']);
        $data = $this->prepareData($refund);

        $pdf = Pdf::loadView('pdf.credit-note', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("{$refund->credit_note_number}.pdf");
    }

    /**
     * Prepare data for the credit note PDF.
     */
    protected function prepareData(Refund $refund): array
    {
        $business = $refund->business;
        $businessProfile = $business?->businessProfile;

        return [
            'refund' => $refund,
            'credit_note_number' => $refund->credit_note_number,
            'issue_date' => $refund->completed_at ?? now(),

            // Company details
            'company' => [
                'name' => config('app.name', 'OvertimeStaff'),
                'address' => config('company.address', '123 Business Street'),
                'city' => config('company.city', 'San Francisco'),
                'state' => config('company.state', 'CA'),
                'zip' => config('company.zip', '94105'),
                'country' => config('company.country', 'United States'),
                'email' => config('company.email', 'billing@overtimestaff.com'),
                'phone' => config('company.phone', '+1 (555) 123-4567'),
                'tax_id' => config('company.tax_id', ''),
            ],

            // Business (customer) details
            'customer' => [
                'name' => $business?->name ?? 'Unknown Business',
                'email' => $business?->email ?? '',
                'company' => $businessProfile?->company_name ?? '',
                'address' => $businessProfile?->address ?? '',
                'city' => $businessProfile?->city ?? '',
                'state' => $businessProfile?->state ?? '',
                'zip' => $businessProfile?->zip_code ?? '',
            ],

            // Refund details
            'original_amount' => $refund->original_amount,
            'refund_amount' => $refund->refund_amount,
            'refund_reason' => $this->formatRefundReason($refund->refund_reason),
            'reason_description' => $refund->reason_description,
            'refund_method' => $this->formatRefundMethod($refund->refund_method),
            'payment_gateway' => ucfirst($refund->payment_gateway ?? 'N/A'),

            // Original payment reference
            'original_payment' => [
                'reference' => $refund->shiftPayment?->transaction_id ?? 'N/A',
                'date' => $refund->shiftPayment?->created_at?->format('M d, Y') ?? 'N/A',
            ],

            // Shift details (if applicable)
            'shift' => $refund->shift ? [
                'title' => $refund->shift->title,
                'date' => $refund->shift->start_time?->format('M d, Y'),
            ] : null,
        ];
    }

    /**
     * Format refund reason for display.
     */
    protected function formatRefundReason(string $reason): string
    {
        return match ($reason) {
            'cancellation_72hr' => 'Shift Cancellation (72+ hours notice)',
            'dispute_resolved' => 'Dispute Resolution',
            'billing_error' => 'Billing Error Correction',
            'overcharge' => 'Overcharge Correction',
            'duplicate_charge' => 'Duplicate Charge',
            'goodwill' => 'Goodwill Adjustment',
            default => ucwords(str_replace('_', ' ', $reason)),
        };
    }

    /**
     * Format refund method for display.
     */
    protected function formatRefundMethod(string $method): string
    {
        return match ($method) {
            'original_payment_method' => 'Original Payment Method',
            'credit_balance' => 'Account Credit',
            'manual' => 'Manual Processing',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }
}
