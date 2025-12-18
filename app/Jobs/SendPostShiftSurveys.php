<?php

namespace App\Jobs;

use App\Models\ShiftAssignment;
use App\Models\Survey;
use App\Services\FeedbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * QUA-003: SendPostShiftSurveys Job
 *
 * Sends post-shift survey notifications to workers 24 hours after shift completion.
 * Should be scheduled to run every hour.
 */
class SendPostShiftSurveys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Hours to wait after shift completion before sending survey.
     */
    protected int $hoursAfterCompletion = 24;

    /**
     * Execute the job.
     */
    public function handle(FeedbackService $feedbackService): void
    {
        Log::info('Starting SendPostShiftSurveys job');

        try {
            // Get the active post-shift survey
            $survey = $feedbackService->getPostShiftSurvey();

            if (! $survey) {
                Log::info('No active post-shift survey found, skipping');

                return;
            }

            // Find assignments completed ~24 hours ago that haven't received survey
            $eligibleAssignments = $this->getEligibleAssignments($survey);

            $sentCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($eligibleAssignments as $assignment) {
                try {
                    // Skip if worker already responded for this shift
                    if ($survey->hasUserRespondedForShift($assignment->worker_id, $assignment->shift_id)) {
                        $skippedCount++;

                        continue;
                    }

                    // Send the survey notification
                    $feedbackService->sendPostShiftSurvey($assignment->worker, $assignment->shift);
                    $sentCount++;

                    // Mark assignment as survey sent to avoid duplicate notifications
                    $assignment->update(['post_shift_survey_sent_at' => now()]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'assignment_id' => $assignment->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::warning('Failed to send post-shift survey for assignment', [
                        'assignment_id' => $assignment->id,
                        'worker_id' => $assignment->worker_id,
                        'shift_id' => $assignment->shift_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('SendPostShiftSurveys completed', [
                'survey_id' => $survey->id,
                'total_eligible' => $eligibleAssignments->count(),
                'sent' => $sentCount,
                'skipped' => $skippedCount,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('SendPostShiftSurveys failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get shift assignments eligible for post-shift survey.
     *
     * Criteria:
     * - Assignment completed (status = 'completed')
     * - Completed between 24-48 hours ago (to allow window)
     * - Survey not already sent
     * - Worker is active
     */
    protected function getEligibleAssignments(Survey $survey)
    {
        $windowStart = now()->subHours($this->hoursAfterCompletion + 24); // 48 hours ago
        $windowEnd = now()->subHours($this->hoursAfterCompletion); // 24 hours ago

        return ShiftAssignment::query()
            ->where('status', 'completed')
            ->whereNotNull('check_out_time')
            ->whereBetween('check_out_time', [$windowStart, $windowEnd])
            ->whereNull('post_shift_survey_sent_at')
            ->whereHas('worker', function ($query) {
                $query->where('status', 'active')
                    ->whereNotNull('email');
            })
            ->whereHas('shift', function ($query) {
                $query->where('is_demo', false);
            })
            ->whereNotExists(function ($query) use ($survey) {
                // Exclude if survey response already exists
                $query->select('id')
                    ->from('survey_responses')
                    ->whereColumn('survey_responses.user_id', 'shift_assignments.worker_id')
                    ->whereColumn('survey_responses.shift_id', 'shift_assignments.shift_id')
                    ->where('survey_responses.survey_id', $survey->id);
            })
            ->with(['worker', 'shift'])
            ->limit(100) // Process in batches
            ->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPostShiftSurveys job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
