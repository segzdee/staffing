<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-003: Labor Law Compliance - Worker Exemptions Table
 *
 * Stores worker opt-outs and exemptions from specific labor law rules,
 * such as the EU Working Time Directive opt-out.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('labor_law_rule_id')->constrained()->onDelete('cascade');
            $table->text('reason'); // Worker's reason for opting out
            $table->string('document_url')->nullable(); // URL to signed opt-out form
            $table->string('document_type')->nullable(); // pdf, signed_form, etc.
            $table->date('valid_from');
            $table->date('valid_until')->nullable(); // Null = indefinite
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'revoked'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('worker_acknowledged')->default(false); // Worker acknowledged consequences
            $table->timestamp('worker_acknowledged_at')->nullable();
            $table->string('ip_address')->nullable(); // IP when signed
            $table->string('user_agent')->nullable(); // Browser info when signed
            $table->timestamps();

            // Unique constraint - one exemption per worker per rule
            $table->unique(['user_id', 'labor_law_rule_id'], 'worker_rule_exemption_unique');

            // Indexes for common queries
            $table->index('user_id');
            $table->index('labor_law_rule_id');
            $table->index('status');
            $table->index('valid_from');
            $table->index('valid_until');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_exemptions');
    }
};
