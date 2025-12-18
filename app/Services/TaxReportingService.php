<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\TaxJurisdiction;
use App\Models\TaxReport;
use App\Models\TaxWithholding;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * FIN-007: Tax Reporting Service
 *
 * Handles tax report generation, 1099-NEC creation, P60 generation,
 * and tax document management for workers.
 */
class TaxReportingService
{
    protected TaxJurisdictionService $taxJurisdictionService;

    public function __construct(TaxJurisdictionService $taxJurisdictionService)
    {
        $this->taxJurisdictionService = $taxJurisdictionService;
    }

    /**
     * Generate an annual tax report for a user.
     */
    public function generateAnnualReport(User $user, int $year): TaxReport
    {
        $earnings = $this->calculateYearlyEarnings($user, $year);
        $withholdings = $this->getWithholdingSummary($user, $year);

        // Determine report type based on user's country
        $countryCode = $this->getUserCountryCode($user);
        $reportType = TaxReport::getPrimaryReportTypeForCountry($countryCode);

        // Check if report already exists
        $report = TaxReport::where('user_id', $user->id)
            ->where('tax_year', $year)
            ->where('report_type', $reportType)
            ->first();

        if (! $report) {
            $report = new TaxReport([
                'user_id' => $user->id,
                'tax_year' => $year,
                'report_type' => $reportType,
            ]);
        }

        // Update report data
        $report->fill([
            'total_earnings' => $earnings['total_gross'],
            'total_fees' => $earnings['total_fees'],
            'total_taxes_withheld' => $withholdings['total_withheld'],
            'total_shifts' => $earnings['shift_count'],
            'monthly_breakdown' => $earnings['monthly'],
            'jurisdiction_breakdown' => $withholdings['by_jurisdiction'],
            'status' => TaxReport::STATUS_DRAFT,
        ]);

        $report->save();

        return $report;
    }

    /**
     * Generate 1099-NEC form for US workers.
     */
    public function generate1099NEC(User $user, int $year): TaxReport
    {
        $earnings = $this->calculateYearlyEarnings($user, $year);
        $withholdings = $this->getWithholdingSummary($user, $year);

        // Check if user meets the $600 threshold
        if ($earnings['total_gross'] < TaxReport::US_1099_THRESHOLD) {
            Log::info("User {$user->id} does not meet 1099-NEC threshold for year {$year}", [
                'earnings' => $earnings['total_gross'],
                'threshold' => TaxReport::US_1099_THRESHOLD,
            ]);
        }

        // Check if report already exists
        $report = TaxReport::where('user_id', $user->id)
            ->where('tax_year', $year)
            ->where('report_type', TaxReport::TYPE_1099_NEC)
            ->first();

        if (! $report) {
            $report = new TaxReport([
                'user_id' => $user->id,
                'tax_year' => $year,
                'report_type' => TaxReport::TYPE_1099_NEC,
            ]);
        }

        $report->fill([
            'total_earnings' => $earnings['total_gross'],
            'total_fees' => $earnings['total_fees'],
            'total_taxes_withheld' => $withholdings['federal_total'] ?? 0,
            'total_shifts' => $earnings['shift_count'],
            'monthly_breakdown' => $earnings['monthly'],
            'jurisdiction_breakdown' => $withholdings['by_jurisdiction'],
            'status' => TaxReport::STATUS_DRAFT,
        ]);

        $report->save();

        // Generate PDF
        $documentUrl = $this->generatePDFReport($report);
        $report->markAsGenerated($documentUrl);

        return $report;
    }

    /**
     * Generate P60 form for UK workers.
     */
    public function generateP60(User $user, int $year): TaxReport
    {
        $earnings = $this->calculateYearlyEarnings($user, $year);
        $withholdings = $this->getWithholdingSummary($user, $year);

        // Check if report already exists
        $report = TaxReport::where('user_id', $user->id)
            ->where('tax_year', $year)
            ->where('report_type', TaxReport::TYPE_P60)
            ->first();

        if (! $report) {
            $report = new TaxReport([
                'user_id' => $user->id,
                'tax_year' => $year,
                'report_type' => TaxReport::TYPE_P60,
            ]);
        }

        $report->fill([
            'total_earnings' => $earnings['total_gross'],
            'total_fees' => $earnings['total_fees'],
            'total_taxes_withheld' => $withholdings['total_withheld'],
            'total_shifts' => $earnings['shift_count'],
            'monthly_breakdown' => $earnings['monthly'],
            'jurisdiction_breakdown' => $withholdings['by_jurisdiction'],
            'status' => TaxReport::STATUS_DRAFT,
        ]);

        $report->save();

        // Generate PDF
        $documentUrl = $this->generatePDFReport($report);
        $report->markAsGenerated($documentUrl);

        return $report;
    }

