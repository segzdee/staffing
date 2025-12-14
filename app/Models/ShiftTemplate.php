<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
