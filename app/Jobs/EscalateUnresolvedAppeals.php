<?php

namespace App\Jobs;

use App\Models\PenaltyAppeal;
use App\Models\User;
use App\Notifications\SeniorAdminAppealEscalationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to escalate unresolved appeals that have been pending for more than 7 days.
 * FIN-006: Worker Penalty Appeal Notifications - Escalation Workflow
 *
 * Runs daily at 10 AM to check for appeals that need escalation.
 */
class EscalateUnresolvedAppeals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of days after which an appeal is considered overdue.
     */
    protected int $escalationThresholdDays;

    /**
     * Create a new job instance.
     *
     * @param int $escalationThresholdDays
     */
    public function __construct(int $escalationThresholdDays = 7)
    {
        $this->escalationThresholdDays = $escalationThresholdDays;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('EscalateUnresolvedAppeals: Starting escalation check', [
            'threshold_days' => $this->escalationThresholdDays,
        ]);

        // Find appeals that are pending or under review and older than threshold
        $overdueAppeals = PenaltyAppeal::with(['penalty', 'worker'])
            ->whereIn('status', ['pending', 'under_review'])
            ->where('submitted_at', '<=', now()->subDays($this->escalationThresholdDays))
            ->orderBy('submitted_at', 'asc')
            ->get();

        if ($overdueAppeals->isEmpty()) {
            Log::info('EscalateUnresolvedAppeals: No overdue appeals found');
            return;
        }

        Log::info('EscalateUnresolvedAppeals: Found overdue appeals', [
            'count' => $overdueAppeals->count(),
            'appeal_ids' => $overdueAppeals->pluck('id')->toArray(),
        ]);

        // Get senior admins to notify
        $seniorAdmins = $this->getSeniorAdmins();

        if ($seniorAdmins->isEmpty()) {
            Log::warning('EscalateUnresolvedAppeals: No senior admins found to notify');
            return;
        }

        // Send escalation notification to each senior admin
        foreach ($seniorAdmins as $admin) {
            try {
                $admin->notify(new SeniorAdminAppealEscalationNotification(
                    $overdueAppeals,
                    $this->escalationThresholdDays
                ));

                Log::info('EscalateUnresolvedAppeals: Escalation notification sent', [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'appeal_count' => $overdueAppeals->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('EscalateUnresolvedAppeals: Failed to send notification', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark appeals as escalated (add tracking)
        $this->markAppealsAsEscalated($overdueAppeals);

        Log::info('EscalateUnresolvedAppeals: Escalation complete', [
            'appeals_escalated' => $overdueAppeals->count(),
            'admins_notified' => $seniorAdmins->count(),
        ]);
    }

    /**
     * Get senior admins who should receive escalation notifications.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSeniorAdmins()
    {
        // Get users with admin role and full_access permissions
        // or users with specific senior admin designation
        return User::where('role', 'admin')
            ->where(function ($query) {
                $query->where('permissions', 'full_access')
                    ->orWhere('permissions', 'like', '%appeals%')
                    ->orWhere('permissions', 'like', '%senior_admin%');
            })
            ->where('status', 'active')
            ->get();
    }

    /**
     * Mark appeals as escalated for tracking purposes.
     *
     * @param \Illuminate\Support\Collection $appeals
     * @return void
     */
    protected function markAppealsAsEscalated($appeals): void
    {
        $now = now()->toDateTimeString();

        foreach ($appeals as $appeal) {
            // Add escalation tracking to admin_notes
            $existingNotes = $appeal->admin_notes ?? '';
            $escalationNote = "[ESCALATED {$now}] Appeal pending > {$this->escalationThresholdDays} days - Senior admin notification sent.";

            // Only add note if not already escalated today
            if (!str_contains($existingNotes, "[ESCALATED {$now->format('Y-m-d')}")) {
                $appeal->update([
                    'admin_notes' => trim($existingNotes . "\n\n" . $escalationNote),
                ]);
            }
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
        Log::error('EscalateUnresolvedAppeals: Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
