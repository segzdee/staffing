<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GLO-001: Multi-Currency Support - Exchange Rate Update Command
 *
 * Fetches the latest exchange rates from configured source (ECB, OpenExchangeRates)
 * and updates the database. Should be scheduled to run daily after ECB update.
 */
class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates
                            {--source= : Override the configured rate source (ecb, openexchangerates)}
                            {--force : Force update even if rates were recently updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update exchange rates from external API sources';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $source = $this->option('source') ?? config('currencies.exchange_rate_source', 'ecb');
        $force = $this->option('force');

        $this->info("Updating exchange rates from {$source}...");

        try {
            // Check if update is needed (unless forced)
            if (! $force && ! $exchangeRateService->areRatesStale()) {
                $status = $exchangeRateService->getRateStatus();
                $this->info("Rates are current (last updated: {$status['last_updated']->format('Y-m-d H:i:s')})");
                $this->info('Use --force to update anyway.');

                return Command::SUCCESS;
            }

            // Update rates
            $success = $exchangeRateService->updateRates();

            if (! $success) {
                $this->error('Failed to update exchange rates. Check logs for details.');
                Log::error('UpdateExchangeRates command failed');

                return Command::FAILURE;
            }

            // Display results
            $status = $exchangeRateService->getRateStatus();
            $rates = $exchangeRateService->getAllCurrentRates();

            $this->info('Exchange rates updated successfully!');
            $this->newLine();
            $this->info("Source: {$status['source']}");
            $this->info("Last Updated: {$status['last_updated']->format('Y-m-d H:i:s')}");
            $this->info("Available Rates: {$status['available_rates']}/{$status['total_supported']}");

            if (count($status['missing_rates']) > 0) {
                $this->warn('Missing rates for: '.implode(', ', $status['missing_rates']));
            }

            $this->newLine();
            $this->info('Current Rates (Base: '.config('currencies.default', 'EUR').')');
            $this->table(
                ['Currency', 'Rate', 'Date', 'Status'],
                collect($rates)->map(function ($rate, $currency) {
                    return [
                        $currency,
                        number_format($rate['rate'], 6),
                        $rate['date'],
                        $rate['is_stale'] ? 'STALE' : 'OK',
                    ];
                })->toArray()
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error updating rates: {$e->getMessage()}");
            Log::error('UpdateExchangeRates command error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
