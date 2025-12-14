<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessLogicFieldsToShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // ===== SL-001: Shift Creation & Cost Calculation =====

            // Venue/Location reference (if using venues table)
            $table->foreignId('venue_id')->nullable()->after('business_id')->constrained('business_profiles')->onDelete('cascade');

            // Role and requirements
            $table->string('role_type')->nullable()->after('title'); // e.g., "Server", "Bartender", "RN"
            $table->json('required_skills')->nullable()->after('requirements'); // Array of skill IDs/names
            $table->json('required_certifications')->nullable()->after('required_skills'); // Array of cert IDs

            // Jurisdiction compliance (hours limits)
            $table->decimal('minimum_shift_duration', 5, 2)->default(3.00)->after('duration_hours'); // Min hours (e.g., 3-4)
            $table->decimal('maximum_shift_duration', 5, 2)->default(12.00)->after('minimum_shift_duration'); // Max consecutive hours
            $table->decimal('required_rest_hours', 5, 2)->default(8.00)->after('maximum_shift_duration'); // Rest between shifts
            $table->decimal('minimum_wage', 10, 2)->nullable()->after('base_rate'); // Jurisdiction minimum wage

            // Financial calculations (SL-001)
            $table->decimal('base_worker_pay', 10, 2)->nullable()->after('final_rate'); // Rate × Hours × Workers
            $table->decimal('platform_fee_rate', 5, 2)->default(35.00)->after('base_worker_pay'); // Default 35%
            $table->decimal('platform_fee_amount', 10, 2)->nullable()->after('platform_fee_rate');
            $table->decimal('vat_rate', 5, 2)->default(18.00)->after('platform_fee_amount'); // Malta default 18%
            $table->decimal('vat_amount', 10, 2)->nullable()->after('vat_rate');
            $table->decimal('total_business_cost', 10, 2)->nullable()->after('vat_amount');
            $table->decimal('escrow_amount', 10, 2)->nullable()->after('total_business_cost'); // Total + 5% buffer
            $table->decimal('contingency_buffer_rate', 5, 2)->default(5.00)->after('escrow_amount'); // Default 5%

            // SL-008: Surge pricing
            $table->decimal('surge_multiplier', 5, 2)->default(1.00)->after('urgency_level');
            $table->decimal('time_surge', 5, 2)->default(1.00)->after('surge_multiplier');
            $table->decimal('demand_surge', 5, 2)->default(0.00)->after('time_surge');
            $table->decimal('event_surge', 5, 2)->default(0.00)->after('demand_surge');
            $table->boolean('is_public_holiday')->default(false)->after('event_surge');
            $table->boolean('is_night_shift')->default(false)->after('is_public_holiday');
            $table->boolean('is_weekend')->default(false)->after('is_night_shift');

            // SL-005: Clock-in verification
            $table->integer('geofence_radius')->default(100)->after('location_lng'); // meters
            $table->integer('early_clockin_minutes')->default(15)->after('geofence_radius'); // Allow 15 min early
            $table->integer('late_grace_minutes')->default(10)->after('early_clockin_minutes'); // 10 min grace

            // ===== Lifecycle Timestamps =====

            // SL-004: Booking confirmation
            $table->timestamp('confirmed_at')->nullable()->after('status'); // All workers confirmed
            $table->timestamp('priority_notification_sent_at')->nullable()->after('confirmed_at'); // Gold/Platinum notified

            // SL-005: Clock-in
            $table->timestamp('started_at')->nullable()->after('priority_notification_sent_at'); // Actual start
            $table->timestamp('first_worker_clocked_in_at')->nullable()->after('started_at');

            // SL-007: Clock-out & completion
            $table->timestamp('completed_at')->nullable()->after('first_worker_clocked_in_at'); // Actual end
            $table->timestamp('last_worker_clocked_out_at')->nullable()->after('completed_at');

            // BIZ-006: Business verification
            $table->timestamp('verified_at')->nullable()->after('last_worker_clocked_out_at'); // Business approved hours
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->onDelete('set null');
            $table->timestamp('auto_approved_at')->nullable()->after('verified_by'); // 72-hour auto-approval

            // ===== Cancellation Tracking (SL-009, SL-010) =====

            $table->foreignId('cancelled_by')->nullable()->after('auto_approved_at')->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->enum('cancellation_type', ['business', 'worker', 'admin', 'system'])->nullable()->after('cancellation_reason');
            $table->decimal('cancellation_penalty_amount', 10, 2)->nullable()->after('cancellation_type');
            $table->decimal('worker_compensation_amount', 10, 2)->nullable()->after('cancellation_penalty_amount');

            // ===== Additional Status Flags =====

            $table->boolean('requires_overtime_approval')->default(false)->after('status');
            $table->boolean('has_disputes')->default(false)->after('requires_overtime_approval');
            $table->boolean('auto_approval_eligible')->default(true)->after('has_disputes');

            // ===== Matching & Application Tracking =====

            $table->integer('application_count')->default(0)->after('filled_workers');
            $table->integer('view_count')->default(0)->after('application_count');
            $table->timestamp('first_application_at')->nullable()->after('view_count');
            $table->timestamp('last_application_at')->nullable()->after('first_application_at');

            // Indexes for performance
            $table->index('venue_id');
            $table->index('role_type');
            $table->index('surge_multiplier');
            $table->index('confirmed_at');
            $table->index('started_at');
            $table->index('completed_at');
            $table->index('cancelled_at');
            $table->index(['is_public_holiday', 'is_weekend', 'is_night_shift']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['shifts_venue_id_index']);
            $table->dropIndex(['shifts_role_type_index']);
            $table->dropIndex(['shifts_surge_multiplier_index']);
            $table->dropIndex(['shifts_confirmed_at_index']);
            $table->dropIndex(['shifts_started_at_index']);
            $table->dropIndex(['shifts_completed_at_index']);
            $table->dropIndex(['shifts_cancelled_at_index']);
            $table->dropIndex(['shifts_is_public_holiday_is_weekend_is_night_shift_index']);

            // Drop columns in reverse order
            $table->dropColumn([
                'last_application_at',
                'first_application_at',
                'view_count',
                'application_count',
                'auto_approval_eligible',
                'has_disputes',
                'requires_overtime_approval',
                'worker_compensation_amount',
                'cancellation_penalty_amount',
                'cancellation_type',
                'cancellation_reason',
                'cancelled_at',
                'cancelled_by',
                'auto_approved_at',
                'verified_by',
                'verified_at',
                'last_worker_clocked_out_at',
                'completed_at',
                'first_worker_clocked_in_at',
                'started_at',
                'priority_notification_sent_at',
                'confirmed_at',
                'late_grace_minutes',
                'early_clockin_minutes',
                'geofence_radius',
                'is_weekend',
                'is_night_shift',
                'is_public_holiday',
                'event_surge',
                'demand_surge',
                'time_surge',
                'surge_multiplier',
                'contingency_buffer_rate',
                'escrow_amount',
                'total_business_cost',
                'vat_amount',
                'vat_rate',
                'platform_fee_amount',
                'platform_fee_rate',
                'base_worker_pay',
                'minimum_wage',
                'required_rest_hours',
                'maximum_shift_duration',
                'minimum_shift_duration',
                'required_certifications',
                'required_skills',
                'role_type',
                'venue_id',
            ]);
        });
    }
}
