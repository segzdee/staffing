<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsernameRoleStatusToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add username column (nullable for existing users)
            $table->string('username')->nullable()->unique();

            // Add role column for admin/normal users
            $table->enum('role', ['normal', 'admin'])->default('normal');

            // Add status column for account management
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            // Add MFA column for admin users
            $table->boolean('mfa_enabled')->default(false);

            // Add indexes
            $table->index('role');
            $table->index('status');
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
            $table->dropColumn(['username', 'role', 'status', 'mfa_enabled']);
        });
    }
}
