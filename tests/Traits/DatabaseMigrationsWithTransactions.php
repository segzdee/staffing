<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * DatabaseMigrationsWithTransactions Trait
 * 
 * Hybrid approach that:
 * 1. Runs migrations once before tests (like RefreshDatabase)
 * 2. Uses transactions for rollback (like DatabaseTransactions)
 * 
 * This ensures migrations are available while providing fast test isolation.
 * Works with both Pest and PHPUnit.
 */
trait DatabaseMigrationsWithTransactions
{
    use DatabaseTransactions;

    /**
     * Track if migrations have been run
     */
    protected static $migrationsRun = false;

    /**
     * Initialize migrations (called by both setUp and beforeEach)
     */
    protected function initializeMigrations(): void
    {
        // Run migrations only once for the test suite
        if (!static::$migrationsRun) {
            $this->runMigrations();
            static::$migrationsRun = true;
        }

        // Disable foreign key checks for transaction rollback
        // This prevents issues when rolling back transactions with foreign keys
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
    }

    /**
     * Cleanup after test (called by both tearDown and afterEach)
     */
    protected function cleanupMigrations(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Run migrations
     */
    protected function runMigrations(): void
    {
        // Check if migrations table exists
        $migrationsTableExists = DB::getSchemaBuilder()->hasTable('migrations');

        if (!$migrationsTableExists || $this->hasPendingMigrations()) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    /**
     * Check if there are pending migrations
     */
    protected function hasPendingMigrations(): bool
    {
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
            return strpos($output, 'Pending') !== false;
        } catch (\Exception $e) {
            // If we can't check, assume we need to run migrations
            return true;
        }
    }
}
