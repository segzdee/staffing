<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * QUA-002: Quality Audits - Mystery shopper program
     */
    public function up(): void
    {
        Schema::create('mystery_shoppers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('audits_completed')->default(0);
            $table->decimal('avg_quality_score', 5, 2)->nullable();
            $table->json('specializations')->nullable(); // venue types they can audit
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('audits_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mystery_shoppers');
    }
};
