<?php

namespace App\Jobs;

use App\Models\AdminDisputeQueue;
use App\Services\DisputeEscalationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * MonitorDisputeSLAs Job
 *
 * Monitors dispute SLAs and triggers escalations/warnings as needed.
 * Runs hourly via Console Kernel scheduler.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * Actions:
 * 1. Check for disputes approaching SLA breach (80% of time elapsed)
 * 2. Send warning notifications to assigned admins
 * 3. Escalate breached disputes to senior admins
 * 4. Auto-assign unassigned disputes
 */
class MonitorDisputeSLAs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Metrics for this job run.
     *
     * @var array
     */
    protected $metrics = [
        'warnings_sent' => 0,
        'disputes_escalated' => 0,
        'disputes_auto_assigned' => 0,
        'errors' => [],
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @param DisputeEscalationService $escalationService
     * @return void
     */
    public function handle(DisputeEscalationService $escalationService): void
    {
        Log::channel('disputes')->info('MonitorDisputeSLAs job started');

        try {
            // 1. Auto-assign unassigned disputes
            $this->autoAssignUnassignedDisputes($escalationService);

            // 2. Send warnings for disputes approaching SLA breach
            $this->sendBreachWarnings($escalationService);

            // 3. Escalate breached disputes
            $this->escalateBreachedDisputes($escalationService);

            // Log summary
            $this->logJobSummary();
        } catch (\Exception $e) {
            Log::channel('disputes')->error('MonitorDisputeSLAs job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Auto-assign unassigned disputes.
     *
     * @param DisputeEscalationService $escalationService
     * @return void
     */
    protected function autoAssignUnassignedDisputes(DisputeEscalationService $escalationService): void
    {
        $unassigned = AdminDisputeQueue::whereNull('assigned_to_admin')
            ->whereIn('status', ['pending'])
            ->orderBy('filed_at', 'asc')
            ->get();

        foreach ($unassigned as $dispute) {
            try {
                $admin = $escalationService->autoAssignDispute($dispute);

                if ($admin) {
                    $this->metrics['disputes_auto_assigned']++;
                    Log::channel('disputes')->info("Dispute #{$dispute->id} auto-assigned to admin #{$admin->id}");
                }
            } catch (\Exception $e) {
                $this->metrics['errors'][] = [
                    'action' => 'auto_assign',
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ];

                Log::channel('disputes')->warning("Failed to auto-assign dispute #{$dispute->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send SLA breach warnings.
     *
     * @param DisputeEscalationService $escalationService
     * @return void
     */
    protected function sendBreachWarnings(DisputeEscalationService $escalationService): void
    {
        $approachingBreach = $escalationService->getDisputesApproachingBreach();

        foreach ($approachingBreach as $dispute) {
            try {
                $escalationService->sendBreachWarning($dispute);
                $this->metrics['warnings_sent']++;

                Log::channel('disputes')->info("SLA breach warning sent for dispute #{$dispute->id}", [
                    'sla_percentage' => $escalationService->getSLAPercentage($dispute),
                    'remaining_hours' => $escalationService->getRemainingHours($dispute),
                ]);
            } catch (\Exception $e) {
                $this->metrics['errors'][] = [
                    'action' => 'send_warning',
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ];

                Log::channel('disputes')->warning("Failed to send SLA warning for dispute #{$dispute->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Escalate disputes that have breached SLA.
     *
     * @param DisputeEscalationService $escalationService
     * @return void
     */
    protected function escalateBreachedDisputes(DisputeEscalationService $escalationService): void
    {
        $breached = $escalationService->getBreachedDisputes();

        foreach ($breached as $dispute) {
            // Skip if already at max escalation level
            if (($dispute->escalation_level ?? 0) >= 3) {
                Log::channel('disputes')->warning("Dispute #{$dispute->id} at max escalation level but still breached");
                continue;
            }

            // Skip if recently escalated (within last 4 hours) to prevent escalation spam
            if ($dispute->escalated_at && $dispute->escalated_at->diffInHours(now()) < 4) {
                continue;
            }

            try {
                $escalation = $escalationService->escalateDispute($dispute, 'SLA breach - automatic escalation');
                $this->metrics['disputes_escalated']++;

                Log::channel('disputes')->info("Dispute #{$dispute->id} escalated", [
                    'escalation_level' => $dispute->fresh()->escalation_level,
                    'escalated_to' => $escalation->escalated_to_admin_id,
                ]);
            } catch (\Exception $e) {
                $this->metrics['errors'][] = [
                    'action' => 'escalate',
                    'dispute_id' => $dispute->id,
                    'error' => $e->getMessage(),
                ];

                Log::channel('disputes')->warning("Failed to escalate dispute #{$dispute->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Log job summary.
     *
     * @return void
     */
    protected function logJobSummary(): void
    {
        Log::channel('disputes')->info('MonitorDisputeSLAs job completed', [
            'warnings_sent' => $this->metrics['warnings_sent'],
            'disputes_escalated' => $this->metrics['disputes_escalated'],
            'disputes_auto_assigned' => $this->metrics['disputes_auto_assigned'],
            'errors_count' => count($this->metrics['errors']),
        ]);

        if (!empty($this->metrics['errors'])) {
            Log::channel('disputes')->warning('MonitorDisputeSLAs job completed with errors', [
                'errors' => $this->metrics['errors'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('disputes')->error('MonitorDisputeSLAs job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'metrics' => $this->metrics,
        ]);

        // Could notify admins here about the job failure
    }
}
