<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class DiagnoseApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose application health: database, Redis, cache, and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 OvertimeStaff Application Diagnostics');
        $this->newLine();

        $issues = [];
        $warnings = [];

        // 1. Check Database Connection
        $this->info('📊 Checking Database Connection...');
        try {
            DB::connection()->getPdo();
            $this->line('   ✅ Database: Connected');
            $this->line('   Host: '.config('database.connections.mysql.host'));
            $this->line('   Database: '.config('database.connections.mysql.database'));
            $this->line('   Username: '.config('database.connections.mysql.username'));

            // Check if migrations table exists
            if (Schema::hasTable('migrations')) {
                $migrationCount = DB::table('migrations')->count();
                $this->line("   Migrations: {$migrationCount} run");
            } else {
                $warnings[] = 'Migrations table does not exist';
                $this->warn('   ⚠️  Migrations table not found');
            }
        } catch (\Exception $e) {
            $issues[] = 'Database connection failed: '.$e->getMessage();
            $this->error('   ❌ Database: Connection failed');
            $this->error('   Error: '.$e->getMessage());
        }
        $this->newLine();

        // 2. Check Redis Connection
        $this->info('🔴 Checking Redis Connection...');
        try {
            $redisHost = config('database.redis.default.host');
            $redisPort = config('database.redis.default.port');
            $redisScheme = config('database.redis.default.scheme', 'tcp');

            $this->line("   Host: {$redisHost}");
            $this->line("   Port: {$redisPort}");
            $this->line("   Scheme: {$redisScheme}");

            if ($redisHost && $redisHost !== '127.0.0.1') {
                Redis::connection()->ping();
                $this->line('   ✅ Redis: Connected');

                // Test cache
                Cache::put('diagnostic_test', 'ok', 10);
                $testValue = Cache::get('diagnostic_test');
                if ($testValue === 'ok') {
                    $this->line('   ✅ Cache: Working');
                    Cache::forget('diagnostic_test');
                } else {
                    $warnings[] = 'Cache test failed';
                    $this->warn('   ⚠️  Cache: Test failed');
                }
            } else {
                $this->line('   ℹ️  Redis: Not configured (using default host)');
            }
        } catch (\Exception $e) {
            $issues[] = 'Redis connection failed: '.$e->getMessage();
            $this->error('   ❌ Redis: Connection failed');
            $this->error('   Error: '.$e->getMessage());
            $this->warn('   💡 Tip: Set CACHE_DRIVER=file and SESSION_DRIVER=file if Redis is unavailable');
        }
        $this->newLine();

        // 3. Check Environment Variables
        $this->info('⚙️  Checking Critical Environment Variables...');
        $requiredVars = [
            'APP_ENV' => config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'APP_URL' => config('app.url'),
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.mysql.host'),
            'DB_DATABASE' => config('database.connections.mysql.database'),
            'CACHE_DRIVER' => config('cache.default'),
            'SESSION_DRIVER' => config('session.driver'),
            'QUEUE_CONNECTION' => config('queue.default'),
        ];

        foreach ($requiredVars as $key => $value) {
            if (empty($value) && $key !== 'APP_DEBUG') {
                $warnings[] = "Environment variable {$key} is not set";
                $this->warn("   ⚠️  {$key}: Not set");
            } else {
                $displayValue = $key === 'DB_PASSWORD' ? '***' : ($value ?? 'null');
                $this->line("   ✅ {$key}: {$displayValue}");
            }
        }
        $this->newLine();

        // 4. Check Cache Configuration
        $this->info('💾 Checking Cache Configuration...');
        $cacheDriver = config('cache.default');
        $this->line("   Driver: {$cacheDriver}");

        if ($cacheDriver === 'redis') {
            try {
                Cache::put('test', 'value', 1);
                $this->line('   ✅ Cache driver is working');
            } catch (\Exception $e) {
                $issues[] = 'Cache driver failed: '.$e->getMessage();
                $this->error('   ❌ Cache driver failed: '.$e->getMessage());
            }
        } else {
            $this->line('   ℹ️  Using '.$cacheDriver.' cache driver');
        }
        $this->newLine();

        // 5. Check Session Configuration
        $this->info('🔐 Checking Session Configuration...');
        $sessionDriver = config('session.driver');
        $this->line("   Driver: {$sessionDriver}");
        $this->line('   Lifetime: '.config('session.lifetime').' minutes');
        $this->line('   Encrypt: '.(config('session.encrypt') ? 'Yes' : 'No'));
        $this->newLine();

        // 6. Check Queue Configuration
        $this->info('📬 Checking Queue Configuration...');
        $queueDriver = config('queue.default');
        $this->line("   Driver: {$queueDriver}");
        if ($queueDriver === 'redis') {
            try {
                Redis::connection('default')->ping();
                $this->line('   ✅ Queue connection is working');
            } catch (\Exception $e) {
                $warnings[] = 'Queue Redis connection failed: '.$e->getMessage();
                $this->warn('   ⚠️  Queue Redis connection failed');
            }
        }
        $this->newLine();

        // 7. Check Storage
        $this->info('📁 Checking Storage...');
        $storageLink = public_path('storage');
        if (is_link($storageLink) || file_exists($storageLink)) {
            $this->line('   ✅ Storage link exists');
        } else {
            $warnings[] = 'Storage link does not exist. Run: php artisan storage:link';
            $this->warn('   ⚠️  Storage link missing (run: php artisan storage:link)');
        }
        $this->newLine();

        // Summary
        $this->info('📋 Summary');
        $this->newLine();

        if (empty($issues) && empty($warnings)) {
            $this->info('✅ All checks passed! Application is healthy.');
            $this->newLine();
            $this->line('💡 Next steps:');
            $this->line('   1. Check Laravel Cloud logs for any runtime errors');
            $this->line('   2. Monitor application performance');
            $this->line('   3. Verify all features are working correctly');

            return Command::SUCCESS;
        }

        if (! empty($issues)) {
            $this->error('❌ Critical Issues Found:');
            foreach ($issues as $issue) {
                $this->error("   • {$issue}");
            }
            $this->newLine();
        }

        if (! empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->warn("   • {$warning}");
            }
            $this->newLine();
        }

        $this->line('💡 Recommendations:');
        if (in_array('Database connection failed', $issues)) {
            $this->line('   1. Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD are set correctly');
            $this->line('   2. Check database server is running and accessible');
            $this->line('   3. Verify firewall rules allow connections');
        }
        if (in_array('Redis connection failed', $issues)) {
            $this->line('   1. Verify REDIS_HOST, REDIS_PASSWORD, REDIS_USERNAME are set correctly');
            $this->line('   2. Temporarily set CACHE_DRIVER=file and SESSION_DRIVER=file');
            $this->line('   3. Check Redis server is running and accessible');
        }

        return ! empty($issues) ? Command::FAILURE : Command::SUCCESS;
    }
}
