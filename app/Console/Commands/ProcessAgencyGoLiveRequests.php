<?php

namespace App\Console\Commands;

use App\Models\AgencyProfile;
use App\Models\User;
use App\Services\AgencyGoLiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessAgencyGoLiveRequests Command
 *
 * Processes pending agency go-live requests:
 * - Validates all requirements are met
 * - Auto-approves agencies that meet all criteria
 * - Flags agencies that need manual review
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 *
 * Schedule: Every 4 hours
 */
class ProcessAgencyGoLiveRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agency:process-go-live
                            {--auto-approve : Automatically approve agencies that meet all requirements}
                            {--agency= : Process specific agency by user ID}
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending agency go-live requests and validate requirements';

    /**
     * Minimum compliance score for auto-approval
     */
    protected int $minComplianceScoreForAutoApproval = 80;

    /**
     * Execute the console command.
     */
    public function handle(AgencyGoLiveService $goLiveService): int
    {
        $this->info('Processing agency go-live requests...');

        $autoApprove = $this->option('auto-approve');
        $specificAgency = $this->option('agency');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made.');
        }

        // Get pending agencies
        $query = AgencyProfile::where('verification_status', 'pending_review');

        if ($specificAgency) {
            $query->where('user_id', $specificAgency);
        }

        $pendingAgencies = $query->get();

        if ($pendingAgencies->isEmpty()) {
            $this->info('No pending go-live requests found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingAgencies->count()} pending go-live requests.");

        $stats = [
            'processed' => 0,
            'approved' => 0,
            'flagged' => 0,
            'not_ready' => 0,
            'errors' => 0,
        ];

        foreach ($pendingAgencies as $agency) {
            $this->newLine();
            $this->info("Processing: {$agency->agency_name} (User ID: {$agency->user_id})");

            try {
                $result = $this->processRequest($agency, $goLiveService, $autoApprove, $dryRun);
                $stats[$result]++;
                $stats['processed']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Go-live processing error', [
                    'agency_id' => $agency->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error: {$e->getMessage()}");
            }
        }

        // Output summary
        $this->newLine();
        $this->info('Go-live processing completed.');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total Processed', $stats['processed']],
                ['Approved', $stats['approved']],
                ['Flagged for Review', $stats['flagged']],
                ['Not Ready', $stats['not_ready']],
                ['Errors', $stats['errors']],
            ]
        );

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process a single go-live request.
     */
    protected function processRequest(
        AgencyProfile $agency,
        AgencyGoLiveService $goLiveService,
        bool $autoApprove,
        bool $dryRun
    ): string {
        // Check readiness
        $readiness = $goLiveService->isReadyForGoLive($agency->user_id);

        if (!$readiness['ready']) {
            $this->warn("Not ready: {$agency->agency_name}");

            if (!empty($readiness['blocking_items'])) {
                $this->line('  Blocking items:');
                foreach ($readiness['blocking_items'] as $item) {
                    $this->line("    - {$item['title']}");
                }
            }

            return 'not_ready';
        }

        // Check compliance score
        $complianceScore = $readiness['compliance_score'] ?? 0;
        $this->line("  Compliance Score: {$complianceScore}%");

        // Determine if we can auto-approve
        $canAutoApprove = $autoApprove &&
                          $complianceScore >= $this->minComplianceScoreForAutoApproval;

        if ($canAutoApprove) {
            $this->info("  Auto-approving agency (score: {$complianceScore}%)");

            if (!$dryRun) {
                $result = $goLiveService->activateAgency($agency->user_id);

                if ($result['success']) {
                    $this->info("  APPROVED: {$agency->agency_name}");
                    return 'approved';
                } else {
                    $this->error("  Failed to activate: " . ($result['error'] ?? 'Unknown error'));
                    return 'errors';
                }
            } else {
                $this->info("  Would approve (dry-run)");
                return 'approved';
            }
        }

        // Flag for manual review
        $this->warn("  Flagged for manual review");

        if (!$dryRun) {
            $this->flagForManualReview($agency, $complianceScore);
        }

        return 'flagged';
    }

    /**
     * Flag agency for manual review.
     */
    protected function flagForManualReview(AgencyProfile $agency, float $complianceScore): void
    {
        $notes = $agency->verification_notes ?? '';
        $timestamp = now()->format('Y-m-d H:i');

        $newNote = "[{$timestamp}] Flagged for manual review. Compliance score: {$complianceScore}%. ";

        if ($complianceScore < $this->minComplianceScoreForAutoApproval) {
            $newNote .= "Score below auto-approval threshold ({$this->minComplianceScoreForAutoApproval}%).";
        }

        $agency->update([
            'verification_notes' => trim($notes . "\n" . $newNote),
        ]);

        // Notify admins (could implement a specific notification here)
        Log::info('Agency flagged for manual go-live review', [
            'agency_id' => $agency->id,
            'agency_name' => $agency->agency_name,
            'compliance_score' => $complianceScore,
        ]);
    }
}
