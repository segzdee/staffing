<?php

namespace App\Services;

use App\Models\SurgeEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SL-008: Surge Event Service
 *
 * Manages surge events including creation, retrieval, and external API imports
 * for events that may affect worker demand (concerts, sports, festivals, etc.)
 */
class SurgeEventService
{
    /**
     * Create a new surge event.
     *
     * @param array{
     *   name: string,
     *   description?: string|null,
     *   region?: string|null,
     *   start_date: string|Carbon,
     *   end_date: string|Carbon,
     *   surge_multiplier?: float,
     *   event_type: string,
     *   expected_demand_increase?: int|null,
     *   is_active?: bool,
     *   created_by?: int|null
     * } $data
     */
    public function createEvent(array $data): SurgeEvent
    {
        // Ensure dates are properly formatted
        if ($data['start_date'] instanceof Carbon) {
            $data['start_date'] = $data['start_date']->toDateString();
        }
        if ($data['end_date'] instanceof Carbon) {
            $data['end_date'] = $data['end_date']->toDateString();
        }

        // Set defaults
        $data['surge_multiplier'] = $data['surge_multiplier'] ?? 1.50;
        $data['is_active'] = $data['is_active'] ?? true;

        $event = SurgeEvent::create($data);

        Log::info('Surge event created', [
            'event_id' => $event->id,
            'name' => $event->name,
            'region' => $event->region,
            'dates' => $event->start_date->toDateString().' to '.$event->end_date->toDateString(),
        ]);

        return $event;
    }

    /**
     * Update an existing surge event.
     */
    public function updateEvent(SurgeEvent $event, array $data): SurgeEvent
    {
        // Ensure dates are properly formatted
        if (isset($data['start_date']) && $data['start_date'] instanceof Carbon) {
            $data['start_date'] = $data['start_date']->toDateString();
        }
        if (isset($data['end_date']) && $data['end_date'] instanceof Carbon) {
            $data['end_date'] = $data['end_date']->toDateString();
        }

        $event->update($data);

        Log::info('Surge event updated', [
            'event_id' => $event->id,
            'name' => $event->name,
        ]);

        return $event->fresh();
    }

    /**
     * Get active events for a specific date and region.
     */
    public function getActiveEvents(Carbon $date, ?string $region): Collection
    {
        return SurgeEvent::getActiveEventsFor($date, $region);
    }