    /**
     * Calculate yearly earnings for a user.
     */
    public function calculateYearlyEarnings(User $user, int $year): array
    {
        $payments = ShiftPayment::where('worker_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereIn('status', ['completed', 'released', 'paid'])
            ->with('shiftAssignment.shift')
            ->get();

        $totalGross = 0;
        $totalFees = 0;
        $totalNet = 0;
        $monthlyBreakdown = array_fill(1, 12, [
            'gross' => 0,
            'fees' => 0,
            'net' => 0,
            'shifts' => 0,
        ]);

        foreach ($payments as $payment) {
            $month = $payment->created_at->month;

            // Convert from cents if stored as integer
            $gross = $this->normalizeAmount($payment->gross_amount ?? $payment->amount ?? 0);
            $fees = $this->normalizeAmount($payment->platform_fee ?? 0);
            $net = $this->normalizeAmount($payment->net_amount ?? ($gross - $fees));

            $totalGross += $gross;
            $totalFees += $fees;
            $totalNet += $net;

            $monthlyBreakdown[$month]['gross'] += $gross;
            $monthlyBreakdown[$month]['fees'] += $fees;
            $monthlyBreakdown[$month]['net'] += $net;
            $monthlyBreakdown[$month]['shifts']++;
        }

        // Convert monthly breakdown to named array
        $months = [];
        foreach ($monthlyBreakdown as $monthNum => $data) {
            $months[] = [
                'month' => $monthNum,
                'month_name' => date('F', mktime(0, 0, 0, $monthNum, 1)),
                'gross' => round($data['gross'], 2),
                'fees' => round($data['fees'], 2),
                'net' => round($data['net'], 2),
                'shifts' => $data['shifts'],
            ];
        }

        return [
            'total_gross' => round($totalGross, 2),
            'total_fees' => round($totalFees, 2),
            'total_net' => round($totalNet, 2),
            'shift_count' => $payments->count(),
            'monthly' => $months,
            'year' => $year,
        ];
    }

    /**
     * Get withholding summary for a user for a specific year.
     */
    public function getWithholdingSummary(User $user, int $year): array
    {
        $withholdings = TaxWithholding::forUser($user->id)
            ->forYear($year)
            ->with('taxJurisdiction')
            ->get();

        $byJurisdiction = [];
        $totalFederal = 0;
        $totalState = 0;
        $totalSocialSecurity = 0;
        $totalMedicare = 0;
        $totalOther = 0;
        $totalWithheld = 0;

        foreach ($withholdings as $withholding) {
            $jurisdictionId = $withholding->tax_jurisdiction_id;
            $jurisdictionName = $withholding->taxJurisdiction?->name ?? 'Unknown';

            if (! isset($byJurisdiction[$jurisdictionId])) {
                $byJurisdiction[$jurisdictionId] = [
                    'jurisdiction_id' => $jurisdictionId,
                    'jurisdiction_name' => $jurisdictionName,
                    'country_code' => $withholding->taxJurisdiction?->country_code,
                    'gross_amount' => 0,
                    'federal' => 0,
                    'state' => 0,
                    'social_security' => 0,
                    'medicare' => 0,
                    'other' => 0,
                    'total_withheld' => 0,
                ];
            }

            $byJurisdiction[$jurisdictionId]['gross_amount'] += $withholding->gross_amount;
            $byJurisdiction[$jurisdictionId]['federal'] += $withholding->federal_withholding;
            $byJurisdiction[$jurisdictionId]['state'] += $withholding->state_withholding;
            $byJurisdiction[$jurisdictionId]['social_security'] += $withholding->social_security;
            $byJurisdiction[$jurisdictionId]['medicare'] += $withholding->medicare;
            $byJurisdiction[$jurisdictionId]['other'] += $withholding->other_withholding;
            $byJurisdiction[$jurisdictionId]['total_withheld'] += $withholding->total_withheld;

            $totalFederal += $withholding->federal_withholding;
            $totalState += $withholding->state_withholding;
            $totalSocialSecurity += $withholding->social_security;
            $totalMedicare += $withholding->medicare;
            $totalOther += $withholding->other_withholding;
            $totalWithheld += $withholding->total_withheld;
        }

        return [
            'federal_total' => round($totalFederal, 2),
            'state_total' => round($totalState, 2),
            'social_security_total' => round($totalSocialSecurity, 2),
            'medicare_total' => round($totalMedicare, 2),
            'other_total' => round($totalOther, 2),
            'total_withheld' => round($totalWithheld, 2),
            'by_jurisdiction' => array_values($byJurisdiction),
            'record_count' => $withholdings->count(),
        ];
    }

    /**
     * Record withholding for a shift payment.
     */
    public function recordWithholding(User $user, Shift $shift, array $withholdings): TaxWithholding
    {
        // Get jurisdiction from shift location
        $jurisdiction = $this->taxJurisdictionService->getJurisdiction(
            $shift->location_country ?? 'US',
            $shift->location_state
        );

        if (! $jurisdiction) {
            // Create a default jurisdiction or use US federal
            $jurisdiction = TaxJurisdiction::where('country_code', 'US')
                ->whereNull('state_code')
                ->first();

            if (! $jurisdiction) {
                throw new \RuntimeException('No tax jurisdiction available for recording withholding');
            }
        }

        return TaxWithholding::createFromCalculation(
            $user,
            $shift,
            $jurisdiction,
            $withholdings,
            $shift->shift_date,
            $shift->shift_date
        );
    }

    /**
     * Generate PDF report for a tax report.
     */
    public function generatePDFReport(TaxReport $report): string
    {
        $user = $report->user;

        // Determine which template to use based on report type
        $template = match ($report->report_type) {
            TaxReport::TYPE_1099_NEC => 'tax.1099-nec',
            TaxReport::TYPE_P60 => 'tax.p60',
            default => 'tax.annual-statement',
        };

        // Get payer information (platform)
        $payerInfo = $this->getPayerInfo();

        // Get recipient information
        $recipientInfo = $this->getRecipientInfo($user);

        // Generate PDF
        $pdf = Pdf::loadView($template, [
            'report' => $report,
            'user' => $user,
            'payer' => $payerInfo,
            'recipient' => $recipientInfo,
            'year' => $report->tax_year,
            'generatedAt' => now(),
        ]);

        // Generate filename
        $filename = sprintf(
            'tax-reports/%d/%s/%s_%d_%s.pdf',
            $report->tax_year,
            $user->id,
            $report->report_type,
            $report->tax_year,
            now()->format('Ymd_His')
        );

        // Store PDF
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Email tax report to user.
     */
    public function emailTaxReport(TaxReport $report): void
    {
        $user = $report->user;

        if (! $report->document_url) {
            throw new \RuntimeException('Cannot email report without generated document');
        }

        // Get the PDF content
        $pdfContent = Storage::disk('local')->get($report->document_url);

        Mail::send('emails.tax-report', [
            'user' => $user,
            'report' => $report,
            'year' => $report->tax_year,
        ], function ($message) use ($user, $report, $pdfContent) {
            $message->to($user->email, $user->name)
                ->subject(sprintf('%s for Tax Year %d', $report->report_type_name, $report->tax_year))
                ->attachData($pdfContent, sprintf('%s_%d.pdf', $report->report_type, $report->tax_year), [
                    'mime' => 'application/pdf',
                ]);
        });

        $report->markAsSent();

        Log::info("Tax report emailed to user {$user->id}", [
            'report_id' => $report->id,
            'report_type' => $report->report_type,
            'tax_year' => $report->tax_year,
        ]);
    }

    /**
     * Get all reports for a specific tax year.
     */
    public function getReportsForYear(int $year): Collection
    {
        return TaxReport::forYear($year)
            ->with('user')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * Mark a report as sent.
     */
    public function markAsSent(TaxReport $report): void
    {
        $report->markAsSent();
    }

    /**
     * Generate reports for all eligible workers for a tax year.
     */
    public function generateBulkReports(int $year, ?string $reportType = null): array
    {
        $results = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Get all workers who had payments in this year
        $workerIds = ShiftPayment::whereYear('created_at', $year)
            ->whereIn('status', ['completed', 'released', 'paid'])
            ->distinct()
            ->pluck('worker_id');

        foreach ($workerIds as $workerId) {
            try {
                $user = User::find($workerId);
                if (! $user) {
                    continue;
                }

                $countryCode = $this->getUserCountryCode($user);
                $type = $reportType ?? TaxReport::getPrimaryReportTypeForCountry($countryCode);

                // Check for 1099 threshold
                if ($type === TaxReport::TYPE_1099_NEC) {
                    $earnings = $this->calculateYearlyEarnings($user, $year);
                    if ($earnings['total_gross'] < TaxReport::US_1099_THRESHOLD) {
                        $results['skipped']++;

                        continue;
                    }
                }

                // Generate the appropriate report
                $report = match ($type) {
                    TaxReport::TYPE_1099_NEC => $this->generate1099NEC($user, $year),
                    TaxReport::TYPE_P60 => $this->generateP60($user, $year),
                    default => $this->generateAnnualReport($user, $year),
                };

                $results['generated']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $workerId,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to generate tax report for user {$workerId}", [
                    'year' => $year,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if a user meets 1099 threshold.
     */
    public function meets1099Threshold(User $user, int $year): bool
    {
        $earnings = $this->calculateYearlyEarnings($user, $year);

        return $earnings['total_gross'] >= TaxReport::US_1099_THRESHOLD;
    }

    /**
     * Get workers who meet 1099 threshold for a year.
     */
    public function getWorkersMeeting1099Threshold(int $year): Collection
    {
        // Get all workers with payments in the year
        $workers = User::whereHas('shiftPaymentsReceived', function ($query) use ($year) {
            $query->whereYear('created_at', $year)
                ->whereIn('status', ['completed', 'released', 'paid']);
        })->get();

        return $workers->filter(function ($worker) use ($year) {
            return $this->meets1099Threshold($worker, $year);
        });
    }

    /**
     * Get payer (platform) information for tax forms.
     */
    protected function getPayerInfo(): array
    {
        return [
            'name' => config('app.name', 'OvertimeStaff'),
            'address' => config('overtimestaff.company.address', ''),
            'city' => config('overtimestaff.company.city', ''),
            'state' => config('overtimestaff.company.state', ''),
            'zip' => config('overtimestaff.company.zip', ''),
            'country' => config('overtimestaff.company.country', 'US'),
            'ein' => config('overtimestaff.company.ein', ''),
            'phone' => config('overtimestaff.company.phone', ''),
        ];
    }

    /**
     * Get recipient (worker) information for tax forms.
     */
    protected function getRecipientInfo(User $user): array
    {
        $profile = $user->workerProfile;

        return [
            'name' => $user->name,
            'address' => $profile?->address ?? '',
            'city' => $profile?->city ?? '',
            'state' => $profile?->state ?? $profile?->state_code ?? '',
            'zip' => $profile?->postal_code ?? $profile?->zip ?? '',
            'country' => $profile?->country_code ?? $profile?->country ?? 'US',
            'ssn_last4' => $profile?->ssn_last4 ?? '****',
            'tin' => $profile?->tax_id ?? '',
        ];
    }

    /**
     * Get user's country code.
     */
    protected function getUserCountryCode(User $user): string
    {
        if ($user->workerProfile?->country_code) {
            return $user->workerProfile->country_code;
        }

        if ($user->businessProfile?->country_code) {
            return $user->businessProfile->country_code;
        }

        return $user->getCountry() ?? 'US';
    }

    /**
     * Normalize amount (handle cents vs dollars).
     */
    protected function normalizeAmount($amount): float
    {
        // If amount is likely stored as cents (> 1000 for a typical shift payment)
        // and the value seems unreasonably high, divide by 100
        if (is_numeric($amount) && $amount > 10000) {
            return (float) $amount / 100;
        }

        return (float) $amount;
    }
}
