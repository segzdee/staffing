<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AgencyPerformanceScorecard Model
 *
 * Tracks weekly performance metrics for agencies.
 *
 * @property int $id
 * @property int $agency_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $period_type
 * @property numeric $fill_rate
 * @property numeric $no_show_rate
 * @property numeric $average_worker_rating
 * @property numeric $complaint_rate
 * @property int $total_shifts_assigned
 * @property int $shifts_filled
 * @property int $shifts_unfilled
 * @property int $no_shows
 * @property int $complaints_received
 * @property int $total_ratings
 * @property numeric $total_rating_sum
 * @property int $urgent_fill_requests
 * @property int $urgent_fills_completed
 * @property numeric $urgent_fill_rate
 * @property numeric|null $average_response_time_minutes
 * @property string $status
 * @property array|null $warnings
 * @property array|null $flags
 * @property numeric $target_fill_rate
 * @property numeric $target_no_show_rate
 * @property numeric $target_average_rating
 * @property numeric $target_complaint_rate
 * @property bool $warning_sent
 * @property \Illuminate\Support\Carbon|null $warning_sent_at
 * @property bool $sanction_applied
 * @property string|null $sanction_type
 * @property \Illuminate\Support\Carbon|null $sanction_applied_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property int|null $generated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AgencyPerformanceScorecard extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'period_start',
        'period_end',
        'period_type',
        'fill_rate',
        'no_show_rate',
        'average_worker_rating',
        'complaint_rate',
        'total_shifts_assigned',
        'shifts_filled',
        'shifts_unfilled',
        'no_shows',
        'complaints_received',
        'total_ratings',
        'total_rating_sum',
        'urgent_fill_requests',
        'urgent_fills_completed',
        'urgent_fill_rate',
        'average_response_time_minutes',
        'status',
        'warnings',
        'flags',
        'target_fill_rate',
        'target_no_show_rate',
        'target_average_rating',
        'target_complaint_rate',
        'warning_sent',
        'warning_sent_at',
        'sanction_applied',
        'sanction_type',
        'sanction_applied_at',
        'notes',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'fill_rate' => 'decimal:2',
        'no_show_rate' => 'decimal:2',
        'average_worker_rating' => 'decimal:2',
        'complaint_rate' => 'decimal:2',
        'total_shifts_assigned' => 'integer',
        'shifts_filled' => 'integer',
        'shifts_unfilled' => 'integer',
        'no_shows' => 'integer',
        'complaints_received' => 'integer',
        'total_ratings' => 'integer',
        'total_rating_sum' => 'decimal:2',
        'urgent_fill_requests' => 'integer',
        'urgent_fills_completed' => 'integer',
        'urgent_fill_rate' => 'decimal:2',
        'average_response_time_minutes' => 'decimal:2',
        'warnings' => 'array',
        'flags' => 'array',
        'target_fill_rate' => 'decimal:2',
        'target_no_show_rate' => 'decimal:2',
        'target_average_rating' => 'decimal:2',
        'target_complaint_rate' => 'decimal:2',
        'warning_sent' => 'boolean',
        'warning_sent_at' => 'datetime',
        'sanction_applied' => 'boolean',
        'sanction_applied_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the agency this scorecard belongs to.
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the admin who generated this scorecard.
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope: Recent scorecards (last 12 weeks).
     */
    public function scopeRecent($query, $weeks = 12)
    {
        return $query->where('period_start', '>=', now()->subWeeks($weeks));
    }

    /**
     * Scope: Failed scorecards (red status).
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'red');
    }

    /**
     * Scope: Warning scorecards (yellow status).
     */
    public function scopeWarning($query)
    {
        return $query->where('status', 'yellow');
    }

    /**
     * Scope: For specific agency.
     */
    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Check if performance is passing all targets.
     */
    public function isPassing()
    {
        return $this->status === 'green';
    }

    /**
     * Check if performance needs attention.
     */
    public function needsAttention()
    {
        return in_array($this->status, ['yellow', 'red']);
    }

    /**
     * Get a summary of failed metrics.
     */
    public function getFailedMetrics()
    {
        $failed = [];

        if ($this->fill_rate < $this->target_fill_rate) {
            $failed[] = [
                'metric' => 'Fill Rate',
                'actual' => $this->fill_rate,
                'target' => $this->target_fill_rate,
                'severity' => $this->fill_rate < ($this->target_fill_rate - 10) ? 'critical' : 'warning',
            ];
        }

        if ($this->no_show_rate > $this->target_no_show_rate) {
            $failed[] = [
                'metric' => 'No-Show Rate',
                'actual' => $this->no_show_rate,
                'target' => $this->target_no_show_rate,
                'severity' => $this->no_show_rate > ($this->target_no_show_rate + 2) ? 'critical' : 'warning',
            ];
        }

        if ($this->average_worker_rating < $this->target_average_rating) {
            $failed[] = [
                'metric' => 'Average Worker Rating',
                'actual' => $this->average_worker_rating,
                'target' => $this->target_average_rating,
                'severity' => $this->average_worker_rating < ($this->target_average_rating - 0.5) ? 'critical' : 'warning',
            ];
        }

        if ($this->complaint_rate > $this->target_complaint_rate) {
            $failed[] = [
                'metric' => 'Complaint Rate',
                'actual' => $this->complaint_rate,
                'target' => $this->target_complaint_rate,
                'severity' => $this->complaint_rate > ($this->target_complaint_rate + 2) ? 'critical' : 'warning',
            ];
        }

        return $failed;
    }

    /**
     * Apply warning to agency.
     */
    public function sendWarning()
    {
        $this->update([
            'warning_sent' => true,
            'warning_sent_at' => now(),
        ]);
    }

    /**
     * Apply sanction to agency.
     */
    public function applySanction($sanctionType, $notes = null)
    {
        $this->update([
            'sanction_applied' => true,
            'sanction_type' => $sanctionType,
            'sanction_applied_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get performance trend (comparing to previous period).
     */
    public function getTrend()
    {
        $previous = static::where('agency_id', $this->agency_id)
            ->where('period_end', '<', $this->period_start)
            ->orderBy('period_end', 'desc')
            ->first();

        if (!$previous) {
            return null;
        }

        return [
            'fill_rate_change' => round($this->fill_rate - $previous->fill_rate, 2),
            'no_show_rate_change' => round($this->no_show_rate - $previous->no_show_rate, 2),
            'rating_change' => round($this->average_worker_rating - $previous->average_worker_rating, 2),
            'complaint_rate_change' => round($this->complaint_rate - $previous->complaint_rate, 2),
        ];
    }
}
