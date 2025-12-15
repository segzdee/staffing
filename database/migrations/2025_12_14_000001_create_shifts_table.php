<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftsTable extends Migration
{
    /**
     * Run the migrations - Comprehensive shifts table with all columns.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            // ===== BUSINESS & OWNERSHIP =====
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('venue_id')->nullable()->constrained('business_profiles')->onDelete('cascade');

            // AI Agent tracking
            $table->boolean('posted_by_agent')->default(false);
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

            // Agency tracking
            $table->foreignId('agency_client_id')->nullable()->index();
            $table->foreignId('posted_by_agency_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('allow_agencies')->default(true)->index();

            // Template reference
            $table->foreignId('template_id')->nullable()->constrained('shift_templates')->onDelete('set null');

            // ===== BASIC SHIFT INFORMATION =====
            $table->string('title');
            $table->text('description');
            $table->string('role_type')->nullable()->index(); // e.g., "Server", "Bartender", "RN"

            $table->enum('industry', [
                'hospitality',
                'healthcare',
                'retail',
                'events',
                'warehouse',
                'professional',
                'logistics',
                'construction',
                'security',
                'cleaning'
            ])->index();

            // ===== LOCATION =====
            $table->string('location_address');
            $table->string('location_city');
            $table->string('location_state');
            $table->string('location_country');
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();

            // Geofencing for clock-in
            $table->integer('geofence_radius')->default(100); // meters
            $table->integer('early_clockin_minutes')->default(15); // Allow 15 min early
            $table->integer('late_grace_minutes')->default(10); // 10 min grace

            // ===== TIMING =====
            $table->date('shift_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->dateTime('start_datetime')->nullable()->index();
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('duration_hours', 5, 2);

            // Duration limits
            $table->decimal('minimum_shift_duration', 5, 2)->default(3.00);
            $table->decimal('maximum_shift_duration', 5, 2)->default(12.00);
            $table->decimal('required_rest_hours', 5, 2)->default(8.00);

            // ===== PRICING & FINANCIAL =====
            // Base rates
            $table->integer('base_rate')->nullable(); // Stored as cents
            $table->integer('dynamic_rate')->nullable();
            $table->integer('final_rate')->nullable();
            $table->integer('minimum_wage')->nullable(); // Jurisdiction minimum wage

            // Financial calculations
            $table->integer('base_worker_pay')->nullable(); // Rate × Hours × Workers
            $table->decimal('platform_fee_rate', 5, 2)->default(35.00); // Default 35%
            $table->integer('platform_fee_amount')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(18.00); // Malta default 18%
            $table->integer('vat_amount')->nullable();
            $table->integer('total_business_cost')->nullable();
            $table->integer('escrow_amount')->nullable(); // Total + 5% buffer
            $table->decimal('contingency_buffer_rate', 5, 2)->default(5.00); // Default 5%

            // Surge pricing
            $table->decimal('surge_multiplier', 5, 2)->default(1.00)->index();
            $table->decimal('time_surge', 5, 2)->default(1.00);
            $table->decimal('demand_surge', 5, 2)->default(0.00);
            $table->decimal('event_surge', 5, 2)->default(0.00);
            $table->boolean('is_public_holiday')->default(false);
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_weekend')->default(false);

            // ===== STATUS & LIFECYCLE =====
            $table->enum('status', [
                'draft',
                'open',
                'assigned',
                'in_progress',
                'completed',
                'cancelled',
                'filled'
            ])->default('open'); // Indexed via composite indexes below

            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('normal');

            // Status flags
            $table->boolean('requires_overtime_approval')->default(false);
            $table->boolean('has_disputes')->default(false);
            $table->boolean('auto_approval_eligible')->default(true);

            // Lifecycle timestamps
            $table->timestamp('confirmed_at')->nullable()->index(); // All workers confirmed
            $table->timestamp('priority_notification_sent_at')->nullable();
            $table->timestamp('started_at')->nullable()->index(); // Actual start
            $table->timestamp('first_worker_clocked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index(); // Actual end
            $table->timestamp('last_worker_clocked_out_at')->nullable();
            $table->timestamp('verified_at')->nullable(); // Business approved hours
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('auto_approved_at')->nullable(); // 72-hour auto-approval

            // ===== CANCELLATION TRACKING =====
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancellation_type', ['business', 'worker', 'admin', 'system'])->nullable();
            $table->integer('cancellation_penalty_amount')->nullable();
            $table->integer('worker_compensation_amount')->nullable();

            // ===== WORKER CAPACITY =====
            $table->integer('required_workers')->default(1);
            $table->integer('filled_workers')->default(0);

            // ===== REQUIREMENTS =====
            $table->json('requirements')->nullable(); // skills, certifications needed
            $table->json('required_skills')->nullable(); // Array of skill IDs/names
            $table->json('required_certifications')->nullable(); // Array of cert IDs
            $table->string('dress_code')->nullable();
            $table->string('parking_info')->nullable();
            $table->string('break_info')->nullable();
            $table->text('special_instructions')->nullable();

            // ===== LIVE MARKET FEATURES =====
            $table->boolean('in_market')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamp('market_posted_at')->nullable();
            $table->boolean('instant_claim_enabled')->default(false);
            $table->integer('market_views')->default(0);
            $table->integer('market_applications')->default(0);
            $table->string('demo_business_name')->nullable();

            // ===== APPLICATION TRACKING =====
            $table->integer('application_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('first_application_at')->nullable();
            $table->timestamp('last_application_at')->nullable();

            // ===== STANDARD TIMESTAMPS =====
            $table->timestamps();
            $table->softDeletes();

            // ===== INDEXES FOR PERFORMANCE =====
            // Note: Some columns already have ->index() in their definition
            $table->index(['shift_date', 'status']);
            $table->index(['industry', 'status']);
            $table->index(['location_city', 'location_state']);
            $table->index(['is_public_holiday', 'is_weekend', 'is_night_shift']);
            $table->index(['in_market', 'status', 'shift_date'], 'idx_market_shifts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
}
