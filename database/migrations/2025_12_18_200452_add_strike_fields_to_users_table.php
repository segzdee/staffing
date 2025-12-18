<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // WKR-009: Enhanced suspension tracking fields
            // Note: is_suspended provides quick lookup, while worker_suspensions table has full details
            if (! Schema::hasColumn('users', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false)->after('status');
            }
            if (! Schema::hasColumn('users', 'strike_count')) {
                $table->integer('strike_count')->default(0)->after('suspension_count');
            }
            if (! Schema::hasColumn('users', 'last_strike_at')) {
                $table->timestamp('last_strike_at')->nullable()->after('strike_count');
            }

            // Add index for suspended users lookup
            $table->index('is_suspended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_suspended']);

            if (Schema::hasColumn('users', 'is_suspended')) {
                $table->dropColumn('is_suspended');
            }
            if (Schema::hasColumn('users', 'strike_count')) {
                $table->dropColumn('strike_count');
            }
            if (Schema::hasColumn('users', 'last_strike_at')) {
                $table->dropColumn('last_strike_at');
            }
        });
    }
};
