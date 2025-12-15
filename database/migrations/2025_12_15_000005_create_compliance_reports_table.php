<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplianceReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compliance_reports', function (Blueprint $table) {
            $table->id();

            // Report type
            $table->enum('report_type', [
                'daily_financial_reconciliation',
                'monthly_vat_summary',
                'quarterly_worker_classification',
                'annual_tax_summary',
                'payment_audit',
                'worker_hours_summary'
            ]);

            // Period covered
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_label')->nullable(); // "Q1 2025", "January 2025", etc.

            // Report status
            $table->enum('status', ['pending', 'generating', 'completed', 'failed']);
            $table->text('error_message')->nullable();

            // Report data (JSON for flexibility)
            $table->json('report_data')->nullable();
            $table->json('summary_stats')->nullable();

            // File storage
            $table->string('file_path')->nullable(); // path to generated PDF/CSV
            $table->string('file_format')->default('pdf'); // pdf, csv, excel
            $table->integer('file_size')->nullable(); // in bytes

            // Generation details
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->integer('generation_time_seconds')->nullable();

            // Audit trail
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->foreignId('last_downloaded_by_user_id')->nullable()->constrained('users');

            // Retention
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_archived')->default(false);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['report_type', 'period_start', 'period_end']);
            $table->index('status');
            $table->index('generated_at');
            $table->index('is_archived');
        });

        // Create audit log for report access
        Schema::create('compliance_report_access_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('compliance_report_id')->constrained('compliance_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Access details
            $table->enum('action', ['view', 'download', 'export', 'email']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Context
            $table->json('metadata')->nullable();

            // Timestamp
            $table->timestamp('accessed_at');
            $table->timestamps();

            // Indexes
            $table->index('compliance_report_id');
            $table->index('user_id');
            $table->index('accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compliance_report_access_logs');
        Schema::dropIfExists('compliance_reports');
    }
}
