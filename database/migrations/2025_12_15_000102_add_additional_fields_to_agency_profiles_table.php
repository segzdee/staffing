<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToAgencyProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // Registration and legal information
            $table->string('business_registration_number')->nullable();

            // Contact information
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address fields
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            // Agency description
            $table->text('description')->nullable();

            // Specializations (JSON array)
            $table->json('specializations')->nullable();

            // Performance metrics
            $table->integer('total_workers')->default(0);
            $table->integer('total_placements')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);

            // Verification and completion
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_complete')->default(false);

            // Indexes
            $table->index('city');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'business_registration_number',
                'phone',
                'website',
                'address',
                'city',
                'state',
                'zip_code',
                'country',
                'description',
                'specializations',
                'total_workers',
                'total_placements',
                'rating',
                'is_verified',
                'verified_at',
                'is_complete',
            ]);
        });
    }
}
