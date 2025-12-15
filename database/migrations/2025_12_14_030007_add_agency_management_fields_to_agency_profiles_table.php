<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgencyManagementFieldsToAgencyProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // AGY-001: Onboarding & Verification
            $table->boolean('onboarding_completed')->default(false)->after('user_id');
            $table->integer('onboarding_step')->nullable()->after('onboarding_completed');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->string('verification_status')->default('pending')->after('license_verified');
            $table->text('verification_notes')->nullable()->after('verification_status');

            // AGY-002: Worker Pool Management
            $table->integer('active_workers')->default(0)->after('total_workers_managed');
            $table->integer('available_workers')->default(0)->after('active_workers');
            $table->decimal('average_worker_rating', 3, 2)->default(0.00)->after('available_workers');
            $table->json('worker_skill_distribution')->nullable()->after('average_worker_rating');

            // AGY-003: Commission & Payments
            $table->decimal('variable_commission_rate', 5, 2)->nullable()->after('commission_rate'); // Can override per shift
            $table->decimal('total_commission_earned', 12, 2)->default(0.00)->after('variable_commission_rate');
            $table->decimal('pending_commission', 12, 2)->default(0.00)->after('total_commission_earned');
            $table->decimal('paid_commission', 12, 2)->default(0.00)->after('pending_commission');

            // AGY-004: Urgent Fill Service
            $table->boolean('urgent_fill_enabled')->default(false)->after('paid_commission');
            $table->decimal('urgent_fill_commission_multiplier', 3, 2)->default(1.50)->after('urgent_fill_enabled');
            $table->integer('urgent_fills_completed')->default(0)->after('urgent_fill_commission_multiplier');
            $table->decimal('average_urgent_fill_time_hours', 8, 2)->default(0.00)->after('urgent_fills_completed');

            // AGY-005: Analytics & Performance
            $table->decimal('fill_rate', 5, 2)->default(0.00)->after('average_urgent_fill_time_hours');
            $table->integer('shifts_declined')->default(0)->after('fill_rate');
            $table->integer('worker_dropouts')->default(0)->after('shifts_declined');
            $table->decimal('client_satisfaction_score', 3, 2)->default(0.00)->after('worker_dropouts');
            $table->integer('repeat_clients')->default(0)->after('client_satisfaction_score');

            // Indexes
            $table->index('verification_status');
            $table->index('onboarding_completed');
            $table->index('urgent_fill_enabled');
            $table->index('fill_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['onboarding_completed']);
            $table->dropIndex(['urgent_fill_enabled']);
            $table->dropIndex(['fill_rate']);

            $table->dropColumn([
                'onboarding_completed',
                'onboarding_step',
                'onboarding_completed_at',
                'verification_status',
                'verification_notes',
                'active_workers',
                'available_workers',
                'average_worker_rating',
                'worker_skill_distribution',
                'variable_commission_rate',
                'total_commission_earned',
                'pending_commission',
                'paid_commission',
                'urgent_fill_enabled',
                'urgent_fill_commission_multiplier',
                'urgent_fills_completed',
                'average_urgent_fill_time_hours',
                'fill_rate',
                'shifts_declined',
                'worker_dropouts',
                'client_satisfaction_score',
                'repeat_clients',
            ]);
        });
    }
}
