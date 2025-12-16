<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBudgetFieldsToBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Budget tracking fields
            if (!Schema::hasColumn('business_profiles', 'monthly_budget')) {
                $table->integer('monthly_budget')->default(0)->after('fill_rate'); // in cents
            }
            if (!Schema::hasColumn('business_profiles', 'current_month_spend')) {
                $table->integer('current_month_spend')->default(0); // in cents
            }
            if (!Schema::hasColumn('business_profiles', 'ytd_spend')) {
                $table->integer('ytd_spend')->default(0); // in cents
            }

            // Budget alert preferences
            if (!Schema::hasColumn('business_profiles', 'enable_budget_alerts')) {
                $table->boolean('enable_budget_alerts')->default(true);
            }
            if (!Schema::hasColumn('business_profiles', 'budget_alert_threshold_75')) {
                $table->integer('budget_alert_threshold_75')->default(75);
            }
            if (!Schema::hasColumn('business_profiles', 'budget_alert_threshold_90')) {
                $table->integer('budget_alert_threshold_90')->default(90);
            }
            if (!Schema::hasColumn('business_profiles', 'budget_alert_threshold_100')) {
                $table->integer('budget_alert_threshold_100')->default(100);
            }
            if (!Schema::hasColumn('business_profiles', 'last_budget_alert_sent_at')) {
                $table->timestamp('last_budget_alert_sent_at')->nullable();
            }

            // Cancellation tracking
            if (!Schema::hasColumn('business_profiles', 'total_late_cancellations')) {
                $table->integer('total_late_cancellations')->default(0);
            }
            if (!Schema::hasColumn('business_profiles', 'late_cancellations_last_30_days')) {
                $table->integer('late_cancellations_last_30_days')->default(0);
            }
            if (!Schema::hasColumn('business_profiles', 'cancellation_rate')) {
                $table->decimal('cancellation_rate', 5, 2)->default(0.00); // percentage
            }
            if (!Schema::hasColumn('business_profiles', 'last_late_cancellation_at')) {
                $table->timestamp('last_late_cancellation_at')->nullable();
            }

            // Escrow/credit adjustments based on behavior
            if (!Schema::hasColumn('business_profiles', 'requires_increased_escrow')) {
                $table->boolean('requires_increased_escrow')->default(false);
            }
            if (!Schema::hasColumn('business_profiles', 'credit_suspended')) {
                $table->boolean('credit_suspended')->default(false);
            }
            if (!Schema::hasColumn('business_profiles', 'credit_suspended_at')) {
                $table->timestamp('credit_suspended_at')->nullable();
            }
            if (!Schema::hasColumn('business_profiles', 'credit_suspension_reason')) {
                $table->text('credit_suspension_reason')->nullable();
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
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_budget',
                'current_month_spend',
                'ytd_spend',
                'enable_budget_alerts',
                'budget_alert_threshold_75',
                'budget_alert_threshold_90',
                'budget_alert_threshold_100',
                'last_budget_alert_sent_at',
                'total_late_cancellations',
                'late_cancellations_last_30_days',
                'cancellation_rate',
                'last_late_cancellation_at',
                'requires_increased_escrow',
                'credit_suspended',
                'credit_suspended_at',
                'credit_suspension_reason',
            ]);
        });
    }
}
