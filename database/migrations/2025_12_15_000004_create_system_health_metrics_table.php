<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemHealthMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();

            // Metric type
            $table->enum('metric_type', [
                'api_response_time',
                'shift_fill_rate',
                'payment_success_rate',
                'active_users',
                'queue_depth',
                'error_rate',
                'database_connections',
                'redis_connections',
                'disk_usage',
                'memory_usage',
                'cpu_usage'
            ]);

            // Metric values
            $table->decimal('value', 15, 4);
            $table->string('unit')->nullable(); // ms, %, count, MB, etc.

            // Context
            $table->string('environment')->default('production'); // production, staging, etc.
            $table->json('metadata')->nullable(); // additional context

            // Thresholds and alerts
            $table->boolean('is_healthy')->default(true);
            $table->decimal('threshold_warning', 15, 4)->nullable();
            $table->decimal('threshold_critical', 15, 4)->nullable();

            // Timestamp
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Indexes
            $table->index(['metric_type', 'recorded_at']);
            $table->index('is_healthy');
            $table->index('recorded_at');
        });

        // Create incidents table
        Schema::create('system_incidents', function (Blueprint $table) {
            $table->id();

            // Incident details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['open', 'investigating', 'resolved', 'closed']);

            // Related metrics
            $table->foreignId('triggered_by_metric_id')->nullable()->constrained('system_health_metrics');
            $table->string('affected_service')->nullable(); // api, database, payments, etc.

            // Timeline
            $table->timestamp('detected_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // calculated on resolution

            // Assignment
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');

            // Impact
            $table->integer('affected_users')->default(0);
            $table->integer('affected_shifts')->default(0);
            $table->integer('failed_payments')->default(0);

            // Resolution
            $table->text('resolution_notes')->nullable();
            $table->text('prevention_steps')->nullable();

            // Notifications
            $table->boolean('email_sent')->default(false);
            $table->boolean('slack_sent')->default(false);
            $table->timestamp('last_notification_sent_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('severity');
            $table->index('status');
            $table->index('detected_at');
            $table->index('assigned_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_incidents');
        Schema::dropIfExists('system_health_metrics');
    }
}
