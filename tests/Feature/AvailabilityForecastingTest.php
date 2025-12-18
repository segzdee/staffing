<?php

use App\Models\AvailabilityPattern;
use App\Models\AvailabilityPrediction;
use App\Models\DemandForecast;
use App\Models\User;
use App\Services\AvailabilityForecastingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WKR-013: Availability Forecasting Tests
 */
describe('AvailabilityPattern Model', function () {
    it('can create an availability pattern', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);

        $pattern = AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 1, // Monday
            'typical_start_time' => '09:00:00',
            'typical_end_time' => '17:00:00',
            'availability_probability' => 0.85,
            'historical_shifts_count' => 20,
            'historical_available_count' => 17,
        ]);

        expect($pattern)->not->toBeNull();
        expect($pattern->day_name)->toBe('Monday');
        expect($pattern->probability_percent)->toBe(85.0);
        expect($pattern->isReliable())->toBeTrue();
    });

    it('returns correct day names for all days', function () {
        $dayNames = AvailabilityPattern::DAY_NAMES;

        expect($dayNames[0])->toBe('Sunday');
        expect($dayNames[1])->toBe('Monday');
        expect($dayNames[6])->toBe('Saturday');
    });

    it('calculates confidence level based on historical data', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);

        $lowData = AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 1,
            'historical_shifts_count' => 2,
            'historical_available_count' => 1,
        ]);

        $highData = AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 2,
            'historical_shifts_count' => 25,
            'historical_available_count' => 20,
        ]);

        expect($lowData->getConfidenceLevel())->toBe('very_low');
        expect($highData->getConfidenceLevel())->toBe('very_high');
    });
});

describe('AvailabilityPrediction Model', function () {
    it('can create an availability prediction', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);
        $date = Carbon::tomorrow();

        $prediction = AvailabilityPrediction::create([
            'user_id' => $worker->id,
            'prediction_date' => $date,
            'morning_probability' => 0.7,
            'afternoon_probability' => 0.8,
            'evening_probability' => 0.6,
            'night_probability' => 0.1,
            'overall_probability' => 0.65,
            'factors' => ['historical_pattern' => 0.7],
        ]);

        expect($prediction)->not->toBeNull();
        expect($prediction->overall_percent)->toBe(65.0);
        expect($prediction->best_slot)->toBe('afternoon');
        expect($prediction->strength_label)->toBe('Likely');
    });

    it('maps hours to correct time slots', function () {
        expect(AvailabilityPrediction::getSlotForHour(8))->toBe('morning');
        expect(AvailabilityPrediction::getSlotForHour(14))->toBe('afternoon');
        expect(AvailabilityPrediction::getSlotForHour(20))->toBe('evening');
        expect(AvailabilityPrediction::getSlotForHour(2))->toBe('night');
    });

    it('scopes to future predictions', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);

        AvailabilityPrediction::create([
            'user_id' => $worker->id,
            'prediction_date' => Carbon::yesterday(),
            'overall_probability' => 0.5,
        ]);

        AvailabilityPrediction::create([
            'user_id' => $worker->id,
            'prediction_date' => Carbon::tomorrow(),
            'overall_probability' => 0.8,
        ]);

        $future = AvailabilityPrediction::future()->count();

        expect($future)->toBeGreaterThanOrEqual(1);
    });
});

describe('DemandForecast Model', function () {
    it('can create a demand forecast', function () {
        $forecast = DemandForecast::create([
            'forecast_date' => Carbon::tomorrow(),
            'region' => 'London',
            'predicted_demand' => 100,
            'predicted_supply' => 80,
            'supply_demand_ratio' => 0.8,
            'demand_level' => 'high',
        ]);

        expect($forecast)->not->toBeNull();
        expect($forecast->has_shortage)->toBeTrue();
        expect($forecast->supply_gap)->toBe(-20);
        expect($forecast->shortage_percent)->toBe(20.0);
    });

    it('calculates correct demand level from ratio', function () {
        expect(DemandForecast::calculateDemandLevel(0.3))->toBe('critical');
        expect(DemandForecast::calculateDemandLevel(0.7))->toBe('high');
        expect(DemandForecast::calculateDemandLevel(1.0))->toBe('normal');
        expect(DemandForecast::calculateDemandLevel(1.5))->toBe('low');
    });

    it('generates recommendations for shortages', function () {
        $forecast = DemandForecast::create([
            'forecast_date' => Carbon::tomorrow(),
            'region' => 'London',
            'predicted_demand' => 100,
            'predicted_supply' => 40,
            'supply_demand_ratio' => 0.4,
            'demand_level' => 'critical',
        ]);

        $recommendations = $forecast->getRecommendations();

        expect($recommendations)->not->toBeEmpty();
        expect($recommendations[0]['type'])->toBe('urgent');
    });
});

