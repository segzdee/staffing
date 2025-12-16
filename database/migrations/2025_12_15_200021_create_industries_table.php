<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-003: Industries Master List
     */
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();

            // NAICS/SIC codes for standardization
            $table->string('naics_code')->nullable();
            $table->string('sic_code')->nullable();

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->integer('level')->default(1); // 1 = top level, 2 = sub-industry
            $table->integer('sort_order')->default(0);

            // Industry-specific requirements
            $table->json('common_certifications')->nullable();
            $table->json('common_skills')->nullable();
            $table->json('compliance_requirements')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Usage statistics
            $table->integer('business_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('naics_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};
