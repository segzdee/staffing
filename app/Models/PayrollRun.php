<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PayrollRun Model - FIN-005: Payroll Processing System
 *
 * Represents a payroll batch run for processing worker payments.
 *
 * @property int $id
 * @property string $reference
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property \Illuminate\Support\Carbon $pay_date
 * @property string $status
 * @property int $total_workers
 * @property int $total_shifts
 * @property float $gross_amount
 * @property float $total_deductions
 * @property float $total_taxes
 * @property float $net_amount
 * @property int $created_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayrollRun extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'period_start',
        'period_end',
        'pay_date',
        'status',
        'total_workers',
        'total_shifts',
        'gross_amount',
        'total_deductions',
        'total_taxes',
        'net_amount',
        'created_by',
        'approved_by',
        'approved_at',
        'processed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'pay_date' => 'date',
        'gross_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'total_workers' => 'integer',
        'total_shifts' => 'integer',
    ];

    /**
     * Get the user who created this payroll run.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this payroll run.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all payroll items for this run.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Generate a unique reference number for a new payroll run.
     */
    public static function generateReference(): string
    {
        $year = now()->year;
        $lastRun = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRun ? intval(substr($lastRun->reference, -3)) + 1 : 1;

        return sprintf('PR-%d-%03d', $year, $sequence);
    }

    /**
     * Check if this payroll run is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if this payroll run is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if this payroll run is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if this payroll run is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if this payroll run is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this payroll run has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if this payroll run can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if this payroll run can be approved.
     */
    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if this payroll run can be processed.
     */
    public function canProcess(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Submit this payroll run for approval.
     */
    public function submitForApproval(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->update(['status' => self::STATUS_PENDING_APPROVAL]);

        return true;
    }

    /**
     * Approve this payroll run.
     */
    public function approve(User $approver): bool
    {
        if (! $this->canApprove()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark this payroll run as processing.
     */
    public function markAsProcessing(): bool
    {
        if (! $this->canProcess()) {
            return false;
        }

        $this->update(['status' => self::STATUS_PROCESSING]);

        return true;
    }

    /**
     * Mark this payroll run as completed.
     */
    public function markAsCompleted(): bool
    {
        if ($this->status !== self::STATUS_PROCESSING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark this payroll run as failed.
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason ? ($this->notes ? $this->notes."\n\nFailure reason: ".$reason : 'Failure reason: '.$reason) : $this->notes,
        ]);

        return true;
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items;

        $this->update([
            'total_workers' => $items->pluck('user_id')->unique()->count(),
            'total_shifts' => $items->whereNotNull('shift_id')->count(),
            'gross_amount' => $items->sum('gross_amount'),
            'total_deductions' => $items->sum('deductions'),
            'total_taxes' => $items->sum('tax_withheld'),
            'net_amount' => $items->sum('net_amount'),
        ]);
    }

    /**
     * Get processing progress percentage.
     */
    public function getProgressPercentage(): int
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0;
        }

        $processed = $this->items()->whereIn('status', ['paid', 'failed'])->count();

        return (int) round(($processed / $total) * 100);
    }

    /**
     * Get items grouped by worker.
     */
    public function getItemsByWorker(): \Illuminate\Support\Collection
    {
        return $this->items()
            ->with('user')
            ->get()
            ->groupBy('user_id');
    }

    /**
     * Scope for draft payroll runs.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for pending approval payroll runs.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for approved payroll runs.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for completed payroll runs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for payroll runs within a date range.
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }
}
