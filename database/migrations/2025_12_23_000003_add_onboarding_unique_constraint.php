<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PRIORITY-0: Ensure one active onboarding progress per user
     * Note: MySQL doesn't support partial unique indexes, so we use application-level enforcement
     * and add a regular unique index on user_id for active statuses
     */
    public function up(): void
    {
        // For worker onboarding
        if (Schema::hasTable('onboarding_progress')) {
            Schema::table('onboarding_progress', function (Blueprint $table) {
                // Add unique index on user_id to prevent multiple active onboarding records
                // Application logic will enforce "one active per user" rule
                if (! $this->hasIndex('onboarding_progress', 'onboarding_progress_user_id_unique')) {
                    $table->unique('user_id', 'onboarding_progress_user_id_unique');
                }
            });
        }

        // For business onboarding
        if (Schema::hasTable('business_onboarding')) {
            Schema::table('business_onboarding', function (Blueprint $table) {
                // Add unique index on user_id to prevent multiple active onboarding records
                if (! $this->hasIndex('business_onboarding', 'business_onboarding_user_id_unique')) {
                    $table->unique('user_id', 'business_onboarding_user_id_unique');
                }
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?',
            [$databaseName, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('onboarding_progress')) {
            Schema::table('onboarding_progress', function (Blueprint $table) {
                $table->dropUnique('unique_active_onboarding');
            });
        }

        if (Schema::hasTable('business_onboarding')) {
            Schema::table('business_onboarding', function (Blueprint $table) {
                $table->dropUnique('unique_active_business_onboarding');
            });
        }
    }
};
