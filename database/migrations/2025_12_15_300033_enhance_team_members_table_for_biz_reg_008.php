<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-008: Team Members Enhancement
 *
 * Adds additional fields to team_members for comprehensive activity tracking
 * and permission management.
 */
class EnhanceTeamMembersTableForBizReg008 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('team_members', function (Blueprint $table) {
            // Add new permission columns if they don't exist
            if (!Schema::hasColumn('team_members', 'can_manage_billing')) {
                $table->boolean('can_manage_billing')->default(false)->after('can_manage_settings');
            }
            if (!Schema::hasColumn('team_members', 'can_view_activity')) {
                $table->boolean('can_view_activity')->default(false)->after('can_manage_billing');
            }
            if (!Schema::hasColumn('team_members', 'can_manage_favorites')) {
                $table->boolean('can_manage_favorites')->default(false)->after('can_view_activity');
            }
            if (!Schema::hasColumn('team_members', 'can_manage_integrations')) {
                $table->boolean('can_manage_integrations')->default(false)->after('can_manage_favorites');
            }
            if (!Schema::hasColumn('team_members', 'can_view_reports')) {
                $table->boolean('can_view_reports')->default(false)->after('can_manage_integrations');
            }

            // Enhanced activity tracking
            if (!Schema::hasColumn('team_members', 'workers_approved')) {
                $table->integer('workers_approved')->default(0)->after('applications_processed');
            }
            if (!Schema::hasColumn('team_members', 'shifts_cancelled')) {
                $table->integer('shifts_cancelled')->default(0)->after('workers_approved');
            }
            if (!Schema::hasColumn('team_members', 'venues_managed')) {
                $table->integer('venues_managed')->default(0)->after('shifts_cancelled');
            }
            if (!Schema::hasColumn('team_members', 'login_count')) {
                $table->integer('login_count')->default(0)->after('venues_managed');
            }
            if (!Schema::hasColumn('team_members', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('login_count');
            }
            if (!Schema::hasColumn('team_members', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('last_login_at');
            }

            // Invitation tracking
            if (!Schema::hasColumn('team_members', 'invitation_id')) {
                $table->unsignedBigInteger('invitation_id')->nullable()->after('invitation_token');
            }

            // Suspension tracking
            if (!Schema::hasColumn('team_members', 'suspended_by')) {
                $table->unsignedBigInteger('suspended_by')->nullable()->after('revoked_at');
            }
            if (!Schema::hasColumn('team_members', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('suspended_by');
            }
            if (!Schema::hasColumn('team_members', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }

            // Two-factor authentication status
            if (!Schema::hasColumn('team_members', 'requires_2fa')) {
                $table->boolean('requires_2fa')->default(false)->after('suspension_reason');
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
        Schema::table('team_members', function (Blueprint $table) {
            $columns = [
                'can_manage_billing',
                'can_view_activity',
                'can_manage_favorites',
                'can_manage_integrations',
                'can_view_reports',
                'workers_approved',
                'shifts_cancelled',
                'venues_managed',
                'login_count',
                'last_login_at',
                'last_login_ip',
                'invitation_id',
                'suspended_by',
                'suspended_at',
                'suspension_reason',
                'requires_2fa',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('team_members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
