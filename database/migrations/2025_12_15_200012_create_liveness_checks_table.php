<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-004: Identity Verification - Liveness Check Table
 *
 * Stores liveness check records for identity verification.
 * Ensures the person submitting documents is physically present.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('liveness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identity_verification_id')
                ->constrained('identity_verifications')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Provider Information
            $table->string('provider')->default('onfido'); // onfido, jumio, aws_rekognition
            $table->string('provider_check_id')->nullable();
            $table->string('provider_report_id')->nullable();

            // Check Type
            $table->enum('check_type', [
                'passive',      // Passive liveness (single image)
                'active',       // Active liveness (follow prompts)
                'video',        // Video-based liveness
                'motion'        // Motion-based challenges
            ])->default('active');

            // Status
            $table->enum('status', [
                'pending',
                'in_progress',
                'processing',
                'passed',
                'failed',
                'expired',
                'cancelled'
            ])->default('pending');

            // Challenge Configuration
            $table->json('challenges')->nullable(); // Required challenges/prompts
            $table->json('challenge_responses')->nullable(); // User's responses
            $table->integer('challenges_completed')->default(0);
            $table->integer('challenges_required')->default(3);

            // Results
            $table->string('result')->nullable(); // clear, consider, rejected
            $table->decimal('liveness_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->decimal('face_quality_score', 5, 4)->nullable();
            $table->json('result_breakdown')->nullable(); // Detailed result breakdown

            // Face Matching (with ID document)
            $table->boolean('face_match_attempted')->default(false);
            $table->decimal('face_similarity_score', 5, 4)->nullable();
            $table->string('face_match_result')->nullable(); // match, no_match, unable

            // Anti-Spoofing Results
            $table->json('spoofing_checks')->nullable();
            $table->boolean('is_real_person')->nullable();
            $table->boolean('photo_detected')->default(false); // Photo of photo detected
            $table->boolean('screen_detected')->default(false); // Screen replay detected
            $table->boolean('mask_detected')->default(false); // Mask detected
            $table->boolean('deepfake_detected')->default(false);

            // Media Storage
            $table->string('video_storage_path')->nullable();
            $table->json('frame_storage_paths')->nullable(); // Captured frames
            $table->string('selfie_storage_path')->nullable();
            $table->string('storage_encryption_key')->nullable();

            // Session Information
            $table->string('session_token')->nullable();
            $table->timestamp('session_started_at')->nullable();
            $table->timestamp('session_completed_at')->nullable();
            $table->integer('session_duration_seconds')->nullable();

            // Device & Environment
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('device_os')->nullable();
            $table->string('browser')->nullable();
            $table->string('camera_used')->nullable(); // front, back
            $table->json('environment_checks')->nullable(); // Lighting, etc.

            // Retry Information
            $table->integer('attempt_number')->default(1);
            $table->string('failure_reason')->nullable();
            $table->json('failure_details')->nullable();

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->json('geolocation')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('identity_verification_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('provider_check_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liveness_checks');
    }
};
