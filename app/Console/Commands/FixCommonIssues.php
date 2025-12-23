<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class FixCommonIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-issues {--force : Force fixes without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fix common application issues (Redis failures, cache issues, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 OvertimeStaff Issue Fixer');
        $this->newLine();

        $fixesApplied = [];

        // Check Redis connection
        $this->info('Checking Redis connection...');
        $redisWorking = false;
        try {
            $redisHost = config('database.redis.default.host');
            if ($redisHost && $redisHost !== '127.0.0.1') {
                Redis::connection()->ping();
                $redisWorking = true;
                $this->line('   ✅ Redis is working');
            } else {
                $this->line('   ℹ️  Redis not configured');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Redis connection failed: '.$e->getMessage());
            $this->newLine();

            if ($this->option('force') || $this->confirm('Redis is failing. Switch to file-based cache/session?', true)) {
                $this->info('   🔧 Applying fix: Switching to file-based cache and session...');
                $this->warn('   ⚠️  You need to set these environment variables in Laravel Cloud:');
                $this->line('      CACHE_DRIVER=file');
                $this->line('      SESSION_DRIVER=file');
                $this->line('      QUEUE_CONNECTION=database');
                $fixesApplied[] = 'Redis fallback recommended';
            }
        }
        $this->newLine();

        // Check database connection
        $this->info('Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->line('   ✅ Database is working');
        } catch (\Exception $e) {
            $this->error('   ❌ Database connection failed: '.$e->getMessage());
            $this->newLine();
            $this->warn('   ⚠️  Cannot fix database connection automatically.');
            $this->line('   Please verify these environment variables in Laravel Cloud:');
            $this->line('      DB_HOST');
            $this->line('      DB_DATABASE');
            $this->line('      DB_USERNAME');
            $this->line('      DB_PASSWORD');
        }
        $this->newLine();

        // Clear all caches
        $this->info('Clearing all caches...');
        try {
            Artisan::call('optimize:clear');
            $this->line('   ✅ Caches cleared');
            $fixesApplied[] = 'Caches cleared';
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to clear caches: '.$e->getMessage());
        }
        $this->newLine();

        // Rebuild caches
        $this->info('Rebuilding caches...');
        try {
            Artisan::call('config:cache');
            $this->line('   ✅ Config cache rebuilt');
            $fixesApplied[] = 'Config cache rebuilt';

            Artisan::call('route:cache');
            $this->line('   ✅ Route cache rebuilt');
            $fixesApplied[] = 'Route cache rebuilt';

            Artisan::call('view:cache');
            $this->line('   ✅ View cache rebuilt');
            $fixesApplied[] = 'View cache rebuilt';
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to rebuild caches: '.$e->getMessage());
        }
        $this->newLine();

        // Check storage link
        $this->info('Checking storage link...');
        $storageLink = public_path('storage');
        if (! is_link($storageLink) && ! file_exists($storageLink)) {
            if ($this->option('force') || $this->confirm('Storage link missing. Create it?', true)) {
                try {
                    Artisan::call('storage:link');
                    $this->line('   ✅ Storage link created');
                    $fixesApplied[] = 'Storage link created';
                } catch (\Exception $e) {
                    $this->error('   ❌ Failed to create storage link: '.$e->getMessage());
                }
            }
        } else {
            $this->line('   ✅ Storage link exists');
        }
        $this->newLine();

        // Summary
        $this->info('📋 Summary');
        $this->newLine();

        if (empty($fixesApplied)) {
            $this->info('✅ No fixes needed. Application appears healthy.');
        } else {
            $this->info('✅ Fixes applied:');
            foreach ($fixesApplied as $fix) {
                $this->line("   • {$fix}");
            }
            $this->newLine();
            $this->warn('⚠️  If Redis was failing, you MUST set these environment variables in Laravel Cloud:');
            $this->line('   CACHE_DRIVER=file');
            $this->line('   SESSION_DRIVER=file');
            $this->line('   QUEUE_CONNECTION=database');
            $this->newLine();
            $this->line('   Then redeploy your application.');
        }

        return Command::SUCCESS;
    }
}