describe('AvailabilityForecastingService', function () {
    it('calculates pattern probability', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);

        AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 1, // Monday
            'availability_probability' => 0.85,
            'historical_shifts_count' => 20,
            'historical_available_count' => 17,
        ]);

        $service = new AvailabilityForecastingService;
        $probability = $service->calculatePatternProbability($worker, 1);

        expect($probability)->toBe(0.85);
    });

    it('returns neutral probability when no pattern exists', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);

        $service = new AvailabilityForecastingService;
        $probability = $service->calculatePatternProbability($worker, 3); // Wednesday with no data

        expect($probability)->toBe(0.5);
    });

    it('predicts availability for a worker', function () {
        $worker = User::factory()->create(['user_type' => 'worker', 'status' => 'active']);
        $date = Carbon::now()->next('Monday');

        AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 1, // Monday
            'availability_probability' => 0.9,
            'historical_shifts_count' => 15,
        ]);

        $service = new AvailabilityForecastingService;
        $prediction = $service->predictAvailability($worker, $date);

        expect($prediction)->toBeInstanceOf(AvailabilityPrediction::class);
        expect($prediction->user_id)->toBe($worker->id);
        expect($prediction->prediction_date->toDateString())->toBe($date->toDateString());
    });

    it('forecasts demand for a date and region', function () {
        $date = Carbon::tomorrow();
        $region = 'TestRegion';

        $service = new AvailabilityForecastingService;
        $forecast = $service->forecastDemand($date, $region);

        expect($forecast)->toBeInstanceOf(DemandForecast::class);
        expect($forecast->region)->toBe($region);
        expect($forecast->forecast_date->toDateString())->toBe($date->toDateString());
    });

    it('gets supply demand gap analysis', function () {
        $date = Carbon::tomorrow();
        $region = 'GapTestRegion';

        // Call the service - it will generate a forecast
        $service = new AvailabilityForecastingService;
        $gap = $service->getSupplyDemandGap($date, $region);

        // Verify the structure of the returned gap analysis
        expect($gap)->toHaveKeys(['date', 'region', 'predicted_demand', 'predicted_supply', 'gap', 'has_shortage', 'recommendations']);
        expect($gap['region'])->toBe($region);
        expect($gap['date'])->toBe($date->toDateString());
        // Verify demand and supply are numeric
        expect($gap['predicted_demand'])->toBeGreaterThanOrEqual(0);
        expect($gap['predicted_supply'])->toBeGreaterThanOrEqual(0);
        // Gap should be supply minus demand
        expect($gap['gap'])->toBe($gap['predicted_supply'] - $gap['predicted_demand']);
    });
});

describe('Availability Forecasting API', function () {
    it('returns worker patterns for authenticated user', function () {
        $worker = User::factory()->create(['user_type' => 'worker', 'status' => 'active']);

        AvailabilityPattern::create([
            'user_id' => $worker->id,
            'day_of_week' => 1,
            'availability_probability' => 0.85,
            'historical_shifts_count' => 10,
        ]);

        $response = $this->actingAs($worker, 'sanctum')
            ->getJson("/api/forecasting/workers/{$worker->id}/patterns");

        $response->assertOk()
            ->assertJsonStructure([
                'worker_id',
                'patterns' => [
                    '*' => [
                        'day_of_week',
                        'day_name',
                        'probability',
                        'probability_percent',
                        'confidence',
                    ],
                ],
            ]);
    });

    it('returns worker predictions for authenticated user', function () {
        $worker = User::factory()->create(['user_type' => 'worker', 'status' => 'active']);

        AvailabilityPrediction::create([
            'user_id' => $worker->id,
            'prediction_date' => Carbon::tomorrow(),
            'morning_probability' => 0.7,
            'afternoon_probability' => 0.8,
            'evening_probability' => 0.6,
            'night_probability' => 0.1,
            'overall_probability' => 0.65,
        ]);

        $response = $this->actingAs($worker, 'sanctum')
            ->getJson("/api/forecasting/workers/{$worker->id}/predictions");

        $response->assertOk()
            ->assertJsonStructure([
                'worker_id',
                'start_date',
                'end_date',
                'predictions',
            ]);
    });

    it('returns demand forecasts', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        DemandForecast::create([
            'forecast_date' => Carbon::tomorrow(),
            'region' => 'London',
            'predicted_demand' => 100,
            'predicted_supply' => 80,
            'supply_demand_ratio' => 0.8,
            'demand_level' => 'high',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/forecasting/demand');

        $response->assertOk()
            ->assertJsonStructure([
                'start_date',
                'end_date',
                'forecasts',
            ]);
    });

    it('returns critical forecasts', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        DemandForecast::create([
            'forecast_date' => Carbon::tomorrow(),
            'region' => 'Paris',
            'predicted_demand' => 100,
            'predicted_supply' => 30,
            'supply_demand_ratio' => 0.3,
            'demand_level' => 'critical',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/forecasting/demand/critical');

        $response->assertOk()
            ->assertJsonStructure([
                'critical_forecasts',
                'total',
            ]);
    });

    it('returns available regions', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/forecasting/regions');

        $response->assertOk()
            ->assertJsonStructure(['regions']);
    });

    it('requires authentication for forecasting endpoints', function () {
        $response = $this->getJson('/api/forecasting/demand');

        $response->assertUnauthorized();
    });
});

describe('Forecasting Command', function () {
    it('command is registered', function () {
        $this->artisan('forecasting:generate --help')
            ->assertExitCode(0);
    });
});
