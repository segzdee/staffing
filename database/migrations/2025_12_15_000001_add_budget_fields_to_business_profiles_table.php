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
            $table->integer('monthly_budget')->default(0)->after('fill_rate'); // in cents
            $table->integer('current_month_spend')->default(0); // in cents
            $table->integer('ytd_spend')->default(0); // in cents

            // Budget alert preferences
            $table->boolean('enable_budget_alerts')->default(true);
            $table->integer('budget_alert_threshold_75')->default(75);
            $table->integer('budget_alert_threshold_90')->default(90);
            $table->integer('budget_alert_threshold_100')->default(100);
            $table->timestamp('last_budget_alert_sent_at')->nullable();

            // Cancellation tracking
            $table->integer('total_late_cancellations')->default(0);
            $table->integer('late_cancellations_last_30_days')->default(0);
            $table->decimal('cancellation_rate', 5, 2)->default(0.00); // percentage
            $table->timestamp('last_late_cancellation_at')->nullable();

            // Escrow/credit adjustments based on behavior
            $table->boolean('requires_increased_escrow')->default(false);
            $table->boolean('credit_suspended')->default(false);
            $table->timestamp('credit_suspended_at')->nullable();
            $table->text('credit_suspension_reason')->nullable();
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
