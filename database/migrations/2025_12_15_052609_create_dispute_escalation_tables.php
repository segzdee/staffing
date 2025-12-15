<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for ADM-002: Automated Dispute Resolution Escalation
 *
 * Creates tables for:
 * - dispute_messages: Communication thread between parties and admins
 * - dispute_escalations: Escalation history tracking
 * - payment_adjustments: Financial adjustments from dispute resolutions
 *
 * Also adds escalation-related columns to admin_dispute_queue table
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dispute Messages - Communication Thread
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dispute_id')->unsigned();
            $table->morphs('sender'); // Can be worker, business, or admin (user)
            $table->text('message');
            $table->enum('message_type', ['text', 'evidence', 'system', 'resolution'])->default('text');
            $table->boolean('is_internal')->default(false); // Internal admin notes not visible to parties
            $table->json('attachments')->nullable(); // File attachments
            $table->timestamp('read_at')->nullable();
            $table->bigInteger('read_by')->unsigned()->nullable(); // Who read it
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dispute_id')
                ->references('id')
                ->on('admin_dispute_queue')
                ->onDelete('cascade');

            $table->index(['dispute_id', 'created_at']);
            $table->index('message_type');
            $table->index('is_internal');
        });

        // Dispute Escalations - Escalation History
        Schema::create('dispute_escalations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dispute_id')->unsigned();
            $table->tinyInteger('escalation_level')->default(1); // 1=senior admin, 2=supervisor, 3=manager
            $table->string('escalation_reason');
            $table->bigInteger('escalated_from_admin_id')->unsigned()->nullable();
            $table->bigInteger('escalated_to_admin_id')->unsigned()->nullable();
            $table->float('sla_hours_at_escalation')->nullable(); // Hours remaining at escalation
            $table->text('notes')->nullable();
            $table->timestamp('escalated_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('dispute_id')
                ->references('id')
                ->on('admin_dispute_queue')
                ->onDelete('cascade');

            $table->index(['dispute_id', 'escalation_level']);
            $table->index('escalated_at');
        });

        // Payment Adjustments - Financial adjustments from resolutions
        Schema::create('payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dispute_id')->unsigned()->nullable();
            $table->bigInteger('shift_payment_id')->unsigned()->nullable();
            $table->enum('adjustment_type', [
                'worker_payout',
                'business_refund',
                'split_resolution',
                'no_adjustment',
                'bonus',
                'penalty',
                'other'
            ]);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('reason');
            $table->enum('applied_to', ['worker', 'business', 'both'])->nullable();
            $table->bigInteger('worker_id')->unsigned()->nullable();
            $table->bigInteger('business_id')->unsigned()->nullable();
            $table->bigInteger('created_by_admin_id')->unsigned()->nullable();
            $table->enum('status', ['pending', 'applied', 'reversed', 'failed'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('stripe_transfer_id')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->timestamps();

            $table->index(['dispute_id']);
            $table->index(['shift_payment_id']);
            $table->index(['status', 'created_at']);
            $table->index(['worker_id']);
            $table->index(['business_id']);
        });

        // Add escalation columns to admin_dispute_queue if they don't exist
        if (Schema::hasTable('admin_dispute_queue')) {
            Schema::table('admin_dispute_queue', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_dispute_queue', 'escalation_level')) {
                    $table->tinyInteger('escalation_level')->default(0)->after('priority');
                }
                if (!Schema::hasColumn('admin_dispute_queue', 'escalated_at')) {
                    $table->timestamp('escalated_at')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('admin_dispute_queue', 'sla_warning_sent_at')) {
                    $table->timestamp('sla_warning_sent_at')->nullable()->after('escalated_at');
                }
                if (!Schema::hasColumn('admin_dispute_queue', 'previous_assigned_admin')) {
                    $table->bigInteger('previous_assigned_admin')->unsigned()->nullable()->after('assigned_to_admin');
                }
                if (!Schema::hasColumn('admin_dispute_queue', 'internal_notes')) {
                    $table->text('internal_notes')->nullable()->after('resolution_notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_adjustments');
        Schema::dropIfExists('dispute_escalations');
        Schema::dropIfExists('dispute_messages');

        if (Schema::hasTable('admin_dispute_queue')) {
            Schema::table('admin_dispute_queue', function (Blueprint $table) {
                $columns = ['escalation_level', 'escalated_at', 'sla_warning_sent_at', 'previous_assigned_admin', 'internal_notes'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('admin_dispute_queue', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
