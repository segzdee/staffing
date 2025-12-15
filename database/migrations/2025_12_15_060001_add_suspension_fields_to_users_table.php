<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuspensionFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Suspension tracking fields
            $table->timestamp('suspended_until')->nullable()->after('status');
            $table->text('suspension_reason')->nullable()->after('suspended_until');
            $table->unsignedInteger('suspension_count')->default(0)->after('suspension_reason');
            $table->timestamp('last_suspended_at')->nullable()->after('suspension_count');

            // Add index for querying suspended users
            $table->index('suspended_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['suspended_until']);
            $table->dropColumn([
                'suspended_until',
                'suspension_reason',
                'suspension_count',
                'last_suspended_at'
            ]);
        });
    }
}
