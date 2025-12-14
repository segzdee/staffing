<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgencyFieldsToShiftAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            // Agency relationship
            $table->bigInteger('agency_id')->unsigned()->nullable()->after('worker_id');
            $table->decimal('agency_commission_rate', 5, 2)->nullable()->after('agency_id');
            $table->timestamp('assigned_at')->nullable()->after('agency_commission_rate');

            // Indexes
            $table->index('agency_id');

            // Foreign key
            $table->foreign('agency_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropIndex(['agency_id']);
            $table->dropColumn(['agency_id', 'agency_commission_rate', 'assigned_at']);
        });
    }
}
