<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for worker direct hire conversions.
 * BIZ-010: Direct Hire & Conversion
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
        Schema::create('worker_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->decimal('total_hours_worked', 10, 2)->default(0)->comment('Total hours worked for this business');
            $table->integer('total_shifts_completed')->default(0);
            $table->integer('conversion_fee_cents')->comment('Fee in cents');
            $table->string('conversion_fee_tier')->comment('0-200h, 201-400h, 401-600h, 600+h');
            $table->string('status')->default('pending')->comment('pending, paid, completed, cancelled');
            $table->timestamp('hire_intent_submitted_at')->nullable();
            $table->text('hire_intent_notes')->nullable();
            $table->timestamp('worker_notified_at')->nullable();
            $table->boolean('worker_accepted')->default(false);
            $table->timestamp('worker_accepted_at')->nullable();
            $table->text('worker_response_notes')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->timestamp('conversion_completed_at')->nullable();
            $table->timestamp('non_solicitation_expires_at')->nullable()->comment('6 months from hire date');
            $table->boolean('is_active')->default(true)->comment('Conversion still active');
            $table->timestamps();
            $table->softDeletes();

            // Relationships
            $table->foreign('worker_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('status');
            $table->index('non_solicitation_expires_at');
            $table->index(['worker_id', 'business_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_conversions');
    }
};
