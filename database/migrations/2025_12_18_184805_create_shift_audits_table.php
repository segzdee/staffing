<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * QUA-002: Quality Audits - Shift audit and spot-check system
     */
    public function up(): void
    {
        Schema::create('shift_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number')->unique(); // AUD-2024-00001
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_assignment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('auditor_id')->nullable()->constrained('users');
            $table->enum('audit_type', ['random', 'complaint', 'scheduled', 'mystery_shopper']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->json('checklist_items')->nullable(); // [{item, passed, notes}]
            $table->integer('overall_score')->nullable(); // 0-100
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('evidence_urls')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['status', 'audit_type']);
            $table->index('scheduled_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_audits');
    }
};
