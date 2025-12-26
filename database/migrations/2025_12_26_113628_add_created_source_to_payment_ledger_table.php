<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SECURITY: Add created_source to track whether ledger entry was created by user, webhook, cron, or system
     */
    public function up(): void
    {
        Schema::table('payment_ledger', function (Blueprint $table) {
            $table->string('created_source', 50)->nullable()->after('created_by')
                ->comment('Source of entry: user, webhook, cron, system');

            $table->index('created_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_ledger', function (Blueprint $table) {
            $table->dropIndex(['created_source']);
            $table->dropColumn('created_source');
        });
    }
};
