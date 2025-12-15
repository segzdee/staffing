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

class GenerateDailyReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    protected $date;

    /**
     * Create a new job instance.
     */
    public function __construct($date = null)
    {
        $this->date = $date ?? Carbon::yesterday()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(ComplianceReportService $complianceReportService)
    {
        try {
            Log::info("Generating daily reconciliation report for {$this->date}");

            $report = $complianceReportService->generateDailyReconciliation($this->date);

            Log::info("Daily reconciliation report generated successfully", [
                'report_id' => $report->id,
                'date' => $this->date,
                'file_path' => $report->file_path,
            ]);

            // Optionally, send notification to admins
            // $this->notifyAdmins($report);

        } catch (\Exception $e) {
            Log::error("Failed to generate daily reconciliation report", [
                'date' => $this->date,
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
        Log::error("Daily reconciliation job failed after all retries", [
            'date' => $this->date,
            'error' => $exception->getMessage(),
        ]);

        // Notify administrators about the failure
    }
}
