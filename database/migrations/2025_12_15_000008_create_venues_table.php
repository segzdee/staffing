<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVenuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();

            // Business relationship
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');

            // Venue information
            $table->string('name');
            $table->string('code')->nullable(); // unique venue identifier
            $table->text('description')->nullable();

            // Location
            $table->string('address');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country')->default('US');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();

            // Budget tracking per venue
            $table->integer('monthly_budget')->default(0); // in cents
            $table->integer('current_month_spend')->default(0); // in cents
            $table->integer('ytd_spend')->default(0); // in cents

            // Performance metrics
            $table->integer('total_shifts')->default(0);
            $table->integer('completed_shifts')->default(0);
            $table->integer('cancelled_shifts')->default(0);
            $table->decimal('fill_rate', 5, 2)->default(0.00);
            $table->decimal('average_rating', 3, 2)->default(0.00);

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('business_profile_id');
            $table->index('code');
            $table->index('is_active');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venues');
    }
}
