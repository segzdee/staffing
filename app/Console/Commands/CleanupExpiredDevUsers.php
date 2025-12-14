<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use App\Models\AiAgentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredDevUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:cleanup-expired {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired development user accounts and all associated data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
        }

        // Find expired dev users
        $expiredUsers = User::where('is_dev_account', true)
            ->where('dev_expires_at', '<', Carbon::now())
            ->get();

        if ($expiredUsers->isEmpty()) {
            $this->info('✅ No expired dev accounts found.');
            return 0;
        }

        $this->info("Found {$expiredUsers->count()} expired dev account(s) to clean up.");

        $deletedCount = 0;
        $deletedProfiles = 0;
        $deletedRelated = 0;

        foreach ($expiredUsers as $user) {
            $this->line("Processing: {$user->email} (expired: {$user->dev_expires_at})");

            if ($isDryRun) {
                $this->warn("  Would delete user ID: {$user->id}");
                $deletedCount++;
                continue;
            }

            try {
                DB::beginTransaction();

                // Delete associated profiles
                $profilesDeleted = 0;
                if ($user->workerProfile) {
                    $user->workerProfile->delete();
                    $profilesDeleted++;
                }
                if ($user->businessProfile) {
                    $user->businessProfile->delete();
                    $profilesDeleted++;
                }
                if ($user->agencyProfile) {
                    $user->agencyProfile->delete();
                    $profilesDeleted++;
                }
                if ($user->aiAgentProfile) {
                    $user->aiAgentProfile->delete();
                    $profilesDeleted++;
                }
                $deletedProfiles += $profilesDeleted;

                // Delete related records
                $relatedDeleted = 0;

                // Delete shift applications
                $relatedDeleted += DB::table('shift_applications')
                    ->where('worker_id', $user->id)
                    ->orWhere('shift_id', 'IN', function($query) use ($user) {
                        $query->select('id')
                            ->from('shifts')
                            ->where('business_id', $user->id);
                    })
                    ->delete();

                // Delete shift assignments
                $relatedDeleted += DB::table('shift_assignments')
                    ->where('worker_id', $user->id)
                    ->orWhere('shift_id', 'IN', function($query) use ($user) {
                        $query->select('id')
                            ->from('shifts')
                            ->where('business_id', $user->id);
                    })
                    ->delete();

                // Delete shifts (if business)
                if ($user->isBusiness()) {
                    $relatedDeleted += DB::table('shifts')
                        ->where('business_id', $user->id)
                        ->delete();
                }

                // Delete messages
                $relatedDeleted += DB::table('messages')
                    ->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id)
                    ->delete();

                // Delete conversations
                $relatedDeleted += DB::table('conversations')
                    ->where('worker_id', $user->id)
                    ->orWhere('business_id', $user->id)
                    ->delete();

                // Delete notifications
                $relatedDeleted += DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->delete();

                // Delete worker skills
                $relatedDeleted += DB::table('worker_skills')
                    ->where('worker_id', $user->id)
                    ->delete();

                // Delete ratings
                $relatedDeleted += DB::table('ratings')
                    ->where('rater_id', $user->id)
                    ->orWhere('rated_id', $user->id)
                    ->delete();

                $deletedRelated += $relatedDeleted;

                // Delete the user
                $user->delete();
                $deletedCount++;

                DB::commit();

                $this->info("  ✓ Deleted user and {$profilesDeleted} profile(s), {$relatedDeleted} related record(s)");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Error deleting user {$user->id}: {$e->getMessage()}");
                Log::error("Failed to delete expired dev user", [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Summary
        $this->newLine();
        if ($isDryRun) {
            $this->info("📊 DRY RUN SUMMARY:");
            $this->info("   Would delete: {$deletedCount} user(s)");
        } else {
            $this->info("📊 CLEANUP SUMMARY:");
            $this->info("   Deleted: {$deletedCount} user(s)");
            $this->info("   Deleted: {$deletedProfiles} profile(s)");
            $this->info("   Deleted: {$deletedRelated} related record(s)");

            Log::info("Expired dev users cleanup completed", [
                'users_deleted' => $deletedCount,
                'profiles_deleted' => $deletedProfiles,
                'related_records_deleted' => $deletedRelated,
            ]);
        }

        return 0;
    }
}
