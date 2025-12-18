<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-003: Labor Law Compliance - Labor Law Rules Table
 *
 * Stores labor law rules for different jurisdictions (EU, UK, US-CA, AU, etc.)
 * including working time directives, rest periods, breaks, overtime, and age restrictions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('labor_law_rules', function (Blueprint $table) {
            $table->id();
            $table->string('jurisdiction'); // EU, UK, US-CA, AU, etc.
            $table->string('rule_code')->unique(); // WTD_WEEKLY_MAX, REST_PERIOD_DAILY, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', [
                'working_time',
                'rest_period',
                'break',
                'overtime',
                'age_restriction',
                'wage',
                'night_work',
            ]);
            $table->json('parameters'); // {max_hours: 48, period: 'weekly', etc.}
            $table->enum('enforcement', ['hard_block', 'soft_warning', 'log_only'])->default('soft_warning');
            $table->boolean('is_active')->default(true);
            $table->boolean('allows_opt_out')->default(false); // Can workers opt out of this rule?
            $table->text('opt_out_requirements')->nullable(); // Requirements for opting out
            $table->string('legal_reference')->nullable(); // e.g., "Working Time Directive 2003/88/EC"
            $table->date('effective_from')->nullable(); // When this rule became effective
            $table->date('effective_until')->nullable(); // When this rule expires (null = ongoing)
            $table->timestamps();

            // Indexes for common queries
            $table->index('jurisdiction');
            $table->index('rule_type');
            $table->index('is_active');
            $table->index(['jurisdiction', 'rule_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_law_rules');
    }
};
