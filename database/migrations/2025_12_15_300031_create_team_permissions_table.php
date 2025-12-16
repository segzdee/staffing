<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-008: Team Permissions Configuration
 *
 * Stores the permission matrix and role configurations.
 * Allows dynamic permission management without code changes.
 */
class CreateTeamPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_permissions', function (Blueprint $table) {
            $table->id();

            // Permission identifier
            $table->string('slug')->unique(); // manage_team, manage_venues, post_shifts, etc.

            // Display name
            $table->string('name'); // "Manage Team", "Manage Venues", etc.

            // Description
            $table->text('description')->nullable();

            // Category for grouping in UI
            $table->string('category')->default('general'); // general, shifts, team, venues, billing, reports

            // Display order within category
            $table->integer('sort_order')->default(0);

            // Is this a sensitive/critical permission?
            $table->boolean('is_sensitive')->default(false);

            // Default value for new roles
            $table->boolean('default_value')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('sort_order');
        });

        // Role-Permission pivot table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // owner, admin, manager, scheduler, viewer
            $table->foreignId('permission_id')->constrained('team_permissions')->onDelete('cascade');
            $table->boolean('granted')->default(false);
            $table->timestamps();

            // Unique constraint
            $table->unique(['role', 'permission_id']);

            // Indexes
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('team_permissions');
    }
}
