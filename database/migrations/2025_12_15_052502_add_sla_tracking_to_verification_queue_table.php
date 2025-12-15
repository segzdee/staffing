<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ADM-001: Adds SLA tracking columns to the verification queue
     * for monitoring verification processing times and compliance.
     */
    public function up(): void
    {
        Schema::table('verification_queue', function (Blueprint $table) {
            // SLA deadline calculated based on verification type
            $table->timestamp('sla_deadline')->nullable()->after('reviewed_at');

            // SLA status: on_track, at_risk (80%+), breached (exceeded)
            $table->enum('sla_status', ['on_track', 'at_risk', 'breached'])
                ->default('on_track')
                ->after('sla_deadline');

            // Track when SLA warning was sent (to avoid duplicate notifications)
            $table->timestamp('sla_warning_sent_at')->nullable()->after('sla_status');

            // Track when SLA breach notification was sent
            $table->timestamp('sla_breach_notified_at')->nullable()->after('sla_warning_sent_at');

            // Priority for sorting (calculated from SLA remaining time)
            $table->integer('priority_score')->default(0)->after('sla_breach_notified_at');

            // Add indexes for efficient querying
            $table->index('sla_deadline');
            $table->index('sla_status');
            $table->index(['status', 'sla_status', 'sla_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_queue', function (Blueprint $table) {
            $table->dropIndex(['verification_queue_sla_deadline_index']);
            $table->dropIndex(['verification_queue_sla_status_index']);
            $table->dropIndex(['verification_queue_status_sla_status_sla_deadline_index']);

            $table->dropColumn([
                'sla_deadline',
                'sla_status',
                'sla_warning_sent_at',
                'sla_breach_notified_at',
                'priority_score',
            ]);
        });
    }
};
