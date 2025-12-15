<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alert configurations table - per-metric alerting rules
        Schema::create('alert_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();

            // Thresholds
            $table->decimal('warning_threshold', 12, 4)->nullable();
            $table->decimal('critical_threshold', 12, 4)->nullable();
            $table->enum('comparison', ['greater_than', 'less_than', 'equals'])->default('greater_than');

            // Severity and routing
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('slack_channel')->nullable();
            $table->string('pagerduty_routing_key')->nullable();

            // Enable/disable controls
            $table->boolean('enabled')->default(true);
            $table->boolean('slack_enabled')->default(true);
            $table->boolean('pagerduty_enabled')->default(false);
            $table->boolean('email_enabled')->default(true);

            // Alert suppression settings
            $table->integer('cooldown_minutes')->default(60); // Don't resend within this period
            $table->integer('escalation_delay_minutes')->default(15); // Wait before escalating
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->default('22:00:00');
            $table->time('quiet_hours_end')->default('08:00:00');

            // Metadata
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->index('enabled');
            $table->index(['metric_name', 'enabled']);
        });

        // Alert history table - track all sent alerts
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id')->nullable();
            $table->foreignId('alert_configuration_id')->nullable()->constrained('alert_configurations')->nullOnDelete();

            // Alert details
            $table->string('metric_name');
            $table->string('alert_type'); // slack, pagerduty, email
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->string('title');
            $table->text('message');

            // Delivery details
            $table->string('channel')->nullable(); // Slack channel, email address, etc.
            $table->enum('status', ['pending', 'sent', 'failed', 'suppressed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);

            // External references
            $table->string('external_id')->nullable(); // PagerDuty incident ID, Slack message ts
            $table->string('dedup_key')->nullable(); // For grouping similar alerts

            // Acknowledgment tracking
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();

            // Resolution tracking
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->integer('resolution_duration_minutes')->nullable();

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('incident_id');
            $table->index('metric_name');
            $table->index('alert_type');
            $table->index('status');
            $table->index('dedup_key');
            $table->index(['created_at', 'status']);

            // Add foreign key to system_incidents if it exists
            if (Schema::hasTable('system_incidents')) {
                $table->foreign('incident_id')
                    ->references('id')
                    ->on('system_incidents')
                    ->nullOnDelete();
            }
        });

        // Alert integrations table - store integration settings
        Schema::create('alert_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // slack, pagerduty, email
            $table->string('display_name');
            $table->boolean('enabled')->default(false);

            // Configuration
            $table->json('config')->nullable(); // Encrypted webhook URLs, API keys, etc.

            // Status
            $table->boolean('verified')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('total_alerts_sent')->default(0);
            $table->integer('failed_alerts')->default(0);

            $table->timestamps();
        });

        // Alert digest table - for grouped alert summaries
        Schema::create('alert_digests', function (Blueprint $table) {
            $table->id();
            $table->string('digest_key')->unique();
            $table->integer('alert_count')->default(0);
            $table->json('alert_ids'); // Array of alert_history ids
            $table->json('metrics_summary'); // Aggregated metrics info
            $table->enum('status', ['collecting', 'sent', 'cancelled'])->default('collecting');
            $table->timestamp('period_start');
            $table->timestamp('period_end')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('digest_key');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_digests');
        Schema::dropIfExists('alert_integrations');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_configurations');
    }
};
