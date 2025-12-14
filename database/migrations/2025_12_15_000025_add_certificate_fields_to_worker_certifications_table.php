<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateFieldsToWorkerCertificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            // Add fields for certificate upload and verification
            $table->string('certificate_file')->nullable()->after('expiry_date');
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'expired'])->default('pending')->after('certificate_file');
            // verified_at already exists in the original table
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->onDelete('set null');
            $table->text('verification_notes')->nullable()->after('verified_by');
            $table->boolean('expiry_reminder_sent')->default(false)->after('verification_notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'certificate_file',
                'verification_status',
                'verified_by',
                'verification_notes',
                'expiry_reminder_sent'
            ]);
        });
    }
}
