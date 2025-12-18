<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-004: Venue Safety Flags Migration
 *
 * Creates the venue_safety_flags table for workers to report
 * safety concerns at venues with severity levels and tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_safety_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->enum('flag_type', [
                'harassment',
                'unsafe_conditions',
                'poor_lighting',
                'no_breaks',
                'unpaid_overtime',
                'inadequate_training',
                'equipment_failure',
                'other',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->json('evidence_urls')->nullable();
            $table->enum('status', [
                'reported',
                'investigating',
                'resolved',
                'dismissed',
            ])->default('reported');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('business_notified')->default(false);
            $table->timestamp('business_response_due')->nullable();
            $table->text('business_response')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['venue_id', 'status']);
            $table->index(['venue_id', 'severity']);
            $table->index(['venue_id', 'flag_type']);
            $table->index(['venue_id', 'created_at']);
            $table->index('reported_by');
            $table->index('assigned_to');
            $table->index(['status', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_safety_flags');
    }
};
