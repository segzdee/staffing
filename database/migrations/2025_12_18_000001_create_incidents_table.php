<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAF-002: Incident Reporting System
     */
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number')->unique(); // INC-2024-00001
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('involves_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['injury', 'harassment', 'theft', 'safety_hazard', 'property_damage', 'verbal_abuse', 'other']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->string('location_description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('incident_time');
            $table->json('evidence_urls')->nullable(); // photos, videos
            $table->json('witness_info')->nullable(); // [{name, phone, statement}]
            $table->enum('status', ['reported', 'investigating', 'resolved', 'escalated', 'closed'])->default('reported');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('requires_insurance_claim')->default(false);
            $table->string('insurance_claim_number')->nullable();
            $table->boolean('authorities_notified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('incident_number');
            $table->index('shift_id');
            $table->index('venue_id');
            $table->index('reported_by');
            $table->index('type');
            $table->index('severity');
            $table->index('status');
            $table->index('incident_time');
            $table->index('assigned_to');
            $table->index(['status', 'severity']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
