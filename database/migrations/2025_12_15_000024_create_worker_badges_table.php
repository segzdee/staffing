<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerBadgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Badge details
            $table->string('badge_type'); // 'reliability', 'top_rated', 'fast_responder', 'multi_skilled', etc.
            $table->string('badge_name');
            $table->text('description');
            $table->string('icon')->nullable(); // Icon/image filename

            // Badge criteria
            $table->json('criteria')->nullable(); // What was achieved
            $table->integer('level')->default(1); // Bronze, Silver, Gold (1, 2, 3)

            // Metadata
            $table->timestamp('earned_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('display_on_profile')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index(['worker_id', 'badge_type']);
            $table->index(['badge_type', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_badges');
    }
}
