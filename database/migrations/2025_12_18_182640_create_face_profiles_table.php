<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SL-005: Face profiles for clock-in/out verification.
     * Stores enrolled face data for facial recognition verification.
     */
    public function up(): void
    {
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('face_id')->nullable()->comment('Provider face ID from AWS/Azure');
            $table->string('provider')->default('aws')->comment('aws, azure, faceplusplus');
            $table->json('face_attributes')->nullable()->comment('Age, gender confidence, etc.');
            $table->integer('photo_count')->default(0)->comment('Number of enrolled photos');
            $table->boolean('is_enrolled')->default(false);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->integer('verification_count')->default(0);
            $table->decimal('avg_confidence', 5, 2)->nullable()->comment('Average confidence score');
            $table->string('enrollment_image_url')->nullable()->comment('Primary enrolled face image');
            $table->json('additional_images')->nullable()->comment('Array of additional enrolled images');
            $table->string('collection_id')->nullable()->comment('AWS Rekognition collection ID');
            $table->enum('status', ['pending', 'active', 'suspended', 'deleted'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['provider', 'is_enrolled']);
            $table->index('face_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_profiles');
    }
};
