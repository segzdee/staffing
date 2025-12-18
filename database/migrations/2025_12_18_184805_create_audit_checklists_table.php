<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * QUA-002: Quality Audits - Audit checklist templates
     */
    public function up(): void
    {
        Schema::create('audit_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // punctuality, appearance, performance, compliance
            $table->json('items'); // [{id, question, weight, required}]
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_checklists');
    }
};
