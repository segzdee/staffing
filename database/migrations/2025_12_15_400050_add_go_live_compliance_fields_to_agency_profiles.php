<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-005: Go-Live Checklist and Compliance Monitoring
 *
 * Adds fields needed for:
 * - Go-live checklist tracking
 * - Compliance scoring and monitoring
 * - Document verification status
 * - Background check tracking
 * - Commercial agreement signing
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // ======= GO-LIVE STATUS =======
            $table->boolean('is_live')->default(false)->after('is_verified');
            $table->timestamp('activated_at')->nullable()->after('is_live');
            $table->unsignedBigInteger('activated_by')->nullable()->after('activated_at');
            $table->timestamp('go_live_requested_at')->nullable()->after('activated_by');

            // ======= COMPLIANCE SCORING =======
            $table->decimal('compliance_score', 5, 2)->default(0)->after('go_live_requested_at');
            $table->string('compliance_grade', 2)->default('F')->after('compliance_score');
            $table->timestamp('compliance_last_checked')->nullable()->after('compliance_grade');

            // ======= LICENSE VERIFICATION =======
            $table->timestamp('license_verified_at')->nullable()->after('license_verified');
            $table->date('license_expires_at')->nullable()->after('license_verified_at');

            // ======= TAX COMPLIANCE =======
            $table->string('tax_id')->nullable()->after('license_expires_at');
            $table->boolean('tax_verified')->default(false)->after('tax_id');
            $table->timestamp('tax_verified_at')->nullable()->after('tax_verified');

            // ======= BACKGROUND CHECK =======
            $table->string('background_check_status')->default('pending')->after('tax_verified_at');
            $table->boolean('background_check_passed')->default(false)->after('background_check_status');
            $table->timestamp('background_check_initiated_at')->nullable()->after('background_check_passed');
            $table->timestamp('background_check_completed_at')->nullable()->after('background_check_initiated_at');

            // ======= REFERENCES =======
            $table->json('references')->nullable()->after('background_check_completed_at');

            // ======= COMMERCIAL AGREEMENT =======
            $table->boolean('agreement_signed')->default(false)->after('references');
            $table->timestamp('agreement_signed_at')->nullable()->after('agreement_signed');
            $table->string('agreement_version')->nullable()->after('agreement_signed_at');
            $table->string('agreement_signer_name')->nullable()->after('agreement_version');
            $table->string('agreement_signer_title')->nullable()->after('agreement_signer_name');
            $table->string('agreement_signer_ip')->nullable()->after('agreement_signer_title');

            // ======= TEST SHIFT TRACKING =======
            $table->boolean('test_shift_completed')->default(false)->after('agreement_signer_ip');
            $table->unsignedBigInteger('test_shift_id')->nullable()->after('test_shift_completed');

            // ======= MANUAL VERIFICATIONS (Admin overrides) =======
            $table->json('manual_verifications')->nullable()->after('test_shift_id');

            // ======= INDEXES =======
            $table->index('is_live');
            $table->index('compliance_score');
            $table->index('compliance_grade');
            // verification_status index already exists from previous migration
            $table->index('background_check_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['is_live']);
            $table->dropIndex(['compliance_score']);
            $table->dropIndex(['compliance_grade']);
            // Don't drop verification_status index - it was created in a previous migration
            $table->dropIndex(['background_check_status']);

            // Drop columns
            $table->dropColumn([
                'is_live',
                'activated_at',
                'activated_by',
                'go_live_requested_at',
                'compliance_score',
                'compliance_grade',
                'compliance_last_checked',
                'license_verified_at',
                'license_expires_at',
                'tax_id',
                'tax_verified',
                'tax_verified_at',
                'background_check_status',
                'background_check_passed',
                'background_check_initiated_at',
                'background_check_completed_at',
                'references',
                'agreement_signed',
                'agreement_signed_at',
                'agreement_version',
                'agreement_signer_name',
                'agreement_signer_title',
                'agreement_signer_ip',
                'test_shift_completed',
                'test_shift_id',
                'manual_verifications',
            ]);
        });
    }
};
