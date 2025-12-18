<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SL-005: Face verification logs for clock-in/out verification.
     * Stores all face verification attempts for audit and analytics.
     */
    public function up(): void
    {
        Schema::create('face_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('shift_assignment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action', ['enroll', 'verify_clock_in', 'verify_clock_out', 're_verify', 'manual_override']);
            $table->string('provider')->comment('aws, azure, faceplusplus, manual');
            $table->decimal('confidence_score', 5, 2)->nullable()->comment('0-100 confidence percentage');
            $table->boolean('liveness_passed')->nullable();
            $table->boolean('match_result')->nullable();
            $table->string('source_image_url')->nullable()->comment('Image used for verification');
            $table->json('provider_response')->nullable()->comment('Raw response from provider');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('processing_time_ms')->nullable()->comment('Time taken for verification');
            $table->json('face_attributes')->nullable()->comment('Detected face attributes');
            $table->boolean('fallback_used')->default(false)->comment('Manual verification fallback');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index(['shift_id', 'action']);
            $table->index('created_at');
            $table->index(['match_result', 'liveness_passed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_verification_logs');
    }
};
