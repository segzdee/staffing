<?php

namespace App\Services;

use App\Models\PayrollDeduction;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Notifications\PaymentProcessedNotification;
use App\Notifications\PayrollReadyForApprovalNotification;
use App\Notifications\PaystubAvailableNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * PayrollService - FIN-005: Payroll Processing System
 *
 * Handles all payroll-related operations including creating payroll runs,
 * generating payroll items, calculating deductions and taxes, processing
 * payments, and generating paystubs.
 */
class PayrollService
{
    protected ?StripeClient $stripe = null;

    public function __construct()
    {
        if (config('services.stripe.secret')) {
            $this->stripe = new StripeClient(config('services.stripe.secret'));
        }
    }

    /**
     * Create a new payroll run for the given period.
     */
    public function createPayrollRun(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $payDate,
        User $creator
    ): PayrollRun {
        return DB::transaction(function () use ($periodStart, $periodEnd, $payDate, $creator) {
            $payrollRun = PayrollRun::create([
                'reference' => PayrollRun::generateReference(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'pay_date' => $payDate,
                'status' => PayrollRun::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            Log::info('PayrollService: Created payroll run', [
                'reference' => $payrollRun->reference,
                'period' => "{$periodStart->toDateString()} to {$periodEnd->toDateString()}",
                'pay_date' => $payDate->toDateString(),
            ]);

            return $payrollRun;
        });
    }

    /**
     * Generate payroll items for all completed shifts in the payroll period.
     */
    public function generatePayrollItems(PayrollRun $run): int
    {
        if (! $run->isDraft()) {
            throw new \Exception('Can only generate items for draft payroll runs');
        }

        return DB::transaction(function () use ($run) {
            // Delete existing items (if regenerating)
            $run->items()->delete();

            // Get all completed shift assignments in the period
            $assignments = ShiftAssignment::query()
                ->whereHas('shift', function ($query) use ($run) {
                    $query->whereBetween('shift_date', [
                        $run->period_start,
                        $run->period_end,
                    ]);
                })
                ->where('status', 'completed')
                ->whereNotNull('hours_worked')
                ->with(['shift', 'worker'])
                ->get();

            $itemsCreated = 0;

            foreach ($assignments as $assignment) {
                // Skip if already processed in another payroll
                if ($this->isAssignmentAlreadyProcessed($assignment)) {
                    continue;
                }

                $item = $this->createPayrollItemFromAssignment($run, $assignment);
                if ($item) {
                    $this->calculateDeductions($item);
                    $this->calculateTaxWithholding($item);
                    $item->calculateNetAmount();
                    $itemsCreated++;
                }
            }

            // Recalculate totals
            $run->recalculateTotals();

            Log::info('PayrollService: Generated payroll items', [
                'reference' => $run->reference,
                'items_created' => $itemsCreated,
            ]);

            return $itemsCreated;
        });
    }

    /**
     * Check if an assignment has already been processed in a payroll.
     */
    protected function isAssignmentAlreadyProcessed(ShiftAssignment $assignment): bool
    {
        return PayrollItem::where('shift_assignment_id', $assignment->id)
            ->whereHas('payrollRun', function ($query) {
                $query->whereIn('status', [
                    PayrollRun::STATUS_APPROVED,
                    PayrollRun::STATUS_PROCESSING,
                    PayrollRun::STATUS_COMPLETED,
                ]);
            })
            ->exists();
    }

    /**
     * Create a payroll item from a shift assignment.
     */
    protected function createPayrollItemFromAssignment(
        PayrollRun $run,
        ShiftAssignment $assignment
    ): ?PayrollItem {
        $shift = $assignment->shift;
        $worker = $assignment->worker;

        if (! $shift || ! $worker) {
            return null;
        }

        // Calculate hours and rate
        $hours = (float) ($assignment->net_hours_worked ?? $assignment->hours_worked ?? 0);
        $rate = $this->getWorkerRate($assignment);
        $overtimeHours = (float) ($assignment->overtime_hours ?? 0);

        // Create regular pay item
        $regularHours = $hours - $overtimeHours;
        $regularGross = $regularHours * $rate;

        $item = PayrollItem::create([
            'payroll_run_id' => $run->id,
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'shift_assignment_id' => $assignment->id,
            'type' => PayrollItem::TYPE_REGULAR,
            'description' => "Shift: {$shift->title} on {$shift->shift_date->format('M d, Y')}",
            'hours' => $regularHours,
            'rate' => $rate,
            'gross_amount' => $regularGross,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => $regularGross,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        // Create overtime item if applicable
        if ($overtimeHours > 0) {
            $overtimeRate = $rate * config('payroll.overtime_multiplier', 1.5);
            $overtimeGross = $overtimeHours * $overtimeRate;

            PayrollItem::create([
                'payroll_run_id' => $run->id,
                'user_id' => $worker->id,
                'shift_id' => $shift->id,
                'shift_assignment_id' => $assignment->id,
                'type' => PayrollItem::TYPE_OVERTIME,
                'description' => "Overtime: {$shift->title} on {$shift->shift_date->format('M d, Y')}",
                'hours' => $overtimeHours,
                'rate' => $overtimeRate,
                'gross_amount' => $overtimeGross,
                'deductions' => 0,
                'tax_withheld' => 0,
                'net_amount' => $overtimeGross,
                'status' => PayrollItem::STATUS_PENDING,
            ]);
        }

        return $item;
    }

    /**
     * Get the worker's hourly rate for the assignment.
     */
    protected function getWorkerRate(ShiftAssignment $assignment): float
    {
        $shift = $assignment->shift;

        // Check for custom rate on the assignment
        if ($assignment->worker_pay_amount && $assignment->hours_worked) {
            return (float) ($assignment->worker_pay_amount / $assignment->hours_worked);
        }

        // Use shift's final rate or base rate
        $rate = $shift->final_rate ?? $shift->base_rate;

        // If Money object, convert to decimal
        if (is_object($rate) && method_exists($rate, 'getAmount')) {
            return ((float) $rate->getAmount()) / 100;
        }

        return (float) $rate;
    }

    /**
     * Calculate and apply deductions to a payroll item.
     */
    public function calculateDeductions(PayrollItem $item): void
    {
        $totalDeductions = 0;

        // Platform fee deduction
        $platformFeeRate = config('payroll.platform_fee_rate', 10);
        if ($platformFeeRate > 0) {
            $platformFee = PayrollDeduction::calculateFromPercentage(
                $item->gross_amount,
                $platformFeeRate
            );

            $item->addDeduction(
                PayrollDeduction::TYPE_PLATFORM_FEE,
                "Platform fee ({$platformFeeRate}%)",
                $platformFee,
                true,
                $platformFeeRate
            );

            $totalDeductions += $platformFee;
        }

        // Check for any garnishments or advance repayments for the worker
        // This could be expanded to pull from a worker_deductions table
        // For now, we'll skip this as it's not implemented

        $item->deductions = $totalDeductions;
        $item->save();
    }

    /**
     * Calculate and apply tax withholding to a payroll item.
     */
    public function calculateTaxWithholding(PayrollItem $item): void
    {
        $worker = $item->user;

        // Get effective tax rate based on worker's jurisdiction
        $taxRate = $this->getWorkerTaxRate($worker);

        if ($taxRate > 0) {
            $taxAmount = PayrollDeduction::calculateFromPercentage(
                $item->gross_amount - $item->deductions,
                $taxRate
            );

            $item->addDeduction(
                PayrollDeduction::TYPE_TAX,
                "Tax withholding ({$taxRate}%)",
                $taxAmount,
                true,
                $taxRate
            );

            $item->tax_withheld = $taxAmount;
            $item->save();
        }
    }

    /**
     * Get the tax rate for a worker based on their jurisdiction.
     */
    protected function getWorkerTaxRate(User $worker): float
    {
        // Try to use TaxJurisdictionService if available
        try {
            $taxService = app(TaxJurisdictionService::class);

            // Get worker's country - if null, use default tax rate
            $countryCode = $this->getWorkerCountry($worker);
            if (! $countryCode) {
                return config('payroll.default_tax_rate', 0);
            }

            // Get the worker's jurisdiction using public method
            $jurisdiction = $taxService->getJurisdiction(
                $countryCode,
                $this->getWorkerState($worker)
            );

            if ($jurisdiction) {
                return $taxService->getEffectiveTaxRate($worker, $jurisdiction);
            }

            // Fallback to default tax rate if no jurisdiction found
            return config('payroll.default_tax_rate', 0);
        } catch (\Exception $e) {
            // Fallback to default tax rate
            return config('payroll.default_tax_rate', 0);
        }
    }

    /**
     * Get the worker's country code from their profile.
     */
    protected function getWorkerCountry(User $worker): ?string
    {
        if ($worker->workerProfile && $worker->workerProfile->country_code) {
            return $worker->workerProfile->country_code;
        }

        return null;
    }

    /**
     * Get the worker's state code from their profile.
     */
    protected function getWorkerState(User $worker): ?string
    {
        if ($worker->workerProfile && $worker->workerProfile->state) {
            return $worker->workerProfile->state;
        }

        return null;
    }

    /**
     * Submit a payroll run for approval.
     */
    public function submitForApproval(PayrollRun $run): bool
    {
        if (! $run->isDraft()) {
            return false;
        }

        if ($run->items()->count() === 0) {
            throw new \Exception('Cannot submit empty payroll for approval');
        }

        $run->submitForApproval();

        // Notify admins
        $this->notifyAdminsForApproval($run);

        Log::info('PayrollService: Submitted payroll for approval', [
            'reference' => $run->reference,
        ]);

        return true;
    }

    /**
     * Approve a payroll run.
     */
    public function approvePayrollRun(PayrollRun $run, User $approver): bool
    {
        if (! $run->canApprove()) {
            return false;
        }

        // Cannot approve your own payroll (separation of duties)
        if ($run->created_by === $approver->id && config('payroll.require_different_approver', true)) {
            throw new \Exception('Cannot approve your own payroll run');
        }

        $success = $run->approve($approver);

        if ($success) {
            // Approve all items
            $run->items()->update(['status' => PayrollItem::STATUS_APPROVED]);

            Log::info('PayrollService: Payroll approved', [
                'reference' => $run->reference,
                'approved_by' => $approver->id,
            ]);

            // Auto-process if configured
            if (config('payroll.auto_process_approved', false)) {
                $this->processPayrollRun($run);
            }
        }

        return $success;
    }

    /**
     * Process all payments in a payroll run.
     */
    public function processPayrollRun(PayrollRun $run): array
    {
        if (! $run->canProcess()) {
            throw new \Exception('Payroll run cannot be processed in current state');
        }

        $run->markAsProcessing();

        $results = [
            'total' => $run->items()->count(),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Process items grouped by worker to potentially batch transfers
        $itemsByWorker = $run->getItemsByWorker();

        foreach ($itemsByWorker as $userId => $items) {
            foreach ($items as $item) {
                try {
                    $success = $this->processPayrollItem($item);

                    if ($success) {
                        $results['successful']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item_id' => $item->id,
                        'worker_id' => $userId,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('PayrollService: Failed to process item', [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);

                    $item->markAsFailed();
                }
            }
        }

        // Update payroll run status based on results
        if ($results['failed'] === 0 && $results['successful'] > 0) {
            $run->markAsCompleted();
            $this->sendPaystubNotifications($run);
        } elseif ($results['successful'] > 0) {
            // Partial success - keep in processing for manual intervention
            Log::warning('PayrollService: Partial payroll completion', [
                'reference' => $run->reference,
                'results' => $results,
            ]);
        } else {
            $run->markAsFailed('All payment transfers failed');
        }

        return $results;
    }

    /**
     * Process a single payroll item payment.
     */
    public function processPayrollItem(PayrollItem $item): bool
    {
        if (! $item->isApproved()) {
            return false;
        }

        // Check minimum payout amount
        $minPayout = config('payroll.min_payout_amount', 10.00);
        if ($item->net_amount < $minPayout) {
            Log::info('PayrollService: Item below minimum payout', [
                'item_id' => $item->id,
                'net_amount' => $item->net_amount,
                'min_payout' => $minPayout,
            ]);

            return false;
        }

        $worker = $item->user;

        // Check if worker has valid payout method
        if (! $worker->hasValidPayoutMethod()) {
            throw new \Exception("Worker {$worker->id} does not have a valid payout method");
        }

        // Process payment via Stripe Connect
        $transferId = $this->executeStripeTransfer($item, $worker);

        if ($transferId) {
            $paymentReference = 'PAY-'.strtoupper(substr(md5($item->id.time()), 0, 8));
            $item->markAsPaid($paymentReference, $transferId);

            // Send notification
            try {
                $worker->notify(new PaymentProcessedNotification($item));
            } catch (\Exception $e) {
                Log::warning('PayrollService: Failed to send payment notification', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        }

        return false;
    }

    /**
     * Execute a Stripe transfer for the payroll item.
     */
    protected function executeStripeTransfer(PayrollItem $item, User $worker): ?string
    {
        if (! $this->stripe || ! $worker->stripe_connect_id) {
            // Simulate transfer for testing/non-Stripe environments
            Log::info('PayrollService: Simulated transfer (no Stripe)', [
                'item_id' => $item->id,
                'amount' => $item->net_amount,
            ]);

            return 'sim_'.uniqid();
        }

        try {
            $transfer = $this->stripe->transfers->create([
                'amount' => (int) ($item->net_amount * 100), // Convert to cents
                'currency' => config('payroll.currency', 'usd'),
                'destination' => $worker->stripe_connect_id,
                'transfer_group' => $item->payrollRun->reference,
                'metadata' => [
                    'payroll_run_id' => $item->payroll_run_id,
                    'payroll_item_id' => $item->id,
                    'worker_id' => $worker->id,
                    'type' => $item->type,
                ],
            ]);

            return $transfer->id;
        } catch (\Exception $e) {
            Log::error('PayrollService: Stripe transfer failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate paystubs for all workers in a payroll run.
     */
    public function generatePaystubs(PayrollRun $run): Collection
    {
        $paystubs = collect();
        $itemsByWorker = $run->getItemsByWorker();

        foreach ($itemsByWorker as $userId => $items) {
            $worker = User::find($userId);
            if (! $worker) {
                continue;
            }

            $paystub = $this->getWorkerPaystub($run, $worker);
            $paystubs->push($paystub);
        }

        return $paystubs;
    }

    /**
     * Get paystub data for a specific worker in a payroll run.
     */
    public function getWorkerPaystub(PayrollRun $run, User $worker): array
    {
        $items = $run->items()
            ->where('user_id', $worker->id)
            ->with(['shift', 'payrollDeductions'])
            ->get();

        $grossTotal = $items->sum('gross_amount');
        $deductionsTotal = $items->sum('deductions');
        $taxTotal = $items->sum('tax_withheld');
        $netTotal = $items->sum('net_amount');

        // Get all deductions grouped by type
        $deductionsByType = PayrollDeduction::whereIn('payroll_item_id', $items->pluck('id'))
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->sum('amount'));

        return [
            'payroll_run' => [
                'reference' => $run->reference,
                'period_start' => $run->period_start->format('M d, Y'),
                'period_end' => $run->period_end->format('M d, Y'),
                'pay_date' => $run->pay_date->format('M d, Y'),
                'status' => $run->status,
            ],
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'email' => $worker->email,
            ],
            'earnings' => $items->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type,
                'type_label' => $item->type_label,
                'description' => $item->description,
                'hours' => $item->hours,
                'rate' => $item->rate,
                'gross_amount' => $item->gross_amount,
                'status' => $item->status,
                'shift' => $item->shift ? [
                    'title' => $item->shift->title,
                    'date' => $item->shift->shift_date->format('M d, Y'),
                ] : null,
            ])->toArray(),
            'deductions' => [
                'platform_fee' => $deductionsByType->get(PayrollDeduction::TYPE_PLATFORM_FEE, 0),
                'tax' => $deductionsByType->get(PayrollDeduction::TYPE_TAX, 0),
                'garnishment' => $deductionsByType->get(PayrollDeduction::TYPE_GARNISHMENT, 0),
                'advance_repayment' => $deductionsByType->get(PayrollDeduction::TYPE_ADVANCE_REPAYMENT, 0),
                'other' => $deductionsByType->get(PayrollDeduction::TYPE_OTHER, 0),
            ],
            'totals' => [
                'gross' => $grossTotal,
                'deductions' => $deductionsTotal,
                'taxes' => $taxTotal,
                'net' => $netTotal,
            ],
            'total_hours' => $items->sum('hours'),
            'item_count' => $items->count(),
        ];
    }

    /**
     * Export payroll data in the specified format.
     */
    public function exportPayroll(PayrollRun $run, string $format = 'csv'): string
    {
        $items = $run->items()
            ->with(['user', 'shift', 'payrollDeductions'])
            ->get();

        switch (strtolower($format)) {
            case 'csv':
                return $this->exportToCsv($run, $items);
            case 'json':
                return $this->exportToJson($run, $items);
            default:
                throw new \Exception("Unsupported export format: {$format}");
        }
    }

    /**
     * Export payroll to CSV format.
     */
    protected function exportToCsv(PayrollRun $run, Collection $items): string
    {
        $headers = [
            'Payroll Ref',
            'Worker ID',
            'Worker Name',
            'Worker Email',
            'Shift ID',
            'Shift Title',
            'Type',
            'Description',
            'Hours',
            'Rate',
            'Gross Amount',
            'Deductions',
            'Tax Withheld',
            'Net Amount',
            'Status',
            'Payment Reference',
            'Paid At',
        ];

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $run->reference,
                $item->user_id,
                $item->user->name ?? 'N/A',
                $item->user->email ?? 'N/A',
                $item->shift_id ?? '',
                $item->shift->title ?? 'N/A',
                $item->type,
                $item->description,
                $item->hours,
                $item->rate,
                $item->gross_amount,
                $item->deductions,
                $item->tax_withheld,
                $item->net_amount,
                $item->status,
                $item->payment_reference ?? '',
                $item->paid_at ? $item->paid_at->format('Y-m-d H:i:s') : '',
            ];
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export payroll to JSON format.
     */
    protected function exportToJson(PayrollRun $run, Collection $items): string
    {
        $data = [
            'payroll_run' => [
                'reference' => $run->reference,
                'period_start' => $run->period_start->toDateString(),
                'period_end' => $run->period_end->toDateString(),
                'pay_date' => $run->pay_date->toDateString(),
                'status' => $run->status,
                'totals' => [
                    'workers' => $run->total_workers,
                    'shifts' => $run->total_shifts,
                    'gross_amount' => $run->gross_amount,
                    'total_deductions' => $run->total_deductions,
                    'total_taxes' => $run->total_taxes,
                    'net_amount' => $run->net_amount,
                ],
            ],
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'worker' => [
                    'id' => $item->user_id,
                    'name' => $item->user->name ?? 'N/A',
                    'email' => $item->user->email ?? 'N/A',
                ],
                'shift' => $item->shift ? [
                    'id' => $item->shift_id,
                    'title' => $item->shift->title,
                ] : null,
                'type' => $item->type,
                'description' => $item->description,
                'hours' => (float) $item->hours,
                'rate' => (float) $item->rate,
                'gross_amount' => (float) $item->gross_amount,
                'deductions' => (float) $item->deductions,
                'tax_withheld' => (float) $item->tax_withheld,
                'net_amount' => (float) $item->net_amount,
                'status' => $item->status,
                'payment_reference' => $item->payment_reference,
                'paid_at' => $item->paid_at?->toIso8601String(),
            ])->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Get a summary of a payroll run.
     */
    public function getPayrollSummary(PayrollRun $run): array
    {
        $items = $run->items()->with('user')->get();

        $byType = $items->groupBy('type')->map(fn ($group) => [
            'count' => $group->count(),
            'total_hours' => $group->sum('hours'),
            'gross_amount' => $group->sum('gross_amount'),
            'net_amount' => $group->sum('net_amount'),
        ]);

        $byStatus = $items->groupBy('status')->map(fn ($group) => $group->count());

        return [
            'reference' => $run->reference,
            'period' => [
                'start' => $run->period_start->format('M d, Y'),
                'end' => $run->period_end->format('M d, Y'),
            ],
            'pay_date' => $run->pay_date->format('M d, Y'),
            'status' => $run->status,
            'totals' => [
                'workers' => $run->total_workers,
                'shifts' => $run->total_shifts,
                'items' => $items->count(),
                'gross' => $run->gross_amount,
                'deductions' => $run->total_deductions,
                'taxes' => $run->total_taxes,
                'net' => $run->net_amount,
            ],
            'by_type' => $byType->toArray(),
            'by_status' => $byStatus->toArray(),
            'progress' => $run->getProgressPercentage(),
            'creator' => $run->creator ? $run->creator->name : 'N/A',
            'approver' => $run->approver ? $run->approver->name : null,
            'approved_at' => $run->approved_at?->format('M d, Y H:i'),
            'processed_at' => $run->processed_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Add a manual adjustment or bonus to a payroll run.
     */
    public function addManualItem(
        PayrollRun $run,
        User $worker,
        string $type,
        string $description,
        float $amount,
        float $hours = 0,
        float $rate = 0
    ): PayrollItem {
        if (! $run->canEdit()) {
            throw new \Exception('Cannot add items to payroll run in current state');
        }

        $item = PayrollItem::create([
            'payroll_run_id' => $run->id,
            'user_id' => $worker->id,
            'shift_id' => null,
            'shift_assignment_id' => null,
            'type' => $type,
            'description' => $description,
            'hours' => $hours,
            'rate' => $rate,
            'gross_amount' => $amount,
            'deductions' => 0,
            'tax_withheld' => 0,
            'net_amount' => $amount,
            'status' => PayrollItem::STATUS_PENDING,
        ]);

        // Apply deductions and taxes if not a reimbursement
        if ($type !== PayrollItem::TYPE_REIMBURSEMENT) {
            $this->calculateDeductions($item);
            $this->calculateTaxWithholding($item);
            $item->calculateNetAmount();
        }

        // Recalculate totals
        $run->recalculateTotals();

        return $item;
    }

    /**
     * Remove an item from a payroll run.
     */
    public function removeItem(PayrollItem $item): bool
    {
        $run = $item->payrollRun;

        if (! $run->canEdit()) {
            throw new \Exception('Cannot remove items from payroll run in current state');
        }

        $item->delete();
        $run->recalculateTotals();

        return true;
    }

    /**
     * Notify admins that a payroll run is ready for approval.
     */
    protected function notifyAdminsForApproval(PayrollRun $run): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify(new PayrollReadyForApprovalNotification($run));
            } catch (\Exception $e) {
                Log::warning('PayrollService: Failed to notify admin', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send paystub notifications to all workers in a completed payroll run.
     */
    protected function sendPaystubNotifications(PayrollRun $run): void
    {
        $workerIds = $run->items()->pluck('user_id')->unique();

        foreach ($workerIds as $workerId) {
            $worker = User::find($workerId);
            if (! $worker) {
                continue;
            }

            try {
                $worker->notify(new PaystubAvailableNotification($run));
            } catch (\Exception $e) {
                Log::warning('PayrollService: Failed to send paystub notification', [
                    'worker_id' => $workerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get payroll history for a worker.
     */
    public function getWorkerPayrollHistory(User $worker, int $limit = 12): Collection
    {
        return PayrollRun::whereHas('items', function ($query) use ($worker) {
            $query->where('user_id', $worker->id);
        })
            ->where('status', PayrollRun::STATUS_COMPLETED)
            ->orderBy('pay_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($run) => [
                'reference' => $run->reference,
                'period' => "{$run->period_start->format('M d')} - {$run->period_end->format('M d, Y')}",
                'pay_date' => $run->pay_date->format('M d, Y'),
                'net_amount' => $run->items()->where('user_id', $worker->id)->sum('net_amount'),
            ]);
    }
}
