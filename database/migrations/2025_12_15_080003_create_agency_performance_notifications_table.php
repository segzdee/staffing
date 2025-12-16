<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-005: Agency Performance Notification System
 *
 * Creates the agency_performance_notifications table to track all performance-related
 * notifications sent to agencies, including acknowledgment status and escalation tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_performance_notifications', function (Blueprint $table) {
            $table->id();

            // Agency reference
            $table->foreignId('agency_id')->constrained('users')->onDelete('cascade');

            // Scorecard reference (nullable for system-generated notifications)
            $table->foreignId('scorecard_id')->nullable()->constrained('agency_performance_scorecards')->onDelete('set null');

            // Notification classification
            $table->enum('notification_type', [
                'yellow_warning',       // First warning when status becomes yellow
                'red_alert',            // Critical alert when status becomes red
                'fee_increase',         // Fee increased due to poor performance
                'suspension',           // Account suspended for consecutive red
                'improvement',          // Status improved from red to yellow/green
                'escalation',           // Escalation due to no acknowledgment
                'admin_review',         // Sent to admin for review
            ]);

            // Severity level for prioritization
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');

            // Performance status at time of notification
            $table->enum('status_at_notification', ['green', 'yellow', 'red'])->nullable();
            $table->enum('previous_status', ['green', 'yellow', 'red'])->nullable();

            // Notification content
            $table->string('title');
            $table->text('message');
            $table->json('metrics_snapshot')->nullable(); // Current metrics at notification time
            $table->json('action_items')->nullable();     // Specific actions agency should take
            $table->date('improvement_deadline')->nullable();

            // Tracking consecutive issues
            $table->integer('consecutive_yellow_weeks')->default(0);
            $table->integer('consecutive_red_weeks')->default(0);

            // Fee increase tracking (when applicable)
            $table->decimal('previous_commission_rate', 5, 2)->nullable();
            $table->decimal('new_commission_rate', 5, 2)->nullable();

            // Delivery tracking
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_via')->default('email'); // email, database, sms
            $table->boolean('email_delivered')->default(false);
            $table->timestamp('email_delivered_at')->nullable();

            // Acknowledgment tracking
            $table->boolean('requires_acknowledgment')->default(true);
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('acknowledgment_notes')->nullable();

            // Escalation tracking
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('escalation_reason')->nullable();
            $table->integer('escalation_level')->default(0);
            $table->timestamp('escalation_due_at')->nullable(); // When escalation should happen if not acknowledged

            // Admin review (for suspension/critical cases)
            $table->boolean('admin_reviewed')->default(false);
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->enum('admin_decision', ['pending', 'uphold', 'reduce', 'dismiss'])->nullable();

            // Appeal handling
            $table->boolean('appealed')->default(false);
            $table->timestamp('appealed_at')->nullable();
            $table->text('appeal_reason')->nullable();
            $table->enum('appeal_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->text('appeal_response')->nullable();
            $table->timestamp('appeal_resolved_at')->nullable();

            // Follow-up tracking
            $table->integer('follow_up_count')->default(0);
            $table->timestamp('last_follow_up_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying (using short names to avoid MySQL 64-char limit)
            $table->index(['agency_id', 'notification_type'], 'apn_agency_type_idx');
            $table->index(['agency_id', 'created_at'], 'apn_agency_created_idx');
            $table->index(['notification_type', 'acknowledged'], 'apn_type_ack_idx');
            $table->index(['escalated', 'escalation_due_at'], 'apn_escalation_idx');
            $table->index(['requires_acknowledgment', 'acknowledged', 'escalated'], 'apn_ack_status_idx');
            $table->index('severity', 'apn_severity_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_performance_notifications');
    }
};
