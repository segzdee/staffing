<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FIN-007: Tax Reporting - Tax reports table for 1099-NEC, P60, and annual statements
     */
    public function up(): void
    {
        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('tax_year');
            $table->enum('report_type', ['1099_nec', '1099_k', 'p60', 'payment_summary', 'annual_statement']);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('total_taxes_withheld', 10, 2)->default(0);
            $table->integer('total_shifts')->default(0);
            $table->json('monthly_breakdown')->nullable();
            $table->json('jurisdiction_breakdown')->nullable();
            $table->string('document_url')->nullable();
            $table->enum('status', ['draft', 'generated', 'sent', 'acknowledged'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tax_year', 'report_type']);
            $table->index(['tax_year', 'report_type']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_reports');
    }
};
