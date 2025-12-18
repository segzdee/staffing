<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-003: Labor Law Compliance - Compliance Violations Table
 *
 * Records violations of labor law rules, including actual values vs limits,
 * severity levels, and resolution status.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists (may have been created by a previous migration)
        if (Schema::hasTable('compliance_violations')) {
            return;
        }

        Schema::create('compliance_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('labor_law_rule_id'); // FK added in later migration
            $table->string('violation_code'); // e.g., WTD_WEEKLY_EXCEEDED
            $table->text('description');
            $table->json('violation_data')->nullable(); // {actual_hours: 52, limit: 48, period: 'weekly'}
            $table->enum('severity', ['info', 'warning', 'violation', 'critical'])->default('warning');
            $table->enum('status', ['detected', 'acknowledged', 'resolved', 'exempted', 'appealed'])->default('detected');
            $table->boolean('was_blocked')->default(false); // Was the action blocked?
            $table->boolean('worker_notified')->default(false);
            $table->boolean('business_notified')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('shift_id');
            $table->index('labor_law_rule_id');
            $table->index('violation_code');
            $table->index('severity');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_violations');
    }
};
