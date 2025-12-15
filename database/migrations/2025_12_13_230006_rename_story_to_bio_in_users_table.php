<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameStoryToBioInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Only rename if 'story' column exists (for existing Paxpally databases)
        if (Schema::hasColumn('users', 'story')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('story', 'bio');
            });
        } else if (!Schema::hasColumn('users', 'bio')) {
            // For fresh databases, just add the bio column
            Schema::table('users', function (Blueprint $table) {
                $table->text('bio')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'bio')) {
            Schema::table('users', function (Blueprint $table) {
                // If bio column exists, either rename back to story or drop it
                if (Schema::hasColumn('users', 'story')) {
                    // story exists, so we must have just added bio - drop it
                    $table->dropColumn('bio');
                } else {
                    // story doesn't exist, so we renamed it - rename back
                    $table->renameColumn('bio', 'story');
                }
            });
        }
    }
}
