<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgencyCommissionToShiftPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_payments', function (Blueprint $table) {
            // Agency commission tracking
            $table->decimal('agency_commission', 10, 2)->nullable()->after('platform_fee');
            $table->decimal('worker_amount', 10, 2)->nullable()->after('agency_commission'); // Amount after agency commission

            // Note: Payment flow for agency placements:
            // amount_gross = total payment from business
            // platform_fee = OvertimeStaff's commission
            // agency_commission = agency's commission
            // worker_amount = what worker receives (amount_gross - platform_fee - agency_commission)
            // amount_net = final amount after all deductions (for backward compatibility)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_payments', function (Blueprint $table) {
            $table->dropColumn(['agency_commission', 'worker_amount']);
        });
    }
}
