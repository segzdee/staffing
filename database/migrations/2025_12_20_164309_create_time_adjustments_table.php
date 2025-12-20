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
        Schema::create('time_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('adjusted_by')->constrained('users')->onDelete('cascade');
            $table->string('adjustment_type'); // hours, clock_in, clock_out, break
            $table->decimal('original_value', 8, 2)->nullable();
            $table->decimal('new_value', 8, 2)->nullable();
            $table->timestamp('original_timestamp')->nullable();
            $table->timestamp('new_timestamp')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();

            $table->index(['shift_assignment_id', 'adjustment_type']);
            $table->index(['adjusted_by', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_adjustments');
    }
};
