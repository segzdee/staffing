<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-010: Dispute Resolution System
 *
 * Creates the disputes table for handling financial disagreements
 * between workers and businesses. This is the worker/business-facing
 * dispute system that integrates with the admin dispute queue.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['payment', 'hours', 'deduction', 'bonus', 'expenses', 'other']);
            $table->enum('status', [
                'open',
                'under_review',
                'awaiting_evidence',
                'mediation',
                'resolved',
                'escalated',
                'closed',
            ])->default('open');
            $table->decimal('disputed_amount', 10, 2);
            $table->text('worker_description');
            $table->text('business_response')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('evidence_worker')->nullable();
            $table->json('evidence_business')->nullable();
            $table->enum('resolution', [
                'worker_favor',
                'business_favor',
                'split',
                'withdrawn',
                'expired',
            ])->nullable();
            $table->decimal('resolution_amount', 10, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('evidence_deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['worker_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index('shift_id');
            $table->index('assigned_to');
            $table->index('type');
            $table->index('evidence_deadline');
        });

        // Create dispute_timeline table for tracking all actions
        Schema::create('dispute_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // e.g., 'opened', 'responded', 'evidence_submitted', 'escalated', 'resolved'
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional action-specific data
            $table->timestamps();

            $table->index(['dispute_id', 'created_at']);
            $table->index('action');
        });

        // Link disputes to admin_dispute_queue if it exists
        if (Schema::hasTable('admin_dispute_queue')) {
            Schema::table('disputes', function (Blueprint $table) {
                if (! Schema::hasColumn('disputes', 'admin_queue_id')) {
                    $table->unsignedBigInteger('admin_queue_id')->nullable()->after('assigned_to');
                    $table->foreign('admin_queue_id')
                        ->references('id')
                        ->on('admin_dispute_queue')
                        ->nullOnDelete();
                    $table->index('admin_queue_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispute_timeline');
        Schema::dropIfExists('disputes');
    }
};
