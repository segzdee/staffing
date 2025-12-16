<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-006: Venue Managers Pivot Table
 *
 * Many-to-many relationship between venues and team members.
 * Allows assigning multiple managers to a venue.
 */
class CreateVenueManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venue_managers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');

            // Is this the primary manager for this venue?
            $table->boolean('is_primary')->default(false);

            // Permission overrides for this specific venue
            $table->boolean('can_post_shifts')->default(true);
            $table->boolean('can_edit_shifts')->default(true);
            $table->boolean('can_cancel_shifts')->default(false);
            $table->boolean('can_approve_workers')->default(true);
            $table->boolean('can_manage_venue_settings')->default(false);

            // Notification preferences for this venue
            $table->boolean('notify_new_applications')->default(true);
            $table->boolean('notify_shift_changes')->default(true);
            $table->boolean('notify_worker_checkins')->default(false);

            $table->timestamps();

            // Unique constraint
            $table->unique(['venue_id', 'team_member_id']);

            // Indexes
            $table->index('venue_id');
            $table->index('team_member_id');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venue_managers');
    }
}
