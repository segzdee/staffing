<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * FirstShiftProgress Model
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Tracks business progress through the first shift wizard
 * and stores saved draft data for continuing later.
 */
class FirstShiftProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'first_shift_progress';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_profile_id',
        'wizard_completed',
        'wizard_completed_at',
        'current_step',
        'highest_step_reached',
        'step_1_venue_complete',
        'step_2_role_complete',
        'step_3_schedule_complete',
        'step_4_rate_complete',
        'step_5_details_complete',
        'step_6_review_complete',
        'step_1_completed_at',
        'step_2_completed_at',
        'step_3_completed_at',
        'step_4_completed_at',
        'step_5_completed_at',
        'step_6_completed_at',
        'draft_data',
        'selected_venue_id',
        'selected_role',
        'selected_date',
        'selected_start_time',
        'selected_end_time',
        'selected_hourly_rate',
        'selected_workers_needed',
        'posting_mode',
        'save_as_template',
        'template_name',
        'first_shift_id',
        'total_time_spent_seconds',
        'session_count',
        'last_activity_at',
        'promo_applied',
        'promo_code',
        'promo_discount_cents',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'wizard_completed' => 'boolean',
        'wizard_completed_at' => 'datetime',
        'current_step' => 'integer',
        'highest_step_reached' => 'integer',
        'step_1_venue_complete' => 'boolean',
        'step_2_role_complete' => 'boolean',
        'step_3_schedule_complete' => 'boolean',
        'step_4_rate_complete' => 'boolean',
        'step_5_details_complete' => 'boolean',
        'step_6_review_complete' => 'boolean',
        'step_1_completed_at' => 'datetime',
        'step_2_completed_at' => 'datetime',
        'step_3_completed_at' => 'datetime',
        'step_4_completed_at' => 'datetime',
        'step_5_completed_at' => 'datetime',
        'step_6_completed_at' => 'datetime',
        'draft_data' => 'array',
        'selected_date' => 'date',
        'selected_hourly_rate' => 'integer',
        'selected_workers_needed' => 'integer',
        'save_as_template' => 'boolean',
        'total_time_spent_seconds' => 'integer',
        'session_count' => 'integer',
        'last_activity_at' => 'datetime',
        'promo_applied' => 'boolean',
        'promo_discount_cents' => 'integer',
    ];

    /**
     * Wizard step constants.
     */
    const STEP_VENUE = 1;
    const STEP_ROLE = 2;
    const STEP_SCHEDULE = 3;
    const STEP_RATE = 4;
    const STEP_DETAILS = 5;
    const STEP_REVIEW = 6;

    /**
     * Step names for display.
     */
    const STEP_NAMES = [
        1 => 'Select Venue',
        2 => 'Choose Role',
        3 => 'Set Schedule',
        4 => 'Set Pay Rate',
        5 => 'Add Details',
        6 => 'Review & Post',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the selected venue.
     */
    public function selectedVenue()
    {
        return $this->belongsTo(Venue::class, 'selected_venue_id');
    }

    /**
     * Get the created first shift.
     */
    public function firstShift()
    {
        return $this->belongsTo(Shift::class, 'first_shift_id');
    }

    // =========================================
    // Progress Methods
    // =========================================

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentageAttribute(): int
    {
        if ($this->wizard_completed) {
            return 100;
        }

        $completedSteps = 0;
        if ($this->step_1_venue_complete) $completedSteps++;
        if ($this->step_2_role_complete) $completedSteps++;
        if ($this->step_3_schedule_complete) $completedSteps++;
        if ($this->step_4_rate_complete) $completedSteps++;
        if ($this->step_5_details_complete) $completedSteps++;
        if ($this->step_6_review_complete) $completedSteps++;

        return (int) round(($completedSteps / 6) * 100);
    }

    /**
     * Get step status array.
     */
    public function getStepsStatusAttribute(): array
    {
        return [
            1 => [
                'name' => self::STEP_NAMES[1],
                'complete' => $this->step_1_venue_complete,
                'completed_at' => $this->step_1_completed_at,
                'current' => $this->current_step === 1,
            ],
            2 => [
                'name' => self::STEP_NAMES[2],
                'complete' => $this->step_2_role_complete,
                'completed_at' => $this->step_2_completed_at,
                'current' => $this->current_step === 2,
            ],
            3 => [
                'name' => self::STEP_NAMES[3],
                'complete' => $this->step_3_schedule_complete,
                'completed_at' => $this->step_3_completed_at,
                'current' => $this->current_step === 3,
            ],
            4 => [
                'name' => self::STEP_NAMES[4],
                'complete' => $this->step_4_rate_complete,
                'completed_at' => $this->step_4_completed_at,
                'current' => $this->current_step === 4,
            ],
            5 => [
                'name' => self::STEP_NAMES[5],
                'complete' => $this->step_5_details_complete,
                'completed_at' => $this->step_5_completed_at,
                'current' => $this->current_step === 5,
            ],
            6 => [
                'name' => self::STEP_NAMES[6],
                'complete' => $this->step_6_review_complete,
                'completed_at' => $this->step_6_completed_at,
                'current' => $this->current_step === 6,
            ],
        ];
    }

    /**
     * Check if a step is complete.
     */
    public function isStepComplete(int $step): bool
    {
        return match($step) {
            1 => $this->step_1_venue_complete,
            2 => $this->step_2_role_complete,
            3 => $this->step_3_schedule_complete,
            4 => $this->step_4_rate_complete,
            5 => $this->step_5_details_complete,
            6 => $this->step_6_review_complete,
            default => false,
        };
    }

    /**
     * Mark a step as complete.
     */
    public function completeStep(int $step, array $data = []): self
    {
        $stepColumn = "step_{$step}_" . $this->getStepColumnSuffix($step) . "_complete";
        $timestampColumn = "step_{$step}_completed_at";

        $updateData = [
            $stepColumn => true,
            $timestampColumn => now(),
            'last_activity_at' => now(),
        ];

        // Update highest step reached
        if ($step > $this->highest_step_reached) {
            $updateData['highest_step_reached'] = $step;
        }

        // Merge draft data
        if (!empty($data)) {
            $draftData = $this->draft_data ?? [];
            $draftData["step_{$step}"] = $data;
            $updateData['draft_data'] = $draftData;
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Get step column suffix.
     */
    protected function getStepColumnSuffix(int $step): string
    {
        return match($step) {
            1 => 'venue',
            2 => 'role',
            3 => 'schedule',
            4 => 'rate',
            5 => 'details',
            6 => 'review',
            default => 'unknown',
        };
    }

    /**
     * Go to step.
     */
    public function goToStep(int $step): self
    {
        $this->update([
            'current_step' => min(max($step, 1), 6),
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    /**
     * Can navigate to step (must have completed previous steps or be going back).
     */
    public function canNavigateToStep(int $step): bool
    {
        if ($step <= 1) {
            return true;
        }

        // Can go back to any completed step
        if ($step <= $this->highest_step_reached) {
            return true;
        }

        // Can only go forward if previous step is complete
        return $this->isStepComplete($step - 1);
    }

    /**
     * Complete the wizard.
     */
    public function completeWizard(int $shiftId): self
    {
        $this->update([
            'wizard_completed' => true,
            'wizard_completed_at' => now(),
            'first_shift_id' => $shiftId,
            'step_6_review_complete' => true,
            'step_6_completed_at' => now(),
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    // =========================================
    // Draft Data Methods
    // =========================================

    /**
     * Save draft data for a step.
     */
    public function saveDraftData(int $step, array $data): self
    {
        $draftData = $this->draft_data ?? [];
        $draftData["step_{$step}"] = $data;

        $this->update([
            'draft_data' => $draftData,
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    /**
     * Get draft data for a step.
     */
    public function getDraftDataForStep(int $step): array
    {
        return $this->draft_data["step_{$step}"] ?? [];
    }

    /**
     * Get all draft data as flat array.
     */
    public function getAllDraftDataAttribute(): array
    {
        $allData = [];

        foreach (($this->draft_data ?? []) as $stepKey => $stepData) {
            if (is_array($stepData)) {
                $allData = array_merge($allData, $stepData);
            }
        }

        return $allData;
    }

    // =========================================
    // Time Tracking
    // =========================================

    /**
     * Start a new session.
     */
    public function startSession(): self
    {
        $this->increment('session_count');
        $this->update(['last_activity_at' => now()]);

        return $this;
    }

    /**
     * Add time to total.
     */
    public function addTimeSpent(int $seconds): self
    {
        $this->increment('total_time_spent_seconds', $seconds);

        return $this;
    }

    /**
     * Get formatted time spent.
     */
    public function getFormattedTimeSpentAttribute(): string
    {
        $seconds = $this->total_time_spent_seconds;

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);

        if ($minutes < 60) {
            return "{$minutes} minute" . ($minutes !== 1 ? 's' : '');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours} hour" . ($hours !== 1 ? 's' : '') .
               ($remainingMinutes > 0 ? " {$remainingMinutes} min" : '');
    }

    // =========================================
    // Promotional Credits
    // =========================================

    /**
     * Apply promotional code.
     */
    public function applyPromoCode(string $code, int $discountCents): self
    {
        $this->update([
            'promo_applied' => true,
            'promo_code' => $code,
            'promo_discount_cents' => $discountCents,
        ]);

        return $this;
    }

    /**
     * Remove promotional code.
     */
    public function removePromoCode(): self
    {
        $this->update([
            'promo_applied' => false,
            'promo_code' => null,
            'promo_discount_cents' => 0,
        ]);

        return $this;
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get incomplete wizards.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('wizard_completed', false);
    }

    /**
     * Scope to get completed wizards.
     */
    public function scopeCompleted($query)
    {
        return $query->where('wizard_completed', true);
    }

    /**
     * Scope to get active (recent activity) wizards.
     */
    public function scopeActive($query)
    {
        return $query->where('last_activity_at', '>=', now()->subDays(7));
    }

    /**
     * Scope to get abandoned wizards.
     */
    public function scopeAbandoned($query)
    {
        return $query->incomplete()
            ->where('last_activity_at', '<', now()->subDays(7));
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get or create progress for business.
     */
    public static function getOrCreateForBusiness(int $businessProfileId): self
    {
        return self::firstOrCreate(
            ['business_profile_id' => $businessProfileId],
            [
                'current_step' => 1,
                'highest_step_reached' => 1,
                'posting_mode' => 'detailed',
                'selected_workers_needed' => 1,
            ]
        );
    }
}
