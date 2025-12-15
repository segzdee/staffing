<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReliabilityScoreHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reliability_score_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Overall score (0-100)
            $table->decimal('score', 5, 2);

            // Component scores (0-100 each)
            $table->decimal('attendance_score', 5, 2)->comment('40% weight - No-shows and completions');
            $table->decimal('cancellation_score', 5, 2)->comment('25% weight - Cancellation timing');
            $table->decimal('punctuality_score', 5, 2)->comment('20% weight - Clock-in timing');
            $table->decimal('responsiveness_score', 5, 2)->comment('15% weight - Confirmation speed');

            // Metrics used in calculation
            $table->json('metrics')->comment('Raw data used for calculation');

            // Period this score covers
            $table->date('period_start');
            $table->date('period_end');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reliability_score_history');
    }
}
