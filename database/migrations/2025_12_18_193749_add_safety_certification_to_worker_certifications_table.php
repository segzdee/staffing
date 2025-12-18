<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-003: Add Safety Certification Reference to Worker Certifications
 *
 * Adds a foreign key reference to the safety_certifications table,
 * allowing worker certifications to be linked to the new safety
 * certification system while maintaining backward compatibility
 * with existing certification_id and certification_type_id.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            // Add safety_certification_id for the new system
            $table->foreignId('safety_certification_id')
                ->nullable()
                ->after('certification_type_id')
                ->constrained('safety_certifications')
                ->onDelete('set null');

            // Add rejection reason field if not exists
            if (! Schema::hasColumn('worker_certifications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verification_notes');
            }

            // Index for the new relationship
            $table->index('safety_certification_id', 'wc_safety_cert_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            $table->dropForeign(['safety_certification_id']);
            $table->dropIndex('wc_safety_cert_idx');
            $table->dropColumn('safety_certification_id');

            if (Schema::hasColumn('worker_certifications', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
