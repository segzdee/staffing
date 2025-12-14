<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Support\Collection;

class DemoShiftService
{
    /**
     * Role definitions with typical rates and industries.
     */
    protected $roles = [
        'bartender' => ['industry' => 'hospitality', 'base_rate' => 18.00, 'description' => 'Mix and serve drinks'],
        'server' => ['industry' => 'hospitality', 'base_rate' => 15.00, 'description' => 'Serve food and beverages'],
        'security_guard' => ['industry' => 'events', 'base_rate' => 22.00, 'description' => 'Provide security services'],
        'warehouse_worker' => ['industry' => 'warehouse', 'base_rate' => 17.00, 'description' => 'Pick, pack, and ship orders'],
        'retail_associate' => ['industry' => 'retail', 'base_rate' => 16.00, 'description' => 'Assist customers and stock shelves'],
        'event_staff' => ['industry' => 'events', 'base_rate' => 19.00, 'description' => 'Support event operations'],
        'kitchen_staff' => ['industry' => 'hospitality', 'base_rate' => 17.00, 'description' => 'Prep and cook food'],
        'delivery_driver' => ['industry' => 'warehouse', 'base_rate' => 20.00, 'description' => 'Deliver packages to customers'],
        'cashier' => ['industry' => 'retail', 'base_rate' => 15.00, 'description' => 'Process transactions'],
        'cleaner' => ['industry' => 'hospitality', 'base_rate' => 16.00, 'description' => 'Maintain cleanliness'],
    ];

    /**
     * Business name generators by industry.
     */
    protected $businessNames = [
        'hospitality' => [
            'The Grand Hotel', 'Riverside Restaurant', 'Urban Bar & Grill',
            'Sunset Bistro', 'The Marina Cafe', 'Downtown Tavern',
            'Crystal Palace Hotel', 'Harborside Eatery', 'The Metropolitan'
        ],
        'retail' => [
            'StyleMart', 'TechZone Electronics', 'HomeGoods Plus',
            'Fashion District', 'QuickMart', 'Urban Outpost',
            'The Shopping Hub', 'Elite Boutique', 'Prime Retail'
        ],
        'warehouse' => [
            'FastShip Logistics', 'MegaWarehouse Co', 'Prime Distribution',
            'QuickPack Fulfillment', 'Global Shipping Inc', 'RapidBox Logistics',
            'Metro Distribution', 'Precision Logistics', 'Swift Cargo'
        ],
        'events' => [
            'Grand Events Arena', 'Metro Convention Center', 'The Pavilion',
            'City Stadium', 'Elite Event Venue', 'Crown Conference Hall',
            'Premier Event Space', 'The Grand Ballroom', 'Arena Complex'
        ],
    ];

    /**
     * Cities with coordinates.
     */
    protected $cities = [
        ['name' => 'New York', 'state' => 'NY', 'lat' => 40.7128, 'lng' => -74.0060],
        ['name' => 'Los Angeles', 'state' => 'CA', 'lat' => 34.0522, 'lng' => -118.2437],
        ['name' => 'Chicago', 'state' => 'IL', 'lat' => 41.8781, 'lng' => -87.6298],
        ['name' => 'Houston', 'state' => 'TX', 'lat' => 29.7604, 'lng' => -95.3698],
        ['name' => 'Miami', 'state' => 'FL', 'lat' => 25.7617, 'lng' => -80.1918],
        ['name' => 'Seattle', 'state' => 'WA', 'lat' => 47.6062, 'lng' => -122.3321],
        ['name' => 'Boston', 'state' => 'MA', 'lat' => 42.3601, 'lng' => -71.0589],
        ['name' => 'Atlanta', 'state' => 'GA', 'lat' => 33.7490, 'lng' => -84.3880],
    ];