    /**
     * Get upcoming events within the specified number of days.
     */
    public function getUpcomingEvents(int $days = 14): Collection
    {
        return SurgeEvent::query()
            ->active()
            ->upcoming($days)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get all events for a specific region.
     */
    public function getEventsByRegion(?string $region): Collection
    {
        $query = SurgeEvent::query()->orderByDesc('start_date');

        if ($region) {
            $query->where(function ($q) use ($region) {
                $q->where('region', $region)
                    ->orWhereNull('region');
            });
        }

        return $query->get();
    }

    /**
     * Get events by type.
     */
    public function getEventsByType(string $type): Collection
    {
        return SurgeEvent::query()
            ->where('event_type', $type)
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Import events from Ticketmaster API.
     *
     * Note: Requires TICKETMASTER_API_KEY environment variable.
     */
    public function importFromTicketmaster(?string $region = null, int $days = 30): int
    {
        $apiKey = config('services.ticketmaster.api_key');

        if (! $apiKey) {
            Log::warning('Ticketmaster API key not configured');

            return 0;
        }

        $startDate = now()->format('Y-m-d\TH:i:s\Z');
        $endDate = now()->addDays($days)->format('Y-m-d\TH:i:s\Z');

        try {
            $params = [
                'apikey' => $apiKey,
                'startDateTime' => $startDate,
                'endDateTime' => $endDate,
                'size' => 100,
                'sort' => 'date,asc',
            ];

            if ($region) {
                $params['city'] = $region;
            }

            $response = Http::get('https://app.ticketmaster.com/discovery/v2/events.json', $params);

            if (! $response->successful()) {
                Log::error('Ticketmaster API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 0;
            }

            $data = $response->json();
            $events = $data['_embedded']['events'] ?? [];

            return $this->processTicketmasterEvents($events);
        } catch (\Exception $e) {
            Log::error('Ticketmaster import failed', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Process events from Ticketmaster response.
     */
    protected function processTicketmasterEvents(array $events): int
    {
        $imported = 0;

        foreach ($events as $event) {
            try {
                // Map Ticketmaster event type to our event types
                $eventType = $this->mapTicketmasterType($event['classifications'][0]['segment']['name'] ?? 'Other');

                // Get venue city
                $venue = $event['_embedded']['venues'][0] ?? null;
                $city = $venue['city']['name'] ?? null;

                // Parse dates
                $startDate = Carbon::parse($event['dates']['start']['localDate']);
                $endDate = isset($event['dates']['end']['localDate'])
                    ? Carbon::parse($event['dates']['end']['localDate'])
                    : $startDate;

                // Calculate expected demand increase based on event size
                $expectedDemand = $this->estimateDemandIncrease($event);

                // Calculate surge multiplier based on expected demand
                $surgeMultiplier = $this->calculateEventSurge($expectedDemand);

                // Create event if it doesn't exist
                $existingEvent = SurgeEvent::where('name', $event['name'])
                    ->where('start_date', $startDate)
                    ->first();

                if (! $existingEvent) {
                    $this->createEvent([
                        'name' => $event['name'],
                        'description' => $event['info'] ?? null,
                        'region' => $city,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'surge_multiplier' => $surgeMultiplier,
                        'event_type' => $eventType,
                        'expected_demand_increase' => $expectedDemand,
                        'is_active' => true,
                    ]);
                    $imported++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to import Ticketmaster event', [
                    'event_name' => $event['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Ticketmaster events imported', ['count' => $imported]);

        return $imported;
    }

    /**
     * Import events from Eventbrite API.
     *
     * Note: Requires EVENTBRITE_TOKEN environment variable.
     */
    public function importFromEventbrite(?string $region = null, int $days = 30): int
    {
        $token = config('services.eventbrite.token');

        if (! $token) {
            Log::warning('Eventbrite token not configured');

            return 0;
        }

        $startDate = now()->format('Y-m-d\TH:i:s');
        $endDate = now()->addDays($days)->format('Y-m-d\TH:i:s');

        try {
            $params = [
                'start_date.range_start' => $startDate,
                'start_date.range_end' => $endDate,
                'expand' => 'venue,category',
            ];

            if ($region) {
                $params['location.address'] = $region;
                $params['location.within'] = '50km';
            }

            $response = Http::withToken($token)
                ->get('https://www.eventbriteapi.com/v3/events/search/', $params);

            if (! $response->successful()) {
                Log::error('Eventbrite API request failed', [
                    'status' => $response->status(),
                ]);

                return 0;
            }

            $data = $response->json();
            $events = $data['events'] ?? [];

            return $this->processEventbriteEvents($events);
        } catch (\Exception $e) {
            Log::error('Eventbrite import failed', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Process events from Eventbrite response.
     */
    protected function processEventbriteEvents(array $events): int
    {
        $imported = 0;

        foreach ($events as $event) {
            try {
                $eventType = $this->mapEventbriteCategory($event['category']['name'] ?? 'Other');
                $city = $event['venue']['address']['city'] ?? null;

                $startDate = Carbon::parse($event['start']['local']);
                $endDate = Carbon::parse($event['end']['local']);

                // Estimate demand based on capacity
                $capacity = $event['capacity'] ?? 100;
                $expectedDemand = min(100, (int) ($capacity / 50));

                $surgeMultiplier = $this->calculateEventSurge($expectedDemand);

                $existingEvent = SurgeEvent::where('name', $event['name']['text'])
                    ->where('start_date', $startDate->toDateString())
                    ->first();

                if (! $existingEvent) {
                    $this->createEvent([
                        'name' => $event['name']['text'],
                        'description' => $event['description']['text'] ?? null,
                        'region' => $city,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'surge_multiplier' => $surgeMultiplier,
                        'event_type' => $eventType,
                        'expected_demand_increase' => $expectedDemand,
                        'is_active' => true,
                    ]);
                    $imported++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to import Eventbrite event', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Eventbrite events imported', ['count' => $imported]);

        return $imported;
    }

    /**
     * Import events from all configured APIs.
     */
    public function importEventsFromAPI(?string $region = null): int
    {
        $total = 0;

        // Import from Ticketmaster
        $total += $this->importFromTicketmaster($region);

        // Import from Eventbrite
        $total += $this->importFromEventbrite($region);

        return $total;
    }

    /**
     * Map Ticketmaster segment to our event type.
     */
    protected function mapTicketmasterType(string $segment): string
    {
        return match (strtolower($segment)) {
            'music' => SurgeEvent::TYPE_CONCERT,
            'sports' => SurgeEvent::TYPE_SPORTS,
            'arts & theatre' => SurgeEvent::TYPE_FESTIVAL,
            'miscellaneous' => SurgeEvent::TYPE_OTHER,
            default => SurgeEvent::TYPE_OTHER,
        };
    }

    /**
     * Map Eventbrite category to our event type.
     */
    protected function mapEventbriteCategory(string $category): string
    {
        $category = strtolower($category);

        return match (true) {
            str_contains($category, 'music') => SurgeEvent::TYPE_CONCERT,
            str_contains($category, 'sport') => SurgeEvent::TYPE_SPORTS,
            str_contains($category, 'business') || str_contains($category, 'conference') => SurgeEvent::TYPE_CONFERENCE,
            str_contains($category, 'festival') || str_contains($category, 'food') => SurgeEvent::TYPE_FESTIVAL,
            str_contains($category, 'holiday') => SurgeEvent::TYPE_HOLIDAY,
            default => SurgeEvent::TYPE_OTHER,
        };
    }

    /**
     * Estimate demand increase percentage based on event data.
     */
    protected function estimateDemandIncrease(array $event): int
    {
        // Base estimation on price range and event type
        $priceRanges = $event['priceRanges'] ?? [];
        $maxPrice = 0;
        foreach ($priceRanges as $range) {
            $maxPrice = max($maxPrice, $range['max'] ?? 0);
        }

        // Higher ticket prices often indicate larger events
        return match (true) {
            $maxPrice >= 200 => 75, // Major event
            $maxPrice >= 100 => 50, // Large event
            $maxPrice >= 50 => 30,  // Medium event
            $maxPrice > 0 => 20,    // Small event
            default => 15,          // Unknown size
        };
    }

    /**
     * Calculate surge multiplier based on expected demand increase.
     */
    protected function calculateEventSurge(int $expectedDemandIncrease): float
    {
        return match (true) {
            $expectedDemandIncrease >= 75 => 2.0,
            $expectedDemandIncrease >= 50 => 1.75,
            $expectedDemandIncrease >= 30 => 1.5,
            $expectedDemandIncrease >= 15 => 1.25,
            default => 1.0,
        };
    }

    /**
     * Create a holiday surge event.
     */
    public function createHolidayEvent(
        string $name,
        Carbon $date,
        ?string $region = null,
        float $multiplier = 1.5
    ): SurgeEvent {
        return $this->createEvent([
            'name' => $name,
            'description' => "Public holiday: {$name}",
            'region' => $region,
            'start_date' => $date,
            'end_date' => $date,
            'surge_multiplier' => $multiplier,
            'event_type' => SurgeEvent::TYPE_HOLIDAY,
            'expected_demand_increase' => 50,
            'is_active' => true,
        ]);
    }

    /**
     * Create a weather surge event.
     */
    public function createWeatherEvent(
        string $name,
        string $description,
        Carbon $startDate,
        Carbon $endDate,
        ?string $region = null,
        float $multiplier = 1.5
    ): SurgeEvent {
        return $this->createEvent([
            'name' => $name,
            'description' => $description,
            'region' => $region,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'surge_multiplier' => $multiplier,
            'event_type' => SurgeEvent::TYPE_WEATHER,
            'expected_demand_increase' => 40,
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate expired events.
     */
    public function deactivateExpiredEvents(): int
    {
        $count = SurgeEvent::query()
            ->where('is_active', true)
            ->where('end_date', '<', now()->toDateString())
            ->update(['is_active' => false]);

        if ($count > 0) {
            Log::info('Deactivated expired surge events', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Get event statistics for dashboard.
     *
     * @return array{
     *   total_events: int,
     *   active_events: int,
     *   upcoming_events: int,
     *   events_by_type: array<string, int>,
     *   average_multiplier: float
     * }
     */
    public function getEventStatistics(): array
    {
        $allEvents = SurgeEvent::all();
        $activeEvents = SurgeEvent::query()->active()->current()->get();
        $upcomingEvents = SurgeEvent::query()->active()->upcoming(14)->get();

        $eventsByType = $allEvents->groupBy('event_type')
            ->map(fn ($events) => $events->count())
            ->toArray();

        return [
            'total_events' => $allEvents->count(),
            'active_events' => $activeEvents->count(),
            'upcoming_events' => $upcomingEvents->count(),
            'events_by_type' => $eventsByType,
            'average_multiplier' => round($activeEvents->avg('surge_multiplier') ?? 1.0, 2),
        ];
    }
}
