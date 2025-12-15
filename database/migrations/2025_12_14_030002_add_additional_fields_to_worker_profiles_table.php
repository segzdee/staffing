<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToWorkerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Contact information
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();

            // Address fields
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Skills and certifications (JSON arrays)
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();

            // Hourly rate (single value for simplicity)
            $table->decimal('hourly_rate', 10, 2)->nullable();

            // Rating (for backward compatibility)
            $table->decimal('rating', 3, 2)->default(0.00);

            // Completed shifts count
            $table->integer('completed_shifts')->default(0);

            // Availability flags
            $table->boolean('is_available')->default(true);
            $table->boolean('is_complete')->default(false);

            // Location coordinates for shift matching
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->integer('preferred_radius')->default(25);

            // Indexes
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'address',
                'city',
                'state',
                'zip_code',
                'country',
                'emergency_contact_name',
                'emergency_contact_phone',
                'skills',
                'certifications',
                'hourly_rate',
                'rating',
                'completed_shifts',
                'is_available',
                'is_complete',
                'location_lat',
                'location_lng',
                'preferred_radius',
            ]);
        });
    }
}
