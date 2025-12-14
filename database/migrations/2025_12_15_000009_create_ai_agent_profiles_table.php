<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAgentProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_agent_profiles', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Agent information
            $table->string('agent_name');
            $table->text('api_key'); // hashed API key for authentication
            $table->json('capabilities')->nullable(); // what actions agent can perform
            $table->json('rate_limits')->nullable(); // API rate limiting config

            // Ownership
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null'); // business/agency that owns the agent

            // Status and activity
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();

            // Performance metrics
            $table->integer('total_api_calls')->default(0);
            $table->integer('total_shifts_created')->default(0);
            $table->integer('total_workers_matched')->default(0);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('owner_id');
            $table->index('is_active');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_agent_profiles');
    }
}
