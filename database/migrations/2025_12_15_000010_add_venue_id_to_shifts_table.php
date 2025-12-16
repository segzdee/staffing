<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVenueIdToShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Check if column already exists
            if (!Schema::hasColumn('shifts', 'venue_id')) {
                // Try to add after business_id if business_profile_id doesn't exist
                if (Schema::hasColumn('shifts', 'business_profile_id')) {
                    $table->foreignId('venue_id')->nullable()->after('business_profile_id')->constrained('venues')->onDelete('set null');
                } else {
                    $table->foreignId('venue_id')->nullable()->after('business_id')->constrained('venues')->onDelete('set null');
                }
                $table->index('venue_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropIndex(['venue_id']);
            $table->dropColumn('venue_id');
        });
    }
}
