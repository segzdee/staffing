<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for worker endorsements from businesses.
 * WKR-010: Enhanced Profile Marketing
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_endorsements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('skill_id')->nullable()->index();
            $table->unsignedBigInteger('shift_id')->nullable()->comment('Shift that prompted endorsement');
            $table->string('endorsement_type')->default('general'); // general, skill, quality
            $table->text('endorsement_text')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('featured')->default(false)->comment('Featured on worker profile');
            $table->timestamps();

            // Relationships
            $table->foreign('worker_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('set null');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');

            // Unique constraint - one endorsement per business-worker-skill combo
            $table->unique(['worker_id', 'business_id', 'skill_id'], 'unique_endorsement');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_endorsements');
    }
};
