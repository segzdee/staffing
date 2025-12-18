<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SL-012: Multi-Position Shifts
 *
 * Creates the shift_position_assignments table to track which workers are assigned
 * to which positions within a multi-position shift. This links shift_positions
 * with shift_assignments and users.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_position_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_position_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure a worker can only be assigned to a position once
            $table->unique(['shift_position_id', 'user_id'], 'position_user_unique');

            // Indexes for common queries
            $table->index('shift_position_id');
            $table->index('shift_assignment_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_position_assignments');
    }
};
