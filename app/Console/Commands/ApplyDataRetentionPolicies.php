<?php

namespace App\Console\Commands;

use App\Models\DataRetentionPolicy;
use App\Services\PrivacyComplianceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Retention Policy Command
 *
 * Scheduled command to apply data retention policies.
 * Should be run daily via scheduler.
 */
class ApplyDataRetentionPolicies extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'privacy:apply-retention
                            {--dry-run : Preview what would be affected without making changes}
                            {--policy= : Apply only a specific policy by ID}
                            {--force : Skip confirmation for destructive actions}';

    /**
     * The console command description.
     */
    protected $description = 'Apply data retention policies to clean up old data (GDPR/CCPA compliance)';

    public function __construct(
        protected PrivacyComplianceService $privacyService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting data retention policy execution...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $policyId = $this->option('policy');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get policies to execute
        $query = DataRetentionPolicy::where('is_active', true);
        if ($policyId) {
            $query->where('id', $policyId);
        }
        $policies = $query->get();

        if ($policies->isEmpty()) {
            $this->warn('No active retention policies found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$policies->count()} active retention policies.");
        $this->newLine();

        $totalAffected = 0;
        $results = [];

        foreach ($policies as $policy) {
            $this->info("Processing: {$policy->data_type}");
            $this->line("  Model: {$policy->model_class}");
            $this->line("  Retention: {$policy->retention_days} days");
            $this->line("  Action: {$policy->action_label}");

            // Get count of affected records
            $affectedCount = $policy->getAffectedCount();
            $this->line("  Records to process: {$affectedCount}");

            if ($dryRun) {
                // In dry run mode, just show preview
                if ($affectedCount > 0 && $this->option('verbose')) {
                    $preview = $policy->preview(5);
                    $this->line('  Sample records:');
                    foreach ($preview['sample_records'] ?? [] as $record) {
                        $this->line('    - ID: '.($record['id'] ?? 'N/A'));
                    }
                }
                $results[] = [
                    'Policy' => $policy->data_type,
                    'Action' => $policy->action,
                    'Would Affect' => $affectedCount,
                    'Status' => 'Dry Run',
                ];
            } else {
                // Execute the policy
                if ($affectedCount > 0) {
                    // Confirm destructive actions unless --force is used
                    if ($policy->action === DataRetentionPolicy::ACTION_DELETE && ! $this->option('force')) {
                        if (! $this->confirm("  Delete {$affectedCount} records?")) {
                            $this->line('  Skipped.');
                            $results[] = [
                                'Policy' => $policy->data_type,
                                'Action' => $policy->action,
                                'Affected' => 0,
                                'Status' => 'Skipped',
                            ];

                            continue;
                        }
                    }

                    try {
                        $affected = $policy->execute();
                        $totalAffected += $affected;
                        $this->info("  Processed: {$affected} records");
                        $results[] = [
                            'Policy' => $policy->data_type,
                            'Action' => $policy->action,
                            'Affected' => $affected,
                            'Status' => 'Success',
                        ];
                    } catch (\Exception $e) {
                        $this->error('  Error: '.$e->getMessage());
                        Log::error('Retention policy execution failed', [
                            'policy_id' => $policy->id,
                            'error' => $e->getMessage(),
                        ]);
                        $results[] = [
                            'Policy' => $policy->data_type,
                            'Action' => $policy->action,
                            'Affected' => 0,
                            'Status' => 'Failed',
                        ];
                    }
                } else {
                    $this->line('  No records to process.');
                    $results[] = [
                        'Policy' => $policy->data_type,
                        'Action' => $policy->action,
                        'Affected' => 0,
                        'Status' => 'No Data',
                    ];
                }
            }

            $this->newLine();
        }

        // Display summary table
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            array_keys($results[0] ?? []),
            $results
        );

        if (! $dryRun) {
            $this->newLine();
            $this->info("Total records affected: {$totalAffected}");

            Log::info('Data retention policies executed', [
                'total_affected' => $totalAffected,
                'policies_count' => $policies->count(),
            ]);
        }

        return Command::SUCCESS;
    }
}
