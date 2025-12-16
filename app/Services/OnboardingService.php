<?php

namespace App\Services;

use App\Models\User;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\WorkerProfile;
use App\Notifications\OnboardingStepCompletedNotification;
use App\Notifications\OnboardingReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Onboarding Service
 * STAFF-REG-010: Onboarding Progress Tracking
 *
 * Manages worker onboarding progress and step completion.
 */
class OnboardingService
{
    /**
     * Initialize onboarding for a new worker.
     */
    public function initializeOnboarding(User $worker): array
    {
        if ($worker->user_type !== 'worker') {
            return [
                'success' => false,
                'error' => 'User is not a worker.',
            ];
        }

        DB::beginTransaction();

        try {
            // Get all steps for workers
            $steps = OnboardingStep::active()
                ->forUserType('worker')
                ->ordered()
                ->get();

            $progressRecords = [];

            foreach ($steps as $step) {
                $progress = OnboardingProgress::getOrCreate($worker->id, $step->id);
                $progressRecords[] = $progress;
            }

            // Auto-complete account_created step
            $accountCreatedStep = OnboardingStep::findByStepId('account_created');
            if ($accountCreatedStep) {
                $this->completeStep($worker, 'account_created', [
                    'completed_by' => 'system',
                    'auto' => true,
                ]);
            }

            // Update worker profile tracking
            if ($worker->workerProfile) {
                $worker->workerProfile->update([
                    'onboarding_started_at' => now(),
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'steps_initialized' => count($progressRecords),
                'progress' => $this->calculateProgress($worker),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update progress for a specific step.
     */
    public function updateProgress(User $worker, string $stepId, array $data = []): array
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step) {
            return [
                'success' => false,
                'error' => 'Step not found.',
            ];
        }

        $progress = OnboardingProgress::getOrCreate($worker->id, $step->id);

        // Update progress
        $percentage = $data['percentage'] ?? $progress->progress_percentage;

        if (isset($data['increment'])) {
            $percentage = min(100, $progress->progress_percentage + $data['increment']);
        }

        $progress->updateProgress($percentage, $data['data'] ?? null);

        // Update profile last step timestamp
        if ($worker->workerProfile) {
            $worker->workerProfile->update([
                'onboarding_last_step_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'progress' => $progress->fresh(),
            'overall_progress' => $this->calculateProgress($worker),
        ];
    }

    /**
     * Complete a specific step.
     */
    public function completeStep(User $worker, string $stepId, array $options = []): array
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step) {
            return [
                'success' => false,
                'error' => 'Step not found.',
            ];
        }

        // Check dependencies
        if ($step->hasDependencies()) {
            foreach ($step->getDependencyIds() as $depStepId) {
                $depStep = OnboardingStep::findByStepId($depStepId);
                if ($depStep) {
                    $depProgress = OnboardingProgress::where('user_id', $worker->id)
                        ->where('onboarding_step_id', $depStep->id)
                        ->first();

                    if (!$depProgress || !$depProgress->isCompleted()) {
                        return [
                            'success' => false,
                            'error' => "Dependency '{$depStepId}' must be completed first.",
                        ];
                    }
                }
            }
        }

        $progress = OnboardingProgress::getOrCreate($worker->id, $step->id);
        $progress->complete(
            $options['completed_by'] ?? 'user',
            $options['notes'] ?? null
        );

        // Update profile
        if ($worker->workerProfile) {
            $worker->workerProfile->update([
                'onboarding_step' => $step->order,
                'onboarding_last_step_at' => now(),
            ]);
        }

        // Send notification (unless suppressed)
        if (!($options['suppress_notification'] ?? false)) {
            try {
                $worker->notify(new OnboardingStepCompletedNotification($step, $progress));
            } catch (\Exception $e) {
                // Log but don't fail
                \Log::warning('Failed to send onboarding notification: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'step' => $step,
            'progress' => $progress->fresh(),
            'overall_progress' => $this->calculateProgress($worker),
            'next_step' => $this->getNextRequiredStep($worker),
        ];
    }

    /**
     * Skip an optional step.
     */
    public function skipOptionalStep(User $worker, string $stepId, ?string $reason = null): array
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step) {
            return [
                'success' => false,
                'error' => 'Step not found.',
            ];
        }

        if (!$step->canBeSkipped()) {
            return [
                'success' => false,
                'error' => 'This step is required and cannot be skipped.',
            ];
        }

        $progress = OnboardingProgress::getOrCreate($worker->id, $step->id);
        $progress->skip($reason);

        return [
            'success' => true,
            'progress' => $progress->fresh(),
            'overall_progress' => $this->calculateProgress($worker),
        ];
    }

    /**
     * Calculate overall progress for a worker.
     */
    public function calculateProgress(User $worker): array
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->get();

        $requiredSteps = $steps->where('step_type', 'required');
        $recommendedSteps = $steps->where('step_type', 'recommended');

        $progressRecords = OnboardingProgress::where('user_id', $worker->id)
            ->with('step')
            ->get()
            ->keyBy('onboarding_step_id');

        // Calculate required steps progress
        $requiredComplete = 0;
        $requiredTotal = $requiredSteps->count();
        $requiredWeight = 0;
        $requiredWeightTotal = $requiredSteps->sum('weight');

        foreach ($requiredSteps as $step) {
            $progress = $progressRecords->get($step->id);
            if ($progress && $progress->isCompleted()) {
                $requiredComplete++;
                $requiredWeight += $step->weight;
            }
        }

        // Calculate recommended steps progress
        $recommendedComplete = 0;
        $recommendedTotal = $recommendedSteps->count();

        foreach ($recommendedSteps as $step) {
            $progress = $progressRecords->get($step->id);
            if ($progress && $progress->isCompleted()) {
                $recommendedComplete++;
            }
        }

        // Calculate overall percentage
        $overallPercentage = $requiredWeightTotal > 0
            ? round(($requiredWeight / $requiredWeightTotal) * 100, 1)
            : 0;

        return [
            'overall_percentage' => $overallPercentage,
            'required' => [
                'completed' => $requiredComplete,
                'total' => $requiredTotal,
                'percentage' => $requiredTotal > 0 ? round(($requiredComplete / $requiredTotal) * 100, 1) : 0,
            ],
            'recommended' => [
                'completed' => $recommendedComplete,
                'total' => $recommendedTotal,
                'percentage' => $recommendedTotal > 0 ? round(($recommendedComplete / $recommendedTotal) * 100, 1) : 0,
            ],
            'all_required_complete' => $requiredComplete === $requiredTotal,
            'is_eligible_for_activation' => $requiredComplete === $requiredTotal,
        ];
    }

    /**
     * Get the next required step for a worker.
     */
    public function getNextRequiredStep(User $worker): ?array
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->required()
            ->ordered()
            ->get();

        foreach ($steps as $step) {
            $progress = OnboardingProgress::where('user_id', $worker->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || $progress->isIncomplete()) {
                // Check dependencies
                $dependenciesMet = true;
                if ($step->hasDependencies()) {
                    foreach ($step->getDependencyIds() as $depStepId) {
                        $depStep = OnboardingStep::findByStepId($depStepId);
                        if ($depStep) {
                            $depProgress = OnboardingProgress::where('user_id', $worker->id)
                                ->where('onboarding_step_id', $depStep->id)
                                ->first();

                            if (!$depProgress || !$depProgress->isCompleted()) {
                                $dependenciesMet = false;
                                break;
                            }
                        }
                    }
                }

                if ($dependenciesMet) {
                    return [
                        'step' => $step,
                        'progress' => $progress,
                        'route_url' => $step->getRouteUrl(),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get all missing required steps.
     */
    public function getMissingRequiredSteps(User $worker): Collection
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->required()
            ->ordered()
            ->get();

        $missing = [];

        foreach ($steps as $step) {
            $progress = OnboardingProgress::where('user_id', $worker->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || !$progress->isCompleted()) {
                $missing[] = [
                    'step' => $step,
                    'progress' => $progress,
                    'status' => $progress ? $progress->status : 'pending',
                ];
            }
        }

        return collect($missing);
    }

    /**
     * Get detailed progress report.
     */
    public function getDetailedProgress(User $worker): array
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->ordered()
            ->get();

        $progressRecords = OnboardingProgress::where('user_id', $worker->id)
            ->with('step')
            ->get()
            ->keyBy('onboarding_step_id');

        $stepsWithProgress = $steps->map(function ($step) use ($progressRecords) {
            $progress = $progressRecords->get($step->id);

            return [
                'id' => $step->id,
                'step_id' => $step->step_id,
                'name' => $step->name,
                'description' => $step->description,
                'help_text' => $step->help_text,
                'step_type' => $step->step_type,
                'category' => $step->category,
                'order' => $step->order,
                'weight' => $step->weight,
                'is_required' => $step->step_type === 'required',
                'icon' => $step->icon,
                'route_url' => $step->getRouteUrl(),
                'estimated_time' => $step->getEstimatedTimeString(),
                'has_target' => $step->hasTarget(),
                'target' => $step->target,
                'status' => $progress ? $progress->status : 'pending',
                'progress_percentage' => $progress ? $progress->progress_percentage : 0,
                'started_at' => $progress?->started_at,
                'completed_at' => $progress?->completed_at,
                'can_skip' => $step->canBeSkipped(),
            ];
        });

        return [
            'steps' => $stepsWithProgress,
            'summary' => $this->calculateProgress($worker),
        ];
    }

    /**
     * Auto-validate and complete steps based on current user data.
     */
    public function autoValidateSteps(User $worker): array
    {
        $completedSteps = [];
        $profile = $worker->workerProfile;

        // Email verified
        if ($worker->email_verified_at) {
            $result = $this->completeStep($worker, 'email_verified', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'email_verified';
            }
        }

        // Phone verified
        if ($profile && $profile->phone_verified) {
            $result = $this->completeStep($worker, 'phone_verified', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'phone_verified';
            }
        }

        // Identity verified
        if ($profile && $profile->identity_verified) {
            $result = $this->completeStep($worker, 'identity_verified', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'identity_verified';
            }
        }

        // RTW verified
        if ($profile && $profile->rtw_verified) {
            $result = $this->completeStep($worker, 'rtw_verified', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'rtw_verified';
            }
        }

        // Background check
        if ($profile && in_array($profile->background_check_status, ['approved', 'pending', 'clear'])) {
            $result = $this->completeStep($worker, 'background_check_initiated', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'background_check_initiated';
            }
        }

        // Payment setup
        if ($profile && $profile->payment_setup_complete) {
            $result = $this->completeStep($worker, 'payment_setup', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'payment_setup';
            }
        }

        // Profile complete (check profile completion percentage)
        $completionService = app(ProfileCompletionService::class);
        $completion = $completionService->calculateCompletion($worker);
        if ($completion['percentage'] >= 80) {
            $result = $this->completeStep($worker, 'profile_complete', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'profile_complete';
            }
        }

        // Skills added (recommended)
        $skillsCount = $worker->skills()->count();
        if ($skillsCount >= 3) {
            $result = $this->completeStep($worker, 'skills_added', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'skills_added';
            }
        } elseif ($skillsCount > 0) {
            $step = OnboardingStep::findByStepId('skills_added');
            if ($step && $step->hasTarget()) {
                $this->updateProgress($worker, 'skills_added', [
                    'percentage' => min(100, ($skillsCount / $step->target) * 100),
                ]);
            }
        }

        // Certifications (recommended)
        $certsCount = $worker->certifications()->count();
        if ($certsCount >= 1) {
            $result = $this->completeStep($worker, 'certifications_uploaded', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'certifications_uploaded';
            }
        }

        // Availability set (recommended)
        if ($profile && $profile->hasAvailabilitySet()) {
            $result = $this->completeStep($worker, 'availability_set', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'availability_set';
            }
        }

        // Bio written (recommended)
        if ($profile && $profile->bio && strlen($profile->bio) >= 50) {
            $result = $this->completeStep($worker, 'bio_written', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'bio_written';
            }
        }

        // Emergency contact (recommended)
        if ($profile && $profile->emergency_contact_name && $profile->emergency_contact_phone) {
            $result = $this->completeStep($worker, 'emergency_contact_added', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'emergency_contact_added';
            }
        }

        // Profile photo approved (recommended)
        if ($profile && $profile->profile_photo_status === 'approved') {
            $result = $this->completeStep($worker, 'profile_photo_approved', [
                'completed_by' => 'auto',
                'suppress_notification' => true,
            ]);
            if ($result['success']) {
                $completedSteps[] = 'profile_photo_approved';
            }
        }

        return [
            'completed_steps' => $completedSteps,
            'overall_progress' => $this->calculateProgress($worker),
        ];
    }

    /**
     * Send reminder notifications for incomplete steps.
     */
    public function sendReminders(): array
    {
        $sent = 0;
        $users = User::where('user_type', 'worker')
            ->whereHas('workerProfile', function ($q) {
                $q->where('onboarding_completed', false);
            })
            ->get();

        foreach ($users as $user) {
            $nextStep = $this->getNextRequiredStep($user);

            if ($nextStep && $nextStep['progress']) {
                $progress = $nextStep['progress'];

                // Check if reminder should be sent (not sent in last 2 days)
                if (!$progress->last_reminder_at || $progress->last_reminder_at->diffInDays(now()) >= 2) {
                    try {
                        $user->notify(new OnboardingReminderNotification($nextStep['step']));
                        $progress->recordReminderSent();
                        $sent++;
                    } catch (\Exception $e) {
                        \Log::warning("Failed to send onboarding reminder to user {$user->id}: " . $e->getMessage());
                    }
                }
            }
        }

        return [
            'reminders_sent' => $sent,
        ];
    }
}
