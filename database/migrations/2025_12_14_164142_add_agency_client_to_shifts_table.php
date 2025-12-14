<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // agency_client_id already exists, only add posted_by_agency_id
            // Add foreign key constraint to existing agency_client_id
            $table->foreign('agency_client_id')->references('id')->on('agency_clients')->onDelete('set null');
            $table->foreignId('posted_by_agency_id')->nullable()->after('agency_client_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['agency_client_id']);
            $table->dropForeign(['posted_by_agency_id']);
            $table->dropColumn('posted_by_agency_id');
        });
    }
};
