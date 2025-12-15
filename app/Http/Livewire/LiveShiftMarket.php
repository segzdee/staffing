<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Shift;
use Carbon\Carbon;

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
        $query = Shift::with(['business', 'location'])
            ->withCount('applications')
            ->where('status', 'open')
            ->where('start_datetime', '>', now())
            ->orderBy('start_datetime', 'asc')
            ->limit(12);

        if ($this->selectedIndustry !== 'all') {
            $query->where('industry', $this->selectedIndustry);
        }

        $this->shifts = $query->get()->map(function ($shift) {
            // Use the eager loaded applications_count instead of calling count() again
            $applicationsCount = $shift->applications_count;
            return [
                'id' => $shift->id,
                'title' => $shift->title,
                'business_name' => $shift->business->business_name ?? $shift->business->name,
                'location' => $shift->location_address ?? $shift->location_city,
                'hourly_rate' => $shift->hourly_rate,
                'rate_trend' => $this->getRateTrend($shift->hourly_rate),
                'start_time' => Carbon::parse($shift->start_datetime)->format('M j, g:i A'),
                'end_time' => Carbon::parse($shift->end_datetime)->format('g:i A'),
                'duration' => $this->calculateDuration($shift->start_datetime, $shift->end_datetime),
                'skills' => $shift->required_skills ? json_decode($shift->required_skills, true) : [],
                'demand_level' => $this->calculateDemandLevelFromCount($applicationsCount, $shift->max_workers ?? 1),
                'urgency' => $this->calculateUrgency($shift),
                'countdown' => $this->calculateCountdown($shift->start_datetime),
                'viewers' => rand(3, 24),
                'applications_count' => $applicationsCount,
                'max_workers' => $shift->max_workers ?? 1,
            ];
        })->toArray();
    }

    public function filterByIndustry($industry)
    {
        $this->selectedIndustry = $industry;
        $this->loadShifts();
    }

    private function getRateTrend($rate)
    {
        return rand(0, 1) ? 'up' : 'stable';
    }

    private function calculateDuration($start, $end)
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $hours = $start->diffInHours($end);

        return $hours . 'h';
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

        if ($ratio >= 5) return 'Very High';
        if ($ratio >= 3) return 'High';
        if ($ratio >= 1) return 'Medium';
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
        $hoursUntilStart = Carbon::now()->diffInHours(Carbon::parse($shift->start_datetime), false);

        if ($hoursUntilStart < 0) return 'expired';
        if ($hoursUntilStart <= 4) return 'urgent';
        if ($hoursUntilStart <= 12) return 'high';
        if ($hoursUntilStart <= 24) return 'medium';
        return 'normal';
    }

    private function calculateCountdown($startDatetime)
    {
        $start = Carbon::parse($startDatetime);
        $now = Carbon::now();

        if ($start->isPast()) {
            return 'Started';
        }

        $diffInHours = $now->diffInHours($start, false);

        if ($diffInHours < 24) {
            return $now->diffForHumans($start);
        }

        return $start->diffInDays($now) . ' days';
    }

    public function render()
    {
        return view('livewire.live-shift-market');
    }
}
