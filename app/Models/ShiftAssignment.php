<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property int|null $agency_id
 * @property numeric|null $agency_commission_rate
 * @property string|null $assigned_at
 * @property int $assigned_by
 * @property \Illuminate\Support\Carbon|null $check_in_time
 * @property string|null $actual_clock_in
 * @property numeric|null $clock_in_lat
 * @property numeric|null $clock_in_lng
 * @property int|null $clock_in_accuracy
 * @property string|null $clock_in_photo_url
 * @property int $clock_in_verified
 * @property int $clock_in_attempts
 * @property string|null $clock_in_failure_reason
 * @property int $late_minutes
 * @property int $was_late
 * @property int $lateness_flagged
 * @property numeric|null $face_match_confidence
 * @property int $liveness_passed
 * @property string|null $verification_method
 * @property string|null $breaks
 * @property int $total_break_minutes
 * @property int $mandatory_break_taken
 * @property int $break_compliance_met
 * @property string|null $break_required_by
 * @property string|null $break_warning_sent_at
 * @property \Illuminate\Support\Carbon|null $check_out_time
 * @property string|null $actual_clock_out
 * @property numeric|null $clock_out_lat
 * @property numeric|null $clock_out_lng
 * @property string|null $clock_out_photo_url
 * @property string|null $completion_notes
 * @property string|null $supervisor_signature
 * @property numeric|null $hours_worked
 * @property numeric|null $gross_hours
 * @property numeric $break_deduction_hours
 * @property numeric|null $net_hours_worked
 * @property numeric|null $billable_hours
 * @property numeric $overtime_hours
 * @property int $early_departure
 * @property int $early_departure_minutes
 * @property string|null $early_departure_reason
 * @property int $overtime_worked
 * @property int $overtime_approved
 * @property int|null $overtime_approved_by
 * @property string|null $overtime_approved_at
 * @property int $auto_clocked_out
 * @property string|null $auto_clock_out_time
 * @property string|null $auto_clock_out_reason
 * @property numeric|null $business_adjusted_hours
 * @property string|null $business_adjustment_reason
 * @property string|null $business_verified_at
 * @property int|null $business_verified_by
 * @property numeric|null $business_rating
 * @property string|null $business_feedback
 * @property string $status
 * @property string $payment_status
 * @property numeric|null $worker_pay_amount
 * @property string|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $assignedBy
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\ShiftPayment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratings
 * @property-read int|null $ratings_count
 * @property-read \App\Models\Shift $shift
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSwap> $swapRequests
 * @property-read int|null $swap_requests_count
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereActualClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereActualClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAgencyCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAutoClockOutReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAutoClockOutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereAutoClockedOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBillableHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBreakComplianceMet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBreakDeductionHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBreakRequiredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBreakWarningSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBreaks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessAdjustedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessAdjustmentReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereBusinessVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereCheckInTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereCheckOutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInPhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockInVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockOutLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockOutLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereClockOutPhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereCompletionNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereEarlyDeparture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereEarlyDepartureMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereEarlyDepartureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereFaceMatchConfidence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereGrossHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereLateMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereLatenessFlagged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereLivenessPassed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereMandatoryBreakTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereNetHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereOvertimeApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereOvertimeApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereOvertimeApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereOvertimeWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereSupervisorSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereTotalBreakMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereVerificationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereWasLate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereWorkerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAssignment whereWorkerPayAmount($value)
 * @mixin \Eloquent
 */
class ShiftAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shift_id',
        'worker_id',
        'assigned_by',
        'check_in_time',
        'check_out_time',
        'hours_worked',
        'status',
        'payment_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'hours_worked' => 'decimal:2',
    ];

    /**
     * Get the shift this assignment is for.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker assigned.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the user who assigned the worker (business or agent).
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the payment for this assignment.
     */
    public function payment()
    {
        return $this->hasOne(ShiftPayment::class);
    }

    /**
     * Alias for payment() - used in views for semantic clarity.
     * Maps to the shift_payments table via shift_assignment_id.
     */
    public function shiftPayment()
    {
        return $this->payment();
    }

    /**
     * Get the rating for this assignment.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get swap requests for this assignment.
     */
    public function swapRequests()
    {
        return $this->hasMany(ShiftSwap::class);
    }

    /**
     * Get notifications for this assignment.
     */
    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable');
    }

    /**
     * Check in to shift.
     */
    public function checkIn()
    {
        $this->update([
            'check_in_time' => now(),
            'status' => 'checked_in'
        ]);
    }

    /**
     * Check out from shift.
     */
    public function checkOut()
    {
        $checkOutTime = now();
        $hoursWorked = $this->check_in_time
            ? $this->check_in_time->diffInMinutes($checkOutTime) / 60
            : 0;

        $this->update([
            'check_out_time' => $checkOutTime,
            'hours_worked' => round($hoursWorked, 2),
            'status' => 'checked_out'
        ]);
    }

    /**
     * Mark as completed.
     */
    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark as no-show.
     */
    public function markNoShow()
    {
        $this->update(['status' => 'no_show']);
    }

    /**
     * Check if worker is checked in.
     */
    public function isCheckedIn()
    {
        return $this->status === 'checked_in';
    }

    /**
     * Check if worker is checked out.
     */
    public function isCheckedOut()
    {
        return $this->status === 'checked_out';
    }

    /**
     * Check if assignment is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Scope for active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'checked_in']);
    }

    /**
     * Scope for completed assignments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // =========================================
    // SL-006: Break Enforcement Methods
    // =========================================

    /**
     * Get hours worked since clock-in (real-time).
     *
     * @return float
     */
    public function getHoursWorkedSinceClockIn(): float
    {
        if (!$this->check_in_time) {
            return 0;
        }

        $endTime = $this->check_out_time ?? now();
        $minutes = $this->check_in_time->diffInMinutes($endTime);

        // Subtract break time
        $breakMinutes = $this->total_break_minutes ?? 0;
        $netMinutes = $minutes - $breakMinutes;

        return round($netMinutes / 60, 2);
    }

    /**
     * Check if worker needs to take a break based on shift requirements.
     *
     * @return array
     */
    public function checkBreakCompliance(): array
    {
        $shift = $this->shift;

        // If shift doesn't require break, always compliant
        if (!$shift || !$shift->requiresBreak()) {
            return [
                'requires_break' => false,
                'break_taken' => false,
                'break_minutes' => 0,
                'compliant' => true,
                'needs_reminder' => false,
            ];
        }

        $requiredMinutes = $shift->getRequiredBreakMinutes();
        $hoursWorked = $this->getHoursWorkedSinceClockIn();
        $breakTaken = $this->mandatory_break_taken ?? false;
        $breakMinutes = $this->total_break_minutes ?? 0;

        // Check if worker has been working long enough to need a break
        $needsBreak = $hoursWorked >= 6;

        // Compliance: break taken and meets minimum duration
        $compliant = !$needsBreak || ($breakTaken && $breakMinutes >= $requiredMinutes);

        // Needs reminder if:
        // 1. Working 6+ hours
        // 2. Haven't taken break yet or break was insufficient
        // 3. No reminder sent in last 30 minutes
        $needsReminder = $needsBreak
            && (!$breakTaken || $breakMinutes < $requiredMinutes)
            && !$this->hasRecentBreakWarning();

        return [
            'requires_break' => $needsBreak,
            'break_taken' => $breakTaken,
            'break_minutes' => $breakMinutes,
            'required_minutes' => $requiredMinutes,
            'compliant' => $compliant,
            'needs_reminder' => $needsReminder,
            'hours_worked' => $hoursWorked,
        ];
    }

    /**
     * Check if a break warning was sent recently (within 30 minutes).
     *
     * @return bool
     */
    protected function hasRecentBreakWarning(): bool
    {
        if (!$this->break_warning_sent_at) {
            return false;
        }

        $warningSentAt = \Carbon\Carbon::parse($this->break_warning_sent_at);
        return $warningSentAt->diffInMinutes(now()) < 30;
    }

    /**
     * Record that a break warning was sent.
     */
    public function recordBreakWarning()
    {
        $this->break_warning_sent_at = now();
        $this->save();
    }

    /**
     * Start a break for this assignment.
     *
     * @return bool
     */
    public function startBreak(): bool
    {
        $breaks = $this->breaks ? json_decode($this->breaks, true) : [];

        // Check if already on break
        foreach ($breaks as $break) {
            if (!isset($break['end_time'])) {
                return false; // Already on break
            }
        }

        // Add new break
        $breaks[] = [
            'start_time' => now()->toDateTimeString(),
            'end_time' => null,
        ];

        $this->breaks = json_encode($breaks);
        $this->save();

        return true;
    }

    /**
     * End the current break.
     *
     * @return bool
     */
    public function endBreak(): bool
    {
        $breaks = $this->breaks ? json_decode($this->breaks, true) : [];

        // Find active break
        foreach ($breaks as $index => $break) {
            if (!isset($break['end_time'])) {
                $breaks[$index]['end_time'] = now()->toDateTimeString();

                // Calculate break duration
                $startTime = \Carbon\Carbon::parse($break['start_time']);
                $endTime = now();
                $minutes = $startTime->diffInMinutes($endTime);
                $breaks[$index]['duration_minutes'] = $minutes;

                $this->breaks = json_encode($breaks);

                // Update total break minutes
                $this->total_break_minutes = ($this->total_break_minutes ?? 0) + $minutes;

                // Check if this break meets minimum requirement
                $shift = $this->shift;
                if ($shift && $shift->requiresBreak()) {
                    $requiredMinutes = $shift->getRequiredBreakMinutes();
                    if ($this->total_break_minutes >= $requiredMinutes) {
                        $this->mandatory_break_taken = true;
                        $this->break_compliance_met = true;
                    }
                }

                $this->save();
                return true;
            }
        }

        return false; // No active break found
    }

    /**
     * Get all breaks taken during this assignment.
     *
     * @return array
     */
    public function getBreaks(): array
    {
        if (!$this->breaks) {
            return [];
        }

        return json_decode($this->breaks, true) ?? [];
    }

    /**
     * Calculate net hours worked (gross hours - break hours).
     *
     * @return float
     */
    public function calculateNetHoursWorked(): float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }

        $grossMinutes = $this->check_in_time->diffInMinutes($this->check_out_time);
        $breakMinutes = $this->total_break_minutes ?? 0;
        $netMinutes = $grossMinutes - $breakMinutes;

        $this->gross_hours = round($grossMinutes / 60, 2);
        $this->break_deduction_hours = round($breakMinutes / 60, 2);
        $this->net_hours_worked = round($netMinutes / 60, 2);
        $this->save();

        return $this->net_hours_worked;
    }
}
