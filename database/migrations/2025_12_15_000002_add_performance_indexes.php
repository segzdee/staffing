<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            // Add composite indexes for common dashboard queries
            $table->index(['worker_id', 'created_at'], 'idx_worker_created_at');
            $table->index(['worker_id', 'status', 'created_at'], 'idx_worker_status_created');
            $table->index(['shift_id', 'status', 'check_in_time'], 'idx_shift_status_checkin');
        });

        Schema::table('shifts', function (Blueprint $table) {
            // Add composite indexes for business dashboard queries
            $table->index(['business_id', 'shift_date', 'status'], 'idx_business_date_status');
            $table->index(['business_id', 'created_at'], 'idx_business_created_at');
            $table->index(['status', 'shift_date'], 'idx_status_date');
            $table->index(['in_market', 'shift_date', 'status'], 'idx_market_date_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_worker_created_at');
            $table->dropIndex('idx_worker_status_created');
            $table->dropIndex('idx_shift_status_checkin');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_business_date_status');
            $table->dropIndex('idx_business_created_at');
            $table->dropIndex('idx_status_date');
            $table->dropIndex('idx_market_date_status');
        });
    }
}