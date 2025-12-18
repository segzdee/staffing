<?php

namespace App\Services;

use App\Models\AuditChecklist;
use App\Models\MysteryShopper;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftAudit;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QUA-002: Quality Audits Service
 *
 * Manages the quality audit system including shift selection for audits,
 * audit creation and assignment, score calculation, and reporting.
 */
class QualityAuditService
{
    /**
     * Default random audit percentage.
     */
    public const DEFAULT_RANDOM_AUDIT_PERCENTAGE = 5;

    /**
     * High-value shift threshold in cents.
     */
    public const HIGH_VALUE_THRESHOLD_CENTS = 20000; // 200.00

    /**
     * New worker first shifts to audit.
     */
    public const NEW_WORKER_AUDIT_SHIFTS = 5;

    /**
     * Problem venue rating threshold.
     */
    public const PROBLEM_VENUE_RATING_THRESHOLD = 3.5;

    /**
     * Select shifts for audit based on various criteria.
     *
     * @param  int  $count  Number of shifts to select
     * @param  string  $method  Selection method: 'random', 'high_value', 'new_worker', 'problem_venue'
     */
    public function selectShiftsForAudit(int $count, string $method = 'random'): Collection
    {
        return match ($method) {
            'high_value' => $this->selectHighValueShifts($count),
            'new_worker' => $this->selectNewWorkerShifts($count),
            'problem_venue' => $this->selectProblemVenueShifts($count),
            default => $this->selectRandomShifts($count),
        };
    }

