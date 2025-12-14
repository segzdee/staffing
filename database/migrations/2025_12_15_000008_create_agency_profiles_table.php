<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgencyProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_profiles', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Agency information
            $table->string('agency_name');
            $table->string('license_number')->nullable();
            $table->boolean('license_verified')->default(false);
            $table->enum('business_model', [
                'staffing_agency',
                'temp_agency',
                'consulting'
            ])->default('staffing_agency');

            // Commission structure
            $table->decimal('commission_rate', 5, 2)->default(10.00); // percentage earned per shift

            // Managed workers (cached for performance)
            $table->json('managed_workers')->nullable(); // array of worker IDs

            // Performance metrics
            $table->integer('total_shifts_managed')->default(0);
            $table->integer('total_workers_managed')->default(0);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('license_verified');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agency_profiles');
    }
}
