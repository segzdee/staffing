<?php

namespace App\Jobs;

use App\Services\ComplianceReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyVATReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes
    public $tries = 3;

    protected $month;
    protected $year;

    /**
     * Create a new job instance.
     */
    public function __construct($month = null, $year = null)
    {
        // Default to last month if not specified
        $lastMonth = Carbon::now()->subMonth();
        $this->month = $month ?? $lastMonth->month;
        $this->year = $year ?? $lastMonth->year;
    }

    /**
     * Execute the job.
     */
    public function handle(ComplianceReportService $complianceReportService)
    {
        try {
            Log::info("Generating monthly VAT report", [
                'month' => $this->month,
                'year' => $this->year,
            ]);

            $report = $complianceReportService->generateMonthlyVATReport($this->month, $this->year);

            Log::info("Monthly VAT report generated successfully", [
                'report_id' => $report->id,
                'month' => $this->month,
                'year' => $this->year,
                'file_path' => $report->file_path,
            ]);

            // Optionally, send notification to finance team
            // $this->notifyFinanceTeam($report);

        } catch (\Exception $e) {
            Log::error("Failed to generate monthly VAT report", [
                'month' => $this->month,
                'year' => $this->year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Monthly VAT report job failed after all retries", [
            'month' => $this->month,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);

        // Notify administrators about the failure
    }
}
