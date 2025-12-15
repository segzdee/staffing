<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Business information
            $table->string('business_name');
            $table->enum('business_type', ['independent', 'small_business', 'enterprise'])->default('small_business');
            $table->enum('industry', [
                'hospitality',
                'healthcare',
                'retail',
                'events',
                'warehouse',
                'professional'
            ])->nullable();

            // Business location
            $table->string('business_address')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_country')->nullable();
            $table->string('business_phone')->nullable();

            // Tax information (encrypted)
            $table->text('ein_tax_id')->nullable(); // encrypted EIN

            // Performance metrics
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->integer('total_shifts_posted')->default(0);
            $table->integer('total_shifts_completed')->default(0);
            $table->integer('total_shifts_cancelled')->default(0);
            $table->decimal('fill_rate', 3, 2)->default(0.00); // percentage of shifts filled

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('business_type');
            $table->index('industry');
            $table->index('rating_average');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_profiles');
    }
}
