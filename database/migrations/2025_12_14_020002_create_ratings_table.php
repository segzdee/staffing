<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade'); // who gave the rating
            $table->foreignId('rated_id')->constrained('users')->onDelete('cascade'); // who received the rating
            $table->enum('rater_type', ['worker', 'business']);

            $table->integer('rating')->unsigned(); // 1-5
            $table->text('review_text')->nullable();
            $table->json('categories')->nullable(); // {professionalism: 5, punctuality: 4, quality: 5}

            $table->timestamps();

            // Indexes
            $table->index('shift_assignment_id');
            $table->index('rater_id');
            $table->index('rated_id');
            $table->index(['rated_id', 'rating']);
            $table->index('created_at');

            // Unique constraint: can only rate once per assignment
            $table->unique(['shift_assignment_id', 'rater_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
