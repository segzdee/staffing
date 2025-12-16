<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-003: Business Types Master List
     */
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or URL

            // Categorization
            $table->string('category')->nullable(); // Parent category
            $table->integer('sort_order')->default(0);

            // Features enabled for this business type
            $table->json('enabled_features')->nullable();
            /*
             * Structure:
             * {
             *   "shift_types": ["on_demand", "scheduled", "recurring"],
             *   "worker_types": ["permanent", "temporary", "contract"],
             *   "requires_certification": true,
             *   "requires_background_check": true,
             *   "requires_uniform": false
             * }
             */

            // Industry-specific settings
            $table->json('industry_settings')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_types');
    }
};
