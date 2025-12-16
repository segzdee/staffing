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
        Schema::create('time_tracking_records', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            
            // Record type and timing
            $table->enum('type', [
                'clock_in', 'clock_out', 'break_start', 'break_end'
            ])->index();
            
            $table->timestamp('verified_at')->index();
            
            // Verification details
            $table->json('verification_methods'); // Array of methods used
            $table->json('verification_results'); // Results of each verification
            $table->json('location_data'); // GPS coordinates and accuracy
            $table->decimal('face_confidence', 5, 2)->nullable(); // Face match confidence percentage
            $table->json('device_info'); // Device type, app version, etc.
            $table->string('timezone', 50); // Worker's timezone at verification
            
            // Time calculations
            $table->decimal('calculated_hours', 5, 2)->nullable(); // Total hours calculated
            $table->integer('early_departure_minutes')->default(0);
            $table->text('early_departure_reason')->nullable();
            $table->integer('overtime_minutes')->default(0);
            $table->text('manual_reason')->nullable(); // Manual entry reason
            
            // Indexes
            $table->index(['worker_id', 'verified_at']);
            $table->index(['shift_id', 'type']);
            $table->index(['assignment_id', 'type']);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_tracking_records');
    }
};