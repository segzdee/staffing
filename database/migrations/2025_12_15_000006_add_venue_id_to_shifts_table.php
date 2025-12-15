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
            $table->foreignId('venue_id')->nullable()->after('business_profile_id')->constrained('venues')->onDelete('set null');
            $table->index('venue_id');
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
