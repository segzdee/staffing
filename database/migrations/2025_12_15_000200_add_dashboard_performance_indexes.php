<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Indexes for shift_assignments table (most queried in dashboards)
        Schema::table('shift_assignments', function (Blueprint $table) {
            // Composite index for worker dashboard queries
            try {
                $table->index(['worker_id', 'status', 'created_at'], 'idx_worker_status_created');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for status filtering
            try {
                $table->index('status', 'idx_status');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for shift_id joins
            try {
                $table->index('shift_id', 'idx_shift_id');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for shifts table
        Schema::table('shifts', function (Blueprint $table) {
            // Composite index for business dashboard queries
            try {
                $table->index(['business_id', 'status', 'shift_date'], 'idx_business_status_date');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for status filtering
            try {
                $table->index('status', 'idx_status');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for date filtering
            try {
                $table->index('shift_date', 'idx_shift_date');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for allow_agencies (agency dashboard)
            try {
                $table->index('allow_agencies', 'idx_allow_agencies');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for shift_applications table
        Schema::table('shift_applications', function (Blueprint $table) {
            // Composite index for business dashboard queries
            try {
                $table->index(['worker_id', 'status'], 'idx_worker_status');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for shift_id joins
            try {
                $table->index('shift_id', 'idx_shift_id');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for status filtering
            try {
                $table->index('status', 'idx_status');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for shift_payments table
        Schema::table('shift_payments', function (Blueprint $table) {
            // Composite index for worker earnings queries
            try {
                $table->index(['worker_id', 'status', 'payout_completed_at'], 'idx_worker_status_date');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Composite index for business cost queries
            try {
                $table->index(['business_id', 'status', 'created_at'], 'idx_business_status_created');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for shift_assignment_id joins
            try {
                $table->index('shift_assignment_id', 'idx_shift_assignment_id');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for agency_workers table
        Schema::table('agency_workers', function (Blueprint $table) {
            // Composite index for agency dashboard queries
            try {
                $table->index(['agency_id', 'status'], 'idx_agency_status');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
            
            // Index for worker_id joins
            try {
                $table->index('worker_id', 'idx_worker_id');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for shift_notifications table
        Schema::table('shift_notifications', function (Blueprint $table) {
            // Composite index for notification count queries
            try {
                $table->index(['user_id', 'read'], 'idx_user_read');
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
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
            $table->dropIndex('idx_worker_status_created');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_shift_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_business_status_date');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_shift_date');
            $table->dropIndex('idx_allow_agencies');
        });

        Schema::table('shift_applications', function (Blueprint $table) {
            $table->dropIndex('idx_worker_status');
            $table->dropIndex('idx_shift_id');
            $table->dropIndex('idx_status');
        });

        Schema::table('shift_payments', function (Blueprint $table) {
            $table->dropIndex('idx_worker_status_date');
            $table->dropIndex('idx_business_status_created');
            $table->dropIndex('idx_shift_assignment_id');
        });

        Schema::table('agency_workers', function (Blueprint $table) {
            $table->dropIndex('idx_agency_status');
            $table->dropIndex('idx_worker_id');
        });

        Schema::table('shift_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_user_read');
        });
    }

}
