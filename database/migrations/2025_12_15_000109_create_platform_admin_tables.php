<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformAdminTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ADM-001: Verification Queue
        Schema::create('verification_queue', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable'); // Can be worker, business, or agency
            $table->enum('verification_type', ['identity', 'business_license', 'background_check', 'certification']);
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected'])->default('pending');
            $table->json('documents')->nullable();
            $table->text('admin_notes')->nullable();
            $table->bigInteger('reviewed_by')->unsigned()->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index('verification_type');
        });

        // ADM-002: Dispute Resolution Queue
        Schema::create('admin_dispute_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shift_payment_id')->unsigned();
            $table->string('filed_by'); // 'worker' or 'business'
            $table->bigInteger('worker_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->enum('status', ['pending', 'investigating', 'evidence_review', 'resolved', 'closed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('dispute_reason');
            $table->json('evidence_urls')->nullable();
            $table->bigInteger('assigned_to_admin')->unsigned()->nullable();
            $table->text('resolution_notes')->nullable();
            $table->enum('resolution_outcome', ['worker_favor', 'business_favor', 'split', 'no_fault'])->nullable();
            $table->decimal('adjustment_amount', 10, 2)->nullable();
            $table->timestamp('filed_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'filed_at']);
            $table->index('assigned_to_admin');
            $table->index('priority');
        });

        // ADM-003: Platform Analytics (aggregated daily)
        Schema::create('platform_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_shifts_posted')->default(0);
            $table->integer('total_shifts_filled')->default(0);
            $table->integer('total_shifts_completed')->default(0);
            $table->decimal('platform_revenue', 12, 2)->default(0.00);
            $table->decimal('total_gmv', 12, 2)->default(0.00); // Gross Merchandise Value
            $table->integer('new_workers')->default(0);
            $table->integer('new_businesses')->default(0);
            $table->integer('new_agencies')->default(0);
            $table->integer('active_workers')->default(0);
            $table->integer('active_businesses')->default(0);
            $table->decimal('average_shift_value', 10, 2)->default(0.00);
            $table->decimal('fill_rate', 5, 2)->default(0.00);
            $table->integer('total_disputes')->default(0);
            $table->integer('disputes_resolved')->default(0);
            $table->timestamps();

            $table->index('date');
        });

        // ADM-004: Compliance Alerts
        Schema::create('compliance_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('alert_type', ['payment_failure', 'high_dispute_rate', 'suspicious_activity', 'tax_compliance', 'license_expiry', 'background_check_expiry']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->morphs('alertable'); // Can be user, business, worker, etc.
            $table->text('alert_message');
            $table->json('alert_data')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->bigInteger('acknowledged_by')->unsigned()->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['alert_type', 'severity']);
            $table->index(['acknowledged', 'resolved']);
        });

        // ADM-005: System Configuration
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('category'); // 'payment', 'fees', 'limits', 'features', etc.
            $table->text('description')->nullable();
            $table->enum('data_type', ['string', 'integer', 'decimal', 'boolean', 'json'])->default('string');
            $table->boolean('is_public')->default(false); // Can be exposed to API
            $table->bigInteger('last_modified_by')->unsigned()->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('verification_queue');
        Schema::dropIfExists('admin_dispute_queue');
        Schema::dropIfExists('platform_analytics');
        Schema::dropIfExists('compliance_alerts');
        Schema::dropIfExists('system_settings');
    }
}
