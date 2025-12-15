<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property string $template_name
 * @property string|null $description
 * @property string $title
 * @property string $shift_description
 * @property string $industry
 * @property string $location_address
 * @property string $location_city
 * @property string $location_state
 * @property string $location_country
 * @property float|null $location_lat
 * @property float|null $location_lng
 * @property string $start_time
 * @property string $end_time
 * @property float $duration_hours
 * @property float $base_rate
 * @property string $urgency_level
 * @property int $required_workers
 * @property array<array-key, mixed>|null $requirements
 * @property string|null $dress_code
 * @property string|null $parking_info
 * @property string|null $break_info
 * @property string|null $special_instructions
 * @property bool $auto_renew
 * @property string|null $recurrence_pattern
 * @property array<array-key, mixed>|null $recurrence_days
 * @property \Illuminate\Support\Carbon|null $recurrence_start_date
 * @property \Illuminate\Support\Carbon|null $recurrence_end_date
 * @property int $times_used
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate autoRenew()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate forIndustry($industry)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereAutoRenew($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereBaseRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereBreakInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereDressCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereDurationHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLocationState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereParkingInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRecurrenceDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRecurrenceEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRecurrencePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRecurrenceStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRequiredWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereShiftDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereSpecialInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereTimesUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereUrgencyLevel($value)
 * @mixin \Eloquent
 */
class ShiftTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'template_name',
        'description',
        'title',
        'shift_description',
        'industry',
        'location_address',
        'location_city',
        'location_state',
        'location_country',
        'location_lat',
        'location_lng',
        'start_time',
        'end_time',
        'duration_hours',
        'base_rate',
        'urgency_level',
        'required_workers',
        'requirements',
        'dress_code',
        'parking_info',
        'break_info',
        'special_instructions',
        'auto_renew',
        'recurrence_pattern',
        'recurrence_days',
        'recurrence_start_date',
        'recurrence_end_date',
        'times_used',
        'last_used_at',
    ];

    protected $casts = [
        'location_lat' => 'float',
        'location_lng' => 'float',
        'duration_hours' => 'float',
        'base_rate' => 'float',
        'required_workers' => 'integer',
        'requirements' => 'array',
        'recurrence_days' => 'array',
        'recurrence_start_date' => 'date',
        'recurrence_end_date' => 'date',
        'auto_renew' => 'boolean',
        'times_used' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Business relationship
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Shifts created from this template
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class, 'template_id');
    }

    /**
     * Create a shift from this template
     */
    public function createShift($shiftDate, $additionalData = [])
    {
        $shiftData = [
            'business_id' => $this->business_id,
            'title' => $this->title,
            'description' => $this->shift_description,
            'industry' => $this->industry,
            'location_address' => $this->location_address,
            'location_city' => $this->location_city,
            'location_state' => $this->location_state,
            'location_country' => $this->location_country,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'shift_date' => $shiftDate,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_hours' => $this->duration_hours,
            'base_rate' => $this->base_rate,
            'urgency_level' => $this->urgency_level,
            'status' => 'open',
            'required_workers' => $this->required_workers,
            'filled_workers' => 0,
            'requirements' => $this->requirements,
            'dress_code' => $this->dress_code,
            'parking_info' => $this->parking_info,
            'break_info' => $this->break_info,
            'special_instructions' => $this->special_instructions,
            'template_id' => $this->id,
        ];

        // Merge with additional data
        $shiftData = array_merge($shiftData, $additionalData);

        // Calculate dynamic rate
        $matchingService = app(\App\Services\ShiftMatchingService::class);
        $dynamicRate = $matchingService->calculateDynamicRate([
            'base_rate' => $this->base_rate,
            'shift_date' => $shiftDate,
            'start_time' => $this->start_time,
            'industry' => $this->industry,
            'urgency_level' => $this->urgency_level,
        ]);

        $shiftData['dynamic_rate'] = $dynamicRate;
        $shiftData['final_rate'] = $dynamicRate;

        $shift = Shift::create($shiftData);

        // Update template usage
        $this->increment('times_used');
        $this->update(['last_used_at' => now()]);

        return $shift;
    }

    /**
     * Create bulk shifts from template based on recurrence pattern
     */
    public function createBulkShifts($startDate = null, $endDate = null)
    {
        if (!$this->auto_renew || !$this->recurrence_pattern) {
            return collect([]);
        }

        $start = $startDate ? Carbon::parse($startDate) : $this->recurrence_start_date;
        $end = $endDate ? Carbon::parse($endDate) : $this->recurrence_end_date;

        $shifts = collect([]);
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            // Check if this day matches recurrence pattern
            if ($this->shouldCreateShiftOnDate($currentDate)) {
                // Check if shift doesn't already exist
                $existingShift = Shift::where('template_id', $this->id)
                    ->where('shift_date', $currentDate->toDateString())
                    ->first();

                if (!$existingShift) {
                    $shift = $this->createShift($currentDate->toDateString());
                    $shifts->push($shift);
                }
            }

            // Move to next date based on recurrence pattern
            switch ($this->recurrence_pattern) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'biweekly':
                    $currentDate->addWeeks(2);
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }

        return $shifts;
    }

    /**
     * Check if shift should be created on specific date based on recurrence days
     */
    protected function shouldCreateShiftOnDate($date)
    {
        if (!$this->recurrence_days || empty($this->recurrence_days)) {
            return true;
        }

        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.

        return in_array($dayOfWeek, $this->recurrence_days);
    }

    /**
     * Scope: Active templates
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('recurrence_end_date')
              ->orWhere('recurrence_end_date', '>=', Carbon::today());
        });
    }

    /**
     * Scope: Auto-renewable templates
     */
    public function scopeAutoRenew($query)
    {
        return $query->where('auto_renew', true);
    }

    /**
     * Scope: For specific industry
     */
    public function scopeForIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }
}
