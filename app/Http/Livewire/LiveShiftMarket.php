<?php

namespace App\Http\Livewire;

use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;

class LiveShiftMarket extends Component
{
    public $selectedIndustry = 'all';

    public $shifts = [];

    public $industryRates = [];

    protected $listeners = ['refreshShifts' => '$refresh'];

    public function mount()
    {
        $this->loadIndustryRates();
        $this->loadShifts();
    }

    public function loadIndustryRates()
    {
        $this->industryRates = [
            ['name' => 'Hospitality', 'rate' => '$28/hr', 'change' => '+12%', 'trend' => 'up'],
            ['name' => 'Healthcare', 'rate' => '$42/hr', 'change' => '+8%', 'trend' => 'up'],
            ['name' => 'Retail', 'rate' => '$22/hr', 'change' => '-3%', 'trend' => 'down'],
            ['name' => 'Logistics', 'rate' => '$26/hr', 'change' => '+15%', 'trend' => 'up'],
            ['name' => 'Construction', 'rate' => '$35/hr', 'change' => '+5%', 'trend' => 'up'],
            ['name' => 'Events', 'rate' => '$30/hr', 'change' => '+18%', 'trend' => 'up'],
            ['name' => 'Manufacturing', 'rate' => '$29/hr', 'change' => '-2%', 'trend' => 'down'],
            ['name' => 'Food Service', 'rate' => '$24/hr', 'change' => '+10%', 'trend' => 'up'],
        ];
    }

    public function loadShifts()
    {
        // Use withCount to eager load applications count and prevent N+1 queries
        $query = Shift::with(['business'])
            ->withCount('applications')
            ->where('status', 'open')
            ->where('shift_date', '>=', now()->toDateString())
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(12);

        if ($this->selectedIndustry !== 'all') {
            $query->where('industry', $this->selectedIndustry);
        }

        $this->shifts = $query->get()->map(function ($shift) {
            // Use the eager loaded applications_count instead of calling count() again
            $applicationsCount = $shift->applications_count;

            // Combine shift_date and start_time/end_time for display
            $startDateTime = $this->combineDateTime($shift->shift_date, $shift->start_time);
            $endDateTime = $this->combineDateTime($shift->shift_date, $shift->end_time);

            return [
                'id' => $shift->id,
                'title' => $shift->title,
                'business_name' => $shift->business->business_name ?? $shift->business->name ?? 'Unknown',
                'location' => $shift->location_address ?? $shift->location_city ?? $shift->city,
                'hourly_rate' => $shift->hourly_rate,
                'rate_trend' => $this->getRateTrend($shift->hourly_rate),
                'start_time' => $startDateTime ? $startDateTime->format('M j, g:i A') : 'TBD',
                'end_time' => $endDateTime ? $endDateTime->format('g:i A') : 'TBD',
                'duration' => $this->calculateDuration($startDateTime, $endDateTime),
                'skills' => $shift->required_skills ? json_decode($shift->required_skills, true) : [],
                'demand_level' => $this->calculateDemandLevelFromCount($applicationsCount, $shift->max_workers ?? 1),
                'urgency' => $this->calculateUrgency($shift),
                'countdown' => $this->calculateCountdown($startDateTime),
                'viewers' => $shift->market_views ?? 0,
                'applications_count' => $applicationsCount,
                'max_workers' => $shift->max_workers ?? 1,
            ];
        })->toArray();
    }

    /**
     * Combine shift_date and time into a Carbon datetime.
     */
    private function combineDateTime($date, $time): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : ($time ?? '00:00:00');

        return Carbon::parse("{$dateStr} {$timeStr}");
    }

    public function filterByIndustry($industry)
    {
        $this->selectedIndustry = $industry;
        $this->loadShifts();
    }

    /**
     * Calculate rate trend by comparing to average market rate.
     * Returns 'up' if rate is above average, 'down' if below, 'stable' if within 5%.
     */
    private function getRateTrend($rate): string
    {
        if (! $rate || $rate <= 0) {
            return 'stable';
        }

        // Cache the average rate calculation to avoid repeated queries
        static $avgRate = null;
        if ($avgRate === null) {
            $avgRate = Shift::where('status', 'open')
                ->where('shift_date', '>=', now()->subDays(30)->toDateString())
                ->where('hourly_rate', '>', 0)
                ->avg('hourly_rate') ?? 0;
        }

        if ($avgRate <= 0) {
            return 'stable';
        }

        $percentDiff = (($rate - $avgRate) / $avgRate) * 100;

        if ($percentDiff > 5) {
            return 'up';
        }
        if ($percentDiff < -5) {
            return 'down';
        }

        return 'stable';
    }

    private function calculateDuration($start, $end)
    {
        if (! $start || ! $end) {
            return 'N/A';
        }

        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);
        $hours = $start->diffInHours($end);

        return $hours.'h';
    }

    /**
     * Calculate demand level from pre-loaded count to prevent N+1 queries.
     */
    private function calculateDemandLevelFromCount($applicationsCount, $maxWorkers)
    {
        if ($applicationsCount === 0) {
            return 'Low';
        }

        $ratio = $applicationsCount / max(1, $maxWorkers);

        if ($ratio >= 5) {
            return 'Very High';
        }
        if ($ratio >= 3) {
            return 'High';
        }
        if ($ratio >= 1) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * @deprecated Use calculateDemandLevelFromCount() to avoid N+1 queries
     */
    private function calculateDemandLevel($shift)
    {
        return $this->calculateDemandLevelFromCount(
            $shift->applications_count ?? $shift->applications()->count(),
            $shift->max_workers ?? 1
        );
    }

    private function calculateUrgency($shift)
    {
        // Combine shift_date and start_time to get the full datetime
        $startDateTime = $this->combineDateTime($shift->shift_date, $shift->start_time);

        if (! $startDateTime) {
            return 'normal';
        }

        $hoursUntilStart = Carbon::now()->diffInHours($startDateTime, false);

        if ($hoursUntilStart < 0) {
            return 'expired';
        }
        if ($hoursUntilStart <= 4) {
            return 'urgent';
        }
        if ($hoursUntilStart <= 12) {
            return 'high';
        }
        if ($hoursUntilStart <= 24) {
            return 'medium';
        }

        return 'normal';
    }

    private function calculateCountdown($startDateTime)
    {
        if (! $startDateTime) {
            return 'TBD';
        }

        $start = $startDateTime instanceof Carbon ? $startDateTime : Carbon::parse($startDateTime);
        $now = Carbon::now();

        if ($start->isPast()) {
            return 'Started';
        }

        $diffInHours = $now->diffInHours($start, false);

        if ($diffInHours < 24) {
            return $now->diffForHumans($start);
        }

        return $start->diffInDays($now).' days';
    }

    public function render()
    {
        return view('livewire.live-shift-market');
    }
}
