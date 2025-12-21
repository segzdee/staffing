<?php

namespace App\Http\Livewire;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LiveShiftMarket extends Component
{
    public $selectedIndustry = 'all';

    public $shifts = [];

    public $industryRates = [];

    public bool $isGuest = true;

    protected $listeners = ['refreshShifts' => '$refresh'];

    public function mount()
    {
        $this->isGuest = ! auth()->check();
        $this->loadIndustryRates();
        $this->loadShifts();
    }

    public function loadIndustryRates()
    {
        // Cache industry rates for 10 minutes
        $this->industryRates = Cache::remember('live_market_industry_rates', 600, function () {
            return $this->calculateIndustryRates();
        });
    }

    /**
     * Calculate real industry rates from database.
     */
    private function calculateIndustryRates(): array
    {
        $industries = [
            'Hospitality' => 'hospitality',
            'Healthcare' => 'healthcare',
            'Retail' => 'retail',
            'Logistics' => 'logistics',
            'Construction' => 'construction',
            'Events' => 'events',
            'Manufacturing' => 'manufacturing',
            'Food Service' => 'food_service',
        ];

        $rates = [];

        foreach ($industries as $displayName => $dbValue) {
            $currentAvg = Shift::where('industry', $dbValue)
                ->where('status', 'open')
                ->where('shift_date', '>=', now()->toDateString())
                ->whereRaw('COALESCE(final_rate, base_rate) > 0')
                ->selectRaw('AVG(COALESCE(final_rate, base_rate)) as avg_rate')
                ->value('avg_rate');

            $previousAvg = Shift::where('industry', $dbValue)
                ->where('shift_date', '>=', now()->subDays(30)->toDateString())
                ->where('shift_date', '<', now()->toDateString())
                ->whereRaw('COALESCE(final_rate, base_rate) > 0')
                ->selectRaw('AVG(COALESCE(final_rate, base_rate)) as avg_rate')
                ->value('avg_rate');

            // Convert cents to dollars
            $currentRate = $currentAvg ? round($currentAvg / 100, 0) : null;
            $previousRate = $previousAvg ? round($previousAvg / 100, 0) : null;

            // Calculate change percentage
            $change = 0;
            if ($previousRate && $currentRate) {
                $change = round((($currentRate - $previousRate) / $previousRate) * 100);
            }

            // Only include industries with data or use fallback defaults
            if ($currentRate) {
                $rates[] = [
                    'name' => $displayName,
                    'rate' => '$'.$currentRate.'/hr',
                    'change' => ($change >= 0 ? '+' : '').$change.'%',
                    'trend' => $change >= 0 ? 'up' : 'down',
                ];
            }
        }

        // If no real data, return demo rates
        if (empty($rates)) {
            return [
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

        return $rates;
    }

    public function loadShifts()
    {
        // For guests, use a 5-minute cache to reduce server load
        if ($this->isGuest) {
            $cacheKey = 'live_market_shifts_guest_'.$this->selectedIndustry;
            $this->shifts = Cache::remember($cacheKey, 300, function () {
                return $this->fetchShifts();
            });
        } else {
            // For authenticated users, fetch fresh data
            $this->shifts = $this->fetchShifts();
        }
    }

    /**
     * Fetch shifts from database.
     */
    private function fetchShifts(): array
    {
        $query = Shift::with(['business'])
            ->withCount('applications')
            ->where('status', 'open')
            ->where('shift_date', '>=', now()->toDateString())
            ->where(function ($q) {
                // Show demo shifts OR real shifts that are in market
                $q->where('is_demo', true)
                    ->orWhere('in_market', true);
            })
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(12);

        if ($this->selectedIndustry !== 'all') {
            $query->where('industry', $this->selectedIndustry);
        }

        return $query->get()->map(function ($shift) {
            return $this->transformShift($shift);
        })->toArray();
    }

    /**
     * Transform shift model to array for view.
     */
    private function transformShift(Shift $shift): array
    {
        $applicationsCount = $shift->applications_count;

        // Combine shift_date and start_time/end_time for display
        $startDateTime = $this->combineDateTime($shift->shift_date, $shift->start_time);
        $endDateTime = $this->combineDateTime($shift->shift_date, $shift->end_time);

        // Get the effective rate as a numeric value (dollars)
        $rateObj = $shift->getEffectiveHourlyRate();
        $hourlyRate = $rateObj ? ($rateObj->getAmount() / 100) : 0;

        // For demo shifts, use demo_business_name
        // For real shifts, use business name (with opt-in check in future)
        $businessName = $shift->is_demo
            ? ($shift->demo_business_name ?? 'Local Business')
            : ($shift->business->business_name ?? $shift->business->name ?? 'Business');

        // For guests, show only city (not full address)
        $location = $this->isGuest
            ? ($shift->location_city ?? 'Malta')
            : ($shift->location_address ?? $shift->location_city ?? $shift->city ?? 'Malta');

        return [
            'id' => $shift->id,
            'title' => $shift->title,
            'business_name' => $businessName,
            'location' => $location,
            'hourly_rate' => $hourlyRate,
            'rate_trend' => $this->getRateTrend($hourlyRate),
            'start_time' => $startDateTime ? $startDateTime->format('M j, g:i A') : 'TBD',
            'end_time' => $endDateTime ? $endDateTime->format('g:i A') : 'TBD',
            'duration' => $this->calculateDuration($startDateTime, $endDateTime),
            'skills' => $shift->required_skills ? (is_array($shift->required_skills) ? $shift->required_skills : json_decode($shift->required_skills, true)) : [],
            'demand_level' => $this->calculateDemandLevelFromCount($applicationsCount, $shift->required_workers ?? 1),
            'urgency' => $this->calculateUrgency($shift),
            'countdown' => $this->calculateCountdown($startDateTime),
            'viewers' => $shift->market_views ?? rand(5, 50),
            'applications_count' => $this->isGuest ? null : $applicationsCount, // Hide for guests
            'max_workers' => $shift->required_workers ?? 1,
            'spots_remaining' => max(0, ($shift->required_workers ?? 1) - ($shift->filled_workers ?? 0)),
            'is_demo' => $shift->is_demo,
            'industry' => $shift->industry,
        ];
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
        // Rates are stored in cents, so divide by 100 to get dollars
        static $avgRate = null;
        if ($avgRate === null) {
            $avgCents = Shift::where('status', 'open')
                ->where('shift_date', '>=', now()->subDays(30)->toDateString())
                ->whereRaw('COALESCE(final_rate, base_rate) > 0')
                ->selectRaw('AVG(COALESCE(final_rate, base_rate)) as avg_rate')
                ->value('avg_rate') ?? 0;
            $avgRate = $avgCents / 100;
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

    /**
     * Get the signup URL with intent tracking for guests.
     */
    public function getSignupUrl(int $shiftId): string
    {
        return route('register', [
            'redirect' => route('shifts.show', $shiftId),
            'intent' => 'apply_shift',
            'shift_id' => $shiftId,
        ]);
    }

    public function render()
    {
        return view('livewire.live-shift-market', [
            'isGuest' => $this->isGuest,
        ]);
    }
}
