<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('improvement_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('users');
            $table->enum('category', ['feature', 'bug', 'ux', 'process', 'performance', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('description');
            $table->text('expected_impact')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'in_progress', 'completed', 'rejected', 'deferred'])->default('submitted');
            $table->integer('votes')->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['category', 'status']);
            $table->index(['submitted_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_suggestions');
    }
};
