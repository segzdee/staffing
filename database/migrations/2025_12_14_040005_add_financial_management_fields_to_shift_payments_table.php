<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinancialManagementFieldsToShiftPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_payments', function (Blueprint $table) {
            // FIN-005: Dispute Management (enhanced)
            $table->string('dispute_filed_by')->nullable()->after('disputed_at'); // 'worker' or 'business'
            $table->enum('dispute_status', ['pending', 'under_review', 'evidence_requested', 'resolved', 'closed'])->nullable()->after('dispute_filed_by');
            $table->text('dispute_evidence_url')->nullable()->after('dispute_status');
            $table->text('dispute_resolution_notes')->nullable()->after('dispute_evidence_url');
            $table->decimal('dispute_adjustment_amount', 10, 2)->nullable()->after('dispute_resolution_notes');

            // FIN-006: Refunds & Adjustments
            $table->boolean('is_refunded')->default(false)->after('dispute_adjustment_amount');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('is_refunded');
            $table->string('refund_reason')->nullable()->after('refund_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->string('stripe_refund_id')->nullable()->after('refunded_at');
            $table->decimal('adjustment_amount', 10, 2)->default(0.00)->after('stripe_refund_id');
            $table->text('adjustment_notes')->nullable()->after('adjustment_amount');

            // FIN-007: Tax & Reporting
            $table->decimal('vat_amount', 10, 2)->nullable()->after('platform_fee');
            $table->decimal('worker_tax_withheld', 10, 2)->default(0.00)->after('vat_amount');
            $table->string('tax_year')->nullable()->after('worker_tax_withheld');
            $table->string('tax_quarter')->nullable()->after('tax_year');
            $table->boolean('reported_to_tax_authority')->default(false)->after('tax_quarter');

            // FIN-008: Platform Revenue Tracking
            $table->decimal('platform_revenue', 10, 2)->nullable()->after('reported_to_tax_authority');
            $table->decimal('payment_processor_fee', 10, 2)->nullable()->after('platform_revenue');
            $table->decimal('net_platform_revenue', 10, 2)->nullable()->after('payment_processor_fee');

            // FIN-009: Financial Analytics
            $table->integer('payout_delay_minutes')->nullable()->after('payout_completed_at');
            $table->enum('payout_speed', ['instant', 'standard', 'delayed'])->default('instant')->after('payout_delay_minutes');
            $table->boolean('early_payout_requested')->default(false)->after('payout_speed');
            $table->decimal('early_payout_fee', 10, 2)->nullable()->after('early_payout_requested');

            // FIN-010: Compliance & Audit
            $table->boolean('requires_manual_review')->default(false)->after('early_payout_fee');
            $table->text('manual_review_reason')->nullable()->after('requires_manual_review');
            $table->timestamp('reviewed_at')->nullable()->after('manual_review_reason');
            $table->bigInteger('reviewed_by_admin_id')->unsigned()->nullable()->after('reviewed_at');
            $table->text('internal_notes')->nullable()->after('reviewed_by_admin_id');

            // Indexes
            $table->index('dispute_status');
            $table->index('is_refunded');
            $table->index('tax_year');
            $table->index('requires_manual_review');
            $table->index('payout_speed');
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
            $table->dropIndex(['dispute_status']);
            $table->dropIndex(['is_refunded']);
            $table->dropIndex(['tax_year']);
            $table->dropIndex(['requires_manual_review']);
            $table->dropIndex(['payout_speed']);

            $table->dropColumn([
                'dispute_filed_by',
                'dispute_status',
                'dispute_evidence_url',
                'dispute_resolution_notes',
                'dispute_adjustment_amount',
                'is_refunded',
                'refund_amount',
                'refund_reason',
                'refunded_at',
                'stripe_refund_id',
                'adjustment_amount',
                'adjustment_notes',
                'vat_amount',
                'worker_tax_withheld',
                'tax_year',
                'tax_quarter',
                'reported_to_tax_authority',
                'platform_revenue',
                'payment_processor_fee',
                'net_platform_revenue',
                'payout_delay_minutes',
                'payout_speed',
                'early_payout_requested',
                'early_payout_fee',
                'requires_manual_review',
                'manual_review_reason',
                'reviewed_at',
                'reviewed_by_admin_id',
                'internal_notes',
            ]);
        });
    }
}
