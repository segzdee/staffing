<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreditFieldsToBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Credit system fields
            $table->boolean('credit_enabled')->default(false)->after('is_verified');
            $table->decimal('credit_limit', 12, 2)->default(0.00)->after('credit_enabled');
            $table->decimal('credit_used', 12, 2)->default(0.00)->after('credit_limit');
            $table->decimal('credit_available', 12, 2)->default(0.00)->after('credit_used');
            $table->decimal('credit_utilization', 5, 2)->default(0.00)->after('credit_available'); // percentage

            // Payment terms
            $table->enum('payment_terms', ['net_7', 'net_14', 'net_30'])->default('net_14')->after('credit_utilization');
            $table->decimal('interest_rate_monthly', 5, 2)->default(1.50)->after('payment_terms'); // 1.5% default

            // Status tracking
            $table->boolean('credit_paused')->default(false)->after('interest_rate_monthly');
            $table->timestamp('credit_paused_at')->nullable()->after('credit_paused');
            $table->string('credit_pause_reason')->nullable()->after('credit_paused_at');

            // Late payment tracking
            $table->integer('late_payment_count')->default(0)->after('credit_pause_reason');
            $table->timestamp('last_late_payment_at')->nullable()->after('late_payment_count');
            $table->decimal('total_late_fees', 10, 2)->default(0.00)->after('last_late_payment_at');

            // Credit history
            $table->timestamp('credit_approved_at')->nullable()->after('total_late_fees');
            $table->foreignId('credit_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_credit_review_at')->nullable()->after('credit_approved_by');

            // Indexes
            $table->index('credit_enabled');
            $table->index(['credit_enabled', 'credit_paused']);
            $table->index('credit_utilization');
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
                'credit_enabled',
                'credit_limit',
                'credit_used',
                'credit_available',
                'credit_utilization',
                'payment_terms',
                'interest_rate_monthly',
                'credit_paused',
                'credit_paused_at',
                'credit_pause_reason',
                'late_payment_count',
                'last_late_payment_at',
                'total_late_fees',
                'credit_approved_at',
                'credit_approved_by',
                'last_credit_review_at',
            ]);
        });
    }
}
