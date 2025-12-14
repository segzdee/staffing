<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Registration and legal information
            $table->string('business_registration_number')->nullable();

            // Contact information (phone already exists as business_phone)
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address fields (already exist with business_ prefix, add without prefix for compatibility)
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            // Business description
            $table->text('description')->nullable();

            // Business size
            $table->integer('total_locations')->default(1);
            $table->string('employee_count')->nullable();

            // Performance metrics (rating as alias for rating_average, total_shifts_posted already exists)
            $table->decimal('rating', 3, 2)->default(0.00);

            // Verification and completion (is_verified already exists)
            $table->boolean('has_payment_method')->default(false);
            $table->boolean('is_complete')->default(false);

            // Indexes
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
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
                'total_locations',
                'employee_count',
                'rating',
                'has_payment_method',
                'is_complete',
            ]);
        });
    }
}
