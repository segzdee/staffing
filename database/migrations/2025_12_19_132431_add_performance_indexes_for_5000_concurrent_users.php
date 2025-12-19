<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration for 5000 Concurrent Users
 *
 * This migration adds composite and individual indexes identified
 * during the database performance audit to support high-concurrency loads.
 *
 * @see /docs/DATABASE_PERFORMANCE_AUDIT_REPORT.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================
        // USERS TABLE - Critical for 5000 users
        // ========================================
        Schema::table('users', function (Blueprint $table) {
            // Composite index for dashboard queries filtering by type and status
            if (! $this->indexExists('users', 'idx_users_type_status')) {
                $table->index(['user_type', 'status'], 'idx_users_type_status');
            }

            // Index for verification queries (frequently filtered)
            if (! $this->indexExists('users', 'idx_users_verified_worker')) {
                $table->index(['is_verified_worker', 'user_type'], 'idx_users_verified_worker');
            }

            if (! $this->indexExists('users', 'idx_users_verified_business')) {
                $table->index(['is_verified_business', 'user_type'], 'idx_users_verified_business');
            }
        });

        // ========================================
        // SHIFTS TABLE - Additional optimizations
        // ========================================
        Schema::table('shifts', function (Blueprint $table) {
            // For business dashboard: upcoming shifts query
            // Covers: WHERE business_id = ? AND status IN (...) AND shift_date >= ?
            if (! $this->indexExists('shifts', 'idx_shifts_business_upcoming')) {
                $table->index(['business_id', 'status', 'shift_date'], 'idx_shifts_business_upcoming');
            }

            // For worker marketplace: open shifts by location
            // Covers: WHERE status = 'open' AND shift_date >= ? AND location_state = ?
            if (! $this->indexExists('shifts', 'idx_shifts_market_location')) {
                $table->index(['status', 'shift_date', 'location_state', 'location_city'], 'idx_shifts_market_location');
            }

            // For agency queries
            if (! $this->indexExists('shifts', 'idx_shifts_agency_status')) {
                $table->index(['posted_by_agency_id', 'status'], 'idx_shifts_agency_status');
            }

            // For urgency-based queries and notifications
            if (! $this->indexExists('shifts', 'idx_shifts_urgent')) {
                $table->index(['status', 'urgency_level', 'shift_date'], 'idx_shifts_urgent');
            }

            // For filled workers calculation
            if (! $this->indexExists('shifts', 'idx_shifts_fill_status')) {
                $table->index(['business_id', 'filled_workers', 'required_workers'], 'idx_shifts_fill_status');
            }
        });

        // ========================================
        // SHIFT_APPLICATIONS TABLE
        // ========================================
        Schema::table('shift_applications', function (Blueprint $table) {
            // For counting pending applications per business (via shift)
            if (! $this->indexExists('shift_applications', 'idx_applications_pending_date')) {
                $table->index(['status', 'created_at'], 'idx_applications_pending_date');
            }

            // For worker application history views
            if (! $this->indexExists('shift_applications', 'idx_applications_worker_history')) {
                $table->index(['worker_id', 'created_at'], 'idx_applications_worker_history');
            }

            // For application response time analytics
            if (! $this->indexExists('shift_applications', 'idx_applications_response')) {
                $table->index(['status', 'responded_at'], 'idx_applications_response');
            }
        });

        // ========================================
        // SHIFT_ASSIGNMENTS TABLE
        // ========================================
        Schema::table('shift_assignments', function (Blueprint $table) {
            // For payment processing queries
            if (Schema::hasColumn('shift_assignments', 'payment_status')) {
                if (! $this->indexExists('shift_assignments', 'idx_assignments_payment')) {
                    $table->index(['status', 'payment_status'], 'idx_assignments_payment');
                }
            }

            // For late/no-show detection jobs
            if (! $this->indexExists('shift_assignments', 'idx_assignments_checkin')) {
                $table->index(['status', 'check_in_time', 'shift_id'], 'idx_assignments_checkin');
            }

            // For worker earnings calculation and history
            if (! $this->indexExists('shift_assignments', 'idx_assignments_worker_earnings')) {
                $table->index(['worker_id', 'status', 'created_at'], 'idx_assignments_worker_earnings');
            }

            // For business worker management
            if (! $this->indexExists('shift_assignments', 'idx_assignments_assigned_by')) {
                $table->index(['assigned_by', 'status'], 'idx_assignments_assigned_by');
            }
        });

        // ========================================
        // SHIFT_PAYMENTS TABLE
        // ========================================
        Schema::table('shift_payments', function (Blueprint $table) {
            // For payout processing job - critical for instant payouts
            if (! $this->indexExists('shift_payments', 'idx_payments_payout_queue')) {
                $table->index(['status', 'released_at', 'payout_completed_at'], 'idx_payments_payout_queue');
            }

            // For dispute management dashboard
            if (! $this->indexExists('shift_payments', 'idx_payments_disputes')) {
                $table->index(['disputed', 'disputed_at'], 'idx_payments_disputes');
            }

            // For worker earnings reports and tax documents
            if (! $this->indexExists('shift_payments', 'idx_payments_worker_earnings')) {
                $table->index(['worker_id', 'status', 'created_at'], 'idx_payments_worker_earnings');
            }

            // For business spending reports and analytics
            if (! $this->indexExists('shift_payments', 'idx_payments_business_spending')) {
                $table->index(['business_id', 'status', 'created_at'], 'idx_payments_business_spending');
            }

            // For escrow management
            if (! $this->indexExists('shift_payments', 'idx_payments_escrow')) {
                $table->index(['status', 'escrow_held_at'], 'idx_payments_escrow');
            }
        });

        // ========================================
        // WORKER_PROFILES TABLE
        // ========================================
        Schema::table('worker_profiles', function (Blueprint $table) {
            // For worker search/matching algorithms
            if (! $this->indexExists('worker_profiles', 'idx_worker_profiles_performance')) {
                $table->index(['reliability_score', 'rating_average'], 'idx_worker_profiles_performance');
            }

            // For availability broadcasts and instant matching
            if (Schema::hasColumn('worker_profiles', 'is_available')) {
                if (! $this->indexExists('worker_profiles', 'idx_worker_profiles_available')) {
                    $table->index(['is_available', 'background_check_status'], 'idx_worker_profiles_available');
                }
            }

            // For worker search by experience
            if (! $this->indexExists('worker_profiles', 'idx_worker_profiles_experience')) {
                $table->index(['years_experience', 'rating_average'], 'idx_worker_profiles_experience');
            }
        });

        // ========================================
        // RATINGS TABLE (if exists)
        // ========================================
        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                // For calculating average ratings efficiently
                if (Schema::hasColumn('ratings', 'ratee_id') && Schema::hasColumn('ratings', 'ratee_type')) {
                    if (! $this->indexExists('ratings', 'idx_ratings_ratee')) {
                        $table->index(['ratee_id', 'ratee_type', 'created_at'], 'idx_ratings_ratee');
                    }
                }
            });
        }

        // ========================================
        // CONVERSATIONS TABLE (if exists)
        // ========================================
        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                // For messaging performance
                if (Schema::hasColumn('conversations', 'worker_id') && Schema::hasColumn('conversations', 'business_id')) {
                    if (! $this->indexExists('conversations', 'idx_conversations_participants')) {
                        $table->index(['worker_id', 'business_id', 'is_archived'], 'idx_conversations_participants');
                    }
                }

                if (Schema::hasColumn('conversations', 'last_message_at')) {
                    if (! $this->indexExists('conversations', 'idx_conversations_recent')) {
                        $table->index(['last_message_at'], 'idx_conversations_recent');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_users_type_status');
            $table->dropIndexIfExists('idx_users_verified_worker');
            $table->dropIndexIfExists('idx_users_verified_business');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_shifts_business_upcoming');
            $table->dropIndexIfExists('idx_shifts_market_location');
            $table->dropIndexIfExists('idx_shifts_agency_status');
            $table->dropIndexIfExists('idx_shifts_urgent');
            $table->dropIndexIfExists('idx_shifts_fill_status');
        });

        Schema::table('shift_applications', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_applications_pending_date');
            $table->dropIndexIfExists('idx_applications_worker_history');
            $table->dropIndexIfExists('idx_applications_response');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_assignments_payment');
            $table->dropIndexIfExists('idx_assignments_checkin');
            $table->dropIndexIfExists('idx_assignments_worker_earnings');
            $table->dropIndexIfExists('idx_assignments_assigned_by');
        });

        Schema::table('shift_payments', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_payments_payout_queue');
            $table->dropIndexIfExists('idx_payments_disputes');
            $table->dropIndexIfExists('idx_payments_worker_earnings');
            $table->dropIndexIfExists('idx_payments_business_spending');
            $table->dropIndexIfExists('idx_payments_escrow');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_worker_profiles_performance');
            $table->dropIndexIfExists('idx_worker_profiles_available');
            $table->dropIndexIfExists('idx_worker_profiles_experience');
        });

        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                $table->dropIndexIfExists('idx_ratings_ratee');
            });
        }

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropIndexIfExists('idx_conversations_participants');
                $table->dropIndexIfExists('idx_conversations_recent');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