    /**
     * Generate demo shifts.
     *
     * @param int $count
     * @return Collection
     */
    public function generate(int $count = 15): Collection
    {
        $shifts = collect();

        foreach (range(1, $count) as $index) {
            $role = array_rand($this->roles);
            $roleData = $this->roles[$role];
            $industry = $roleData['industry'];

            $city = $this->cities[array_rand($this->cities)];
            $businessName = $this->businessNames[$industry][array_rand($this->businessNames[$industry])];

            // Generate time
            $shiftDate = $this->generateShiftDate();
            $startTime = $this->generateStartTime();
            $duration = $this->generateDuration();
            $endTime = $startTime->copy()->addHours($duration);

            // Calculate surge
            $surgMultiplier = $this->generateSurgeMultiplier($shiftDate, $startTime);
            $baseRate = $roleData['base_rate'];
            $finalRate = $baseRate * $surgMultiplier;

            // Required workers
            $requiredWorkers = rand(1, 5);
            $filledWorkers = rand(0, max(0, $requiredWorkers - 1));

            $shift = new Shift([
                'title' => ucwords(str_replace('_', ' ', $role)) . ' Needed',
                'role_type' => $role,
                'description' => $roleData['description'] . ' at ' . $businessName,
                'industry' => $industry,
                'location_address' => rand(100, 9999) . ' Main St',
                'location_city' => $city['name'],
                'location_state' => $city['state'],
                'location_country' => 'USA',
                'location_lat' => $city['lat'] + (rand(-100, 100) / 1000),
                'location_lng' => $city['lng'] + (rand(-100, 100) / 1000),
                'shift_date' => $shiftDate,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'duration_hours' => $duration,
                'base_rate' => $baseRate,
                'final_rate' => $finalRate,
                'surge_multiplier' => $surgMultiplier,
                'urgency_level' => $surgMultiplier > 1.3 ? 'urgent' : 'normal',
                'status' => 'open',
                'required_workers' => $requiredWorkers,
                'filled_workers' => $filledWorkers,
                'in_market' => true,
                'is_demo' => true,
                'market_posted_at' => now()->subMinutes(rand(1, 120)),
                'instant_claim_enabled' => $surgMultiplier > 1.4,
                'market_views' => rand(5, 150),
                'market_applications' => rand(0, $requiredWorkers * 3),
            ]);

            // Add pseudo ID for frontend
            $shift->id = 'demo_' . $index;
            $shift->demo_business_name = $businessName;

            $shifts->push($shift);
        }

        return $shifts->sortByDesc('surge_multiplier')->values();
    }

    /**
     * Generate a random shift date (today to 7 days out).
     *
     * @return \Carbon\Carbon
     */
    protected function generateShiftDate()
    {
        $daysOut = rand(0, 7);
        return now()->addDays($daysOut)->startOfDay();
    }

    /**
     * Generate a random start time.
     *
     * @return \Carbon\Carbon
     */
    protected function generateStartTime()
    {
        $hour = collect([6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22])->random();
        $minute = collect([0, 15, 30, 45])->random();

        return now()->setTime($hour, $minute, 0);
    }

    /**
     * Generate shift duration (4-10 hours).
     *
     * @return float
     */
    protected function generateDuration()
    {
        return collect([4, 5, 6, 7, 8, 9, 10])->random();
    }

    /**
     * Generate surge multiplier based on time factors.
     *
     * @param \Carbon\Carbon $shiftDate
     * @param \Carbon\Carbon $startTime
     * @return float
     */
    protected function generateSurgeMultiplier($shiftDate, $startTime)
    {
        $surge = 1.0;

        // Urgent (today or tomorrow)
        if ($shiftDate->isToday() || $shiftDate->isTomorrow()) {
            $surge += 0.5;
        }

        // Night shift (10 PM - 6 AM)
        $hour = $startTime->hour;
        if ($hour >= 22 || $hour < 6) {
            $surge += 0.3;
        }

        // Weekend
        if ($shiftDate->isWeekend()) {
            $surge += 0.2;
        }

        // Random event surge (20% chance)
        if (rand(1, 5) === 1) {
            $surge += rand(10, 30) / 100;
        }

        return round($surge, 2);
    }

    /**
     * Simulate activity for demo mode (for real-time animation).
     *
     * @return array
     */
    public function simulateActivity(): array
    {
        $activities = [];
        $count = rand(3, 8);

        $actionTypes = [
            'applied' => ['Worker applied to', 'just applied to'],
            'claimed' => ['Worker claimed', 'instantly claimed'],
            'filled' => ['Shift filled:', 'is now fully staffed'],
            'posted' => ['New shift posted:', 'just posted'],
        ];

        for ($i = 0; $i < $count; $i++) {
            $action = array_rand($actionTypes);
            $templates = $actionTypes[$action];

            $role = array_rand($this->roles);
            $roleLabel = ucwords(str_replace('_', ' ', $role));

            $city = $this->cities[array_rand($this->cities)];

            $activities[] = [
                'type' => $action,
                'message' => $templates[array_rand($templates)] . ' ' . $roleLabel . ' in ' . $city['name'],
                'timestamp' => now()->subSeconds(rand(1, 300))->toIso8601String(),
            ];
        }

        return $activities;
    }
}
