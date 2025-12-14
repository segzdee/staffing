<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateMarketStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:calculate-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update live market statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Calculating market statistics...');

        try {
            $today = Carbon::today();

            // Get or create today's analytics record
            $analytics = DB::table('platform_analytics')
                ->where('date', $today->toDateString())
                ->first();

            if (!$analytics) {
                DB::table('platform_analytics')->insert([
                    'date' => $today->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Calculate live statistics
            $stats = [
                'total_shifts_posted' => Shift::count(),
                'total_shifts_filled' => Shift::where('status', 'filled')->count(),
                'total_shifts_completed' => Shift::where('status', 'completed')->count(),
                'platform_revenue' => ShiftPayment::where('status', 'paid_out')->sum('platform_fee'),
                'total_gmv' => ShiftPayment::where('status', 'paid_out')->sum('amount_gross'),
                'new_workers' => User::where('user_type', 'worker')
                    ->whereDate('created_at', $today)
                    ->count(),
                'new_businesses' => User::where('user_type', 'business')
                    ->whereDate('created_at', $today)
                    ->count(),
                'new_agencies' => User::where('user_type', 'agency')
                    ->whereDate('created_at', $today)
                    ->count(),
                'active_workers' => User::where('user_type', 'worker')
                    ->where('status', 'active')
                    ->whereHas('workerProfile', function($q) {
                        $q->where('total_shifts_completed', '>', 0);
                    })
                    ->count(),
                'active_businesses' => User::where('user_type', 'business')
                    ->where('status', 'active')
                    ->whereHas('businessProfile', function($q) {
                        $q->where('total_shifts_posted', '>', 0);
                    })
                    ->count(),
                'average_shift_value' => ShiftPayment::where('status', 'paid_out')
                    ->avg('amount_gross') ?? 0,
                'fill_rate' => $this->calculateFillRate(),
                'total_disputes' => ShiftPayment::where('disputed', true)->count(),
                'disputes_resolved' => ShiftPayment::where('disputed', true)
                    ->whereNotNull('resolved_at')
                    ->count(),
                'updated_at' => now(),
            ];

            // Update analytics record
            DB::table('platform_analytics')
                ->where('date', $today->toDateString())
                ->update($stats);

            $this->info('✓ Market statistics updated');
            $this->info("  - Total shifts: {$stats['total_shifts_posted']}");
            $this->info("  - Fill rate: {$stats['fill_rate']}%");
            $this->info("  - Platform revenue: $" . number_format($stats['platform_revenue'], 2));

            return 0;
        } catch (\Exception $e) {
            Log::error("CalculateMarketStats command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Calculate fill rate percentage.
     */
    private function calculateFillRate()
    {
        $totalShifts = Shift::where('status', '!=', 'cancelled')->count();
        $filledShifts = Shift::where('status', 'filled')->count();

        if ($totalShifts == 0) {
            return 0;
        }

        return round(($filledShifts / $totalShifts) * 100, 2);
    }
}
