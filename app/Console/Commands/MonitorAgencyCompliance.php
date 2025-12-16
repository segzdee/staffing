<?php

namespace App\Console\Commands;

use App\Models\AgencyProfile;
use App\Models\User;
use App\Services\AgencyComplianceService;
use App\Services\AgencyGoLiveService;
use App\Notifications\AgencyComplianceAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * MonitorAgencyCompliance Command
 *
 * Scheduled task that:
 * - Runs compliance checks on all active agencies
 * - Identifies expiring documents and licenses
 * - Sends alerts for compliance issues
 * - Updates compliance scores
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 *
 * Schedule: Daily at 6:00 AM
 */
class MonitorAgencyCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agency:monitor-compliance
                            {--agency= : Specific agency user ID to check}
                            {--dry-run : Run without sending notifications or updating records}
                            {--force : Force check even if recently checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor agency compliance status, check for expiring documents, and send alerts';

    /**
     * Days before expiration to start warning
     */
    protected int $warningDays = 30;

    /**
     * Days before expiration for urgent warning
     */
    protected int $urgentDays = 7;

    /**
     * Execute the console command.
     */
    public function handle(AgencyComplianceService $complianceService, AgencyGoLiveService $goLiveService): int
    {
        $this->info('Starting agency compliance monitoring...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $specificAgency = $this->option('agency');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made.');
        }

        // Get agencies to check
        $query = AgencyProfile::query();

        if ($specificAgency) {
            $query->where('user_id', $specificAgency);
        } else {
            // Only check active/live agencies unless forced
            if (!$force) {
                $query->where(function ($q) {
                    $q->where('is_live', true)
                      ->orWhere('verification_status', 'approved')
                      ->orWhere('verification_status', 'pending_review');
                });
            }
        }

        $agencies = $query->get();
        $this->info("Found {$agencies->count()} agencies to check.");

        $stats = [
            'checked' => 0,
            'alerts_sent' => 0,
            'score_drops' => 0,
            'expirations_found' => 0,
            'errors' => 0,
        ];

        $progressBar = $this->output->createProgressBar($agencies->count());
        $progressBar->start();

        foreach ($agencies as $agency) {
            try {
                $this->processAgency($agency, $complianceService, $dryRun, $stats);
                $stats['checked']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Compliance monitoring error for agency', [
                    'agency_id' => $agency->id,
                    'user_id' => $agency->user_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error processing agency {$agency->agency_name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Output summary
        $this->info('Compliance monitoring completed.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Agencies Checked', $stats['checked']],
                ['Alerts Sent', $stats['alerts_sent']],
                ['Score Drops Detected', $stats['score_drops']],
                ['Expirations Found', $stats['expirations_found']],
                ['Errors', $stats['errors']],
            ]
        );

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process a single agency for compliance checks.
     */
    protected function processAgency(
        AgencyProfile $agency,
        AgencyComplianceService $complianceService,
        bool $dryRun,
        array &$stats
    ): void {
        $user = User::find($agency->user_id);

        if (!$user) {
            $this->warn("User not found for agency {$agency->agency_name}");
            return;
        }

        // Store previous score for comparison
        $previousScore = $agency->compliance_score ?? 0;

        // Run compliance check
        $complianceResult = $complianceService->calculateComplianceScore($agency);
        $currentScore = $complianceResult['score'];

        // Check for significant score drop
        if ($previousScore > 0 && ($previousScore - $currentScore) >= 10) {
            $stats['score_drops']++;

            if (!$dryRun) {
                $user->notify(new AgencyComplianceAlert(
                    $agency,
                    AgencyComplianceAlert::ALERT_SCORE_DROPPED,
                    [
                        'previous_score' => $previousScore,
                        'current_score' => $currentScore,
                        'threshold' => 60,
                    ]
                ));
                $stats['alerts_sent']++;
            }

            $this->info("Score drop detected for {$agency->agency_name}: {$previousScore}% -> {$currentScore}%");
        }

        // Check for expiring items
        $this->checkExpiringItems($agency, $user, $complianceResult, $dryRun, $stats);

        // Check for expired items
        $this->checkExpiredItems($agency, $user, $dryRun, $stats);
    }

    /**
     * Check for items expiring soon.
     */
    protected function checkExpiringItems(
        AgencyProfile $agency,
        User $user,
        array $complianceResult,
        bool $dryRun,
        array &$stats
    ): void {
        if (empty($complianceResult['expires_soon'])) {
            return;
        }

        foreach ($complianceResult['expires_soon'] as $expiring) {
            $stats['expirations_found']++;

            $daysRemaining = $expiring['days_remaining'];
            $documentType = $expiring['type'];

            // Determine if this is an urgent warning
            $isUrgent = $daysRemaining <= $this->urgentDays;

            // Check if we already sent a warning recently (avoid spam)
            $cacheKey = "compliance_warning_{$agency->id}_{$documentType}_{$daysRemaining}";
            if (!$dryRun && cache()->has($cacheKey)) {
                continue;
            }

            if (!$dryRun) {
                $user->notify(new AgencyComplianceAlert(
                    $agency,
                    AgencyComplianceAlert::ALERT_DOCUMENT_EXPIRING,
                    [
                        'document_type' => $documentType,
                        'days_remaining' => $daysRemaining,
                        'expires_at' => $expiring['expires_at'],
                    ]
                ));
                $stats['alerts_sent']++;

                // Cache to prevent duplicate warnings (7 days for urgent, 14 for standard)
                $cacheDuration = $isUrgent ? 7 * 24 * 60 : 14 * 24 * 60;
                cache()->put($cacheKey, true, now()->addMinutes($cacheDuration));
            }

            $urgentLabel = $isUrgent ? ' [URGENT]' : '';
            $this->info("Expiring{$urgentLabel}: {$agency->agency_name} - {$documentType} in {$daysRemaining} days");
        }
    }

    /**
     * Check for expired items.
     */
    protected function checkExpiredItems(
        AgencyProfile $agency,
        User $user,
        bool $dryRun,
        array &$stats
    ): void {
        // Check license expiration
        if ($agency->isLicenseExpired()) {
            $cacheKey = "compliance_expired_{$agency->id}_license";

            if (!$dryRun && !cache()->has($cacheKey)) {
                $user->notify(new AgencyComplianceAlert(
                    $agency,
                    AgencyComplianceAlert::ALERT_LICENSE_EXPIRED
                ));
                $stats['alerts_sent']++;

                // Only notify once per week for expired items
                cache()->put($cacheKey, true, now()->addWeek());

                // Consider restricting the agency
                $this->restrictAgencyIfNeeded($agency, 'license_expired', $dryRun);
            }

            $this->warn("EXPIRED: {$agency->agency_name} - Business License");
        }
    }

    /**
     * Restrict agency if compliance issues are severe.
     */
    protected function restrictAgencyIfNeeded(
        AgencyProfile $agency,
        string $reason,
        bool $dryRun
    ): void {
        if ($dryRun) {
            $this->warn("Would restrict agency {$agency->agency_name} due to: {$reason}");
            return;
        }

        // Update agency status
        $agency->update([
            'verification_status' => 'restricted',
            'verification_notes' => "Automatically restricted due to: {$reason}. Please update your compliance documents.",
        ]);

        Log::warning('Agency restricted due to compliance issue', [
            'agency_id' => $agency->id,
            'agency_name' => $agency->agency_name,
            'reason' => $reason,
        ]);

        $this->error("Agency {$agency->agency_name} has been restricted due to: {$reason}");
    }
}
