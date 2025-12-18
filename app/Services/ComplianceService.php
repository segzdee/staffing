<?php

namespace App\Services;

use App\Models\ComplianceViolation;
use App\Models\LaborLawRule;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerExemption;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Compliance Service
 *
 * Handles global jurisdiction compliance for OvertimeStaff platform
 * Validates labor laws, minimum wages, and regulatory requirements
 *
 * GLO-001: Jurisdiction Management System
 * GLO-003: Labor Law Compliance (Enhanced)
 * GLO-006: Global Minimum Wage Compliance
 */
class ComplianceService
{
    /**
     * Jurisdiction cache duration (24 hours)
     */
    public const CACHE_DURATION = 86400;

    /**
     * Default weekly hours limit (EU WTD)
     */
    public const DEFAULT_WEEKLY_HOURS_LIMIT = 48;

    /**
     * Default daily rest period (EU WTD)
     */
    public const DEFAULT_DAILY_REST_HOURS = 11;

    /**
     * Default weekly rest period (EU WTD)
     */
    public const DEFAULT_WEEKLY_REST_HOURS = 24;

    // ==================== MAIN COMPLIANCE CHECK ====================

    /**
     * Check all applicable rules for a worker and proposed shift.
     * GLO-003: Full labor law enforcement
     *
     * @return array{
     *     compliant: bool,
     *     can_proceed: bool,
     *     violations: array,
     *     warnings: array,
     *     blocked_rules: array,
     *     exemptions_applied: array
     * }
     */
    public function checkAllRules(User $worker, Shift $proposedShift): array
    {
        $violations = [];
        $warnings = [];
        $blockedRules = [];
        $exemptionsApplied = [];

        try {
            // Get worker's jurisdiction
            $jurisdiction = $this->getWorkerJurisdiction($worker, $proposedShift);

            // Get applicable rules for this jurisdiction
            $rules = LaborLawRule::getApplicableRules(
                $proposedShift->location_country ?? 'US',
                $proposedShift->location_state
            );

            foreach ($rules as $rule) {
                // Check if worker has valid exemption
                if ($this->hasOptedOut($worker, $rule->rule_code)) {
                    $exemptionsApplied[] = [
                        'rule_code' => $rule->rule_code,
                        'rule_name' => $rule->name,
                    ];

                    continue;
                }

                // Check rule based on type
                $result = $this->checkRule($worker, $proposedShift, $rule);

                if ($result !== null) {
                    if ($rule->shouldBlock()) {
                        $blockedRules[] = [
                            'rule' => $rule,
                            'violation' => $result,
                        ];
                        $violations[] = $result;
                    } elseif ($rule->shouldWarn()) {
                        $warnings[] = $result;
                    }
                }
            }

            // Also run legacy jurisdiction checks
            $legacyResult = $this->validateShiftCreation($proposedShift);
            $warnings = array_merge($warnings, $legacyResult['warnings'] ?? []);

            $canProceed = empty($blockedRules);
            $compliant = empty($violations) && empty($warnings);

            return [
                'compliant' => $compliant,
                'can_proceed' => $canProceed,
                'violations' => $violations,
                'warnings' => $warnings,
                'blocked_rules' => $blockedRules,
                'exemptions_applied' => $exemptionsApplied,
                'jurisdiction' => $jurisdiction,
            ];

        } catch (\Exception $e) {
            Log::error('Compliance check error', [
                'worker_id' => $worker->id,
                'shift_id' => $proposedShift->id ?? 'new',
                'error' => $e->getMessage(),
            ]);

            return [
                'compliant' => false,
                'can_proceed' => false,
                'violations' => ['Compliance service unavailable: '.$e->getMessage()],
                'warnings' => [],
                'blocked_rules' => [],
                'exemptions_applied' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check a specific rule against worker and shift.
     */
    protected function checkRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        return match ($rule->rule_type) {
            LaborLawRule::TYPE_WORKING_TIME => $this->checkWorkingTimeRule($worker, $shift, $rule),
            LaborLawRule::TYPE_REST_PERIOD => $this->checkRestPeriodRule($worker, $shift, $rule),
            LaborLawRule::TYPE_BREAK => $this->checkBreakRule($worker, $shift, $rule),
            LaborLawRule::TYPE_OVERTIME => $this->checkOvertimeRule($worker, $shift, $rule),
            LaborLawRule::TYPE_AGE_RESTRICTION => $this->checkAgeRestrictionRule($worker, $shift, $rule),
            LaborLawRule::TYPE_NIGHT_WORK => $this->checkNightWorkRule($worker, $shift, $rule),
            default => null,
        };
    }

    // ==================== ENFORCEMENT METHODS ====================

    /**
     * Enforce weekly hours limit (EU WTD: max 48 hours)
     * GLO-003: Working Time Directive compliance
     */
    public function enforceWeeklyHoursLimit(User $worker, Shift $shift): ?ComplianceViolation
    {
        $rule = LaborLawRule::findByCode('WTD_WEEKLY_MAX');
        if (! $rule || ! $rule->is_active) {
            return null;
        }

        // Check for exemption
        if ($this->hasOptedOut($worker, 'WTD_WEEKLY_MAX')) {
            return null;
        }

        $maxHours = $rule->getMaxHours() ?? self::DEFAULT_WEEKLY_HOURS_LIMIT;
        $shiftWeek = Carbon::parse($shift->shift_date)->startOfWeek();

        $currentWeeklyHours = $this->getWorkerWeeklyHours($worker, $shiftWeek);
        $proposedShiftHours = $shift->duration_hours ?? 0;
        $totalHours = $currentWeeklyHours + $proposedShiftHours;

        if ($totalHours > $maxHours) {
            $violation = ComplianceViolation::createViolation(
                $worker,
                $rule,
                "Weekly hours limit exceeded: {$totalHours}h would exceed {$maxHours}h maximum",
                [
                    'actual' => $totalHours,
                    'limit' => $maxHours,
                    'current_hours' => $currentWeeklyHours,
                    'proposed_hours' => $proposedShiftHours,
                    'week_start' => $shiftWeek->toDateString(),
                ],
                $shift,
                $rule->shouldBlock()
            );

            return $violation;
        }

        return null;
    }

    /**
     * Enforce rest period between shifts (EU WTD: min 11 hours daily)
     * GLO-003: Rest period compliance
     */
    public function enforceRestPeriod(User $worker, Shift $shift): ?ComplianceViolation
    {
        $rule = LaborLawRule::findByCode('REST_PERIOD_DAILY');
        if (! $rule || ! $rule->is_active) {
            return null;
        }

        $minRestHours = $rule->getMinHours() ?? self::DEFAULT_DAILY_REST_HOURS;

        // Get the proposed shift start time
        $proposedStart = Carbon::parse($shift->shift_date.' '.$shift->start_time);
        $proposedEnd = Carbon::parse($shift->shift_date.' '.$shift->end_time);

        // Handle overnight shifts
        if ($proposedEnd->lt($proposedStart)) {
            $proposedEnd->addDay();
        }

        // Find adjacent shifts (24 hours before and after)
        $adjacentShifts = $this->getAdjacentShifts($worker, $proposedStart, $proposedEnd);

        foreach ($adjacentShifts as $existingAssignment) {
            $existingShift = $existingAssignment->shift;
            $existingStart = Carbon::parse($existingShift->shift_date.' '.$existingShift->start_time);
            $existingEnd = Carbon::parse($existingShift->shift_date.' '.$existingShift->end_time);

            // Handle overnight shifts
            if ($existingEnd->lt($existingStart)) {
                $existingEnd->addDay();
            }

            // Calculate rest period
            $restHours = 0;
            if ($proposedStart->gt($existingEnd)) {
                $restHours = $existingEnd->diffInHours($proposedStart);
            } elseif ($existingStart->gt($proposedEnd)) {
                $restHours = $proposedEnd->diffInHours($existingStart);
            }

            if ($restHours > 0 && $restHours < $minRestHours) {
                $violation = ComplianceViolation::createViolation(
                    $worker,
                    $rule,
                    "Insufficient rest period: {$restHours}h between shifts, minimum required is {$minRestHours}h",
                    [
                        'actual' => $restHours,
                        'limit' => $minRestHours,
                        'existing_shift_id' => $existingShift->id,
                        'existing_shift_end' => $existingEnd->toDateTimeString(),
                        'proposed_shift_start' => $proposedStart->toDateTimeString(),
                    ],
                    $shift,
                    $rule->shouldBlock()
                );

                return $violation;
            }
        }

        return null;
    }

    /**
     * Enforce break requirements (EU WTD: 20min break after 6 hours)
     * GLO-003: Break compliance
     */
    public function enforceBreakRequirements(Shift $shift): ?ComplianceViolation
    {
        $rule = LaborLawRule::findByCode('BREAK_6_HOURS');
        if (! $rule || ! $rule->is_active) {
            return null;
        }

        $params = $rule->parameters;
        $thresholdHours = $params['threshold_hours'] ?? 6;
        $breakMinutes = $params['break_minutes'] ?? 20;

        if ($shift->duration_hours >= $thresholdHours) {
            // This is informational - shift requires break enforcement
            // Actual enforcement happens during clock-in/out
            Log::info('Break required for shift', [
                'shift_id' => $shift->id,
                'duration_hours' => $shift->duration_hours,
                'break_minutes_required' => $breakMinutes,
            ]);
        }

        return null;
    }

    /**
     * Enforce daily hours limit
     * GLO-003: Daily working time compliance
     */
    public function enforceDailyHoursLimit(User $worker, Shift $shift): ?ComplianceViolation
    {
        $rule = LaborLawRule::findByCode('DAILY_HOURS_MAX');
        if (! $rule || ! $rule->is_active) {
            return null;
        }

        $maxDailyHours = $rule->getMaxHours() ?? 13; // EU default
        $shiftDate = Carbon::parse($shift->shift_date);

        // Get all shifts for this worker on this day
        $dailyHours = $this->getWorkerDailyHours($worker, $shiftDate);
        $proposedHours = $shift->duration_hours ?? 0;
        $totalHours = $dailyHours + $proposedHours;

        if ($totalHours > $maxDailyHours) {
            $violation = ComplianceViolation::createViolation(
                $worker,
                $rule,
                "Daily hours limit exceeded: {$totalHours}h would exceed {$maxDailyHours}h maximum",
                [
                    'actual' => $totalHours,
                    'limit' => $maxDailyHours,
                    'current_hours' => $dailyHours,
                    'proposed_hours' => $proposedHours,
                    'date' => $shiftDate->toDateString(),
                ],
                $shift,
                $rule->shouldBlock()
            );

            return $violation;
        }

        return null;
    }

    /**
     * Check youth worker restrictions
     * GLO-003: Age restriction compliance
     */
    public function checkYouthWorkerRestrictions(User $worker, Shift $shift): ?ComplianceViolation
    {
        $rule = LaborLawRule::findByCode('YOUTH_NIGHT_WORK');
        if (! $rule || ! $rule->is_active) {
            return null;
        }

        // Get worker's age
        $workerProfile = $worker->workerProfile;
        if (! $workerProfile || ! $workerProfile->date_of_birth) {
            return null; // Can't check without DOB
        }

        $age = Carbon::parse($workerProfile->date_of_birth)->age;
        $params = $rule->parameters;
        $minAgeForNightWork = $params['min_age_for_night_work'] ?? 18;
        $nightStartHour = $params['night_start_hour'] ?? 22;
        $nightEndHour = $params['night_end_hour'] ?? 6;

        if ($age < $minAgeForNightWork) {
            // Check if shift is during night hours
            $startHour = (int) Carbon::parse($shift->start_time)->format('H');
            $endHour = (int) Carbon::parse($shift->end_time)->format('H');

            $isNightShift = $this->isTimeInRange((string) $startHour, $nightStartHour, $nightEndHour)
                || $this->isTimeInRange((string) $endHour, $nightStartHour, $nightEndHour);

            if ($isNightShift) {
                $violation = ComplianceViolation::createViolation(
                    $worker,
                    $rule,
                    "Worker under {$minAgeForNightWork} cannot work night shifts (22:00-06:00)",
                    [
                        'worker_age' => $age,
                        'min_age_required' => $minAgeForNightWork,
                        'shift_start' => $shift->start_time,
                        'shift_end' => $shift->end_time,
                    ],
                    $shift,
                    $rule->shouldBlock()
                );

                return $violation;
            }
        }

        return null;
    }

    // ==================== HOURS CALCULATION ====================

    /**
     * Get worker's total hours for a specific week.
     */
    public function getWorkerWeeklyHours(User $worker, Carbon $weekStart): float
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $cacheKey = "worker_weekly_hours_{$worker->id}_{$weekStart->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($worker, $weekStart, $weekEnd) {
            return ShiftAssignment::where('worker_id', $worker->id)
                ->whereHas('shift', function ($query) use ($weekStart, $weekEnd) {
                    $query->whereBetween('shift_date', [$weekStart, $weekEnd])
                        ->whereNotIn('status', ['cancelled']);
                })
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->with('shift')
                ->get()
                ->sum(function ($assignment) {
                    return $assignment->hours_worked ?? $assignment->shift->duration_hours ?? 0;
                });
        });
    }

    /**
     * Get worker's total hours for a specific day.
     */
    public function getWorkerDailyHours(User $worker, Carbon $date): float
    {
        return ShiftAssignment::where('worker_id', $worker->id)
            ->whereHas('shift', function ($query) use ($date) {
                $query->whereDate('shift_date', $date)
                    ->whereNotIn('status', ['cancelled']);
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->with('shift')
            ->get()
            ->sum(function ($assignment) {
                return $assignment->hours_worked ?? $assignment->shift->duration_hours ?? 0;
            });
    }

    /**
     * Get shifts adjacent to the proposed timeframe.
     */
    protected function getAdjacentShifts(User $worker, Carbon $proposedStart, Carbon $proposedEnd): Collection
    {
        $searchStart = $proposedStart->copy()->subHours(24);
        $searchEnd = $proposedEnd->copy()->addHours(24);

        return ShiftAssignment::where('worker_id', $worker->id)
            ->whereHas('shift', function ($query) use ($searchStart, $searchEnd) {
                $query->whereBetween('shift_date', [$searchStart->toDateString(), $searchEnd->toDateString()])
                    ->whereNotIn('status', ['cancelled']);
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->with('shift')
            ->get();
    }

    // ==================== OPT-OUT / EXEMPTION METHODS ====================

    /**
     * Check if worker has opted out of a specific rule.
     */
    public function hasOptedOut(User $worker, string $ruleCode): bool
    {
        return WorkerExemption::hasValidExemption($worker->id, $ruleCode);
    }

    /**
     * Record a worker's opt-out for a rule.
     */
    public function recordOptOut(User $worker, string $ruleCode, array $data): WorkerExemption
    {
        $rule = LaborLawRule::findByCode($ruleCode);

        if (! $rule) {
            throw new \InvalidArgumentException("Rule not found: {$ruleCode}");
        }

        if (! $rule->allows_opt_out) {
            throw new \InvalidArgumentException("Rule does not allow opt-out: {$ruleCode}");
        }

        return DB::transaction(function () use ($worker, $rule, $data) {
            // Remove any existing exemption for this rule
            WorkerExemption::where('user_id', $worker->id)
                ->where('labor_law_rule_id', $rule->id)
                ->delete();

            // Create new exemption
            $exemption = WorkerExemption::create([
                'user_id' => $worker->id,
                'labor_law_rule_id' => $rule->id,
                'reason' => $data['reason'] ?? 'Worker opt-out',
                'document_url' => $data['document_url'] ?? null,
                'document_type' => $data['document_type'] ?? null,
                'valid_from' => $data['valid_from'] ?? now(),
                'valid_until' => $data['valid_until'] ?? null,
                'status' => WorkerExemption::STATUS_PENDING,
                'ip_address' => $data['ip_address'] ?? request()->ip(),
                'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            ]);

            // Auto-approve if no admin review required
            if (! ($rule->parameters['requires_admin_approval'] ?? false)) {
                $exemption->update([
                    'status' => WorkerExemption::STATUS_APPROVED,
                    'approved_at' => now(),
                    'worker_acknowledged' => true,
                    'worker_acknowledged_at' => now(),
                ]);
            }

            return $exemption;
        });
    }

    /**
     * Get worker's current exemptions.
     */
    public function getWorkerExemptions(User $worker): Collection
    {
        return WorkerExemption::forWorker($worker->id)
            ->active()
            ->with('laborLawRule')
            ->get();
    }

    // ==================== COMPLIANCE REPORTING ====================

    /**
     * Get comprehensive compliance report for a worker.
     */
    public function getComplianceReport(User $worker): array
    {
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        return [
            'worker_id' => $worker->id,
            'worker_name' => $worker->name,
            'report_generated_at' => now()->toIso8601String(),

            // Current status
            'current_weekly_hours' => $this->getWorkerWeeklyHours($worker, $weekStart),
            'weekly_hours_limit' => self::DEFAULT_WEEKLY_HOURS_LIMIT,
            'weekly_hours_remaining' => max(0, self::DEFAULT_WEEKLY_HOURS_LIMIT - $this->getWorkerWeeklyHours($worker, $weekStart)),

            // Active exemptions
            'active_exemptions' => $this->getWorkerExemptions($worker)->map(function ($exemption) {
                return [
                    'rule_code' => $exemption->laborLawRule->rule_code,
                    'rule_name' => $exemption->laborLawRule->name,
                    'valid_from' => $exemption->valid_from->toDateString(),
                    'valid_until' => $exemption->valid_until?->toDateString(),
                    'status' => $exemption->status,
                ];
            }),

            // Recent violations
            'recent_violations' => ComplianceViolation::forUser($worker->id)
                ->recent(30)
                ->with('laborLawRule')
                ->get()
                ->map(function ($violation) {
                    return [
                        'id' => $violation->id,
                        'rule_code' => $violation->laborLawRule->rule_code,
                        'description' => $violation->description,
                        'severity' => $violation->severity,
                        'status' => $violation->status,
                        'was_blocked' => $violation->was_blocked,
                        'created_at' => $violation->created_at->toIso8601String(),
                    ];
                }),

            // Monthly statistics
            'monthly_stats' => [
                'shifts_completed' => ShiftAssignment::where('worker_id', $worker->id)
                    ->where('status', 'completed')
                    ->whereHas('shift', function ($q) use ($monthStart) {
                        $q->where('shift_date', '>=', $monthStart);
                    })
                    ->count(),
                'total_hours' => ShiftAssignment::where('worker_id', $worker->id)
                    ->whereHas('shift', function ($q) use ($monthStart) {
                        $q->where('shift_date', '>=', $monthStart);
                    })
                    ->sum('hours_worked'),
                'violations_count' => ComplianceViolation::forUser($worker->id)
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
            ],

            // Compliance score
            'compliance_score' => $this->calculateComplianceScore($worker),
        ];
    }

    /**
     * Calculate worker's compliance score (0-100).
     */
    protected function calculateComplianceScore(User $worker): int
    {
        $score = 100;

        // Deduct for recent violations
        $recentViolations = ComplianceViolation::forUser($worker->id)
            ->recent(90)
            ->get();

        foreach ($recentViolations as $violation) {
            $deduction = match ($violation->severity) {
                ComplianceViolation::SEVERITY_CRITICAL => 20,
                ComplianceViolation::SEVERITY_VIOLATION => 10,
                ComplianceViolation::SEVERITY_WARNING => 5,
                default => 2,
            };

            // Less deduction if resolved
            if ($violation->isResolved()) {
                $deduction = (int) ($deduction * 0.5);
            }

            $score -= $deduction;
        }

        return max(0, min(100, $score));
    }

    // ==================== RULE-SPECIFIC CHECKS ====================

    /**
     * Check working time rule.
     */
    protected function checkWorkingTimeRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        $period = $rule->getPeriod() ?? 'weekly';
        $maxHours = $rule->getMaxHours();

        if ($period === 'weekly') {
            $weekStart = Carbon::parse($shift->shift_date)->startOfWeek();
            $currentHours = $this->getWorkerWeeklyHours($worker, $weekStart);
            $totalHours = $currentHours + ($shift->duration_hours ?? 0);

            if ($totalHours > $maxHours) {
                return ComplianceViolation::createViolation(
                    $worker,
                    $rule,
                    "Weekly hours limit exceeded: {$totalHours}h exceeds {$maxHours}h",
                    ['actual' => $totalHours, 'limit' => $maxHours],
                    $shift,
                    $rule->shouldBlock()
                );
            }
        } elseif ($period === 'daily') {
            $currentHours = $this->getWorkerDailyHours($worker, Carbon::parse($shift->shift_date));
            $totalHours = $currentHours + ($shift->duration_hours ?? 0);

            if ($totalHours > $maxHours) {
                return ComplianceViolation::createViolation(
                    $worker,
                    $rule,
                    "Daily hours limit exceeded: {$totalHours}h exceeds {$maxHours}h",
                    ['actual' => $totalHours, 'limit' => $maxHours],
                    $shift,
                    $rule->shouldBlock()
                );
            }
        }

        return null;
    }

    /**
     * Check rest period rule.
     */
    protected function checkRestPeriodRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        return $this->enforceRestPeriod($worker, $shift);
    }

    /**
     * Check break rule.
     */
    protected function checkBreakRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        return $this->enforceBreakRequirements($shift);
    }

    /**
     * Check overtime rule.
     */
    protected function checkOvertimeRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        // Overtime checking - typically informational, not blocking
        $params = $rule->parameters;
        $weeklyThreshold = $params['weekly_threshold_hours'] ?? 40;
        $dailyThreshold = $params['daily_threshold_hours'] ?? 8;

        $weekStart = Carbon::parse($shift->shift_date)->startOfWeek();
        $weeklyHours = $this->getWorkerWeeklyHours($worker, $weekStart) + ($shift->duration_hours ?? 0);

        if ($weeklyHours > $weeklyThreshold) {
            Log::info('Overtime will be triggered', [
                'worker_id' => $worker->id,
                'shift_id' => $shift->id ?? 'new',
                'weekly_hours' => $weeklyHours,
                'threshold' => $weeklyThreshold,
            ]);
        }

        return null; // Overtime is typically allowed, just needs tracking
    }

    /**
     * Check age restriction rule.
     */
    protected function checkAgeRestrictionRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        return $this->checkYouthWorkerRestrictions($worker, $shift);
    }

    /**
     * Check night work rule.
     */
    protected function checkNightWorkRule(User $worker, Shift $shift, LaborLawRule $rule): ?ComplianceViolation
    {
        $params = $rule->parameters;
        $maxNightHours = $params['max_hours_per_night'] ?? 8;
        $nightStart = $params['night_start_hour'] ?? 22;
        $nightEnd = $params['night_end_hour'] ?? 6;

        // Calculate night hours in this shift
        $shiftStart = Carbon::parse($shift->shift_date.' '.$shift->start_time);
        $shiftEnd = Carbon::parse($shift->shift_date.' '.$shift->end_time);

        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $nightHours = $this->calculateNightHours($shiftStart, $shiftEnd, $nightStart, $nightEnd);

        if ($nightHours > $maxNightHours) {
            return ComplianceViolation::createViolation(
                $worker,
                $rule,
                "Night work limit exceeded: {$nightHours}h exceeds {$maxNightHours}h maximum",
                ['actual' => $nightHours, 'limit' => $maxNightHours],
                $shift,
                $rule->shouldBlock()
            );
        }

        return null;
    }

    /**
     * Calculate night hours in a shift.
     */
    protected function calculateNightHours(Carbon $start, Carbon $end, int $nightStart, int $nightEnd): float
    {
        $nightHours = 0;
        $current = $start->copy();

        while ($current->lt($end)) {
            $hour = $current->hour;
            if ($hour >= $nightStart || $hour < $nightEnd) {
                $nightHours++;
            }
            $current->addHour();
        }

        return $nightHours;
    }

    // ==================== LEGACY METHODS (PRESERVED) ====================

    /**
     * Validate shift creation against jurisdiction rules
     * SL-001: Shift Creation & Cost Calculation
     */
    public function validateShiftCreation(Shift $shift): array
    {
        $violations = [];
        $warnings = [];

        try {
            $jurisdiction = $this->getJurisdictionRules(
                $shift->location_country ?? 'US',
                $shift->location_state
            );

            // Minimum wage validation
            $minWageValidation = $this->validateMinimumWage($shift, $jurisdiction);
            if (! $minWageValidation['compliant']) {
                $violations[] = $minWageValidation['message'];
            }

            // Maximum shift duration validation
            $durationValidation = $this->validateShiftDuration($shift, $jurisdiction);
            if (! $durationValidation['compliant']) {
                $violations[] = $durationValidation['message'];
            }

            // Break requirement validation
            $breakValidation = $this->validateBreakRequirements($shift, $jurisdiction);
            if (! $breakValidation['compliant']) {
                $warnings[] = $breakValidation['message'];
            }

            // Night work restrictions
            $nightWorkValidation = $this->validateNightWork($shift, $jurisdiction);
            if (! $nightWorkValidation['compliant']) {
                $warnings[] = $nightWorkValidation['message'];
            }

            // Youth worker restrictions
            $youthValidation = $this->validateYouthWorkerRestrictions($shift, $jurisdiction);
            if (! $youthValidation['compliant']) {
                $violations[] = $youthValidation['message'];
            }

            // Sunday/Weekend restrictions
            $weekendValidation = $this->validateWeekendRestrictions($shift, $jurisdiction);
            if (! $weekendValidation['compliant']) {
                $warnings[] = $weekendValidation['message'];
            }

            // Rest period between shifts
            $restValidation = $this->validateRestPeriods($shift, $jurisdiction);
            if (! $restValidation['compliant']) {
                $warnings[] = $restValidation['message'];
            }

            return [
                'compliant' => empty($violations),
                'violations' => $violations,
                'warnings' => $warnings,
                'jurisdiction' => $jurisdiction,
                'recommendations' => $this->getRecommendations($shift, $jurisdiction),
            ];

        } catch (\Exception $e) {
            Log::error('Shift validation error', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'compliant' => false,
                'violations' => ['Validation service unavailable'],
                'warnings' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate worker right-to-work for jurisdiction
     * GLO-005: Right-to-Work Verification
     */
    public function validateRightToWork(User $worker, string $country, ?string $state = null): array
    {
        try {
            $jurisdiction = $this->getJurisdictionRules($country, $state);
            $documents = $worker->documents()->where('status', 'verified')->get();

            $requiredDocuments = $jurisdiction['right_to_work']['required_documents'] ?? [];
            $hasRequiredDocs = true;
            $missingDocs = [];

            foreach ($requiredDocuments as $docType) {
                $hasDoc = $documents->contains('type', $docType);
                if (! $hasDoc) {
                    $hasRequiredDocs = false;
                    $missingDocs[] = $docType;
                }
            }

            // Check document expiry
            $expiringSoon = false;
            $expiredDocs = [];

            foreach ($documents as $doc) {
                if ($doc->expiry_date) {
                    if ($doc->expiry_date->isPast()) {
                        $expiredDocs[] = $doc->type;
                    } elseif ($doc->expiry_date->diffInDays(now()) < 30) {
                        $expiringSoon = true;
                    }
                }
            }

            $compliant = $hasRequiredDocs && empty($expiredDocs);

            return [
                'compliant' => $compliant,
                'has_required_documents' => $hasRequiredDocs,
                'missing_documents' => $missingDocs,
                'expired_documents' => $expiredDocs,
                'expiring_soon' => $expiringSoon,
                'jurisdiction_requirements' => $requiredDocuments,
            ];

        } catch (\Exception $e) {
            Log::error('Right-to-work validation error', [
                'worker_id' => $worker->id,
                'country' => $country,
                'error' => $e->getMessage(),
            ]);

            return [
                'compliant' => false,
                'error' => 'Validation service unavailable',
            ];
        }
    }

    /**
     * Get jurisdiction rules and regulations
     */
    public function getJurisdictionRules(string $country, ?string $state = null): array
    {
        $cacheKey = "jurisdiction_rules_{$country}_{$state}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($country, $state) {
            // In production, this would pull from database or external API
            // For now, return mock jurisdiction data
            return $this->getMockJurisdictionData($country, $state);
        });
    }

    /**
     * Get worker's jurisdiction based on profile and shift location.
     */
    protected function getWorkerJurisdiction(User $worker, Shift $shift): string
    {
        // Prefer shift location, fall back to worker profile
        $country = $shift->location_country ?? 'US';
        $state = $shift->location_state;

        return $state ? "{$country}-{$state}" : $country;
    }

    /**
     * Validate minimum wage compliance
     */
    private function validateMinimumWage(Shift $shift, array $jurisdiction): array
    {
        $minWage = $this->getMinimumWage($jurisdiction, $shift->role_type);
        $actualWage = $shift->final_rate ?? $shift->base_rate ?? 0;

        // Handle Money objects
        if (is_object($actualWage) && method_exists($actualWage, 'getAmount')) {
            $actualWage = (float) $actualWage->getAmount() / 100;
        }

        if ($actualWage < $minWage) {
            return [
                'compliant' => false,
                'message' => "Rate \${$actualWage}/hr is below minimum wage of \${$minWage}/hr for {$shift->role_type} in {$jurisdiction['name']}",
                'required_rate' => $minWage,
                'actual_rate' => $actualWage,
            ];
        }

        return [
            'compliant' => true,
            'minimum_wage' => $minWage,
            'actual_wage' => $actualWage,
        ];
    }

    /**
     * Validate shift duration against jurisdiction limits
     */
    private function validateShiftDuration(Shift $shift, array $jurisdiction): array
    {
        $maxHours = $jurisdiction['labor_rules']['maximum_shift_hours'] ?? 16;
        $actualHours = $shift->duration_hours;

        if ($actualHours > $maxHours) {
            return [
                'compliant' => false,
                'message' => "Shift duration of {$actualHours}h exceeds maximum of {$maxHours}h in {$jurisdiction['name']}",
                'max_hours' => $maxHours,
                'actual_hours' => $actualHours,
            ];
        }

        return [
            'compliant' => true,
            'max_hours' => $maxHours,
            'actual_hours' => $actualHours,
        ];
    }

    /**
     * Validate break requirements
     */
    private function validateBreakRequirements(Shift $shift, array $jurisdiction): array
    {
        $breakRules = $jurisdiction['labor_rules']['breaks'] ?? [];

        if (empty($breakRules)) {
            return ['compliant' => true, 'message' => 'No break requirements'];
        }

        $threshold = $breakRules['meal_break_threshold_hours'] ?? 6;
        $requiredDuration = $breakRules['meal_break_duration_minutes'] ?? 30;
        $isPaid = $breakRules['meal_break_paid'] ?? false;

        if ($shift->duration_hours >= $threshold) {
            return [
                'compliant' => true,
                'message' => "Break required: {$requiredDuration} minutes ".($isPaid ? 'paid' : 'unpaid'),
                'break_required' => true,
                'duration_minutes' => $requiredDuration,
                'paid' => $isPaid,
            ];
        }

        return [
            'compliant' => true,
            'message' => 'No break required for this duration',
            'break_required' => false,
        ];
    }

    /**
     * Validate night work restrictions
     */
    private function validateNightWork(Shift $shift, array $jurisdiction): array
    {
        $restrictions = $jurisdiction['operational_restrictions'] ?? [];

        if (! ($restrictions['night_work'] ?? true)) {
            return ['compliant' => false, 'message' => 'Night work not permitted in this jurisdiction'];
        }

        // Check if shift is during night hours
        $nightStartHour = $restrictions['night_start_hour'] ?? 22;
        $nightEndHour = $restrictions['night_end_hour'] ?? 6;

        $shiftStart = $shift->start_time;
        $shiftEnd = $shift->end_time;

        // Handle Carbon objects
        if ($shiftStart instanceof Carbon) {
            $shiftStart = $shiftStart->format('H:i');
        }
        if ($shiftEnd instanceof Carbon) {
            $shiftEnd = $shiftEnd->format('H:i');
        }

        $isNightShift = $this->isTimeInRange($shiftStart, $nightStartHour, $nightEndHour) ||
            $this->isTimeInRange($shiftEnd, $nightStartHour, $nightEndHour);

        if ($isNightShift) {
            return [
                'compliant' => true,
                'message' => 'Night work detected - ensure compliance with night work regulations',
                'night_shift' => true,
            ];
        }

        return [
            'compliant' => true,
            'night_shift' => false,
        ];
    }

    /**
     * Validate youth worker restrictions
     */
    private function validateYouthWorkerRestrictions(Shift $shift, array $jurisdiction): array
    {
        $youthRules = $jurisdiction['operational_restrictions']['youth_restrictions'] ?? null;

        if (! $youthRules) {
            return ['compliant' => true];
        }

        $minAge = $youthRules['minimum_age'] ?? 18;
        $nightRestriction = $youthRules['no_night_work_under_age'] ?? 18;

        return [
            'compliant' => true,
            'message' => "Workers must be at least {$minAge} years old. Night work restricted for workers under {$nightRestriction}.",
        ];
    }

    /**
     * Validate weekend work restrictions
     */
    private function validateWeekendRestrictions(Shift $shift, array $jurisdiction): array
    {
        $sundayWork = $jurisdiction['operational_restrictions']['sunday_work'] ?? 'allowed';

        if ($sundayWork === 'forbidden') {
            $shiftDate = Carbon::parse($shift->shift_date);
            if ($shiftDate->dayOfWeek === Carbon::SUNDAY) {
                return [
                    'compliant' => false,
                    'message' => 'Sunday work not permitted in this jurisdiction',
                ];
            }
        }

        if ($sundayWork === 'restricted') {
            $shiftDate = Carbon::parse($shift->shift_date);
            if ($shiftDate->dayOfWeek === Carbon::SUNDAY) {
                return [
                    'compliant' => true,
                    'message' => 'Sunday work requires special permits in this jurisdiction',
                ];
            }
        }

        return ['compliant' => true];
    }

    /**
     * Validate rest periods between shifts
     */
    private function validateRestPeriods(Shift $shift, array $jurisdiction): array
    {
        $minRestHours = $jurisdiction['labor_rules']['minimum_rest_between_shifts_hours'] ?? 8;

        return [
            'compliant' => true,
            'message' => "Workers must have at least {$minRestHours} hours between shifts",
        ];
    }

    /**
     * Get minimum wage for role in jurisdiction
     */
    private function getMinimumWage(array $jurisdiction, ?string $role = null): float
    {
        $minimumWages = $jurisdiction['minimum_wage'] ?? [];

        // Check role-specific minimum
        if ($role && isset($minimumWages['role_specific'][$role])) {
            return $minimumWages['role_specific'][$role];
        }

        // Check age-specific minimum
        if (isset($minimumWages['under_18'])) {
            return $minimumWages['under_18'];
        }

        // Return general minimum
        return $minimumWages['general'] ?? 7.25;
    }

    /**
     * Get recommendations for compliance improvement
     */
    private function getRecommendations(Shift $shift, array $jurisdiction): array
    {
        $recommendations = [];

        // Recommend advance posting to avoid surge pricing
        $hoursUntilShift = Carbon::parse($shift->shift_date.' '.$shift->start_time)->diffInHours(now());
        if ($hoursUntilShift < 72) {
            $recommendations[] = 'Post shifts 72+ hours in advance to avoid surge pricing';
        }

        // Recommend templates for recurring shifts
        if (! $shift->template_id && $this->isRecurringPattern($shift)) {
            $recommendations[] = 'Consider creating a shift template for recurring patterns';
        }

        // Recommend adding more details for better matching
        if (strlen($shift->description ?? '') < 100) {
            $recommendations[] = 'Add more detailed description for better worker matching';
        }

        return $recommendations;
    }

    /**
     * Check if time falls within night hours range
     */
    private function isTimeInRange($time, int $startHour, int $endHour): bool
    {
        if (is_string($time)) {
            $hour = (int) explode(':', $time)[0];
        } else {
            $hour = (int) $time;
        }

        if ($startHour > $endHour) {
            return $hour >= $startHour || $hour < $endHour;
        } else {
            return $hour >= $startHour && $hour < $endHour;
        }
    }

    /**
     * Check if shift appears to be part of a recurring pattern
     */
    private function isRecurringPattern(Shift $shift): bool
    {
        return false;
    }

    /**
     * Get mock jurisdiction data
     */
    private function getMockJurisdictionData(string $country, ?string $state = null): array
    {
        $jurisdictions = [
            'US' => [
                'name' => 'United States',
                'currency' => 'USD',
                'minimum_wage' => [
                    'general' => 7.25,
                    'role_specific' => [
                        'server' => 7.25,
                        'bartender' => 7.25,
                        'nurse' => 15.00,
                    ],
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 16,
                    'minimum_rest_between_shifts_hours' => 8,
                    'breaks' => [
                        'meal_break_threshold_hours' => 6,
                        'meal_break_duration_minutes' => 30,
                        'meal_break_paid' => false,
                    ],
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true,
                    'youth_restrictions' => [
                        'minimum_age' => 16,
                        'no_night_work_under_age' => 18,
                    ],
                ],
                'right_to_work' => [
                    'required_documents' => ['government_id', 'i9_documentation'],
                ],
                'tax_rules' => [
                    'platform_service_tax_rate' => 0.0,
                    'sales_tax_applicable' => true,
                ],
            ],
            'US-CA' => [
                'name' => 'California, USA',
                'currency' => 'USD',
                'minimum_wage' => [
                    'general' => 16.00,
                    'role_specific' => [
                        'server' => 16.00,
                        'bartender' => 16.00,
                    ],
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 12,
                    'minimum_rest_between_shifts_hours' => 11,
                    'breaks' => [
                        'meal_break_threshold_hours' => 5,
                        'meal_break_duration_minutes' => 30,
                        'meal_break_paid' => false,
                        'rest_break_interval_hours' => 4,
                        'rest_break_duration_minutes' => 10,
                        'rest_break_paid' => true,
                    ],
                    'overtime' => [
                        'daily_threshold_hours' => 8,
                        'weekly_threshold_hours' => 40,
                        'rate_multiplier' => 1.5,
                        'double_time_threshold' => 12,
                        'double_time_multiplier' => 2.0,
                    ],
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true,
                    'youth_restrictions' => [
                        'minimum_age' => 18,
                    ],
                ],
            ],
            'UK' => [
                'name' => 'United Kingdom',
                'currency' => 'GBP',
                'minimum_wage' => [
                    'general' => 11.44,
                    'role_specific' => [
                        'apprentice' => 5.28,
                        'under_18' => 8.60,
                        '18_20' => 8.60,
                        '21_22' => 11.44,
                    ],
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 13,
                    'minimum_rest_between_shifts_hours' => 11,
                    'breaks' => [
                        'meal_break_threshold_hours' => 6,
                        'meal_break_duration_minutes' => 20,
                        'meal_break_paid' => false,
                    ],
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true,
                ],
                'tax_rules' => [
                    'platform_service_tax_rate' => 0.20,
                    'sales_tax_applicable' => true,
                ],
            ],
        ];

        // Check for state-specific rules first
        if ($state && isset($jurisdictions["{$country}-{$state}"])) {
            return $jurisdictions["{$country}-{$state}"];
        }

        return $jurisdictions[$country] ?? $jurisdictions['US'];
    }

    /**
     * Clear jurisdiction cache (for updates)
     */
    public function clearJurisdictionCache(string $country, ?string $state = null): void
    {
        $cacheKey = "jurisdiction_rules_{$country}_{$state}";
        Cache::forget($cacheKey);
    }

    /**
     * Check for regulatory updates in jurisdiction
     */
    public function checkRegulatoryUpdates(string $country): array
    {
        return [
            'updates' => [],
            'last_checked' => now(),
            'jurisdiction' => $country,
        ];
    }

    /**
     * Clear worker's weekly hours cache.
     */
    public function clearWorkerHoursCache(User $worker, ?Carbon $week = null): void
    {
        $week = $week ?? now()->startOfWeek();
        $cacheKey = "worker_weekly_hours_{$worker->id}_{$week->format('Y-m-d')}";
        Cache::forget($cacheKey);
    }
}