    /**
     * Select random shifts for audit.
     */
    protected function selectRandomShifts(int $count): Collection
    {
        return Shift::where('status', 'completed')
            ->whereDoesntHave('audits', function ($query) {
                $query->whereIn('status', [
                    ShiftAudit::STATUS_PENDING,
                    ShiftAudit::STATUS_IN_PROGRESS,
                    ShiftAudit::STATUS_COMPLETED,
                ]);
            })
            ->where('completed_at', '>=', now()->subDays(30))
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    /**
     * Select high-value shifts for audit.
     */
    protected function selectHighValueShifts(int $count): Collection
    {
        return Shift::where('status', 'completed')
            ->whereDoesntHave('audits', function ($query) {
                $query->whereIn('status', [
                    ShiftAudit::STATUS_PENDING,
                    ShiftAudit::STATUS_IN_PROGRESS,
                    ShiftAudit::STATUS_COMPLETED,
                ]);
            })
            ->where('total_business_cost', '>=', self::HIGH_VALUE_THRESHOLD_CENTS)
            ->where('completed_at', '>=', now()->subDays(30))
            ->orderByDesc('total_business_cost')
            ->limit($count)
            ->get();
    }

    /**
     * Select shifts for new workers (first 5 shifts).
     */
    protected function selectNewWorkerShifts(int $count): Collection
    {
        // Find workers who have completed between 1 and 5 shifts
        $newWorkerIds = ShiftAssignment::select('worker_id')
            ->where('status', 'completed')
            ->groupBy('worker_id')
            ->havingRaw('COUNT(*) <= ?', [self::NEW_WORKER_AUDIT_SHIFTS])
            ->pluck('worker_id');

        return Shift::where('status', 'completed')
            ->whereHas('assignments', function ($query) use ($newWorkerIds) {
                $query->whereIn('worker_id', $newWorkerIds)
                    ->where('status', 'completed');
            })
            ->whereDoesntHave('audits', function ($query) {
                $query->whereIn('status', [
                    ShiftAudit::STATUS_PENDING,
                    ShiftAudit::STATUS_IN_PROGRESS,
                    ShiftAudit::STATUS_COMPLETED,
                ]);
            })
            ->where('completed_at', '>=', now()->subDays(30))
            ->orderByDesc('completed_at')
            ->limit($count)
            ->get();
    }

    /**
     * Select shifts from problem venues (low-rated).
     */
    protected function selectProblemVenueShifts(int $count): Collection
    {
        $problemVenueIds = Venue::where('average_rating', '<', self::PROBLEM_VENUE_RATING_THRESHOLD)
            ->pluck('id');

        return Shift::where('status', 'completed')
            ->whereIn('venue_id', $problemVenueIds)
            ->whereDoesntHave('audits', function ($query) {
                $query->whereIn('status', [
                    ShiftAudit::STATUS_PENDING,
                    ShiftAudit::STATUS_IN_PROGRESS,
                    ShiftAudit::STATUS_COMPLETED,
                ]);
            })
            ->where('completed_at', '>=', now()->subDays(30))
            ->orderByDesc('completed_at')
            ->limit($count)
            ->get();
    }

    /**
     * Create a new audit for a shift.
     */
    public function createAudit(Shift $shift, string $type, ?int $assignmentId = null): ShiftAudit
    {
        $auditNumber = ShiftAudit::generateAuditNumber();

        // Get default checklist items based on type
        $checklistItems = $this->getDefaultChecklistItems();

        return ShiftAudit::create([
            'audit_number' => $auditNumber,
            'shift_id' => $shift->id,
            'shift_assignment_id' => $assignmentId,
            'audit_type' => $type,
            'status' => ShiftAudit::STATUS_PENDING,
            'checklist_items' => $checklistItems,
        ]);
    }

    /**
     * Assign an auditor to an audit.
     */
    public function assignAuditor(ShiftAudit $audit, User $auditor): void
    {
        $audit->update([
            'auditor_id' => $auditor->id,
            'status' => ShiftAudit::STATUS_IN_PROGRESS,
        ]);

        Log::info('Auditor assigned to audit', [
            'audit_id' => $audit->id,
            'audit_number' => $audit->audit_number,
            'auditor_id' => $auditor->id,
        ]);
    }

    /**
     * Submit audit results and calculate the score.
     */
    public function submitAuditResults(ShiftAudit $audit, array $results): void
    {
        DB::transaction(function () use ($audit, $results) {
            // Calculate score from checklist items
            $overallScore = $this->calculateAuditScore($audit, $results['checklist_items'] ?? []);

            // Complete the audit
            $audit->complete([
                'checklist_items' => $results['checklist_items'] ?? null,
                'overall_score' => $overallScore,
                'findings' => $results['findings'] ?? null,
                'recommendations' => $results['recommendations'] ?? null,
                'evidence_urls' => $results['evidence_urls'] ?? null,
            ]);

            // If mystery shopper audit, update shopper statistics
            if ($audit->audit_type === ShiftAudit::TYPE_MYSTERY_SHOPPER && $audit->auditor_id) {
                $mysteryShopper = MysteryShopper::where('user_id', $audit->auditor_id)->first();
                if ($mysteryShopper) {
                    $mysteryShopper->recordAuditCompletion($audit);
                }
            }

            Log::info('Audit results submitted', [
                'audit_id' => $audit->id,
                'audit_number' => $audit->audit_number,
                'overall_score' => $overallScore,
                'passed' => $audit->passed,
            ]);
        });
    }

    /**
     * Calculate the audit score from checklist items.
     */
    public function calculateAuditScore(ShiftAudit $audit, array $checklistItems = []): int
    {
        // Use provided items or existing items
        $items = ! empty($checklistItems) ? $checklistItems : ($audit->checklist_items ?? []);

        if (empty($items)) {
            return 0;
        }

        $totalWeight = 0;
        $earnedWeight = 0;

        foreach ($items as $item) {
            $weight = $item['weight'] ?? 1;
            $totalWeight += $weight;

            if (isset($item['passed']) && $item['passed'] === true) {
                $earnedWeight += $weight;
            }
        }

        if ($totalWeight === 0) {
            return 0;
        }

        return (int) round(($earnedWeight / $totalWeight) * 100);
    }

    /**
     * Get audit statistics for a date range.
     */
    public function getAuditStatistics(Carbon $start, Carbon $end): array
    {
        $audits = ShiftAudit::whereBetween('created_at', [$start, $end])->get();
        $completedAudits = $audits->where('status', ShiftAudit::STATUS_COMPLETED);

        return [
            'total_audits' => $audits->count(),
            'completed' => $completedAudits->count(),
            'pending' => $audits->where('status', ShiftAudit::STATUS_PENDING)->count(),
            'in_progress' => $audits->where('status', ShiftAudit::STATUS_IN_PROGRESS)->count(),
            'cancelled' => $audits->where('status', ShiftAudit::STATUS_CANCELLED)->count(),
            'average_score' => $completedAudits->avg('overall_score'),
            'pass_rate' => $completedAudits->count() > 0
                ? round(($completedAudits->where('passed', true)->count() / $completedAudits->count()) * 100, 2)
                : 0,
            'by_type' => [
                'random' => $audits->where('audit_type', ShiftAudit::TYPE_RANDOM)->count(),
                'complaint' => $audits->where('audit_type', ShiftAudit::TYPE_COMPLAINT)->count(),
                'scheduled' => $audits->where('audit_type', ShiftAudit::TYPE_SCHEDULED)->count(),
                'mystery_shopper' => $audits->where('audit_type', ShiftAudit::TYPE_MYSTERY_SHOPPER)->count(),
            ],
            'score_distribution' => [
                'excellent' => $completedAudits->where('overall_score', '>=', 90)->count(),
                'good' => $completedAudits->where('overall_score', '>=', 70)->where('overall_score', '<', 90)->count(),
                'needs_improvement' => $completedAudits->where('overall_score', '>=', 50)->where('overall_score', '<', 70)->count(),
                'poor' => $completedAudits->where('overall_score', '<', 50)->count(),
            ],
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ];
    }

    /**
     * Get audit history for a worker.
     */
    public function getWorkerAuditHistory(User $worker): Collection
    {
        return ShiftAudit::forWorker($worker->id)
            ->with(['shift', 'auditor', 'shiftAssignment'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get audit history for a venue.
     */
    public function getVenueAuditHistory(Venue $venue): Collection
    {
        return ShiftAudit::forVenue($venue->id)
            ->with(['shift', 'auditor', 'shiftAssignment'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Schedule random audits for completed shifts (to be called by scheduler).
     *
     * @return int Number of audits scheduled
     */
    public function scheduleRandomAudits(): int
    {
        // Get completed shifts from yesterday
        $yesterday = Carbon::yesterday();
        $completedShifts = Shift::where('status', 'completed')
            ->whereDate('completed_at', $yesterday)
            ->whereDoesntHave('audits')
            ->get();

        if ($completedShifts->isEmpty()) {
            return 0;
        }

        // Calculate how many to audit (default 5%)
        $auditPercentage = config('overtimestaff.quality.random_audit_percentage', self::DEFAULT_RANDOM_AUDIT_PERCENTAGE);
        $auditCount = max(1, (int) ceil($completedShifts->count() * ($auditPercentage / 100)));

        // Randomly select shifts
        $selectedShifts = $completedShifts->random(min($auditCount, $completedShifts->count()));

        $scheduledCount = 0;
        foreach ($selectedShifts as $shift) {
            try {
                $this->createAudit($shift, ShiftAudit::TYPE_RANDOM);
                $scheduledCount++;
            } catch (\Exception $e) {
                Log::error('Failed to schedule random audit', [
                    'shift_id' => $shift->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Random audits scheduled', [
            'date' => $yesterday->toDateString(),
            'total_completed' => $completedShifts->count(),
            'scheduled' => $scheduledCount,
        ]);

        return $scheduledCount;
    }

    /**
     * Flag an audit for review (escalation).
     */
    public function flagForReview(ShiftAudit $audit, ?string $reason = null): void
    {
        // Add a flag to the audit via findings
        $currentFindings = $audit->findings ?? '';
        $flagNote = "\n\n[FLAGGED FOR REVIEW]\nDate: ".now()->toDateTimeString();
        if ($reason) {
            $flagNote .= "\nReason: ".$reason;
        }

        $audit->update([
            'findings' => $currentFindings.$flagNote,
        ]);

        Log::warning('Audit flagged for review', [
            'audit_id' => $audit->id,
            'audit_number' => $audit->audit_number,
            'reason' => $reason,
        ]);
    }

    /**
     * Create a complaint-driven audit.
     */
    public function createComplaintAudit(Shift $shift, string $complaintDetails, ?int $assignmentId = null): ShiftAudit
    {
        $audit = $this->createAudit($shift, ShiftAudit::TYPE_COMPLAINT, $assignmentId);

        $audit->update([
            'findings' => "[COMPLAINT DETAILS]\n".$complaintDetails,
        ]);

        return $audit;
    }

    /**
     * Create a scheduled audit.
     */
    public function createScheduledAudit(Shift $shift, Carbon $scheduledAt, ?int $auditorId = null): ShiftAudit
    {
        $audit = $this->createAudit($shift, ShiftAudit::TYPE_SCHEDULED);

        $updateData = ['scheduled_at' => $scheduledAt];
        if ($auditorId) {
            $updateData['auditor_id'] = $auditorId;
        }

        $audit->update($updateData);

        return $audit;
    }

    /**
     * Create a mystery shopper audit.
     */
    public function createMysteryShopperAudit(Shift $shift, MysteryShopper $shopper): ShiftAudit
    {
        if (! $shopper->is_active) {
            throw new \InvalidArgumentException('Mystery shopper is not active');
        }

        $audit = $this->createAudit($shift, ShiftAudit::TYPE_MYSTERY_SHOPPER);
        $audit->update(['auditor_id' => $shopper->user_id]);

        return $audit;
    }

    /**
     * Get default checklist items for audits.
     */
    protected function getDefaultChecklistItems(): array
    {
        // Get active checklists and compile items
        $checklists = AuditChecklist::active()->ordered()->get();

        if ($checklists->isEmpty()) {
            // Return hardcoded defaults if no checklists exist
            return $this->getHardcodedDefaultItems();
        }

        $items = [];
        foreach ($checklists as $checklist) {
            foreach ($checklist->items as $item) {
                $items[] = [
                    'id' => $item['id'],
                    'category' => $checklist->category,
                    'question' => $item['question'],
                    'weight' => $item['weight'] ?? 1,
                    'required' => $item['required'] ?? false,
                    'passed' => null,
                    'notes' => null,
                ];
            }
        }

        return $items;
    }

    /**
     * Get hardcoded default checklist items if none exist in database.
     */
    protected function getHardcodedDefaultItems(): array
    {
        return [
            // Punctuality
            [
                'id' => 'default_punctuality_1',
                'category' => 'punctuality',
                'question' => 'Worker arrived on time for the shift',
                'weight' => 2,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
            [
                'id' => 'default_punctuality_2',
                'category' => 'punctuality',
                'question' => 'Worker clocked in within allowed time window',
                'weight' => 1,
                'required' => false,
                'passed' => null,
                'notes' => null,
            ],
            // Appearance
            [
                'id' => 'default_appearance_1',
                'category' => 'appearance',
                'question' => 'Worker wore proper uniform/attire',
                'weight' => 1.5,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
            [
                'id' => 'default_appearance_2',
                'category' => 'appearance',
                'question' => 'Worker maintained professional appearance throughout shift',
                'weight' => 1,
                'required' => false,
                'passed' => null,
                'notes' => null,
            ],
            // Performance
            [
                'id' => 'default_performance_1',
                'category' => 'performance',
                'question' => 'Worker performed assigned tasks correctly',
                'weight' => 2,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
            [
                'id' => 'default_performance_2',
                'category' => 'performance',
                'question' => 'Worker demonstrated required skills',
                'weight' => 1.5,
                'required' => false,
                'passed' => null,
                'notes' => null,
            ],
            // Attitude
            [
                'id' => 'default_attitude_1',
                'category' => 'attitude',
                'question' => 'Worker displayed professional behavior',
                'weight' => 1.5,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
            [
                'id' => 'default_attitude_2',
                'category' => 'attitude',
                'question' => 'Worker was courteous and respectful',
                'weight' => 1,
                'required' => false,
                'passed' => null,
                'notes' => null,
            ],
            // Compliance
            [
                'id' => 'default_compliance_1',
                'category' => 'compliance',
                'question' => 'Worker followed venue rules and policies',
                'weight' => 2,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
            [
                'id' => 'default_compliance_2',
                'category' => 'compliance',
                'question' => 'Worker adhered to safety requirements',
                'weight' => 2,
                'required' => true,
                'passed' => null,
                'notes' => null,
            ],
        ];
    }

    /**
     * Get workers with audit issues (failed audits).
     */
    public function getWorkersWithAuditIssues(int $limit = 20): Collection
    {
        return DB::table('shift_audits')
            ->join('shift_assignments', 'shift_audits.shift_assignment_id', '=', 'shift_assignments.id')
            ->join('users', 'shift_assignments.worker_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as total_audits'),
                DB::raw('SUM(CASE WHEN shift_audits.passed = 0 THEN 1 ELSE 0 END) as failed_audits'),
                DB::raw('AVG(shift_audits.overall_score) as average_score')
            )
            ->where('shift_audits.status', ShiftAudit::STATUS_COMPLETED)
            ->groupBy('users.id', 'users.name')
            ->havingRaw('SUM(CASE WHEN shift_audits.passed = 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('failed_audits')
            ->limit($limit)
            ->get();
    }

    /**
     * Get venues with audit issues (low scores).
     */
    public function getVenuesWithAuditIssues(int $limit = 20): Collection
    {
        return DB::table('shift_audits')
            ->join('shifts', 'shift_audits.shift_id', '=', 'shifts.id')
            ->join('venues', 'shifts.venue_id', '=', 'venues.id')
            ->select(
                'venues.id',
                'venues.name',
                DB::raw('COUNT(*) as total_audits'),
                DB::raw('AVG(shift_audits.overall_score) as average_score'),
                DB::raw('SUM(CASE WHEN shift_audits.passed = 0 THEN 1 ELSE 0 END) as failed_audits')
            )
            ->where('shift_audits.status', ShiftAudit::STATUS_COMPLETED)
            ->whereNotNull('shifts.venue_id')
            ->groupBy('venues.id', 'venues.name')
            ->havingRaw('AVG(shift_audits.overall_score) < ?', [ShiftAudit::PASSING_SCORE])
            ->orderBy('average_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trends over time.
     */
    public function getAuditTrends(int $months = 6): Collection
    {
        return DB::table('shift_audits')
            ->select(
                DB::raw("DATE_FORMAT(completed_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(overall_score) as avg_score'),
                DB::raw('SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed'),
                DB::raw('SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed')
            )
            ->where('status', ShiftAudit::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->subMonths($months))
            ->groupBy(DB::raw("DATE_FORMAT(completed_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }
}
