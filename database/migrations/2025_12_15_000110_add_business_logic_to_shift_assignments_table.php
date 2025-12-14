<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessLogicToShiftAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            // ===== SL-005: Clock-In Verification Protocol =====

            // Clock-in details
            $table->timestamp('actual_clock_in')->nullable()->after('check_in_time');
            $table->decimal('clock_in_lat', 10, 8)->nullable()->after('actual_clock_in');
            $table->decimal('clock_in_lng', 11, 8)->nullable()->after('clock_in_lat');
            $table->integer('clock_in_accuracy')->nullable()->after('clock_in_lng'); // GPS accuracy in meters
            $table->string('clock_in_photo_url')->nullable()->after('clock_in_accuracy');
            $table->boolean('clock_in_verified')->default(false)->after('clock_in_photo_url');
            $table->integer('clock_in_attempts')->default(0)->after('clock_in_verified');
            $table->text('clock_in_failure_reason')->nullable()->after('clock_in_attempts');

            // Lateness tracking
            $table->integer('late_minutes')->default(0)->after('clock_in_failure_reason');
            $table->boolean('was_late')->default(false)->after('late_minutes');
            $table->boolean('lateness_flagged')->default(false)->after('was_late'); // If >30 min late

            // Face recognition verification
            $table->decimal('face_match_confidence', 5, 2)->nullable()->after('lateness_flagged'); // 0-100
            $table->boolean('liveness_passed')->default(false)->after('face_match_confidence');
            $table->enum('verification_method', ['face_recognition', 'manual_override', 'supervisor_override'])->nullable()->after('liveness_passed');

            // ===== SL-006: Break Enforcement & Compliance =====

            // Break records (JSON array)
            $table->json('breaks')->nullable()->after('verification_method'); // [{start, end, duration, type, compliant}]
            $table->integer('total_break_minutes')->default(0)->after('breaks');
            $table->boolean('mandatory_break_taken')->default(false)->after('total_break_minutes');
            $table->boolean('break_compliance_met')->default(true)->after('mandatory_break_taken');
            $table->timestamp('break_required_by')->nullable()->after('break_compliance_met'); // When break must be taken
            $table->timestamp('break_warning_sent_at')->nullable()->after('break_required_by');

            // ===== SL-007: Clock-Out & Shift Completion =====

            // Clock-out details
            $table->timestamp('actual_clock_out')->nullable()->after('check_out_time');
            $table->decimal('clock_out_lat', 10, 8)->nullable()->after('actual_clock_out');
            $table->decimal('clock_out_lng', 11, 8)->nullable()->after('clock_out_lat');
            $table->string('clock_out_photo_url')->nullable()->after('clock_out_lng');
            $table->text('completion_notes')->nullable()->after('clock_out_photo_url');
            $table->string('supervisor_signature')->nullable()->after('completion_notes');

            // Hours calculations
            $table->decimal('gross_hours', 8, 2)->nullable()->after('hours_worked'); // Clock-out - Clock-in
            $table->decimal('break_deduction_hours', 8, 2)->default(0.00)->after('gross_hours');
            $table->decimal('net_hours_worked', 8, 2)->nullable()->after('break_deduction_hours');
            $table->decimal('billable_hours', 8, 2)->nullable()->after('net_hours_worked'); // MIN(net, scheduled + buffer)
            $table->decimal('overtime_hours', 8, 2)->default(0.00)->after('billable_hours');

            // Early departure tracking
            $table->boolean('early_departure')->default(false)->after('overtime_hours');
            $table->integer('early_departure_minutes')->default(0)->after('early_departure');
            $table->text('early_departure_reason')->nullable()->after('early_departure_minutes');

            // Overtime tracking
            $table->boolean('overtime_worked')->default(false)->after('early_departure_reason');
            $table->boolean('overtime_approved')->default(false)->after('overtime_worked');
            $table->foreignId('overtime_approved_by')->nullable()->after('overtime_approved')->constrained('users')->onDelete('set null');
            $table->timestamp('overtime_approved_at')->nullable()->after('overtime_approved_by');

            // Auto clock-out
            $table->boolean('auto_clocked_out')->default(false)->after('overtime_approved_at');
            $table->timestamp('auto_clock_out_time')->nullable()->after('auto_clocked_out');
            $table->text('auto_clock_out_reason')->nullable()->after('auto_clock_out_time');

            // ===== BIZ-006: Business Verification =====

            $table->decimal('business_adjusted_hours', 8, 2)->nullable()->after('auto_clock_out_reason');
            $table->text('business_adjustment_reason')->nullable()->after('business_adjusted_hours');
            $table->timestamp('business_verified_at')->nullable()->after('business_adjustment_reason');
            $table->foreignId('business_verified_by')->nullable()->after('business_verified_at')->constrained('users')->onDelete('set null');

            // Rating from business
            $table->decimal('business_rating', 3, 2)->nullable()->after('business_verified_by'); // 1-5 stars
            $table->text('business_feedback')->nullable()->after('business_rating');

            // ===== Payment Status =====

            $table->enum('payment_status', [
                'pending',
                'processing',
                'paid',
                'disputed',
                'held'
            ])->default('pending')->after('status');

            $table->decimal('worker_pay_amount', 10, 2)->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('worker_pay_amount');

            // Indexes
            $table->index('actual_clock_in');
            $table->index('actual_clock_out');
            $table->index('payment_status');
            $table->index('business_verified_at');
            $table->index('was_late');
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
            // Drop indexes
            $table->dropIndex(['shift_assignments_actual_clock_in_index']);
            $table->dropIndex(['shift_assignments_actual_clock_out_index']);
            $table->dropIndex(['shift_assignments_payment_status_index']);
            $table->dropIndex(['shift_assignments_business_verified_at_index']);
            $table->dropIndex(['shift_assignments_was_late_index']);

            // Drop columns
            $table->dropColumn([
                'paid_at',
                'worker_pay_amount',
                'payment_status',
                'business_feedback',
                'business_rating',
                'business_verified_by',
                'business_verified_at',
                'business_adjustment_reason',
                'business_adjusted_hours',
                'auto_clock_out_reason',
                'auto_clock_out_time',
                'auto_clocked_out',
                'overtime_approved_at',
                'overtime_approved_by',
                'overtime_approved',
                'overtime_worked',
                'early_departure_reason',
                'early_departure_minutes',
                'early_departure',
                'overtime_hours',
                'billable_hours',
                'net_hours_worked',
                'break_deduction_hours',
                'gross_hours',
                'supervisor_signature',
                'completion_notes',
                'clock_out_photo_url',
                'clock_out_lng',
                'clock_out_lat',
                'actual_clock_out',
                'break_warning_sent_at',
                'break_required_by',
                'break_compliance_met',
                'mandatory_break_taken',
                'total_break_minutes',
                'breaks',
                'verification_method',
                'liveness_passed',
                'face_match_confidence',
                'lateness_flagged',
                'was_late',
                'late_minutes',
                'clock_in_failure_reason',
                'clock_in_attempts',
                'clock_in_verified',
                'clock_in_photo_url',
                'clock_in_accuracy',
                'clock_in_lng',
                'clock_in_lat',
                'actual_clock_in',
            ]);
        });
    }
}
