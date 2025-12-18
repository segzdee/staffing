<?php

namespace App\Console\Commands;

use App\Services\AgencyTierService;
use Illuminate\Console\Command;

class ProcessAgencyTierReview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agency:review-tiers
                            {--dry-run : Show what would happen without making changes}
                            {--agency= : Process only a specific agency by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly tier review for all agencies and update tiers based on performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(AgencyTierService $tierService): int
    {
        $this->info('Starting agency tier review...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $agencyId = $this->option('agency');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($agencyId) {
            return $this->processSingleAgency($tierService, (int) $agencyId, $dryRun);
        }

        return $this->processAllAgencies($tierService, $dryRun);
    }

    /**
     * Process tier review for a single agency.
     */
    protected function processSingleAgency(AgencyTierService $tierService, int $agencyId, bool $dryRun): int
    {
        $agency = \App\Models\User::where('user_type', 'agency')
            ->where('id', $agencyId)
            ->with('agencyProfile.tier')
            ->first();

        if (! $agency) {
            $this->error("Agency with ID {$agencyId} not found.");

            return Command::FAILURE;
        }

        $this->info("Processing agency: {$agency->name} (ID: {$agency->id})");

        $metrics = $tierService->calculateAgencyMetrics($agency);
        $eligibleTier = $tierService->determineEligibleTier($agency);
        $currentTier = $agency->agencyProfile?->tier;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Monthly Revenue', '$'.number_format($metrics['monthly_revenue'], 2)],
                ['Active Workers', $metrics['active_workers']],
                ['Fill Rate', number_format($metrics['fill_rate'], 1).'%'],
                ['Average Rating', number_format($metrics['rating'], 2)],
                ['Total Shifts (90d)', $metrics['total_shifts']],
                ['Completed Shifts', $metrics['completed_shifts']],
            ]
        );

        $this->newLine();
        $this->info('Current Tier: '.($currentTier?->name ?? 'None'));
        $this->info('Eligible Tier: '.($eligibleTier?->name ?? 'None'));

        if (! $dryRun && $eligibleTier) {
            if (! $currentTier) {
                $tierService->upgradeTier($agency, $eligibleTier);
                $this->info("Assigned initial tier: {$eligibleTier->name}");
            } elseif ($eligibleTier->level > $currentTier->level) {
                $tierService->upgradeTier($agency, $eligibleTier);
                $this->info("Upgraded to: {$eligibleTier->name}");
            } elseif ($eligibleTier->level < $currentTier->level) {
                $tierService->downgradeTier($agency, $eligibleTier);
                $this->warn("Downgraded to: {$eligibleTier->name}");
            } else {
                $this->info('No tier change needed.');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Process tier review for all agencies.
     */
    protected function processAllAgencies(AgencyTierService $tierService, bool $dryRun): int
    {
        if ($dryRun) {
            $this->showDryRunResults($tierService);

            return Command::SUCCESS;
        }

        $this->info('Processing tier review for all agencies...');
        $this->newLine();

        $result = $tierService->processMonthlyTierReview();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Reviewed', $result['total_reviewed']],
                ['Upgrades', $result['upgrades']],
                ['Downgrades', $result['downgrades']],
                ['No Change', $result['no_change']],
                ['Errors', $result['errors']],
            ]
        );

        if ($result['errors'] > 0) {
            $this->warn("Review completed with {$result['errors']} errors. Check logs for details.");

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Agency tier review completed successfully!');

        // Show tier distribution
        $this->newLine();
        $this->info('Current Tier Distribution:');
        $distribution = $tierService->getTierDistribution();

        $this->table(
            ['Tier', 'Level', 'Agencies', 'Percentage'],
            collect($distribution)->map(fn ($d) => [
                $d['tier_name'],
                $d['tier_level'],
                $d['agency_count'],
                $d['percentage'].'%',
            ])->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Show dry run results without making changes.
     */
    protected function showDryRunResults(AgencyTierService $tierService): void
    {
        $agencies = \App\Models\User::where('user_type', 'agency')
            ->whereHas('agencyProfile')
            ->with('agencyProfile.tier')
            ->get();

        $results = [];
        $upgrades = 0;
        $downgrades = 0;
        $noChange = 0;

        foreach ($agencies as $agency) {
            $currentTier = $agency->agencyProfile?->tier;
            $eligibleTier = $tierService->determineEligibleTier($agency);
            $metrics = $tierService->calculateAgencyMetrics($agency);

            $action = 'No Change';
            if (! $currentTier && $eligibleTier) {
                $action = 'Initial';
                $upgrades++;
            } elseif ($eligibleTier && $currentTier) {
                if ($eligibleTier->level > $currentTier->level) {
                    $action = 'Upgrade';
                    $upgrades++;
                } elseif ($eligibleTier->level < $currentTier->level) {
                    $action = 'Downgrade';
                    $downgrades++;
                } else {
                    $noChange++;
                }
            } else {
                $noChange++;
            }

            $results[] = [
                $agency->id,
                $agency->name,
                $currentTier?->name ?? 'None',
                $eligibleTier?->name ?? 'None',
                '$'.number_format($metrics['monthly_revenue'], 0),
                $metrics['active_workers'],
                $action,
            ];
        }

        $this->table(
            ['ID', 'Name', 'Current Tier', 'Eligible Tier', 'Revenue', 'Workers', 'Action'],
            $results
        );

        $this->newLine();
        $this->info("Summary: {$upgrades} upgrades, {$downgrades} downgrades, {$noChange} no change");
    }
}
