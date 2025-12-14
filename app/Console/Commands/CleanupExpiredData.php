<?php

namespace App\Console\Commands;

use App\Models\AvailabilityBroadcast;
use App\Models\Notifications;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired broadcasts, old notifications (90 days), and expired dev accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting system cleanup...');

        try {
            $cleaned = [];

            // 1. Expire old availability broadcasts
            $expiredBroadcasts = AvailabilityBroadcast::where('status', 'active')
                ->where('available_to', '<', now())
                ->get();

            foreach ($expiredBroadcasts as $broadcast) {
                $broadcast->expire();
            }

            $cleaned['broadcasts'] = $expiredBroadcasts->count();
            $this->info("✓ Expired {$cleaned['broadcasts']} availability broadcast(s)");

            // 2. Delete old notifications (90+ days)
            $ninetyDaysAgo = Carbon::now()->subDays(90);
            $deletedNotifications = Notifications::where('created_at', '<', $ninetyDaysAgo)->delete();
            $cleaned['notifications'] = $deletedNotifications;
            $this->info("✓ Deleted {$cleaned['notifications']} old notification(s) (90+ days)");

            // 3. Cleanup expired dev accounts (already handled by dev:cleanup-expired, but double-check)
            $expiredDevAccounts = User::where('is_dev_account', true)
                ->whereNotNull('dev_expires_at')
                ->where('dev_expires_at', '<', now())
                ->where('status', 'active')
                ->get();

            foreach ($expiredDevAccounts as $user) {
                $user->update(['status' => 'inactive']);
            }

            $cleaned['dev_accounts'] = $expiredDevAccounts->count();
            $this->info("✓ Deactivated {$cleaned['dev_accounts']} expired dev account(s)");

            // 4. Cleanup old shift notifications (90+ days, read)
            $deletedShiftNotifications = \App\Models\ShiftNotification::where('read', true)
                ->where('read_at', '<', $ninetyDaysAgo)
                ->delete();
            $cleaned['shift_notifications'] = $deletedShiftNotifications;
            $this->info("✓ Deleted {$cleaned['shift_notifications']} old shift notification(s)");

            $total = array_sum($cleaned);
            $this->info("\nSummary: {$total} item(s) cleaned up");

            return 0;
        } catch (\Exception $e) {
            Log::error("CleanupExpiredData command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
